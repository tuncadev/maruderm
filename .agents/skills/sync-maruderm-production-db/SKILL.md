---
name: sync-maruderm-production-db
description: Pull the Maruderm production WordPress database into the local maruderm.dev environment at most once per Europe/Kyiv day. Use before the first Maruderm task of each day, when refreshing local data from production, or when checking the daily database-sync gate. Do not use for local-to-production deployment or media synchronization.
---

# Sync Maruderm Production Database

Use the bundled deterministic script for the database replacement. Load the global `remote-server-ssh-access` and `agent-activity-logging` skills before an actual run.

## Daily gate

Run this before the first substantive project task of each Europe/Kyiv calendar day:

```bash
.agents/skills/sync-maruderm-production-db/scripts/sync-production-db.sh
```

The command exits without network or database work when `.agents/state/daily-production-db-sync.json` already records a successful run for the current Kyiv date. Log the sync under the active user-provided session and task ID; the script deliberately does not invent or write agent activity IDs.

Use the other modes only when appropriate:

```bash
.agents/skills/sync-maruderm-production-db/scripts/sync-production-db.sh --status
.agents/skills/sync-maruderm-production-db/scripts/sync-production-db.sh --preflight
.agents/skills/sync-maruderm-production-db/scripts/sync-production-db.sh --force
```

- `--status` reads only the local daily marker.
- `--preflight` verifies local WordPress/MySQL and production SSH/WP-CLI without exporting or replacing data.
- `--force` performs another same-day replacement; use it only after an explicit same-day refresh request or when the saved state is invalid.

## Safety contract

- Production is a data source only. The script creates one exact `/tmp` SQL archive through remote WP-CLI, downloads and verifies it, and removes that temporary archive. It never imports into or updates production.
- Before replacing local data, it creates a compressed rollback export under `backups/database/daily-production-sync/<run>/`.
- The downloaded production archive is retained beside the rollback export with its SHA-256 and a secret-free JSON report.
- The import disables foreign-key checks only inside the single import stream, removes the imported non-functional `elementor_log` diagnostic option locally, then uses WP-CLI's serialization-safe `search-replace` to map production URL variants to `https://maruderm.dev`, excluding GUID columns.
- Any import, migration, or validation failure triggers an automatic attempt to restore the untouched local rollback export. A failed run never writes the successful daily marker.
- A lock prevents concurrent replacement runs. Do not bypass the lock or manually forge the state marker.
- This workflow does not synchronize uploads or tracked code and does not prune old backup directories automatically.

## Completion checks

Require all of these before treating the daily gate as complete:

- production identity equals `https://www.maruderm.com.ua`;
- source and local WordPress database schema versions match before import;
- source gzip and SHA-256 verification pass;
- local rollback export completes before import;
- local `wp db check` passes after import;
- local `home` and `siteurl` equal `https://maruderm.dev`;
- no production URL variants remain outside GUID columns;
- the local table/product/user inventory is non-empty;
- the JSON state/report is valid and dated for the current Europe/Kyiv day.

Report the backup directory, source checksum, source/local inventory, whether a rollback was needed, and any non-fatal SSH or plugin warnings. Never print credentials or private-key material.
