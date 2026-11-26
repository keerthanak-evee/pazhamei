# Pazhamei data model

Reference for every custom table and option. Update this file in the same commit as any table change (see `CLAUDE.md`).
Source: `docs/build-plan.md`, Phase 3 "Tables". The WordPress prefix is shown as `wp_`.

**Status:** `wp_pz_enquiries` **exists** (Phase 1, plugin version 0.2.0). The other tables are **planned** and arrive in Phase 3 (Subscription engine) and later. All tables are created by the installer with `dbDelta`; it runs automatically on `plugins_loaded` whenever `PZ_VERSION` is newer than the stored `pz_version`.

## Tables

| Table | Holds | Status |
|---|---|---|
| `wp_pz_subscriptions` | One row per purchased pack | planned |
| `wp_pz_schedule` | One row per customer per service day | planned |
| `wp_pz_pauses` | Pause ranges | planned |
| `wp_pz_daily_orders` | Frozen kitchen orders for a date (made by the 20:00 lock job) | planned |
| `wp_pz_holidays` | Kitchen closed dates | planned |
| `wp_pz_change_log` | Every change, with before/after values | planned |
| `wp_pz_customer_profiles` | Mobile, dietary preference, instructions, zone | planned |
| `wp_pz_customer_allergens` | A customer's allergens, with severity | planned |
| `wp_pz_allergens` | Allergen list (peanut, gluten, dairy, egg, shellfish ...) | planned |
| `wp_pz_ingredients` | Ingredient list | planned |
| `wp_pz_ingredient_allergens` | Ingredient → allergen map | planned |
| `wp_pz_meal_ingredients` | Meal → ingredient, with grams | planned |
| `wp_pz_menu_templates` | Weekly fixed menu: weekday × meal type → meals | planned |
| `wp_pz_menus`, `wp_pz_menu_items` | Menu for a real date × meal type, draft or published | planned |
| `wp_pz_substitutions` | Requested meal swaps and their approval | planned |
| `wp_pz_enquiries` | Storefront enquiries | **exists** (0.2.0) |
| `wp_pz_support_tickets`, `wp_pz_support_messages` | Customer support threads | planned |

## Key columns (from the plan)

```
wp_pz_subscriptions (id, customer_id, order_id, order_item_id UNIQUE, product_id,
    plan_code ENUM('TRIAL','D6','D26'), meal_type ENUM('VEG','NONVEG','MIXED'),
    total_meals, consumed_meals, requested_start, start_date, end_date,
    status ENUM('active','queued','paused','completed','cancelled','refunded'),
    renewed_from_id, created_at, updated_at)

wp_pz_schedule (id, customer_id, service_date, subscription_id, meal_type,
    status ENUM('scheduled','skipped','paused','locked','delivered','failed','holiday_cancelled'),
    source ENUM('initial','reflow','admin'), created_at, updated_at,
    UNIQUE (customer_id, service_date))

wp_pz_pauses (id, subscription_id, customer_id, start_date, end_date NULL,
    status ENUM('active','ended'), created_by, created_at)

wp_pz_daily_orders (id, schedule_id UNIQUE, subscription_id, customer_id, service_date,
    meal_type, menu_id, address_json, allergens_json, instructions,
    status ENUM('confirmed','preparing','packed','out_for_delivery','delivered','failed'),
    generated_at, updated_at, INDEX (service_date, meal_type))

wp_pz_change_log (id, entity_type, entity_id, action, old_json, new_json,
    actor_id, actor_role, reason, ip, created_at, INDEX (entity_type, entity_id))

wp_pz_menus (id, menu_date, meal_type, status ENUM('draft','published'), version,
    published_by, published_at, UNIQUE (menu_date, meal_type))

```

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

The other tables' columns are not fixed in the plan yet. Define them in the phase that builds them.

## Not tables

- **Meals** are a custom post type, `pz_meal` (planned), so photos and descriptions use the media library and editor. Meta: kcal, protein, carbs, fat, fibre, suitable meal types, "unavailable" flag.

## Options (exist now, Phase 0)

| Option | Holds |
|---|---|
| `pz_settings` | `cutoff_time` (`20:00`), `trial_enabled` (false), `trial_price` (0), `max_pause_days` (30), `service_days` (`mon`–`sat`), `delivery_pincodes` (empty list) |
| `pz_version` | Plugin version last installed; the installer upgrades when `PZ_VERSION` is newer |
| `pz_nav_menus` | Portal → `wp_navigation` post ID for the four header menus |
| `_transient_pz_rl_enquiry_<hash>` | Enquiry rate limit: recent attempt times for one hashed IP. Expires after 15 minutes (Phase 1) |

## Post meta (exists now, Phase 0)

| Meta key | On | Holds |
|---|---|---|
| `_pz_portal` | pages | `storefront`, `customer`, `kitchen` or `admin`. Empty means "inherit from parent page". |
| `_pz_media_key` | attachments | Name of a storefront photo (e.g. `hero-lunch`), set by `wp pz install-pages`. Theme patterns and `pz/plan-cards` find photos by it (Phase 1). |
