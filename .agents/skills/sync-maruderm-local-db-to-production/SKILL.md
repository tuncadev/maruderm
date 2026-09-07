---
name: sync-maruderm-local-db-to-production
description: Retired workflow. Local database exports must never be imported into Maruderm production.
---

# Retired: Local Database to Production

User policy effective 2026-09-07: never export a local database and import it into production. Do not run the bundled legacy sync script, including its execution mode.

Apply targeted production database changes over SSH using the global `remote-server-ssh-access` skill and preserve live commerce data. If SSH is unavailable, perform the changes manually in production. Code deployments must not replace the database.
