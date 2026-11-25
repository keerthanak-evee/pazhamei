# Pazhamei data model

Reference for every custom table and option. Update this file in the same commit as any table change (see `CLAUDE.md`).
Source: `docs/build-plan.md`, Phase 3 "Tables". The WordPress prefix is shown as `wp_`.

**Status:** all tables are **planned**. None exist yet. They arrive in Phase 3 (Subscription engine) and are created by the installer with `dbDelta`.

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
| `wp_pz_enquiries` | Storefront enquiries | planned |
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

wp_pz_enquiries (id, name, mobile, email, area, pincode, plan_interest, meal_type,
    preferred_start, message, source_page,
    status ENUM('new','contacted','converted','closed'), assigned_to, notes, created_at)
```

The other tables' columns are not fixed in the plan yet. Define them in the phase that builds them.

## Not tables

- **Meals** are a custom post type, `pz_meal` (planned), so photos and descriptions use the media library and editor. Meta: kcal, protein, carbs, fat, fibre, suitable meal types, "unavailable" flag.

## Options (exist now, Phase 0)

| Option | Holds |
|---|---|
| `pz_settings` | `cutoff_time` (`20:00`), `trial_enabled` (false), `trial_price` (0), `max_pause_days` (30), `service_days` (`mon`–`sat`), `delivery_pincodes` (empty list) |
| `pz_version` | Plugin version last installed; the installer upgrades when `PZ_VERSION` is newer |
| `pz_nav_menus` | Portal → `wp_navigation` post ID for the four header menus |

## Post meta (exists now, Phase 0)

| Meta key | On | Holds |
|---|---|---|
| `_pz_portal` | pages | `storefront`, `customer`, `kitchen` or `admin`. Empty means "inherit from parent page". |
