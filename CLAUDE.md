# Pazhamei — project rules for Claude

Pazhamei is a cloud kitchen. This repo is the WordPress web app: a storefront, a customer portal, a kitchen portal and an admin portal. Customers buy prepaid lunch packs (Trial, 6-day, 26-day) as Veg, Non-Veg or Mixed, and can skip days or pause.

## Session start

1. Read this file, `docs/PHASES.md`, and the current phase's section of `docs/build-plan.md`.
2. Check that `git status` is clean and that `wp db query "SELECT 1"` works (`wp db check` fails on LocalWP because of the MySQL port). If `wp db query` fails in Git Bash because the mysql path has a space, `wp option get siteurl` is enough to prove the database is up. If the database fails, ask the owner to start the site in LocalWP. Don't change `wp-config.php`.
3. Record every decision the owner makes in `docs/PHASES.md`, under its phase.

## Phases

0 Foundation · 1 Storefront · 2 Deployment setup · 3 Subscription engine · 4 Customer portal · 5 Actions and manual flows · 6 Notifications · 7 Kitchen portal · 8 Admin portal · 9 QA · 10 Go-live

- `docs/build-plan.md` is the plan (the source of truth; there is no PDF).
- `docs/PHASES.md` tracks progress and decisions.

## Environment

- Local site: LocalWP, site name `pazhamei`. Start Claude Code from LocalWP's **Open site shell** so `wp`, `php` and `mysql` work.
- Claude Code's shell is Git Bash. Prefix `wp` commands that take `/...` arguments with `MSYS_NO_PATHCONV=1`, or Git Bash rewrites them into Windows paths.
- Claude Code already runs in wp-content. Never prefix commands with cd to the project path; use paths relative to wp-content. Prefer separate simple commands over long && chains.
- This repo's root is the site's `wp-content` folder. Only our own code is tracked (see `.gitignore`).
- WordPress 6.9+, WooCommerce, Razorpay for WooCommerce, FluentSMTP, Create Block Theme.
- Timezone: Asia/Kolkata (IST). Currency: INR. All date logic uses `wp_timezone()`, never server time or UTC.
- Environments: `local` (LocalWP) → `staging` (UAT on Hostinger) → `production` (Hostinger). Read the type with `wp_get_environment_type()`.

## What lives where

- `themes/pazhamei-theme/` — block theme, design only: `theme.json`, templates, template parts (`parts/header.html`, `parts/footer.html`), patterns. No business logic.
- `plugins/pazhamei-core/` — all logic, organised in modules:
  - `includes/Install/` — tables, page installer, roles
  - `includes/Domain/` — rules
  - `includes/Services/` — actions
  - `includes/Repositories/` — the only place SQL lives
  - `includes/Rest/` — `/wp-json/pz/v1`
  - `includes/Cron/` — scheduled jobs
  - `includes/Woo/` — WooCommerce hooks
  - `includes/Access/` — portal guard and capabilities
  - `includes/Emails/` — email classes
  - `includes/Admin/` — wp-admin screens
  - `includes/Cli/` — `wp pz` commands
  - `blocks/` — one folder per `pz/*` block
  - `tests/` — PHPUnit tests
- `design/` — reference prototypes (HTML/CSS/JS from any tool). Tracked, never deployed. Convert them into `theme.json` settings, block patterns and `pz/*` blocks. Never copy raw prototype files or stylesheets into the theme or plugin.
- `docs/DATA-MODEL.md` — table reference. Update it in the same commit as any table change.
- `docs/prompts/` — the prompt used to start each phase.

## Portals, URLs and roles

| Portal | URL prefix | Roles allowed | Landing after sign-in |
|---|---|---|---|
| Storefront | top-level pages | everyone | — |
| Customer | `/account/...` | `customer` | `/account/dashboard` |
| Kitchen | `/kitchen/...` | `pz_kitchen`, `pz_admin`, `administrator` | `/kitchen` |
| Admin | `/manage/...` | `pz_admin`, `administrator` | `/manage` |

- Never use the slugs `/login` or `/admin`. WordPress redirects them to `wp-login.php` and `wp-admin`. Use `/sign-in` and `/manage`.
- Each page stores its portal in the post meta `_pz_portal` (`storefront`, `customer`, `kitchen`, `admin`).
- The portal guard (`includes/Access`) checks every request.
- Every REST route must check both a capability and a nonce. The page guard alone is never enough.
- Custom capabilities (all prefixed `pz_`):
  - Customers: `pz_customer`
  - Kitchen: `pz_view_kitchen`, `pz_mark_delivery`
  - Admin: all `pz_*` capabilities, including `pz_manage_menu`, `pz_manage_customers`, `pz_manage_subscriptions`, `pz_view_reports`, `pz_manage_users`, `pz_manage_settings`
