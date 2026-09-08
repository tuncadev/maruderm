# Shipment status rules

Workspace: https://hzlglobal2026.keycrm.app/

## Active KeyCRM delivery rules

Configured in Settings > Deliveries > Status switching rules on 2026-09-07.

| Shipment event | KeyCRM order status |
| --- | --- |
| Invoice created | 8: TTN created |
| In transit | 10: departing |
| Awaiting collection | 10: departing |
| Delivered | 12: completed |
| Delivered, COD transfer pending | 12: completed |
| Delivered, COD received | 12: completed |
| Returned to sender | 19: canceled |
| Return in progress, delivery error/refusal, disposal, unknown | Preserve order status; review manually |

These are shipment/order rules, not payment, refund, or fiscal-receipt rules. The synchronizer sends only status_id to KeyCRM. Do not infer a cancellation reason or refund from a generic delivery error.

## Production fallback and website

- Class: Maruderm_KeyCRM_TTN_Status_Synchronizer in wp-content/mu-plugins/maruderm-transactional-emails/class-keycrm-ttn-status-synchronizer.php.
- Hook: maruderm_keycrm_sync_new_ttn_statuses. The hosting cron runs it every five minutes; GitHub Actions is an additional runner, not the sole scheduler.
- Re-evaluates existing TTNs, not only new tracking numbers. Uses normalized shipping history, an atomic option lock, request pacing, fresh reads before writes, and readback verification.
- Preserves completed/canceled CRM orders and native/custom terminal WooCommerce statuses. Does not regress an order from departing to invoice created.
- Read-only preview: wp eval 'echo wp_json_encode((new Maruderm_KeyCRM_TTN_Status_Synchronizer())->preview());'
- Site source ID is 2. Reconciliation requires numeric source_uuid and an exact stored _keycrm_order_id match. Marketplace order IDs must never be treated as website IDs.
- Website reconciliation reuses the existing authenticated KeyCRM status handler and configurable mappings. A repeated event is ignored only if the actual WooCommerce status still matches.
- Existing site terminal mappings are keycrm-12 (Completed, fallback completed) and keycrm-19 (Cancelled, fallback cancelled). Preserve these configured labels/slugs.
- Logs: WooCommerce sources maruderm-keycrm-ttn-status-sync and maruderm-keycrm-status-webhook.
- Never export a local database into production. Apply any required targeted configuration changes over SSH or manually.

## Rozetka: prepared, not active

No Rozetka source exists in this workspace as of 2026-09-07. Native source creation returned HTTP 500 from /sources with both saved API credentials and the saved owner login. The authorization button did not complete a connection. Existing sources were preserved; do not report Rozetka synchronization as enabled.

After a successful owner-authorized connection, let historical orders finish importing BEFORE enabling outbound mappings. Then configure the following proposed sequence, confirming the exact available labels and transition constraints in the live source:

| KeyCRM stage | Rozetka stage |
| --- | --- |
| New | Processing by manager |
| Confirmed | Assembling; details confirmed |
| TTN created | Keep assembling; transmit TTN only after the preceding stage |
| Ready to send | Handed to delivery service; TTN required |
| Departing | Delivering |
| Completed | Order fulfilled |
| Canceled | Use the actual permitted cancellation reason; do not invent buyer refusal |

Do not skip Rozetka stages, blindly resend a TTN, change stock/prices, or replay old orders into New. Native source rules are dashboard-managed and are not writable through KeyCRM OpenAPI 1.2.0.

References:
- https://help.keycrm.app/uk/integrations-with-delivery-services/automatic-change-of-order-status-with-delivery-status
- https://help.keycrm.app/uk/getting-orders-and-goods-from-marketplaces/adding-rozetka-to-sources
- https://help.keycrm.app/en/getting-orders-and-goods-from-marketplaces/nalashtuvannia-pravil-pieriemikannia-statusiv-dlia-rozetka-v-key-crm

Validation on 2026-09-07: KeyCRM order 5 is completed (12); linked website order 6611 is Completed (keycrm-12). Canceled orders remained unchanged. Lifecycle, replay, identity, concurrent-cancellation, lock, and custom-terminal safeguards passed isolated tests. Native delivery configuration backup is retained in .agents/backups/shipment-rules-before-20260907.json.

## Bidirectional website order-stage synchronization

The `Maruderm_KeyCRM_Order_Status_Sync` adapter adds WooCommerce-to-KeyCRM order-stage writes. It inverts each explicitly included custom mapping: New1, Confirmed2, Waiting for Prepayment4, TTN Created8, Ready to Send9, Departing10, Completed12, Cancelled19, Paid20. Native aliases are pending→1, processing→2, on-hold→4, completed→12, cancelled→19; `maruderm_keycrm_native_status_map` can override explicit aliases. Failed and Refunded have no exact CRM stage: preserve and flag them for mapping review. Drafts are not CRM workflow stages.

Only `status_id` is sent for order-stage updates. The vendor payment-update-on-status callback and the custom payment status-change callback are removed; actual `woocommerce_payment_complete` handling remains. KeyCRM's own cancellation/automation may update its payment entry; a status API call is not a charge/refund operation. Do not promise unchanged payment metadata merely because no payment endpoint was called.

Outbound intent is stored in `_maruderm_keycrm_pending_status`; network/mapping failures add `_maruderm_keycrm_status_sync_error` and a deduplicated internal order note. The existing `maruderm_keycrm_sync_new_ttn_statuses` five-minute scheduler retries mapped pending statuses before shipment reconciliation, with bounded work and request pacing. Review unmapped statuses manually rather than cycling them through automatic retries.

Both directions require exact website source2 + source_uuid + stored CRM ID. Per-order atomic option locks and runtime incoming-event guards prevent echoes. Incoming API lookup failures preserve Woo state; never guess from webhook status_group_id. A pending local change cannot be overwritten by a stale CRM event. Terminal Woo states cannot be overwritten automatically, and active Woo states cannot reopen terminal CRM orders. Conflicts require explicit reconciliation. Equivalent native/custom terminal aliases remain equivalent without unnecessary status transitions.

Validation: `php scripts/test-bidirectional-status.php`. Cover all nine configured stages both directions, native aliases, API failures/retry, identity/terminal protection, pending-local priority, a concurrent newer change, lock ownership and payment callback isolation. Production end-to-end stage cycling must not use real paid/completed orders as test fixtures.

Payment confirmation requires a recorded WooCommerce `date_paid` as well as paid-state eligibility. Never use `is_paid()` alone: native Processing includes unpaid COD orders. An earlier unpaid status callback must not suppress a later payment-complete event in the same request. Validate with `php scripts/test-cod-payment-sync.php`. Website `cod` maps to KeyCRM payment method6 (Наложенный платеж), not method1 (Cash). Correcting payment bookkeeping does not modify an existing TTN's collection amount; verify that separately in the carrier dashboard.
