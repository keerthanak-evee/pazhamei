# Pazhamei data model

Reference for every custom table and option. Update this file in the same commit as any table change (see `CLAUDE.md`).
Source: `docs/build-plan.md`, Phase 3 "Tables". The WordPress prefix is shown as `wp_`.

**Status (plugin 0.3.0, Phase 3):** every table below **exists** except the support tables. The installer creates them with `dbDelta`; it runs on `plugins_loaded` whenever `PZ_VERSION` is newer than the stored `pz_version`. Every table is **InnoDB**: transactions and `SELECT … FOR UPDATE` depend on it. The installer converts any `wp_pz_*` table that isn't InnoDB, and `wp pz tables` shows each table's engine.

Conventions: dates are `date` columns holding IST dates (`Y-m-d`); times are `datetime` in IST from `Clock::now()`. Statuses are `varchar` (not `ENUM`) so new values never need a table rebuild; the allowed values are listed here and in `includes/Domain/*Status.php`. All SQL lives in `includes/Repositories/`.

## Tables

| Table | Holds | Status | Definition |
|---|---|---|---|
| `wp_pz_subscriptions` | One row per purchased pack | exists (0.3.0) | `SubscriptionRepository` |
| `wp_pz_schedule` | One row per customer per date | exists (0.3.0) | `ScheduleRepository` |
| `wp_pz_pauses` | Pause ranges | exists (0.3.0) | `PauseRepository` |
| `wp_pz_daily_orders` | Frozen kitchen orders (made by the 20:00 lock job) | exists (0.3.0) | `DailyOrderRepository` |
| `wp_pz_holidays` | Kitchen closed dates | exists (0.3.0) | `HolidayRepository` |
| `wp_pz_change_log` | Every change, with before/after values | exists (0.3.0) | `ChangeLogRepository` |
| `wp_pz_customer_profiles` | Mobile, dietary preference, instructions, zone; also the per-customer lock row | exists (0.3.0) | `ProfileRepository` |
| `wp_pz_trial_claims` | Who has had a trial, by account, mobile and address (rule 10) | exists (0.3.0) | `TrialClaimRepository` |
| `wp_pz_customer_allergens` | A customer's allergens, with severity | exists (0.3.0), used from Phase 4 | `CatalogSchema` |
| `wp_pz_allergens` | Allergen list | exists (0.3.0), used from Phase 7 | `CatalogSchema` |
| `wp_pz_ingredients` | Ingredient list | exists (0.3.0), used from Phase 7 | `CatalogSchema` |
| `wp_pz_ingredient_allergens` | Ingredient → allergen map | exists (0.3.0), used from Phase 7 | `CatalogSchema` |
| `wp_pz_meal_ingredients` | Meal → ingredient, with grams | exists (0.3.0), used from Phase 7 | `CatalogSchema` |
| `wp_pz_menu_templates` | Weekly fixed menu: weekday × meal type → meals | exists (0.3.0), used from Phase 8 | `CatalogSchema` |
| `wp_pz_menus`, `wp_pz_menu_items` | Menu for a real date × meal type, draft or published | exists (0.3.0), used from Phase 8 | `CatalogSchema` |
| `wp_pz_substitutions` | Requested meal swaps and their approval | exists (0.3.0), used from Phase 7 | `CatalogSchema` |
| `wp_pz_enquiries` | Storefront enquiries | exists (0.2.0) | `EnquiryRepository` |
| `wp_pz_support_tickets`, `wp_pz_support_messages` | Customer support threads | planned (Phase 5) | — |

## wp_pz_subscriptions (packs)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `customer_id` | bigint unsigned | WordPress user ID |
| `order_id` | bigint unsigned | WooCommerce order (HPOS) |
| `order_item_id` | bigint unsigned, **UNIQUE** | The order line; a repeated payment webhook can't create a second pack |
| `product_id` | bigint unsigned | Variation ID |
| `plan_code` | varchar(10) | `TRIAL`, `D6`, `D26` |
| `meal_type` | varchar(10) | `VEG`, `NONVEG`, `MIXED`. Admin may change it (unlocked days only) |
| `quantity` | smallint unsigned, default 1 | Meals per day. Always 1; not exposed anywhere (open decision 5) |
| `total_meals` | smallint unsigned | 1, 6 or 26, plus credited meals |
| `consumed_meals` | smallint unsigned | Rows `delivered` or `failed`; recounted by the jobs |
| `requested_start` | date, NULL | Start date picked at checkout |
| `start_date`, `end_date` | date, NULL | First and last meal (rule 8). Set only by `reflow()`. NULL while the pack has no dates (queued behind an open-ended pause) |
| `status` | varchar(20) | `active`, `queued`, `paused`, `completed`, `cancelled`, `refunded` |
| `renewed_from_id` | bigint unsigned, NULL | The pack this one queued behind |
| `created_at`, `updated_at` | datetime | IST |

