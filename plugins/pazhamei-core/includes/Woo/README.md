# Woo

WooCommerce hooks. HPOS-safe only (`wc_get_order()`, order getters, `update_meta_data()` + `save()`); HPOS compatibility is declared in `pazhamei-core.php`.

- `ShopRedirects`, `OrderReceived` (Phase 0), `HidePrices` (Phase 1).
- `PlanProducts` (Phase 3): the three plan products, trial price and trial switch filters. `wp pz install-products`.
- `CheckoutFields` (Phase 3): start-date field (block and classic checkout) and the plan rules before payment.
- `OrderHooks` (Phase 3): paid order → pack; cancelled or refunded order → pack cancelled.
- `OrderAddress` (Phase 3): delivery address snapshot for daily orders.
