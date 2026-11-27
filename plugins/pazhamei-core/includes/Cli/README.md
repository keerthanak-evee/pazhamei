# Cli

WP-CLI commands (`wp pz --help` lists them all).

- `Commands`: `install-pages`, `seed` (local only).
- `EngineCommands` (any environment): `schedule <user>`, `run-lock`, `run-close`, `run-status`, `tables`, `jobs`, `install-products`.
- `DevCommands` (local only): `time`, `simulate-order`, `skip`, `undo-skip`, `pause`, `resume`, `holiday`, `credit`, `cancel`, `meal-type`, `mark-failed`.

In Git Bash, the `wp` launcher breaks arguments that contain spaces, so pass the date and time to `wp pz time` as two arguments: `wp pz time 2026-11-29 20:01`.
