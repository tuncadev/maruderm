#!/usr/bin/env bash

set -Eeuo pipefail
umask 077

readonly SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
readonly PROJECT_ROOT="$(cd "$SCRIPT_DIR/../../../.." && pwd)"
readonly STATE_DIR="$PROJECT_ROOT/.agents/state"
readonly STATE_FILE="$STATE_DIR/daily-production-db-sync.json"
readonly LOCK_FILE="$STATE_DIR/daily-production-db-sync.lock"
readonly BACKUP_ROOT="$PROJECT_ROOT/backups/database/daily-production-sync"
readonly KYIV_DATE="$(TZ=Europe/Kyiv date +%F)"
readonly RUN_STAMP="$(TZ=Europe/Kyiv date +%Y%m%d-%H%M%S)-${BASHPID}"
readonly STARTED_AT_UTC="$(date -u +%Y-%m-%dT%H:%M:%SZ)"

MODE="sync"
FORCE=0
SSH_AGENT_STARTED=0
REMOTE_ARCHIVE_CREATED=0
ROLLBACK_REQUIRED=0
REMOTE_TEMP_ARCHIVE=""
RUN_DIR=""
SOURCE_ARCHIVE=""
LOCAL_BACKUP_ARCHIVE=""
SOURCE_IMPORT_SQL=""
ROLLBACK_IMPORT_SQL=""

usage() {
    cat <<'USAGE'
Usage: sync-production-db.sh [--status|--preflight|--force|--help]

Without options, synchronizes production to the local database once per
Europe/Kyiv calendar day. A successful same-day marker makes the command a no-op.

  --status     Read the local daily state only.
  --preflight  Verify local and production prerequisites without exporting/importing.
  --force      Run another synchronization even when today is already complete.
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

run_local_wp() {
    "$LOCAL_WP_BIN" --path="$PROJECT_ROOT" --skip-plugins --skip-themes "$@"
}

state_is_current() {
    [[ -s "$STATE_FILE" ]] && jq -e --arg date "$KYIV_DATE" '.status == "ok" and .kyiv_date == $date' "$STATE_FILE" >/dev/null 2>&1
}

show_status() {
    if state_is_current; then
        note "daily_sync=complete"
        jq '{status, kyiv_date, completed_at_utc, source_url, local_url, run_directory, source_sha256}' "$STATE_FILE"
        return
    fi

    note "daily_sync=due"
    if [[ -s "$STATE_FILE" ]]; then
        jq '{status, kyiv_date, completed_at_utc}' "$STATE_FILE" 2>/dev/null || note "state=invalid"
    fi
}

inventory_value() {
    local key="$1"
    awk -F '\t' -v wanted="$key" '$1 == wanted {sub(/^[^\t]*\t/, ""); print; exit}' <<< "$REMOTE_INVENTORY"
}

local_inventory_value() {
    local key="$1"
    local prefix

    prefix="$(run_local_wp db prefix)"

    case "$key" in
        tables)
            run_local_wp db query 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()' --skip-column-names | tr -d '[:space:]'
            ;;
        products)
            run_local_wp db query "SELECT COUNT(*) FROM ${prefix}posts WHERE post_type = 'product' AND post_status = 'publish'" --skip-column-names | tr -d '[:space:]'
            ;;
        users)
            run_local_wp db query "SELECT COUNT(*) FROM ${prefix}users" --skip-column-names | tr -d '[:space:]'
            ;;
        *)
            fail "unsupported local inventory key: $key"
            ;;
    esac
}

