# Services

Actions that change data (buy a pack, skip a day, pause, resume, lock the day). Each one uses Domain rules and Repositories, inside a transaction. Phase 1 added the enquiry flow (`EnquiryService`, `Honeypot`, `RateLimiter`, `ClientIp`); subscription actions arrive in Phase 3.
