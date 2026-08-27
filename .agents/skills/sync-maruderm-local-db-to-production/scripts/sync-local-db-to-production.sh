#!/usr/bin/env bash

set -Eeuo pipefail
umask 077

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
readonly BACKUP_ROOT="$PROJECT_ROOT/backups/database/local-to-production"
readonly STATE_DIR="$PROJECT_ROOT/.agents/state"
readonly STATE_FILE="$STATE_DIR/last-local-db-to-production-sync.json"
readonly LOCK_FILE="$STATE_DIR/local-db-to-production-sync.lock"
readonly RUN_STAMP="$(TZ=Europe/Kyiv date +%Y%m%d-%H%M%S)-${BASHPID}"
readonly STARTED_AT_UTC="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

MODE="preflight"
SSH_AGENT_STARTED=0
RUN_DIR=""
REMOTE_TEMP_DIR=""
LOCAL_SOURCE_ARCHIVE=""
PRODUCTION_BACKUP_ARCHIVE=""
LOCAL_SOURCE_SHA256=""
PRODUCTION_BACKUP_SHA256=""
LOCAL_INVENTORY=""
PRODUCTION_INVENTORY=""
AFTER_INVENTORY=""

usage() {
    cat <<'USAGE'
Usage: sync-local-db-to-production.sh [--preflight|--execute|--help]

  --preflight  Compare local and production identities and commerce fingerprints.
  --execute    Replace production from local with backups, rollback, and validation.
  --help       Show this help.
USAGE
}

note() {
    printf '%s\n' "$*"
}

fail() {
    printf 'error=%s\n' "$*" >&2
    return 1
}

require_command() {
    command -v "$1" >/dev/null 2>&1 || fail "required command not found: $1"
}

load_project_env() {
    local candidate

    for candidate in "$PROJECT_ROOT/.env.local" "$PROJECT_ROOT/env.local" "$PROJECT_ROOT/.env"; do
        if [[ -f "$candidate" ]]; then
            set -a
            # shellcheck disable=SC1090
            source "$candidate" >/dev/null 2>&1
            set +a
            return
        fi
    done

    fail "project environment file not found"
}

start_ephemeral_ssh_agent() {
    if [[ -z "${DEPLOY_SSH_KEY:-}" ]]; then
        return
    fi

    eval "$(ssh-agent -s)" >/dev/null
    SSH_AGENT_STARTED=1
    printf '%s\n' "$DEPLOY_SSH_KEY" | ssh-add - >/dev/null 2>&1
}

cleanup() {
    local exit_status=$?

    if [[ "$SSH_AGENT_STARTED" -eq 1 ]]; then
        ssh-agent -k >/dev/null 2>&1 || true
    fi

    exit "$exit_status"
}

run_local_wp() {
    "$LOCAL_WP_BIN" --path="$PROJECT_ROOT" --skip-plugins --skip-themes "$@"
}

metric() {
    local inventory="$1"
    local key="$2"

    awk -F '\t' -v wanted="$key" '$1 == wanted {sub(/^[^\t]*\t/, ""); print; exit}' <<< "$inventory"
}

