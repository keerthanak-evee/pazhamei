# Domain

Business rules as plain PHP. No database calls and no WordPress hooks, so every rule can be unit-tested.

- Phase 3: `Calendar` (service days), `Cutoff` (lock time), `StartDate`, `TrialIdentity` (mobile and address keys), `Reflow` + `ReflowPlan` (the scheduling walk), the data objects `Pack`, `Day`, `Pause`, the statuses `PackStatus`, `DayStatus`, plus `Dates`, `Actor`, `EngineConfig`, `RuleViolation`.
- Phase 1: `Plans`, `EnquiryValidator`, `EnquiryStatus`.
