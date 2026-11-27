# Rest

The /wp-json/pz/v1 API. Every route checks a capability and a `wp_rest` nonce (X-WP-Nonce header) via `Guard::needs()`. The one exception is the public enquiry form (`EnquiryController`): visitors have no capability, so it checks a nonce, a honeypot and a rate limit instead.

- `ScheduleController` (pz_customer): `GET /me/subscription`, `GET /me/schedule`, `POST|DELETE /schedule/{date}/skip`, `POST /me/pause`, `POST /me/resume`. Staff with pz_manage_subscriptions may pass `customer_id`.
- `AdminController`: holidays (pz_manage_settings); credit, cancel and meal type for a pack (pz_manage_subscriptions).
- Rule violations reply 422 with a message the customer can read.
