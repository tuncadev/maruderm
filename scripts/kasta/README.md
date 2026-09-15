# Maruderm Kasta MU-plugin

## Issue

The initial XML was a local snapshot. Kasta needs a persistent feed URL and fresh inventory, while an administrator needs to enable/disable access and regenerate the file without command-line tools. Keeping XML and reports in a public directory would also expose files outside the intended feed.

## Changes and reasons

The runtime is now `wp-content/mu-plugins/maruderm-kasta-feed.php` and its `maruderm-kasta-feed/` classes. PHP owns collection, SKU matching, XML generation, validation, scheduling and serving. The standalone Python builder was replaced to avoid divergent product rules and a server-side Python/Pillow dependency. `collect.php` remains a thin diagnostic adapter for the shared collector.

### Settings and operation

After deploying the complete MU-plugin, open **WooCommerce → Kasta XML**:

1. Save settings to create an independent feed access key. The plugin starts disabled.
2. Click **Сформувати XML зараз**. This queues a background WordPress Cron job, even when public access is disabled.
3. Refresh the settings page to inspect the status, last successful timestamp, counts, excluded SKUs and warnings.
4. Enable the feed and copy its URL into Kasta HUB. Review category mapping and moderation there.
5. Optionally enable automatic refresh every 15 minutes. A working WordPress Cron or server scheduler is required.

The URL is `https://<wordpress-host>/kasta-feed/<generated-key>/products.xml`. It is a virtual route, not a browsable folder. Only exact GET/HEAD requests with the current key are accepted. Disabling access or rotating the key revokes the previous URL. Rotation requires updating the URL in Kasta HUB. This access key is separate from `KASTA_API` and KeyCRM credentials.

Manual generation runs asynchronously so the browser does not need to remain open. If jobs stay queued, verify WordPress Cron. For server-driven generation after deployment:

```bash
wp --skip-themes kasta-feed generate
```

This synchronous CLI action uses the same generator and lock. It can generate while the public feed is disabled. It does not enable the feed or upload products to Kasta.

### Private storage

By default, storage is under `.maruderm-private/kasta-<site-hash>/` beside the WordPress root, outside both `ABSPATH` and the web server document root. The directory is mode 0700 and XML/lock files are mode 0600. If the host requires another location, define `MARUDERM_KASTA_STORAGE_DIR` as an absolute private base directory in server configuration. Existing symlink paths and storage inside a public root are rejected before creation. No `.htaccess` or directory-listing assumptions are needed.

The endpoint runs before Maruderm's headless redirect gate, only for `/kasta-feed` paths. Login, admin, storefront and other routes keep their current handling. Visitors cannot select a filesystem path or access reports/lock files. The XML response has no-store, noindex, nosniff and no-referrer headers. Reports are kept in a non-autoloaded WordPress option accessible through the authorized settings page.

### Freshness and failure handling

If the first generation reports `Cannot create private Kasta storage`, provision the private base for the PHP-FPM account. Do not grant write access to the entire project parent. On this local machine the base has an ACL granting `www-data` access, while PHP creates its own site directory (0700) and files (0600). Run CLI generation as the same PHP account to avoid ownership conflicts. Saving settings creates a URL; it becomes readable only after a successful generation. Inspect the job error in this page if the URL returns 503.

For the local database clone, exact `https://maruderm.dev/wp-content/uploads/` image URLs are mapped to the same path on `https://wp.maruderm.com.ua/`. Every resulting public image is fetched and validated; a local-only or missing image prevents publication. Other local URLs remain forbidden. The local feed URL itself still needs deployment to a publicly reachable host before Kasta can fetch it.

A build validates a complete source snapshot no older than 15 minutes, checks every image URL, validates generated XML and atomically replaces the private XML under a filesystem lock. Failed jobs preserve the previous file and show an error; they do not publish partial XML. The feed returns 503 when missing or older than the configured maximum age (default 24 hours, range 1–48 hours). Kasta retaining old quantities after a fetch error remains a platform concern: returning 503 does not itself zero marketplace inventory.

### Product contract

- Ukrainian canonical WooCommerce records provide stable IDs, primary names, regular website prices, descriptions, attributes and original featured/gallery images. Linked Russian content remains in its separate XML fields.
- SKU must match exactly one KeyCRM offer. Available stock is `max(0, quantity - reserve)` across all warehouses. CRM names and prices are never copied.
- Both `price` and `price_old` use the regular website price; no sale/promo price is exported.
- Initially unpriced, zero-stock products are excluded and reported. Missing price with available stock fails the build.
- Previously exported sold-out products retain their previous regular price when WooCommerce clears it, and receive explicit zero stock. Changed SKU identities or disappearing exported IDs stop replacement for reconciliation.
- Both language descriptions are required, cleaned of markup/links and capped at 5,000 characters with a warning. At least one public image is required; at most 20, each no larger than 10 MB.
- `category-overrides.json` in the MU-plugin resolves six existing ambiguous supplier-category assignments. Kasta category mapping/moderation must still be reviewed in HUB.
- The existing catalog contains simple products. Variations fail until article/color/size grouping is explicitly reviewed.

## Requirements and validation

Runtime: PHP 8+, WordPress, WooCommerce, Polylang, existing `KeyCRM_Sync_Config`, DOM, XMLWriter and mbstring. KeyCRM API access uses its existing backend configuration. No Kasta API token is required merely to serve XML. No Python or shell execution is used by WordPress.

```bash
php scripts/kasta/test-plugin.php
python3 scripts/kasta/test-http.py -v
```

The PHP suite uses isolated temporary storage and WordPress API doubles for mapping, generation, failure retention, settings, permissions and CSRF. The HTTP suite starts a temporary local PHP server and verifies real response codes, headers, file-only access, revocation, disabling and stale-file handling. These are not a substitute for a deployed WordPress/browser smoke test.

Initial production reads from the earlier XML task matched 149 canonical SKUs, with 76 priced offers and 2,870 available units; those historical numbers are not a current stock guarantee. The MU-plugin must generate fresh data after deployment.

## Scope

This implementation generates and serves XML and optionally refreshes it through WordPress Cron. It does not import products into Kasta, change KeyCRM inventory, process orders, or call Kasta stock-write APIs. Coordinate Kasta's own order reservations with KeyCRM order ingestion separately. Deploy using the project's approved Git workflow; no production database replacement is needed.