Indexes: `UNIQUE order_item_id`, `KEY customer_status (customer_id, status)`, `KEY order_id`.

## wp_pz_schedule (one row per customer per date)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `customer_id` | bigint unsigned | |
| `service_date` | date | |
| `subscription_id` | bigint unsigned | Pack the day belongs to |
| `meal_type` | varchar(10) | Copied from the pack; frozen once locked |
| `status` | varchar(20) | `scheduled`, `skipped`, `paused`, `locked`, `delivered`, `failed`, `holiday_cancelled` |
| `source` | varchar(10) | `initial` (first reflow after purchase), `reflow`, `admin` |
| `created_at`, `updated_at` | datetime | IST |

Indexes: **`UNIQUE customer_date (customer_id, service_date)`** (rule 5), `KEY date_status (service_date, status)`, `KEY subscription_id`.

`reflow()` only deletes or rewrites `scheduled` and `paused` rows on unlocked dates. Rows that use up a meal: `scheduled`, `locked`, `delivered`, `failed`. `holiday_cancelled` gives the meal back to the pack.

## wp_pz_pauses

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `subscription_id` | bigint unsigned | Pack that was current when the pause was made |
| `customer_id` | bigint unsigned | A pause blocks all of the customer's deliveries in its range |
| `start_date` | date | First paused day (unlocked when created) |
| `end_date` | date, NULL | Last paused day (included). NULL = open-ended: lasts until resumed, no auto-resume (rule 7) |
| `status` | varchar(20) | `active`, `ended` (resumed, or past its end date), `cancelled` (resumed before it began) |
| `created_by` | bigint unsigned | User who paused |
| `created_at` | datetime | IST |

Indexes: `KEY customer_id`, `KEY status`.

## wp_pz_daily_orders (frozen kitchen orders)

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `schedule_id` | bigint unsigned, **UNIQUE** | One order per locked schedule row, so the lock job is safe to rerun |
| `subscription_id`, `customer_id` | bigint unsigned | |
| `service_date` | date | |
| `meal_type` | varchar(10) | |
| `quantity` | smallint unsigned, default 1 | Always 1; not exposed (open decision 5) |
| `menu_id` | bigint unsigned, NULL | Menu snapshot (Phase 7–8); NULL until menus exist |
| `address_json` | text | Delivery address snapshot from the pack's WooCommerce order |
| `allergens_json` | text | Customer allergens at lock time |
| `instructions` | text | Delivery instructions at lock time |
| `status` | varchar(20) | `confirmed`, `preparing`, `packed`, `out_for_delivery`, `delivered`, `failed`, `cancelled` (holiday declared after the lock) |
| `generated_at`, `updated_at` | datetime | IST |

Indexes: `UNIQUE schedule_id`, `KEY date_meal (service_date, meal_type)`, `KEY customer_id`.

## wp_pz_holidays

`id`, `holiday_date` (date, **UNIQUE**), `label` (varchar 100), `created_by`, `created_at`.

## wp_pz_change_log

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `entity_type` | varchar(20) | `subscription`, `schedule`, `pause`, `holiday`, `customer`, `job` |
| `entity_id` | bigint unsigned | ID of that thing (customer ID for `customer`, 0 for `job`) |
| `action` | varchar(40) | e.g. `activated`, `skipped`, `skip_undone`, `paused`, `resumed`, `reflow`, `locked`, `delivered`, `failed`, `meal_credited`, `meal_type_changed`, `cancelled`, `refunded`, `holiday_added`, `lock_run` |
| `old_json`, `new_json` | longtext, NULL | Values before and after |
| `actor_id` | bigint unsigned | User ID; 0 for the system |
| `actor_role` | varchar(20) | `customer`, `admin`, `kitchen`, `system`, `cli` |
| `reason` | varchar(255) | Free text |
| `ip` | varchar(45) | Client IP for web requests, empty otherwise |
| `created_at` | datetime | IST |

Indexes: `KEY entity (entity_type, entity_id)`, `KEY created_at`.

## wp_pz_customer_profiles

`id`, `customer_id` (**UNIQUE**), `mobile` (varchar 10, last 10 digits), `dietary_preference`, `instructions` (text), `zone`, `pincode` (varchar 6), `created_at`, `updated_at`. Every schedule change first runs `SELECT … FOR UPDATE` on the customer's profile row, so changes for one customer (a skip at 19:59 and the 20:00 lock job) never interleave.

## wp_pz_trial_claims