read_remote_inventory() {
    REMOTE_INVENTORY="$(ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_WP_PATH" "$REMOTE_PHP_BIN" "$REMOTE_WP_BIN" <<'REMOTE'
set -Eeuo pipefail

wp_path="$1"
php_bin="$2"
wp_bin="$3"

[[ -d "$wp_path" ]] || { printf 'Remote WordPress path is missing.\n' >&2; exit 10; }
[[ -x "$php_bin" ]] || { printf 'Remote PHP binary is missing.\n' >&2; exit 11; }
[[ -f "$wp_bin" ]] || { printf 'Remote WP-CLI binary is missing.\n' >&2; exit 12; }

wp_command=("$php_bin" "$wp_bin" "--path=$wp_path" --skip-plugins --skip-themes)
home="$("${wp_command[@]}" option get home)"
siteurl="$("${wp_command[@]}" option get siteurl)"
core_version="$("${wp_command[@]}" core version)"
db_version="$("${wp_command[@]}" option get db_version)"
prefix="$("${wp_command[@]}" db prefix)"
tables="$("${wp_command[@]}" db query 'SELECT COUNT(*) FROM information_schema.tables WHERE table_schema = DATABASE()' --skip-column-names | tr -d '[:space:]')"
products="$("${wp_command[@]}" db query "SELECT COUNT(*) FROM ${prefix}posts WHERE post_type = 'product' AND post_status = 'publish'" --skip-column-names | tr -d '[:space:]')"
users="$("${wp_command[@]}" db query "SELECT COUNT(*) FROM ${prefix}users" --skip-column-names | tr -d '[:space:]')"

printf 'home\t%s\n' "$home"
printf 'siteurl\t%s\n' "$siteurl"
printf 'core_version\t%s\n' "$core_version"
printf 'db_version\t%s\n' "$db_version"
printf 'tables\t%s\n' "$tables"
printf 'products\t%s\n' "$products"
printf 'users\t%s\n' "$users"
REMOTE
)"
}

validate_preflight() {
    local local_home local_siteurl local_db_version

    [[ -f "$PROJECT_ROOT/wp-config.php" ]] || fail "local wp-config.php is missing"
    local_home="$(run_local_wp option get home)"
    local_siteurl="$(run_local_wp option get siteurl)"
    local_db_version="$(run_local_wp option get db_version)"

    [[ "$local_home" == "$LOCAL_URL" ]] || fail "local home mismatch: $local_home"
    [[ "$local_siteurl" == "$LOCAL_URL" ]] || fail "local siteurl mismatch: $local_siteurl"

    read_remote_inventory

    REMOTE_HOME="$(inventory_value home)"
    REMOTE_SITEURL="$(inventory_value siteurl)"
    REMOTE_CORE_VERSION="$(inventory_value core_version)"
    REMOTE_DB_VERSION="$(inventory_value db_version)"
    REMOTE_TABLES="$(inventory_value tables)"
    REMOTE_PRODUCTS="$(inventory_value products)"
    REMOTE_USERS="$(inventory_value users)"

    [[ "$REMOTE_HOME" == "$EXPECTED_PRODUCTION_URL" ]] || fail "production home mismatch: $REMOTE_HOME"
    [[ "$REMOTE_SITEURL" == "$EXPECTED_PRODUCTION_URL" ]] || fail "production siteurl mismatch: $REMOTE_SITEURL"
    [[ "$REMOTE_DB_VERSION" == "$local_db_version" ]] || fail "WordPress database schema mismatch: production=$REMOTE_DB_VERSION local=$local_db_version"
    [[ "$REMOTE_TABLES" =~ ^[1-9][0-9]*$ ]] || fail "production table inventory is invalid"
    [[ "$REMOTE_PRODUCTS" =~ ^[1-9][0-9]*$ ]] || fail "production product inventory is empty or invalid"
    [[ "$REMOTE_USERS" =~ ^[1-9][0-9]*$ ]] || fail "production user inventory is empty or invalid"

    run_local_wp db check --quiet >/dev/null

    note "preflight=ok"
    note "production_url=$REMOTE_HOME"
    note "production_core=$REMOTE_CORE_VERSION"
    note "database_schema=$REMOTE_DB_VERSION"
    note "production_tables=$REMOTE_TABLES"
    note "production_products=$REMOTE_PRODUCTS"
    note "production_users=$REMOTE_USERS"
}

