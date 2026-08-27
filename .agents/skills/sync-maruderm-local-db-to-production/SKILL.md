---
name: sync-maruderm-local-db-to-production
description: Replace the Maruderm production WordPress database from the current local maruderm.dev database with commerce-divergence guards, verified source and rollback exports, maintenance mode, serialization-safe URL migration, automatic rollback, and post-import validation. Use only when the user explicitly requests a local-to-production database sync; do not use for code or media deployment.
---

# Sync Maruderm Local Database to Production

Load the global `remote-server-ssh-access`, `local-service-access`, and `agent-activity-logging` skills first.

This is a destructive production replacement. Run the preflight before execution:

```bash
.agents/skills/sync-maruderm-local-db-to-production/scripts/sync-local-db-to-production.sh --preflight
```

The preflight compares local and production WordPress identity, database schema, table/product/user inventories, and anonymized WooCommerce order/user fingerprints. It stops when production contains commerce or user changes absent locally.

Only after an explicit user request to replace production from local, run:

```bash
.agents/skills/sync-maruderm-local-db-to-production/scripts/sync-local-db-to-production.sh --execute
```

The execution creates and verifies a local source export and a production rollback export, downloads both into `backups/database/local-to-production/<run>/`, activates production maintenance mode, repeats the divergence guard, imports once, migrates local URLs to `https://www.maruderm.com.ua` with WP-CLI, validates database/commerce inventories and public HTTP health, and automatically restores the production backup on any post-import failure.

Report the run directory, source and rollback checksums, before/after inventories, rollback status, HTTP validation, and any SSH warning. Never delete retained local backups automatically. This workflow changes only the production database and temporary maintenance state; it does not deploy code, upload media, or alter the local database.