`id`, `customer_id`, `subscription_id`, `mobile_key` (last 10 digits), `address_key` (`pincode|house` from `TrialIdentity`), `created_at`. Indexes on `customer_id`, `mobile_key`, `address_key`. Written when a trial pack is activated; checked at checkout (rule 10).

## Catalogue tables (created in 0.3.0, used later)

```
wp_pz_allergens (id, name UNIQUE, created_at)
wp_pz_customer_allergens (id, customer_id, allergen_id, severity, created_at, UNIQUE (customer_id, allergen_id))
wp_pz_ingredients (id, name UNIQUE, unit, created_at)
wp_pz_ingredient_allergens (id, ingredient_id, allergen_id, UNIQUE (ingredient_id, allergen_id))
wp_pz_meal_ingredients (id, meal_id, ingredient_id, grams, UNIQUE (meal_id, ingredient_id))
wp_pz_menu_templates (id, weekday 1-6, meal_type, meal_id, sort_order, UNIQUE (weekday, meal_type, meal_id))
wp_pz_menus (id, menu_date, meal_type, status draft|published, version, published_by, published_at,
    created_at, updated_at, UNIQUE (menu_date, meal_type))
wp_pz_menu_items (id, menu_id, meal_id, sort_order, UNIQUE (menu_id, meal_id))
wp_pz_substitutions (id, menu_id, original_meal_id, replacement_meal_id, reason, status pending|approved|rejected,
    requested_by, decided_by, decided_at, created_at)
```

`meal_id` columns hold `pz_meal` post IDs (the post type arrives with the menu screens).

## wp_pz_enquiries (exists, Phase 1)

One row per enquiry from the `/enquire` form (`pz/enquiry-form` → `POST /wp-json/pz/v1/enquiries`). Definition: `EnquiryRepository::schema()`.

| Column | Type | Notes |
|---|---|---|
| `id` | bigint unsigned, auto increment | Primary key |
| `name` | varchar(100) | Required |
| `mobile` | varchar(10) | Required. Stored as the last 10 digits (no +91 or leading 0), like the trial check (rule 10) |
| `email` | varchar(100) | Optional, `''` if not given |
| `area` | varchar(100) | Optional |
| `pincode` | varchar(6) | Required, 6 digits |
| `plan_interest` | varchar(20) | `trial`, `6-day`, `26-day` or `not-sure` |
| `meal_type` | varchar(10) | `VEG`, `NONVEG`, `MIXED`, or `''` (not sure) |
| `preferred_start` | date, NULL | Optional. After today, within 60 days, never a Sunday |
| `message` | text | Optional, up to 1000 characters, `''` if empty |
| `source_page` | varchar(255) | URL of the page the form was on |
| `status` | varchar(20) | `new` (default), later `contacted`, `converted`, `closed` (Phase 8) |
| `assigned_to` | bigint unsigned, NULL | Staff user ID (Phase 8) |
| `notes` | text, NULL | Staff notes (Phase 8) |
| `created_at` | datetime | IST, from `Clock::now()` |

Indexes: `PRIMARY KEY (id)`, `KEY status_created (status, created_at)`, `KEY mobile (mobile)`.

Not stored: the visitor's IP address. The rate limit (5 per 15 minutes) keeps only a salted hash of the IP, in a transient `pz_rl_enquiry_<hash>` that expires after 15 minutes.


## Not tables

- **Meals** are a custom post type, `pz_meal` (planned), so photos and descriptions use the media library and editor. Meta: kcal, protein, carbs, fat, fibre, suitable meal types, "unavailable" flag.

## Options

| Option | Holds |
|---|---|
| `pz_settings` | `cutoff_time` (`20:00`), `trial_enabled` (false), `trial_price` (0, rupees), `auto_mark_delivered` (true), `service_days` (`mon`–`sat`), `delivery_pincodes` (empty list). `max_pause_days` was removed in 0.3.0 (pauses have no limit) |
| `pz_version` | Plugin version last installed; the installer upgrades when `PZ_VERSION` is newer |
| `pz_nav_menus` | Portal → `wp_navigation` post ID for the four header menus |
| `_transient_pz_rl_enquiry_<hash>` | Enquiry rate limit: recent attempt times for one hashed IP. Expires after 15 minutes (Phase 1) |

## Post meta

| Meta key | On | Holds |
|---|---|---|
| `_pz_portal` | pages | `storefront`, `customer`, `kitchen` or `admin`. Empty means "inherit from parent page". |
| `_pz_media_key` | attachments | Name of a storefront photo (e.g. `hero-lunch`), set by `wp pz install-pages`. Theme patterns and `pz/plan-cards` find photos by it (Phase 1). |