create_remote_export() {
    local export_result

    REMOTE_TEMP_ARCHIVE="/tmp/maruderm-production-db-${RUN_STAMP}.sql.gz"
    export_result="$(ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" bash -s -- "$REMOTE_WP_PATH" "$REMOTE_PHP_BIN" "$REMOTE_WP_BIN" "$REMOTE_TEMP_ARCHIVE" <<'REMOTE'
set -Eeuo pipefail

wp_path="$1"
php_bin="$2"
wp_bin="$3"
archive="$4"
sql_file="${archive%.gz}"

cleanup_failed_export() {
    rm -f -- "$sql_file" "$archive"
}
trap cleanup_failed_export ERR INT TERM

wp_command=("$php_bin" "$wp_bin" "--path=$wp_path" --skip-plugins --skip-themes --quiet)
"${wp_command[@]}" db export "$sql_file" --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4
gzip -9 -- "$sql_file"
gzip -t -- "$archive"

printf 'sha256\t%s\n' "$(sha256sum "$archive" | awk '{print $1}')"
printf 'bytes\t%s\n' "$(wc -c < "$archive" | tr -d '[:space:]')"
REMOTE
)"
    REMOTE_ARCHIVE_CREATED=1
    REMOTE_SHA256="$(awk -F '\t' '$1 == "sha256" {print $2}' <<< "$export_result")"
    REMOTE_BYTES="$(awk -F '\t' '$1 == "bytes" {print $2}' <<< "$export_result")"

    [[ "$REMOTE_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "remote export checksum is invalid"
    [[ "$REMOTE_BYTES" =~ ^[1-9][0-9]*$ ]] || fail "remote export size is invalid"
}

download_remote_export() {
    scp -q "${SCP_OPTIONS[@]}" "$REMOTE_ALIAS:$REMOTE_TEMP_ARCHIVE" "$SOURCE_ARCHIVE"
    [[ -s "$SOURCE_ARCHIVE" ]] || fail "downloaded production archive is empty"
    gzip -t -- "$SOURCE_ARCHIVE"

    SOURCE_SHA256="$(sha256sum "$SOURCE_ARCHIVE" | awk '{print $1}')"
    SOURCE_BYTES="$(wc -c < "$SOURCE_ARCHIVE" | tr -d '[:space:]')"

    [[ "$SOURCE_SHA256" == "$REMOTE_SHA256" ]] || fail "production archive checksum mismatch"
    [[ "$SOURCE_BYTES" == "$REMOTE_BYTES" ]] || fail "production archive size mismatch"

    ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" rm -f -- "$REMOTE_TEMP_ARCHIVE"
    REMOTE_ARCHIVE_CREATED=0
}

create_local_backup() {
    local local_backup_sql="${LOCAL_BACKUP_ARCHIVE%.gz}"

    run_local_wp db export "$local_backup_sql" --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 --quiet
    gzip -9 -- "$local_backup_sql"
    gzip -t -- "$LOCAL_BACKUP_ARCHIVE"
    LOCAL_BACKUP_SHA256="$(sha256sum "$LOCAL_BACKUP_ARCHIVE" | awk '{print $1}')"

    [[ "$LOCAL_BACKUP_SHA256" =~ ^[a-f0-9]{64}$ ]] || fail "local rollback checksum is invalid"
}

prepare_import_sql() {
    local archive="$1"
    local destination="$2"

    {
        printf 'SET FOREIGN_KEY_CHECKS=0;\n'
        gzip -dc -- "$archive"
        printf '\nSET FOREIGN_KEY_CHECKS=1;\n'
    } > "$destination"
}

restore_local_database() {
    note "rollback=started" >&2
    prepare_import_sql "$LOCAL_BACKUP_ARCHIVE" "$ROLLBACK_IMPORT_SQL" || return 1
    run_local_wp db import "$ROLLBACK_IMPORT_SQL" --quiet || return 1
    rm -f -- "$ROLLBACK_IMPORT_SQL" || return 1
    run_local_wp db check --quiet >/dev/null || return 1
    note "rollback=ok" >&2
}

handle_error() {
    local original_status=$?
    local rollback_status=0

    trap - ERR
    if [[ "$ROLLBACK_REQUIRED" -eq 1 ]]; then
        set +e
        restore_local_database
        rollback_status=$?
        set -e

        if [[ "$rollback_status" -ne 0 ]]; then
            note "rollback=failed backup=$LOCAL_BACKUP_ARCHIVE" >&2
        fi
    fi

    exit "$original_status"
}

cleanup() {
    local exit_status=$?

    set +e
    [[ -n "$SOURCE_IMPORT_SQL" ]] && rm -f -- "$SOURCE_IMPORT_SQL"
    [[ -n "$ROLLBACK_IMPORT_SQL" ]] && rm -f -- "$ROLLBACK_IMPORT_SQL"

    if [[ "$REMOTE_ARCHIVE_CREATED" -eq 1 && -n "$REMOTE_TEMP_ARCHIVE" ]]; then
        ssh "${SSH_OPTIONS[@]}" "$REMOTE_ALIAS" rm -f -- "$REMOTE_TEMP_ARCHIVE" >/dev/null 2>&1
    fi

    if [[ "$SSH_AGENT_STARTED" -eq 1 ]]; then
        ssh-agent -k >/dev/null 2>&1
    fi

    exit "$exit_status"
}

replace_source_urls() {
    local source_url
    local -a source_urls=(
        "$REMOTE_HOME"
        "$REMOTE_SITEURL"
        "https://www.maruderm.com.ua"
        "https://maruderm.com.ua"
        "http://www.maruderm.com.ua"
        "http://maruderm.com.ua"
    )
    local -A seen=()

    # Elementor's diagnostic log contains serialized logger objects that are not
    # application state and cannot be safely migrated while plugins are skipped.
    # The same local-only cleanup is used by the project's established manual
    # production migration workflow.
    run_local_wp option delete elementor_log --quiet >/dev/null 2>&1 || true

    for source_url in "${source_urls[@]}"; do
        [[ -n "$source_url" && "$source_url" != "$LOCAL_URL" ]] || continue
        [[ -z "${seen[$source_url]:-}" ]] || continue
        seen["$source_url"]=1
        run_local_wp search-replace "$source_url" "$LOCAL_URL" --all-tables-with-prefix --precise --skip-columns=guid --quiet
    done

    run_local_wp option update home "$LOCAL_URL" --quiet
    run_local_wp option update siteurl "$LOCAL_URL" --quiet
    run_local_wp cache flush --quiet >/dev/null 2>&1 || true
}

validate_local_result() {
    local local_home local_siteurl source_url remaining

    run_local_wp db check --quiet >/dev/null
    local_home="$(run_local_wp option get home)"
    local_siteurl="$(run_local_wp option get siteurl)"
    LOCAL_TABLES="$(local_inventory_value tables)"
    LOCAL_PRODUCTS="$(local_inventory_value products)"
    LOCAL_USERS="$(local_inventory_value users)"

    [[ "$local_home" == "$LOCAL_URL" ]] || fail "post-import local home mismatch: $local_home"
    [[ "$local_siteurl" == "$LOCAL_URL" ]] || fail "post-import local siteurl mismatch: $local_siteurl"
    [[ "$LOCAL_TABLES" =~ ^[1-9][0-9]*$ ]] || fail "post-import local table inventory is invalid"
    [[ "$LOCAL_PRODUCTS" =~ ^[1-9][0-9]*$ ]] || fail "post-import local product inventory is invalid"
    [[ "$LOCAL_USERS" =~ ^[1-9][0-9]*$ ]] || fail "post-import local user inventory is invalid"
    [[ "$(run_local_wp option get db_version)" == "$REMOTE_DB_VERSION" ]] || fail "post-import database schema mismatch"

    for source_url in "$REMOTE_HOME" "$REMOTE_SITEURL" "https://maruderm.com.ua" "http://www.maruderm.com.ua" "http://maruderm.com.ua"; do
        [[ -n "$source_url" && "$source_url" != "$LOCAL_URL" ]] || continue
        remaining="$(run_local_wp search-replace "$source_url" "$LOCAL_URL" --all-tables-with-prefix --precise --skip-columns=guid --dry-run --format=count)"
        [[ "$remaining" == "0" ]] || fail "production URL remains outside GUID columns: $source_url ($remaining replacements)"
    done
}

write_success_state() {
    local completed_at_utc state_tmp report_file run_relative source_relative backup_relative

    completed_at_utc="$(date -u +%Y-%m-%dT%H:%M:%SZ)"
    state_tmp="$STATE_FILE.tmp.$BASHPID"
    report_file="$RUN_DIR/sync-report.json"
    run_relative="${RUN_DIR#"$PROJECT_ROOT/"}"
    source_relative="${SOURCE_ARCHIVE#"$PROJECT_ROOT/"}"
    backup_relative="${LOCAL_BACKUP_ARCHIVE#"$PROJECT_ROOT/"}"

    jq -n \
        --arg status "ok" \
        --arg kyiv_date "$KYIV_DATE" \
        --arg started_at_utc "$STARTED_AT_UTC" \
        --arg completed_at_utc "$completed_at_utc" \
        --arg source_url "$REMOTE_HOME" \
        --arg local_url "$LOCAL_URL" \
        --arg source_core_version "$REMOTE_CORE_VERSION" \
        --arg database_schema "$REMOTE_DB_VERSION" \
        --arg run_directory "$run_relative" \
        --arg source_archive "$source_relative" \
        --arg rollback_archive "$backup_relative" \
        --arg source_sha256 "$SOURCE_SHA256" \
        --arg rollback_sha256 "$LOCAL_BACKUP_SHA256" \
        --argjson source_bytes "$SOURCE_BYTES" \
        --argjson production_tables "$REMOTE_TABLES" \
        --argjson production_products "$REMOTE_PRODUCTS" \
        --argjson production_users "$REMOTE_USERS" \
        --argjson local_tables "$LOCAL_TABLES" \
        --argjson local_products "$LOCAL_PRODUCTS" \
        --argjson local_users "$LOCAL_USERS" \
        '{
            status: $status,
            kyiv_date: $kyiv_date,
            started_at_utc: $started_at_utc,
            completed_at_utc: $completed_at_utc,
            source_url: $source_url,
            local_url: $local_url,
            source_core_version: $source_core_version,
            database_schema: $database_schema,
            run_directory: $run_directory,
            source_archive: $source_archive,
            rollback_archive: $rollback_archive,
            source_sha256: $source_sha256,
            rollback_sha256: $rollback_sha256,
            source_bytes: $source_bytes,
            inventory: {
                production: {tables: $production_tables, products: $production_products, users: $production_users},
                local: {tables: $local_tables, products: $local_products, users: $local_users}
            },
            production_database_mutation: false,
            local_database_replaced: true,
            rollback_needed: false,
            local_cleanup: ["elementor_log option"],
            urls_excluded_from_replacement: ["guid"]
        }' > "$state_tmp"

    jq -e '.status == "ok" and (.source_sha256 | length == 64)' "$state_tmp" >/dev/null
    cp "$state_tmp" "$report_file"
    mv "$state_tmp" "$STATE_FILE"
    chmod 600 "$STATE_FILE" "$report_file"
}

