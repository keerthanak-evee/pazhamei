# Cron

Daily jobs, run by Action Scheduler (`Scheduler` registers them; first run computed in IST, stored as UTC, repeating every 24 hours):

- `LockJob` (`pz_lock_job`, at the cutoff time, 20:00 IST): every `scheduled` day past its cutoff becomes `locked` and gets one daily order. Safe to rerun.
- `CloseDayJob` (`pz_close_day_job`, 23:30 IST): with `auto_mark_delivered` on, locked days become `delivered`.
- `PackStatusJob` (`pz_pack_status_job`, 00:10 IST): ends dated pauses, fires `pz_pause_ongoing`, completes packs, starts queued packs.

Jobs read `Clock::now()` when they run, so a late run still works on the right dates. Run them by hand with `wp pz run-lock`, `run-close`, `run-status`; see their next runs with `wp pz jobs`.