collect_local_inventory() {
    local prefix

    prefix="$(run_local_wp db prefix)"
    {
        printf 'home\t%s\n' "$(run_local_wp option get home)"
        printf 'siteurl\t%s\n' "$(run_local_wp option get siteurl)"
        printf 'core_version\t%s\n' "$(run_local_wp core version)"
        printf 'db_version\t%s\n' "$(run_local_wp option get db_version)"
        run_local_wp db query "
            SELECT 'tables', COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()
            UNION ALL SELECT 'db_bytes', COALESCE(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = DATABASE()
            UNION ALL SELECT 'products', COUNT(*) FROM ${prefix}posts WHERE post_type = 'product' AND post_status = 'publish'
            UNION ALL SELECT 'users', COUNT(*) FROM ${prefix}users
            UNION ALL SELECT 'max_user_id', COALESCE(MAX(ID), 0) FROM ${prefix}users
            UNION ALL SELECT 'latest_user_registered_gmt', COALESCE(DATE_FORMAT(MAX(user_registered), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}users
            UNION ALL SELECT 'good_for_products', COUNT(DISTINCT post_id) FROM ${prefix}postmeta WHERE meta_key = '_maruderm_good_for'
            UNION ALL SELECT 'legacy_orders', COUNT(*) FROM ${prefix}posts WHERE post_type = 'shop_order'
            UNION ALL SELECT 'legacy_max_order_id', COALESCE(MAX(ID), 0) FROM ${prefix}posts WHERE post_type = 'shop_order'
            UNION ALL SELECT 'legacy_latest_modified_gmt', COALESCE(DATE_FORMAT(MAX(post_modified_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}posts WHERE post_type = 'shop_order';
        " --skip-column-names

        if run_local_wp db query "SHOW TABLES LIKE '${prefix}wc_orders';" --skip-column-names | grep -q .; then
            run_local_wp db query "
                SELECT 'hpos_orders', COUNT(*) FROM ${prefix}wc_orders
                UNION ALL SELECT 'hpos_max_order_id', COALESCE(MAX(id), 0) FROM ${prefix}wc_orders
                UNION ALL SELECT 'hpos_latest_updated_gmt', COALESCE(DATE_FORMAT(MAX(date_updated_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}wc_orders;
            " --skip-column-names
        else
            printf 'hpos_orders\t0\nhpos_max_order_id\t0\nhpos_latest_updated_gmt\t\n'
        fi
    }
}

collect_remote_inventory() {
    ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_WP_PATH" "$REMOTE_PHP_BIN" "$REMOTE_WP_BIN" <<'REMOTE'
set -Eeuo pipefail

wp_path="$1"
php_bin="$2"
wp_file="$3"

[[ -d "$wp_path" ]] || { printf 'Remote WordPress path is missing.\n' >&2; exit 10; }
[[ -x "$php_bin" ]] || { printf 'Remote PHP binary is missing.\n' >&2; exit 11; }
[[ -f "$wp_file" ]] || { printf 'Remote WP-CLI binary is missing.\n' >&2; exit 12; }

wp_cmd=("$php_bin" "$wp_file" "--path=$wp_path" --skip-plugins --skip-themes)
prefix="$("${wp_cmd[@]}" db prefix)"

printf 'home\t%s\n' "$("${wp_cmd[@]}" option get home)"
printf 'siteurl\t%s\n' "$("${wp_cmd[@]}" option get siteurl)"
printf 'core_version\t%s\n' "$("${wp_cmd[@]}" core version)"
printf 'db_version\t%s\n' "$("${wp_cmd[@]}" option get db_version)"
"${wp_cmd[@]}" db query "
    SELECT 'tables', COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()
    UNION ALL SELECT 'db_bytes', COALESCE(SUM(data_length + index_length), 0) FROM information_schema.tables WHERE table_schema = DATABASE()
    UNION ALL SELECT 'products', COUNT(*) FROM ${prefix}posts WHERE post_type = 'product' AND post_status = 'publish'
    UNION ALL SELECT 'users', COUNT(*) FROM ${prefix}users
    UNION ALL SELECT 'max_user_id', COALESCE(MAX(ID), 0) FROM ${prefix}users
    UNION ALL SELECT 'latest_user_registered_gmt', COALESCE(DATE_FORMAT(MAX(user_registered), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}users
    UNION ALL SELECT 'good_for_products', COUNT(DISTINCT post_id) FROM ${prefix}postmeta WHERE meta_key = '_maruderm_good_for'
    UNION ALL SELECT 'legacy_orders', COUNT(*) FROM ${prefix}posts WHERE post_type = 'shop_order'
    UNION ALL SELECT 'legacy_max_order_id', COALESCE(MAX(ID), 0) FROM ${prefix}posts WHERE post_type = 'shop_order'
    UNION ALL SELECT 'legacy_latest_modified_gmt', COALESCE(DATE_FORMAT(MAX(post_modified_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}posts WHERE post_type = 'shop_order';
" --skip-column-names

if "${wp_cmd[@]}" db query "SHOW TABLES LIKE '${prefix}wc_orders';" --skip-column-names | grep -q .; then
    "${wp_cmd[@]}" db query "
        SELECT 'hpos_orders', COUNT(*) FROM ${prefix}wc_orders
        UNION ALL SELECT 'hpos_max_order_id', COALESCE(MAX(id), 0) FROM ${prefix}wc_orders
        UNION ALL SELECT 'hpos_latest_updated_gmt', COALESCE(DATE_FORMAT(MAX(date_updated_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}wc_orders;
    " --skip-column-names
else
    printf 'hpos_orders\t0\nhpos_max_order_id\t0\nhpos_latest_updated_gmt\t\n'
fi
REMOTE
}

assert_matching_source_state() {
    local key local_value production_value
    local -a guarded_keys=(
        db_version
        tables
        products
        users
        max_user_id
        latest_user_registered_gmt
        legacy_orders
        legacy_max_order_id
        legacy_latest_modified_gmt
        hpos_orders
        hpos_max_order_id
        hpos_latest_updated_gmt
    )

    for key in "${guarded_keys[@]}"; do
        local_value="$(metric "$LOCAL_INVENTORY" "$key")"
        production_value="$(metric "$PRODUCTION_INVENTORY" "$key")"
        [[ "$local_value" == "$production_value" ]] || fail "production divergence for $key: local=$local_value production=$production_value"
    done
}

run_preflight() {
    LOCAL_INVENTORY="$(collect_local_inventory)"
    PRODUCTION_INVENTORY="$(collect_remote_inventory)"

    [[ "$(metric "$LOCAL_INVENTORY" home)" == "$LOCAL_URL" ]] || fail "local home identity mismatch"
    [[ "$(metric "$LOCAL_INVENTORY" siteurl)" == "$LOCAL_URL" ]] || fail "local siteurl identity mismatch"
    [[ "$(metric "$PRODUCTION_INVENTORY" home)" == "$PRODUCTION_URL" ]] || fail "production home identity mismatch"
    [[ "$(metric "$PRODUCTION_INVENTORY" siteurl)" == "$PRODUCTION_URL" ]] || fail "production siteurl identity mismatch"

    assert_matching_source_state
    run_local_wp db check --quiet >/dev/null

    note "preflight=ok"
    note "ssh_alias=$REMOTE_ALIAS"
    note "remote_path=$REMOTE_WP_PATH"
    note "local_core=$(metric "$LOCAL_INVENTORY" core_version)"
    note "production_core=$(metric "$PRODUCTION_INVENTORY" core_version)"
    note "database_schema=$(metric "$LOCAL_INVENTORY" db_version)"
    note "inventory=tables:$(metric "$LOCAL_INVENTORY" tables),products:$(metric "$LOCAL_INVENTORY" products),users:$(metric "$LOCAL_INVENTORY" users),legacy_orders:$(metric "$LOCAL_INVENTORY" legacy_orders),hpos_orders:$(metric "$LOCAL_INVENTORY" hpos_orders)"
    note "good_for=local:$(metric "$LOCAL_INVENTORY" good_for_products),production:$(metric "$PRODUCTION_INVENTORY" good_for_products)"
}

create_local_source_export() {
    local source_sql="${LOCAL_SOURCE_ARCHIVE%.gz}"

    run_local_wp db export "$source_sql" --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 --quiet
    gzip -9 -- "$source_sql"
    gzip -t -- "$LOCAL_SOURCE_ARCHIVE"
    LOCAL_SOURCE_SHA256="$(sha256sum "$LOCAL_SOURCE_ARCHIVE" | awk '{print $1}')"
    [[ "$LOCAL_SOURCE_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "local source checksum is invalid"
}

create_and_download_production_backup() {
    local backup_result remote_sha256 remote_bytes local_bytes

    backup_result="$(ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_WP_PATH" "$REMOTE_PHP_BIN" "$REMOTE_WP_BIN" "$REMOTE_TEMP_DIR" <<'REMOTE'
set -Eeuo pipefail

wp_path="$1"
php_bin="$2"
wp_file="$3"
temp_dir="$4"
backup_sql="$temp_dir/production-before-sync.sql"
backup_archive="$backup_sql.gz"

mkdir -m 700 -- "$temp_dir"
wp_cmd=("$php_bin" "$wp_file" "--path=$wp_path" --skip-plugins --skip-themes --quiet)
"${wp_cmd[@]}" db export "$backup_sql" --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4
gzip -9 -- "$backup_sql"
gzip -t -- "$backup_archive"
printf 'sha256\t%s\n' "$(sha256sum "$backup_archive" | awk '{print $1}')"
printf 'bytes\t%s\n' "$(wc -c < "$backup_archive" | tr -d '[:space:]')"
REMOTE
)"

    remote_sha256="$(awk -F '\t' '$1 == "sha256" {print $2}' <<< "$backup_result")"
    remote_bytes="$(awk -F '\t' '$1 == "bytes" {print $2}' <<< "$backup_result")"
    scp "${SCP_OPTIONS[@]}" "$REMOTE_ALIAS:$REMOTE_TEMP_DIR/production-before-sync.sql.gz" "$PRODUCTION_BACKUP_ARCHIVE"
    gzip -t -- "$PRODUCTION_BACKUP_ARCHIVE"
    PRODUCTION_BACKUP_SHA256="$(sha256sum "$PRODUCTION_BACKUP_ARCHIVE" | awk '{print $1}')"
    local_bytes="$(wc -c < "$PRODUCTION_BACKUP_ARCHIVE" | tr -d '[:space:]')"

    [[ "$PRODUCTION_BACKUP_SHA256" == "$remote_sha256" ]] || fail "production backup checksum mismatch"
    [[ "$local_bytes" == "$remote_bytes" ]] || fail "production backup size mismatch"
}

upload_local_source() {
    scp "${SCP_OPTIONS[@]}" "$LOCAL_SOURCE_ARCHIVE" "$REMOTE_ALIAS:$REMOTE_TEMP_DIR/local-source.sql.gz"
    ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_TEMP_DIR/local-source.sql.gz" "$LOCAL_SOURCE_SHA256" <<'REMOTE'
set -Eeuo pipefail
archive="$1"
expected_sha256="$2"
gzip -t -- "$archive"
actual_sha256="$(sha256sum "$archive" | awk '{print $1}')"
[[ "$actual_sha256" == "$expected_sha256" ]]
REMOTE
}

replace_production_database() {
    local ssh_status=0

    set +e
    AFTER_INVENTORY="$(ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- \
        "$REMOTE_WP_PATH" "$REMOTE_PHP_BIN" "$REMOTE_WP_BIN" "$REMOTE_TEMP_DIR" \
        "$LOCAL_URL" "$PRODUCTION_URL" \
        "$(metric "$LOCAL_INVENTORY" db_version)" \
        "$(metric "$LOCAL_INVENTORY" tables)" \
        "$(metric "$LOCAL_INVENTORY" products)" \
        "$(metric "$LOCAL_INVENTORY" users)" \
        "$(metric "$LOCAL_INVENTORY" max_user_id)" \
        "$(metric "$LOCAL_INVENTORY" latest_user_registered_gmt)" \
        "$(metric "$LOCAL_INVENTORY" legacy_orders)" \
        "$(metric "$LOCAL_INVENTORY" legacy_max_order_id)" \
        "$(metric "$LOCAL_INVENTORY" legacy_latest_modified_gmt)" \
        "$(metric "$LOCAL_INVENTORY" hpos_orders)" \
        "$(metric "$LOCAL_INVENTORY" hpos_max_order_id)" \
        "$(metric "$LOCAL_INVENTORY" hpos_latest_updated_gmt)" \
        "$(metric "$LOCAL_INVENTORY" good_for_products)" <<'REMOTE'
set -Eeuo pipefail

wp_path="$1"
php_bin="$2"
wp_file="$3"
temp_dir="$4"
local_url="$5"
production_url="$6"
expected_db_version="$7"
expected_tables="$8"
expected_products="$9"
expected_users="${10}"
expected_max_user_id="${11}"
expected_latest_user="${12}"
expected_legacy_orders="${13}"
expected_legacy_max_id="${14}"
expected_legacy_latest="${15}"
expected_hpos_orders="${16}"
expected_hpos_max_id="${17}"
expected_hpos_latest="${18}"
expected_good_for="${19}"
source_archive="$temp_dir/local-source.sql.gz"
backup_archive="$temp_dir/production-before-sync.sql.gz"
import_sql="$temp_dir/local-import.sql"
rollback_sql="$temp_dir/production-rollback.sql"
import_started=0
maintenance_was_active=0

wp_cmd=("$php_bin" "$wp_file" "--path=$wp_path" --skip-plugins --skip-themes)
prefix="$("${wp_cmd[@]}" db prefix)"

require_equal() {
    local key="$1"
    local actual="$2"
    local expected="$3"

    if [[ "$actual" != "$expected" ]]; then
        printf 'guard_failed=%s actual=%q expected=%q\n' "$key" "$actual" "$expected" >&2
        return 1
    fi
}

maintenance_active() {
    "${wp_cmd[@]}" maintenance-mode is-active >/dev/null 2>&1
}

restore_maintenance_state() {
    if [[ "$maintenance_was_active" -eq 0 ]]; then
        "${wp_cmd[@]}" maintenance-mode deactivate >/dev/null 2>&1 || true
    fi
}

restore_database() {
    if ! {
        printf 'SET FOREIGN_KEY_CHECKS=0;\n'
        gzip -dc -- "$backup_archive"
        printf '\nSET FOREIGN_KEY_CHECKS=1;\n'
    } | "${wp_cmd[@]}" db cli; then
        return 1
    fi
    "${wp_cmd[@]}" db check --quiet >/dev/null || return 1
}

handle_error() {
    local exit_status=$?

    trap - ERR
    set +e
    if [[ "$import_started" -eq 1 ]]; then
        if ! maintenance_active; then
            "${wp_cmd[@]}" maintenance-mode activate >/dev/null 2>&1
        fi
        printf 'rollback=started\n' >&2
        if restore_database; then
            printf 'rollback=ok\n' >&2
        else
            printf 'rollback=failed remote_backup=%s\n' "$backup_archive" >&2
        fi
    fi
    restore_maintenance_state
    rm -f -- "$import_sql" "$rollback_sql"
    exit "$exit_status"
}
trap handle_error ERR INT TERM

if maintenance_active; then
    maintenance_was_active=1
else
    "${wp_cmd[@]}" maintenance-mode activate >/dev/null
fi
printf 'remote_phase=maintenance_active\n' >&2

# Re-check commerce fingerprints after maintenance mode closes the public site.
current_users="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}users" --skip-column-names | tr -d '[:space:]')"
current_max_user_id="$("${wp_cmd[@]}" db query "SELECT COALESCE(MAX(ID), 0) FROM ${prefix}users" --skip-column-names | tr -d '[:space:]')"
current_latest_user="$("${wp_cmd[@]}" db query "SELECT COALESCE(DATE_FORMAT(MAX(user_registered), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}users" --skip-column-names)"
current_legacy_orders="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names | tr -d '[:space:]')"
current_legacy_max_id="$("${wp_cmd[@]}" db query "SELECT COALESCE(MAX(ID), 0) FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names | tr -d '[:space:]')"
current_legacy_latest="$("${wp_cmd[@]}" db query "SELECT COALESCE(DATE_FORMAT(MAX(post_modified_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names)"
current_hpos_orders="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}wc_orders" --skip-column-names | tr -d '[:space:]')"
current_hpos_max_id="$("${wp_cmd[@]}" db query "SELECT COALESCE(MAX(id), 0) FROM ${prefix}wc_orders" --skip-column-names | tr -d '[:space:]')"
current_hpos_latest="$("${wp_cmd[@]}" db query "SELECT COALESCE(DATE_FORMAT(MAX(date_updated_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}wc_orders" --skip-column-names)"

require_equal users "$current_users" "$expected_users"
require_equal max_user_id "$current_max_user_id" "$expected_max_user_id"
require_equal latest_user_registered_gmt "$current_latest_user" "$expected_latest_user"
require_equal legacy_orders "$current_legacy_orders" "$expected_legacy_orders"
require_equal legacy_max_order_id "$current_legacy_max_id" "$expected_legacy_max_id"
require_equal legacy_latest_modified_gmt "$current_legacy_latest" "$expected_legacy_latest"
require_equal hpos_orders "$current_hpos_orders" "$expected_hpos_orders"
require_equal hpos_max_order_id "$current_hpos_max_id" "$expected_hpos_max_id"
require_equal hpos_latest_updated_gmt "$current_hpos_latest" "$expected_hpos_latest"
printf 'remote_phase=divergence_guard_passed\n' >&2

import_started=1
printf 'remote_phase=import_started\n' >&2
{
    printf 'SET FOREIGN_KEY_CHECKS=0;\n'
    gzip -dc -- "$source_archive"
    printf '\nSET FOREIGN_KEY_CHECKS=1;\n'
} | "${wp_cmd[@]}" db cli

declare -A seen_urls=()
for source_url in "$local_url" "http://maruderm.dev" "https://www.maruderm.dev" "http://www.maruderm.dev"; do
    [[ -n "$source_url" && "$source_url" != "$production_url" ]] || continue
    [[ -z "${seen_urls[$source_url]:-}" ]] || continue
    seen_urls["$source_url"]=1
    "${wp_cmd[@]}" search-replace "$source_url" "$production_url" --all-tables-with-prefix --precise --skip-columns=guid --quiet
done

"${wp_cmd[@]}" option update home "$production_url" --quiet
"${wp_cmd[@]}" option update siteurl "$production_url" --quiet
"${wp_cmd[@]}" option delete elementor_log --quiet >/dev/null 2>&1 || true
"${wp_cmd[@]}" cache flush --quiet >/dev/null 2>&1 || true
"${wp_cmd[@]}" db check --quiet >/dev/null
printf 'remote_phase=database_imported_and_urls_migrated\n' >&2

actual_home="$("${wp_cmd[@]}" option get home)"
actual_siteurl="$("${wp_cmd[@]}" option get siteurl)"
actual_db_version="$("${wp_cmd[@]}" option get db_version)"
actual_tables="$("${wp_cmd[@]}" db query 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()' --skip-column-names | tr -d '[:space:]')"
actual_products="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}posts WHERE post_type = 'product' AND post_status = 'publish'" --skip-column-names | tr -d '[:space:]')"
actual_users="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}users" --skip-column-names | tr -d '[:space:]')"
actual_good_for="$("${wp_cmd[@]}" db query "SELECT COUNT(DISTINCT post_id) FROM ${prefix}postmeta WHERE meta_key = '_maruderm_good_for'" --skip-column-names | tr -d '[:space:]')"
actual_legacy_orders="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names | tr -d '[:space:]')"
actual_legacy_max_id="$("${wp_cmd[@]}" db query "SELECT COALESCE(MAX(ID), 0) FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names | tr -d '[:space:]')"
actual_legacy_latest="$("${wp_cmd[@]}" db query "SELECT COALESCE(DATE_FORMAT(MAX(post_modified_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}posts WHERE post_type = 'shop_order'" --skip-column-names)"
actual_hpos_orders="$("${wp_cmd[@]}" db query "SELECT COUNT(*) FROM ${prefix}wc_orders" --skip-column-names | tr -d '[:space:]')"
actual_hpos_max_id="$("${wp_cmd[@]}" db query "SELECT COALESCE(MAX(id), 0) FROM ${prefix}wc_orders" --skip-column-names | tr -d '[:space:]')"
actual_hpos_latest="$("${wp_cmd[@]}" db query "SELECT COALESCE(DATE_FORMAT(MAX(date_updated_gmt), '%Y-%m-%dT%H:%i:%s'), '') FROM ${prefix}wc_orders" --skip-column-names)"

require_equal home "$actual_home" "$production_url"
require_equal siteurl "$actual_siteurl" "$production_url"
require_equal db_version "$actual_db_version" "$expected_db_version"
require_equal tables "$actual_tables" "$expected_tables"
require_equal products "$actual_products" "$expected_products"
require_equal users "$actual_users" "$expected_users"
require_equal good_for_products "$actual_good_for" "$expected_good_for"
require_equal legacy_orders "$actual_legacy_orders" "$expected_legacy_orders"
require_equal legacy_max_order_id "$actual_legacy_max_id" "$expected_legacy_max_id"
require_equal legacy_latest_modified_gmt "$actual_legacy_latest" "$expected_legacy_latest"
require_equal hpos_orders "$actual_hpos_orders" "$expected_hpos_orders"
require_equal hpos_max_order_id "$actual_hpos_max_id" "$expected_hpos_max_id"
require_equal hpos_latest_updated_gmt "$actual_hpos_latest" "$expected_hpos_latest"

for source_url in "$local_url" "http://maruderm.dev" "https://www.maruderm.dev" "http://www.maruderm.dev"; do
    remaining="$("${wp_cmd[@]}" search-replace "$source_url" "$production_url" --all-tables-with-prefix --precise --skip-columns=guid --dry-run --format=count)"
    require_equal "remaining_url:$source_url" "$remaining" "0"
done

restore_maintenance_state
curl -fsSL --max-time 30 -o /dev/null "$production_url"
import_started=0
rm -f -- "$source_archive"

printf 'home\t%s\n' "$actual_home"
printf 'siteurl\t%s\n' "$actual_siteurl"
printf 'db_version\t%s\n' "$actual_db_version"
printf 'tables\t%s\n' "$actual_tables"
printf 'products\t%s\n' "$actual_products"
printf 'users\t%s\n' "$actual_users"
printf 'good_for_products\t%s\n' "$actual_good_for"
printf 'legacy_orders\t%s\n' "$actual_legacy_orders"
printf 'legacy_max_order_id\t%s\n' "$actual_legacy_max_id"
printf 'legacy_latest_modified_gmt\t%s\n' "$actual_legacy_latest"
printf 'hpos_orders\t%s\n' "$actual_hpos_orders"
printf 'hpos_max_order_id\t%s\n' "$actual_hpos_max_id"
printf 'hpos_latest_updated_gmt\t%s\n' "$actual_hpos_latest"
printf 'http\tok\n'
printf 'rollback\tnot_needed\n'
REMOTE
)"
    ssh_status=$?
    set -e

    if [[ "$ssh_status" -ne 0 ]]; then
        fail "remote production replacement failed; inspect retained backup directory: $REMOTE_TEMP_DIR"
        return 1
    fi
    if [[ -z "$AFTER_INVENTORY" ]]; then
        fail "remote production replacement returned no validation inventory"
        return 1
    fi
    if [[ "$(metric "$AFTER_INVENTORY" rollback)" != "not_needed" ]]; then
        fail "remote replacement did not report a successful no-rollback result"
        return 1
    fi
}

write_report() {
    local completed_at_utc report_path state_tmp run_relative source_relative backup_relative

    completed_at_utc="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    report_path="$RUN_DIR/sync-report.json"
    state_tmp="$STATE_FILE.tmp.$BASHPID"
    run_relative="${RUN_DIR#"$PROJECT_ROOT/"}"
    source_relative="${LOCAL_SOURCE_ARCHIVE#"$PROJECT_ROOT/"}"
    backup_relative="${PRODUCTION_BACKUP_ARCHIVE#"$PROJECT_ROOT/"}"

    jq -n \
        --arg status "ok" \
        --arg started_at_utc "$STARTED_AT_UTC" \
        --arg completed_at_utc "$completed_at_utc" \
        --arg local_url "$LOCAL_URL" \
        --arg production_url "$PRODUCTION_URL" \
        --arg ssh_alias "$REMOTE_ALIAS" \
        --arg remote_path "$REMOTE_WP_PATH" \
        --arg run_directory "$run_relative" \
        --arg source_archive "$source_relative" \
        --arg source_sha256 "$LOCAL_SOURCE_SHA256" \
        --arg rollback_archive "$backup_relative" \
        --arg rollback_sha256 "$PRODUCTION_BACKUP_SHA256" \
        --arg database_schema "$(metric "$AFTER_INVENTORY" db_version)" \
        --argjson tables "$(metric "$AFTER_INVENTORY" tables)" \
        --argjson products "$(metric "$AFTER_INVENTORY" products)" \
        --argjson users "$(metric "$AFTER_INVENTORY" users)" \
        --argjson good_for_products "$(metric "$AFTER_INVENTORY" good_for_products)" \
        --argjson legacy_orders "$(metric "$AFTER_INVENTORY" legacy_orders)" \
        --argjson hpos_orders "$(metric "$AFTER_INVENTORY" hpos_orders)" \
        '{
            status: $status,
            started_at_utc: $started_at_utc,
            completed_at_utc: $completed_at_utc,
            source: {url: $local_url, archive: $source_archive, sha256: $source_sha256},
            production: {url: $production_url, ssh_alias: $ssh_alias, remote_path: $remote_path},
            rollback: {archive: $rollback_archive, sha256: $rollback_sha256, needed: false},
            run_directory: $run_directory,
            database_schema: $database_schema,
            inventory: {tables: $tables, products: $products, users: $users, good_for_products: $good_for_products, legacy_orders: $legacy_orders, hpos_orders: $hpos_orders},
            url_migration: {from: $local_url, to: $production_url, guid_columns_excluded: true},
            production_database_replaced: true,
            local_database_mutated: false,
            public_http_check: "ok"
        }' > "$report_path"

    jq -e '.status == "ok" and (.source.sha256 | length == 64) and (.rollback.sha256 | length == 64)' "$report_path" >/dev/null
    cp "$report_path" "$state_tmp"
    mv "$state_tmp" "$STATE_FILE"
    chmod 600 "$report_path" "$STATE_FILE"
}

cleanup_remote_temp() {
    ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_TEMP_DIR" <<'REMOTE'
set -Eeuo pipefail
temp_dir="$1"
rm -f -- "$temp_dir/production-before-sync.sql.gz" "$temp_dir/local-source.sql.gz" "$temp_dir/local-import.sql" "$temp_dir/production-rollback.sql"
rmdir -- "$temp_dir"
REMOTE
}

case "${1:---preflight}" in
    --preflight) MODE="preflight" ;;
    --execute) MODE="execute" ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; fail "unknown option: $1" ;;
esac

mkdir -p "$STATE_DIR" "$BACKUP_ROOT"

require_command curl
require_command flock
require_command gzip
require_command jq
require_command scp
require_command sha256sum
require_command ssh
require_command ssh-add
require_command ssh-agent

exec 9>"$LOCK_FILE"
flock -n 9 || fail "another local-to-production database synchronization is already running"

load_project_env

readonly LOCAL_WP_BIN="${MARUDERM_DB_SYNC_LOCAL_WP_BIN:-/usr/local/bin/wp}"
readonly LOCAL_URL="${MARUDERM_DB_SYNC_LOCAL_URL:-https://maruderm.dev}"
readonly PRODUCTION_URL="${MARUDERM_DB_SYNC_PRODUCTION_URL:-https://www.maruderm.com.ua}"
readonly REMOTE_ALIAS="${MARUDERM_DB_SYNC_SSH_ALIAS:-citymody}"
readonly REMOTE_WP_PATH="${MARUDERM_DB_SYNC_REMOTE_PATH:-${DEPLOY_PATH:-/home/citymody/maruderm.com.ua/www}}"
readonly REMOTE_PHP_BIN="${MARUDERM_DB_SYNC_REMOTE_PHP_BIN:-/usr/bin/php8.4}"
readonly REMOTE_WP_BIN="${MARUDERM_DB_SYNC_REMOTE_WP_BIN:-/usr/local/bin/wp}"
readonly -a SSH_OPTIONS=(-o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=2 -o IdentitiesOnly=no -o PreferredAuthentications=publickey)
readonly -a SCP_OPTIONS=(-q -o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=2 -o IdentitiesOnly=no -o PreferredAuthentications=publickey)

[[ -x "$LOCAL_WP_BIN" ]] || fail "local WP-CLI binary is missing: $LOCAL_WP_BIN"

trap cleanup EXIT
start_ephemeral_ssh_agent
run_preflight

if [[ "$MODE" == "preflight" ]]; then
    exit 0
fi

RUN_DIR="$BACKUP_ROOT/$RUN_STAMP"
REMOTE_TEMP_DIR="/tmp/maruderm-local-db-to-production-$RUN_STAMP"
LOCAL_SOURCE_ARCHIVE="$RUN_DIR/local-source.sql.gz"
PRODUCTION_BACKUP_ARCHIVE="$RUN_DIR/production-before-sync.sql.gz"
mkdir -p "$RUN_DIR"

create_local_source_export
create_and_download_production_backup
upload_local_source
if ! replace_production_database; then
    fail "production database replacement did not complete"
    exit 1
fi
write_report
cleanup_remote_temp

note "sync=complete"
note "run_directory=${RUN_DIR#"$PROJECT_ROOT/"}"
note "source_sha256=$LOCAL_SOURCE_SHA256"
note "rollback_sha256=$PRODUCTION_BACKUP_SHA256"
note "inventory=tables:$(metric "$AFTER_INVENTORY" tables),products:$(metric "$AFTER_INVENTORY" products),users:$(metric "$AFTER_INVENTORY" users),good_for:$(metric "$AFTER_INVENTORY" good_for_products),legacy_orders:$(metric "$AFTER_INVENTORY" legacy_orders),hpos_orders:$(metric "$AFTER_INVENTORY" hpos_orders)"
note "public_http=$(metric "$AFTER_INVENTORY" http)"
note "rollback=$(metric "$AFTER_INVENTORY" rollback)"