case "${1:-}" in
    "") ;;
    --status) MODE="status" ;;
    --preflight) MODE="preflight" ;;
    --force) FORCE=1 ;;
    --help|-h) usage; exit 0 ;;
    *) usage >&2; fail "unknown option: $1" ;;
esac

if [[ "$MODE" == "status" ]]; then
    show_status
    exit 0
fi

if [[ "$MODE" == "sync" && "$FORCE" -eq 0 ]] && state_is_current; then
    note "daily_sync=already_complete"
    note "kyiv_date=$KYIV_DATE"
    exit 0
fi

mkdir -p "$STATE_DIR" "$BACKUP_ROOT"

require_command flock
require_command gzip
require_command jq
require_command scp
require_command sha256sum
require_command ssh
require_command ssh-add
require_command ssh-agent

exec 9>"$LOCK_FILE"
flock -n 9 || fail "another database synchronization is already running"

load_project_env

readonly LOCAL_WP_BIN="${MARUDERM_DB_SYNC_LOCAL_WP_BIN:-/usr/local/bin/wp}"
readonly LOCAL_URL="${MARUDERM_DB_SYNC_LOCAL_URL:-https://maruderm.dev}"
readonly REMOTE_ALIAS="${MARUDERM_DB_SYNC_SSH_ALIAS:-citymody}"
readonly REMOTE_WP_PATH="${MARUDERM_DB_SYNC_REMOTE_PATH:-${DEPLOY_PATH:-/home/citymody/maruderm.com.ua/www}}"
readonly REMOTE_PHP_BIN="${MARUDERM_DB_SYNC_REMOTE_PHP_BIN:-/usr/bin/php8.4}"
readonly REMOTE_WP_BIN="${MARUDERM_DB_SYNC_REMOTE_WP_BIN:-/usr/local/bin/wp}"
readonly EXPECTED_PRODUCTION_URL="${MARUDERM_DB_SYNC_PRODUCTION_URL:-https://www.maruderm.com.ua}"
readonly -a SSH_OPTIONS=(-o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=2 -o IdentitiesOnly=no -o PreferredAuthentications=publickey)
readonly -a SCP_OPTIONS=(-o BatchMode=yes -o ConnectTimeout=15 -o ServerAliveInterval=15 -o ServerAliveCountMax=2 -o IdentitiesOnly=no -o PreferredAuthentications=publickey)