- Only `administrator` may open `wp-admin`. Hide the admin bar for everyone else.
- In V1 the owner is the only admin, as `administrator`. `pz_admin` staff accounts are created only after the `/manage` portal exists (Phase 8).

## Header, footer and pages

- One header and one footer for the whole site: the template parts `parts/header.html` and `parts/footer.html`, used by every template.
- Template parts use a plain `div`; the templates add the `header`/`footer` wrapper (`tagName` on the template-part block).
- The header contains the `pz/portal-nav` block. It renders the Navigation menu for the current page's portal. There are four menus: Storefront, Customer, Kitchen, Admin.
- Menus are `wp_navigation` posts, rendered by `pz/portal-nav` through a `core/navigation` block with `ref`. Never create classic `nav_menu` menus.
- Fonts and other assets are self-hosted in the theme. No Google Fonts or other external CDNs.
- Every page is a normal WordPress page, editable in the block editor. Pages are built from core blocks plus `pz/*` blocks. Never hard-code page HTML into PHP templates.
- Pages are created only through `wp pz install-pages`. The installer creates missing pages and never overwrites a page that already exists. Its only changes to existing pages: it sets a missing author (the first administrator); it refreshes starter content that nobody has edited (fingerprint in `_pz_starter_hash`), together with that page's template; and it renames or retires pages that moved, pointing old links at the new address (`Install/MovedPagesMigration`).
- Home, Plans, About Us and FAQ are built from theme patterns (`patterns/home-*.php`, `patterns/page-*.php`). Plans, About Us and FAQ use the `page-landing` template (full width, no title: each page's first section has the H1). After adding or removing a pattern file, bump the theme version in `style.css`, or WordPress keeps its cached pattern list.
- Storefront pages: `/` (Home), `/plans`, `/about-us`, `/faq`, `/sign-in` and the four policy pages. The Storefront menu is Home, Plans, About Us, FAQ.
- The enquiry form (`pz/enquiry-form`) lives only on `/about-us/#contact`. Plan-card Enquire buttons go to `/about-us/?plan=<plan>#contact`, which pre-selects the plan. "Talk to the kitchen" buttons go to `/about-us/#contact`.
- Old addresses redirect permanently (301, `Access/MovedPages`): `/about-contact` → `/about-us/`, and `/enquire` → `/about-us/#contact`, keeping any `?plan=`. Never reuse those slugs.

## WooCommerce pages

- The My Account page is `/sign-in`. `/my-account` redirects to `/sign-in`.
- Logged-in users opening `/sign-in` itself go to their role's landing page; endpoints under `/sign-in/` keep working.
- Shop, product and category pages go to `/plans`. The cart goes to `/checkout`; an empty cart goes to `/plans`.
- The order-received page shows a "Go to my dashboard" button, with no automatic redirect.
- Store visibility stays "Coming soon" until Phase 10.

## Business rules (do not change without the owner's approval)

1. Service days are Monday to Saturday. Sunday is always off. Admin-declared holidays (`wp_pz_holidays`) are also off.
2. A plan is a pack of meals: Trial = 1, 6-day = 6, 26-day = 26. The meal type (VEG, NONVEG, MIXED) is fixed at purchase; only the admin can change it later (unlocked days only).
3. **Cutoff:** a change for date D is allowed until 20:00 IST on the calendar day before D. After that, D is locked. The 20:00 time comes from the `pz_settings` option. Monday follows the same rule: its cutoff is Sunday 20:00.
4. **Start date:** the customer picks it at checkout. It must be an unlocked service day within 30 days.
5. **One meal per customer per day:** `wp_pz_schedule` has `UNIQUE (customer_id, service_date)`. Not final: `wp_pz_subscriptions` and `wp_pz_daily_orders` carry a `quantity` column (default 1, not exposed anywhere) so more meals a day can be allowed later.
6. **Skip:** allowed on an unlocked scheduled day, and can be undone until the cutoff. The meal moves to the end of the plan. No limit on skips per pack.
7. **Pause:** from an unlocked date, either until a chosen date (the last paused day) or until Resume. No time limit and no auto-resume: an open-ended pause lasts until the customer or admin resumes. Paused days move to the end of the plan.
8. **End date:** always the date of the pack's last scheduled meal. It is recalculated only by `reflow()`.
9. **Renewal:** buying while a pack is active creates a `queued` pack. It starts on the next service day after the current pack ends. If the current pack is on an open-ended pause, the queued pack has no dates and waits until the customer resumes. A renewal may use a different meal type.
10. **Trial:** sold only when the admin switch (`trial_enabled`) is on, at the admin-set `trial_price` (can be 0), with no approval step, and once per customer. Checked by account; by mobile number, compared on its last 10 digits only, so +91 or a leading 0 makes no difference; and by address, compared on pincode plus house or flat number with punctuation and filler words ("no", "flat", "door") removed.
11. **Daily orders:** created only by the 20:00 lock job, for the next day, from locked schedule rows. The job must be safe to rerun.
12. **Reflow:** skip, pause, resume, holidays, renewals, cancellations and admin changes never edit dates directly. They record the change, then call `reflow()` for that customer inside a transaction.
13. **Cancel:** cancelling a pack (admin action, or a WooCommerce refund or cancellation) only removes its unlocked days, sets the status and logs it. Refunds are handled offline by the owner; the plugin never calculates a refund.
14. **Failed delivery:** a locked day can be marked `failed`. There is no automatic credit; the admin's "Credit meal" action adds one meal to the pack and reflows.
15. **Closing the day:** with the setting `auto_mark_delivered` on (default), locked days not marked failed by 23:30 count as delivered. With it off, they stay locked until someone marks them.

## Coding conventions

- Prefix everything `pz_` (functions, options, meta, tables `wp_pz_*`, hooks) or namespace `Pazhamei\`.
- SQL only in `includes/Repositories/`, always with `$wpdb->prepare()`.
- Escape all output (`esc_html`, `esc_attr`, `esc_url`, `wp_kses_post`). Sanitise all input.
- Never edit WordPress core, WooCommerce or other plugins. Use hooks and filters.
- WooCommerce HPOS is on. Use `wc_get_order()`, `wc_get_orders()`, `$order->get_meta()`, and `$order->update_meta_data()` + `$order->save()`. Never use `get_post_meta()`, `WP_Query` or direct SQL on orders. Keep the HPOS compatibility declaration.
- Any meta edited in the block editor is registered with `register_post_meta` (`show_in_rest`, `auth_callback`).
- Display money as ₹ with Indian digit grouping (₹1,23,456) and dates like "26 Nov 2026". Store dates as `Y-m-d` in IST.
- Blocks use `block.json`, `render.php`, and the Interactivity API for anything that updates without a reload.
- Keep files small and readable. The owner is a Mendix developer learning PHP, so prefer clear code over clever code, and add a short comment above each class explaining what it is for.
- Bump the plugin version constant whenever the schema changes. The installer upgrades tables with `dbDelta` when the stored version is older.

## Testing

- Every business rule needs a PHPUnit test. Run the tests before every commit.
- Get the current time from `Pazhamei\Clock::now()`. To fake it for time-based rules, run `wp pz time 2026-11-29 20:01` (stored in the option `pz_fake_now`, lasts across commands; `wp pz time --clear` to stop), or define `PZ_FAKE_NOW` (`Y-m-d H:i:s`) in the local `wp-config.php`, which wins over the option.
- `wp pz seed`, `wp pz time`, the other local test helpers and any fake time work only when the environment type is `local`; they refuse or are ignored (with a warning) on staging and production.
- Engine tests run on in-memory stores (`tests/Fakes`) that enforce the same UNIQUE keys as MySQL. Test the real database through the `wp pz` commands (`wp pz schedule <user>`, `wp pz simulate-order`, `wp pz run-lock`).
- After building a page or flow, open it in the browser and check it as the right test user.

## Workflow for each phase

1. Do the session-start steps.
2. Propose a plan, and wait for approval before writing code.
3. Build, run the tests, and check in the browser.
4. Summarise what changed and how to test it, then commit (see Git).

## Git

- One commit per logical change, with a clear message.
- At the end of each phase, tag `phase-N-done` and push `main` and the tags.
- Never force-push, and never rewrite pushed history.

## Never

- Never commit secrets: no API keys, Razorpay keys or passwords. They belong in `wp-config.php` on each server.
- Never print or commit real customer data.
- Never edit code directly on UAT or production. Code only arrives through Git deploys.
- Never use Hostinger's staging "Publish" to push UAT to production after launch. Code moves up; data only moves down (production → UAT).
- Never weaken a business rule to make a test pass. Ask the owner.
