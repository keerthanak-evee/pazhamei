# Repositories

The only place SQL lives, always with `$wpdb->prepare()` (or `$wpdb->insert/update/delete`).

- One class per table, each with its `schema()` for dbDelta. Engine tables are InnoDB (`Db::table_options()`).
- `Contracts/`: the interfaces the services use. PHPUnit swaps in in-memory versions (`tests/Fakes`) that enforce the same UNIQUE keys.
- `Db`: error-checked `$wpdb` calls. A failed statement throws `StorageError`; a UNIQUE clash throws `DuplicateKey`.
- `WpdbTransaction`: START TRANSACTION / COMMIT / ROLLBACK.
- `SchemaRepository`: table engines (`wp pz tables`) and the InnoDB conversion.
