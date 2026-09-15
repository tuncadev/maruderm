---
name: build-maruderm-kasta-feed
description: Generate and validate Maruderm Kasta XML from WooCommerce content and regular prices with KeyCRM available stock matched by SKU. Use for initial Kasta XML preparation and feed regeneration.
---

# Maruderm Kasta feed

Use the MU-plugin in `wp-content/mu-plugins/maruderm-kasta-feed.php` and `maruderm-kasta-feed/`. It owns the shared PHP collector, mapper, validator, private storage and endpoint; `scripts/kasta/` contains diagnostics/tests. Read `scripts/kasta/README.md` for commands, prerequisites and publication boundaries; do not reconstruct a feed from Prom XLSX/YML or CRM product names.

Confirmed source contract:

- Ukrainian WooCommerce records own commercial IDs, primary names, regular prices and images. Linked Russian records supply translations only.
- KeyCRM owns stock: exact unique SKU match, all warehouses, `max(0, quantity - reserve)`.
- Keep prior XML when regenerating: it preserves previously exported identities and permits zero-stock updates when WordPress clears sold-out prices.

Use the global KeyCRM core/catalog-stock skills for API reads. For remote inspection or generation, use the remote-server-access skill. The diagnostic collector adapter requires the MU-plugin files to be deployed. `wp kasta-feed generate` runs the real generator and writes private XML; do not describe that command as read-only. Current project paths are under `/home/strangedenial/Hosting/maruderm/`; disregard obsolete `/home/pardus` paths in older materials.

Generate from fresh data, inspect excluded products and warnings, and require successful image probes plus regression tests. Report snapshot time, matched SKUs, exported/excluded products and stock units. XML creation does not mean HUB mapping, moderation, publication or ongoing stock synchronization is enabled. Preserve the user's current authorization scope for those separate steps.

Settings are under WooCommerce → Kasta XML; default disabled, manual background generation, optional 15-minute refresh. Storage must remain outside ABSPATH and DOCUMENT_ROOT. Do not replace the virtual keyed file route with a public uploads directory. HTTP and PHP regression commands are in the README.