[[ -x "$LOCAL_WP_BIN" ]] || fail "local WP-CLI binary is missing: $LOCAL_WP_BIN"

trap cleanup EXIT
trap handle_error ERR

start_ephemeral_ssh_agent
validate_preflight

if [[ "$MODE" == "preflight" ]]; then
    exit 0
fi

RUN_DIR="$BACKUP_ROOT/$RUN_STAMP"
SOURCE_ARCHIVE="$RUN_DIR/production.sql.gz"
LOCAL_BACKUP_ARCHIVE="$RUN_DIR/local-before-sync.sql.gz"
SOURCE_IMPORT_SQL="$RUN_DIR/production-import.sql"
ROLLBACK_IMPORT_SQL="$RUN_DIR/local-rollback.sql"
mkdir -p "$RUN_DIR"

create_remote_export
download_remote_export
create_local_backup

prepare_import_sql "$SOURCE_ARCHIVE" "$SOURCE_IMPORT_SQL"
ROLLBACK_REQUIRED=1
run_local_wp db import "$SOURCE_IMPORT_SQL" --quiet
rm -f -- "$SOURCE_IMPORT_SQL"
replace_source_urls
validate_local_result
ROLLBACK_REQUIRED=0

write_success_state

note "daily_sync=complete"
note "kyiv_date=$KYIV_DATE"
note "run_directory=${RUN_DIR#"$PROJECT_ROOT/"}"
note "source_sha256=$SOURCE_SHA256"
note "production_inventory=tables:$REMOTE_TABLES,products:$REMOTE_PRODUCTS,users:$REMOTE_USERS"
note "local_inventory=tables:$LOCAL_TABLES,products:$LOCAL_PRODUCTS,users:$LOCAL_USERS"
