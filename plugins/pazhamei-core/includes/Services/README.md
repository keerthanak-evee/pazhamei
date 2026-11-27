# Services

Actions that change data. Each one checks the business rules, runs inside a transaction holding the customer's lock, writes the change log and calls `reflow()`.

- `Engine`: the front door (storage, settings, clock, change log, events) that hands out the services. `Engine::live()` on the site; tests build one on in-memory stores.
- Subscription engine (Phase 3): `ActivationService`, `ReflowService`, `SkipService`, `PauseService`, `HolidayService`, `PackAdminService` (cancel, credit meal, change meal type), `DeliveryService`, `TrialEligibility`, `CheckoutCheck`.
- Enquiries (Phase 1): `EnquiryService`, `Honeypot`, `RateLimiter`, `ClientIp`.
