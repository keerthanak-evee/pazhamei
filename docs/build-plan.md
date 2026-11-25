# Pazhamei Web App — Build Plan (WordPress + WooCommerce)

Nov 25, 2026 · @KK

## Summary

Pazhamei is built as one WordPress site: a custom block theme, WooCommerce with Razorpay for payment, and one custom plugin (`pazhamei-core`) that owns the subscription engine, the portals and all custom tables. Claude Code builds it phase by phase, with the official WordPress agent skills and the WordPress MCP Adapter installed.

Five decisions shape everything else:

1. **Plans are prepaid packs, not recurring billing.** Each plan (Trial, 6-day, 26-day) is a normal WooCommerce product paid once through Razorpay. The custom plugin turns a paid order into a "meal subscription" with a day-by-day schedule. So the paid WooCommerce Subscriptions extension is not needed, and renewal simply means buying the next pack.
2. **Every portal page is a normal WordPress page.** Dynamic parts (dashboard cards, skip/pause calendar, kitchen counts) are custom blocks placed on those pages. You can edit text, images and layout around them in the block editor.
3. **One header, one footer, four menus.** The header and footer are theme template parts, editable in Appearance → Editor. The header holds a "Portal navigation" block that shows the storefront, customer, kitchen or admin menu depending on which portal the page belongs to.
4. **All business logic sits behind a REST API** (`/wp-json/pz/v1/...`). Blocks call it today; a future mobile app or custom front end can call the same API. This is what makes later migration possible.
5. **Build the engine before the customer portal.** Your Phase 2 pages need your Phase 3 plugin data, so the plugin engine moves earlier.

**Revised phase list** (your original phase number in brackets):

| # | Phase | Main output | Your hours (fast track) |
| --- | --- | --- | --- |
| 0 | Foundation (new) | LocalWP site, Git, Claude setup, theme, header/footer, roles, plugin skeleton | 5–7 |
| 1 | Storefront \[1\] | 6 public pages + legal pages + enquiry storage | 6–9 |
| 2 | Deployment setup (new) | Hostinger production + UAT, GitHub auto-deploy, storefront live, Razorpay KYC applied | 4–6 |
| 3 | Subscription engine plugin \[3\] | Tables, rules, schedule, cron jobs, REST API, tests | 10–14 |
| 4 | Customer portal \[2\] | Meals, plans with prices, checkout, dashboard | 7–10 |
| 5 | Actions and manual flows \[4\] | Every button wired; admin overrides | 4–6 |
| 6 | Notifications (new) | Emails for each event | 2–3 |
| 7 | Kitchen portal \[5\] | Dashboard, production sheet, allergen alerts | 3–5 |
| 8 | Admin portal \[6\] | V1: wp-admin screens + dashboard, used only by you as administrator; full custom portal after launch (+12–18 h) | 3–5 |
| 9 | QA, security, performance (new) | Test suite, hardening, caching rules | 6–8 |
| 10 | Go-live (new) | Live payments, real menu, soft launch | 2–3 |
|  | **Total** |  | **52–76 hours** |

## Timeline and effort

On the fast track, V1 can soft-launch in about 5 weeks, around the first week of January 2027, at 15–20 hours a week. Hours are your time at the keyboard: briefing Claude, reviewing, testing and deciding; Claude's own coding time is a small part of it.

The fast track assumes:

- The 12 decisions at the end of this doc are answered before Phase 3.
- Content (menu, prices, photos, About text, FAQ) is collected alongside Phases 1–3, not at the end.
- Claude writes tests and browser checks first and builds until they pass; you review once per phase.
- V1 design is "good enough"; polish comes after launch.
- The custom admin portal comes after launch; V1 admin uses wp-admin.

| Week | Dates | Phases | Milestone |
| --- | --- | --- | --- |
| 1 | 26 Nov – 30 Nov | 0, 1 | Foundation today; storefront done locally |
| 2 | 1 – 7 Dec | 2, start 3 | Hostinger bought (sale ends 14 Dec); storefront live; Razorpay KYC applied |
| 3 | 8 – 14 Dec | 3, start 4 | Engine passing all rule tests |
| 4 | 15 – 21 Dec | 4, 5, 6 | Customer can buy, skip and pause on UAT |
| 5 | 22 – 28 Dec | 7, 8, 9 | Kitchen and admin views ready; full test pass |
| 6 | 29 Dec – 4 Jan | 10 | Soft launch, if Razorpay KYC is approved |

Things outside the code can move these dates: Razorpay KYC approval time, how fast the content arrives, and Claude usage limits during long sessions. Without the fast-track assumptions, expect 115–170 hours, or 7–10 weeks at the same pace.

## Architecture

Four portals share one theme, one header and one footer; the URL prefix of a page decides its portal, its menu and who may open it.

&#91;embedded content: System architecture · 4 portals, 1 API, 1 plugin\]

Blocks never touch the database directly: they call the REST API, which runs the plugin's services, which write to the `wp_pz_*` tables. WooCommerce and Razorpay only handle money; a paid order is the trigger that creates a pack.

### Portals, URLs and menus

| Portal | URL prefix | Who can open | Menu (editable in Appearance → Editor → Navigation) |
| --- | --- | --- | --- |
| Storefront | `/` (top-level pages) | Everyone | Storefront menu: Home, Plans, About & Contact, FAQ, Enquire, Sign in |
| Customer | `/account/...` | Customer | Customer menu: Home, Meals, Plans, Dashboard, Support, Sign out |
| Kitchen | `/kitchen/...` | Kitchen in-charge, Admin | Kitchen menu: Dashboard, Allergens & Instructions, Production sheet |
| Admin | `/manage/...` | Admin | Admin menu: Dashboard, Menu & Meals, Customers, Enquiries, Subscriptions, Users & Roles, Settings |

Avoid the slugs `/login` and `/admin`. WordPress core redirects them to `wp-login.php` and `wp-admin`, so use `/sign-in` and `/manage` instead.

### Header and footer

- `parts/header.html` and `parts/footer.html` in the theme are block template parts. Every template (`page.html`, `front-page.html`, portal templates) includes them, so every page carries the same header and footer.
- The header contains: logo, site title, a custom **Portal navigation** block (`pz/portal-nav`), and an account button (Sign in / My account / Sign out).
- `pz/portal-nav` reads the current page's portal and renders the matching WordPress Navigation menu. All four menus stay editable as normal Navigation menus.
- Edits made in the Site Editor are saved in the database. Use the free **Create Block Theme** plugin to save them back into theme files before you commit to Git.

### Roles and access

| Role | WordPress role | Lands on after sign-in | Custom capabilities |
| --- | --- | --- | --- |
| Anonymous | none | — | none |
| Customer | `customer` (from WooCommerce) | `/account/dashboard` | `pz_customer` |
| Kitchen in-charge | `pz_kitchen` (new) | `/kitchen` | `pz_view_kitchen`, `pz_mark_delivery` |
| Admin | `pz_admin` (new) + you as `administrator` | `/manage` | all `pz_*` capabilities: `pz_manage_menu`, `pz_manage_customers`, `pz_manage_subscriptions`, `pz_view_reports`, `pz_manage_users`, `pz_manage_settings` |

- Each page stores its portal in a page setting (`_pz_portal` meta), shown as a dropdown in the page sidebar.
- A plugin access guard runs on every request. A page from a portal the user's role can't open sends them to `/sign-in` (if logged out) or to their own landing page (if logged in).
- Kitchen and `pz_admin` users never need `wp-admin`. Hide the admin bar for them and block `wp-admin` except for `administrator`.
- Every REST endpoint checks a capability and a nonce, not just the page guard.

### Plugin structure (`pazhamei-core`)

One plugin, split into modules so each folder can be edited on its own in any IDE:

```text
pazhamei-core/
  pazhamei-core.php        bootstrap, constants, autoloader
  includes/
    Install/               table creation (dbDelta), page + menu installer, roles
    Domain/                Subscription, Schedule, Calendar (service days), Cutoff rules
    Services/              Activation, SkipService, PauseService, OrderGenerator, MenuService
    Repositories/          one class per table, all SQL lives here
    Rest/                  /wp-json/pz/v1 controllers
    Cron/                  scheduled jobs (Action Scheduler)
    Woo/                   WooCommerce hooks: product meta, checkout fields, order paid
    Access/                portal guard, capabilities
    Emails/                WooCommerce email classes
    Admin/                 wp-admin screens for raw data and settings
  blocks/                  one folder per block (block.json + render.php + view.js)
  tests/                   PHPUnit tests for rules (cutoff, skip, pause, extension)
  docs/DATA-MODEL.md       table reference, kept in sync with Install/
```

The theme (`pazhamei-theme`) holds only design: `theme.json`, templates, template parts and patterns. All logic stays in the plugin, so switching theme later loses nothing.

## WordPress plugin stack

Use ready plugins for payment, email, security and backups; build only the subscription engine and portals yourself.

| Need | Plugin | Why this one / notes |
| --- | --- | --- |
| Shop, cart, checkout, orders, customer accounts | [WooCommerce](https://wordpress.org/plugins/woocommerce/) | Plans are WooCommerce products; orders give payment and revenue history. Turn on HPOS (new order tables). |
| Payment | [Razorpay for WooCommerce](https://wordpress.org/plugins/woo-razorpay/) | UPI, cards, netbanking. Keep it updated: version 4.8.4 fixed a security hole (CVE-2026-3507) ([changelog](https://fastly.jsdelivr.net/wp/woo-razorpay/trunk/readme.txt)). Use its webhook so payment is confirmed even if the customer closes the browser. |
| Email delivery | FluentSMTP (free) + a mail service such as Brevo, Zoho Mail or Amazon SES | WordPress's default mail often lands in spam. |
| Page design | Create Block Theme (official, free) | Saves Site Editor changes to theme files so Git stays the source of truth. |
| Security | Wordfence or Solid Security (free tiers) | Firewall, login attempt limits, 2FA for admin and kitchen accounts. |
| Backups | UpdraftPlus (free) | Daily database, weekly files, stored off-server (Google Drive). |
| SEO (storefront only) | Yoast SEO or Rank Math | Set portal pages to noindex. |
| Activity log | Simple History (free) | Logins, page edits, plugin changes; your own `pz_change_log` covers subscription changes. |
| Caching | Your host's cache or LiteSpeed Cache | Exclude `/account/*`, `/kitchen/*`, `/manage/*`, cart, checkout and REST calls from page cache. |

**What not to install**

- **WooCommerce Subscriptions (paid) and Razorpay Subscriptions.** They charge cards again and again on a cycle. The Razorpay subscriptions add-on [also requires WooCommerce Subscriptions](https://wordpress.org/plugins/razorpay-subscriptions-for-woocommerce/). Your packs are prepaid, so a one-time payment plus your own engine is simpler and avoids a yearly licence.
- **Membership or page-restriction plugins.** The plugin's portal guard and capabilities already handle access, and one system is easier to debug than two.
- **Form builders for the enquiry form.** Enquiries need their own table so the admin portal can filter, assign and convert them. A small custom block does this in Phase 1.
- **Page builders (Elementor and similar).** They fight the block theme and make the header/footer harder to keep consistent.

## Claude Code setup: skills, MCP, environment

Use Claude Code (terminal or desktop app) on a local WordPress copy, with the official WordPress agent skills installed. The WordPress MCP Adapter is useful but secondary: WP-CLI does most of the build work.

### Tools to install on your laptop

| Tool | Purpose |
| --- | --- |
| Claude Code | The agent that writes the theme and plugin |
| LocalWP | Your local WordPress site; its "Open site shell" gives Claude WP-CLI to create pages, menus, users and test data |
| Git + a private GitHub repo | History, rollback, and the source that UAT and production deploy from |
| Node.js 20+ | Needed once, to install the WordPress agent skills |
| VS Code (optional) | To read and edit plugin code yourself |

### Skills to install

Install these from the official [WordPress/agent-skills](https://github.com/WordPress/agent-skills) repo at project level, so they are committed with the code:

| Skill | Used in |
| --- | --- |
| `wp-project-triage` | Every session: detects versions and tooling |
| `wp-block-themes` | Phase 0–1: `theme.json`, templates, header/footer parts, patterns |
| `wp-block-development` | All portal blocks |
| `wp-interactivity-api` | Skip/pause calendar, dashboards that update without reload |
| `wp-plugin-development` | Phase 3: plugin architecture, hooks, security |
| `wp-rest-api` | Phase 3–5: `/pz/v1` endpoints |
| `wp-wpcli-and-ops` | Page installer, seed data, deploy scripts, cron checks |
| `wp-abilities-api`, `wp-ability-development` | Expose safe Pazhamei actions to the MCP Adapter |
| `wp-phpstan` | Phase 9: static analysis |
| `wp-performance` | Phase 9: query and cache tuning |

```bash
git clone https://github.com/WordPress/agent-skills.git
cd agent-skills
node shared/scripts/skillpack-build.mjs --clean
node shared/scripts/skillpack-install.mjs --dest="<LocalWP site folder>/app/public/wp-content" --targets=claude
```

Also add Anthropic's `frontend-design` skill for converting your HTML homepage into theme patterns.

### MCP servers

| MCP server | Use | When |
| --- | --- | --- |
| [WordPress MCP Adapter](https://github.com/WordPress/mcp-adapter) (official) | Lets Claude discover and run WordPress "abilities" on your site | Phase 3 locally; later on production as a read-only reporting user |
| Playwright MCP | Claude clicks through flows in a real browser (sign up, pay in test mode, skip, pause) | Phases 4–9 testing |
| GitHub MCP (optional) | Issues and pull requests per phase | Any time |

The adapter turns abilities registered on the site into MCP tools; it was [introduced on the WordPress developer blog](https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/) in February 2026 and needs WordPress 6.9+. Only abilities marked public are exposed, so its real value comes once `pazhamei-core` registers its own, for example `pz/tomorrow-counts` or `pz/list-enquiries`. Then you can ask Claude "how many veg meals tomorrow?" against the live data. Local STDIO setup:

```json
{
  "mcpServers": {
    "pazhamei-wp": {
      "command": "wp",
      "args": ["--path=/path/to/wordpress", "mcp-adapter", "serve",
               "--server=mcp-adapter-default-server", "--user=admin"]
    }
  }
}
```

On production, never connect it as an administrator. Create a separate user with an application password and only read abilities.

### Repository layout

```text
Local Sites/pazhamei/app/public/wp-content/   <- Git repo root
  CLAUDE.md                 rules every Claude session must follow
  .gitignore                ignores everything except the items below
  .claude/skills/           WordPress agent skills
  themes/pazhamei-theme/
  plugins/pazhamei-core/
  design/homepage.html      your premium HTML homepage (reference)
  docs/                     this plan, data model, test cases
  .github/workflows/        deploy to UAT and production (Phase 2)
```

### What goes in CLAUDE.md

- Business rules (copy the "Business rules" list from Phase 3 below) and the Asia/Kolkata timezone.
- Prefix everything `pz_`; SQL only in Repositories with `$wpdb->prepare`; every REST route checks capability + nonce.
- Never edit WooCommerce or WordPress core files; use hooks.
- Every page Claude creates must be a normal WordPress page built from core blocks + `pz/*` blocks, created through the page installer (never hard-coded HTML templates).
- Every rule change needs a PHPUnit test; run tests before each commit.
- Update `docs/DATA-MODEL.md` whenever a table changes.

### How to run each phase with Claude

1. Start a fresh session, give it this phase's section, and ask for a plan first (plan mode).
2. Approve or correct the plan, then let it build.
3. Ask it to run tests and open the pages in the browser (Playwright MCP) to check.
4. Ask a second agent to review the diff against the business rules before you merge.
5. Commit, tag the phase (`phase-1-done`), and back up the local database.

## Phase 0 — Foundation (new)

Phase 0 gives every later phase a working site, the shared header and footer, the four roles and a page installer, so nothing gets rebuilt later.

- [ ] LocalWP site `pazhamei` (PHP 8.2+, MySQL 8) with WordPress 6.9+, WooCommerce, Razorpay for WooCommerce, FluentSMTP, Create Block Theme; timezone Asia/Kolkata; currency INR; permalinks set to "Post name".
- [ ] Git repo at `wp-content` with the layout above, pushed to a private GitHub repo; `CLAUDE.md` in place; WordPress skills installed. The MCP Adapter can wait until Phase 3.
- [ ] `pazhamei-theme` block theme: colours, fonts and spacing taken from your HTML homepage into `theme.json` (placeholders if the file isn't ready).
- [ ] Template parts `header.html` and `footer.html`; templates `front-page.html`, `page.html`, `page-portal.html` (wider layout for dashboards), `404.html`.
- [ ] `pazhamei-core` skeleton: bootstrap, autoloader, empty modules, activation hook.
- [ ] Roles and capabilities created on activation (`pz_kitchen`, `pz_admin`, `pz_*` capabilities).
- [ ] Portal guard and `pz/portal-nav` block; four Navigation menus created.
- [ ] Page installer: a WP-CLI command `wp pz install-pages` that creates every page in this plan with its slug, parent, portal setting and starting block content, but never overwrites a page you have edited.
- [ ] Seed command `wp pz seed`: three test users (customer, kitchen, pz_admin) now, plus your own administrator account as the fourth role; customers, meals and menus are added in later phases.

**Done when:** you can sign in as each of the three test users and your own administrator account, see the right menu in the same header, and get blocked from the other portals.

## Phase 1 — Storefront (anonymous visitors)

The storefront is six editable pages plus four legal pages, built from core blocks and theme patterns; only the plan cards and the enquiry form are custom blocks.

| Page | Slug | Built from | Notes |
| --- | --- | --- | --- |
| Home | `/` | Patterns made from your HTML homepage: hero, how it works, Veg / Non-Veg / Mixed, plans teaser, testimonials, FAQ teaser, call to action | Each section is a pattern of core blocks, so every heading, image and button is editable. Animations from the HTML are kept only if they work without breaking editing. |
| Sign in / Register | `/sign-in` | WooCommerce account form, restyled | Register fields: name, email, mobile, password, delivery pincode. Includes "forgot password". After sign-in, each role goes to its own landing page. |
| Plans | `/plans` | `pz/plan-cards` block | Shows Trial, 6-day and 26-day cards with what's included, no price. Button: **Enquire** (goes to `/enquire?plan=26-day`). Logged-in customers see the price and **Buy** instead. |
| About & Contact | `/about-contact` | Core blocks + map embed | Story, kitchen address, phone, email, WhatsApp link, delivery areas, hours. |
| FAQ | `/faq` | Core Details blocks (accordion) | Each question is one editable Details block; add FAQ structured data via the SEO plugin. |
| Enquire | `/enquire` | `pz/enquiry-form` block | Fields: name, mobile, email, area/pincode, plan interest, meal type, preferred start date, message. Saved to `wp_pz_enquiries`; admin gets an email; visitor sees a thank-you message. Spam protection: honeypot field, rate limit per IP, nonce. |
| Terms, Privacy, Refund & Cancellation, Delivery policy | `/terms`, `/privacy-policy`, `/refund-policy`, `/delivery-policy` | Core blocks | Needed for Razorpay activation; linked in the footer. |

**Hiding prices properly.** Hiding the price on `/plans` is not enough. Also redirect the WooCommerce shop and product pages to `/plans` for anonymous visitors, and remove the price from product structured data. Razorpay's website review asks for a [test account username and password](https://razorpay.com/docs/payments/dashboard/account-settings/business-website-details.md), so give them a customer login that can see prices.

**Done when:** all pages can be edited in the block editor without breaking layout, the enquiry appears in the database, and the admin email arrives.

## Phase 2 — Deployment setup (new)

Set up hosting, UAT and automatic deployment straight after the storefront, so every later phase is tested on a real server and the storefront goes live early to collect enquiries and pass Razorpay's website review.

- [ ] Buy Hostinger **Unlimited** (the old Business plan), 48-month term, Mumbai data centre. Confirm it lists staging and SSH before paying.
- [ ] Point the domain, turn on SSL, install WordPress and the same plugins as LocalWP.
- [ ] Production `wp-config.php`: environment type `production`; WP-Cron's visit-triggered mode off; a server cron job in hPanel calling WordPress every 5 minutes.
- [ ] Create UAT with Hostinger's staging tool (e.g. `uat.pazhamei.in`), then: password-protect it, hide it from search, set environment type `staging`, use Razorpay test keys, send all emails only to you, and switch off the lock and reminder jobs.
- [ ] GitHub branches `uat` and `main`, plus a GitHub Actions workflow: push to `uat` copies the plugin and theme to UAT over SSH; merge to `main` copies them to production. SSH key and host are stored as GitHub secrets.
- [ ] After each deploy, the plugin's version check upgrades its own tables automatically; run `wp pz install-pages` once on each site.
- [ ] Email: domain mailbox with SPF and DKIM, connected through FluentSMTP.
- [ ] Backups: Hostinger daily backups, plus UpdraftPlus weekly to Google Drive. Restore one backup on UAT to prove it works.
- [ ] Storefront and policy pages live on production; apply for Razorpay KYC now so approval runs while you build.
- [ ] Add to `CLAUDE.md`: after launch, never use Hostinger's "Publish" from staging to live. Only code moves up; data only moves down (production → UAT).

**Done when:** a commit pushed to `uat` appears on UAT within minutes, merging to `main` updates production, and the live storefront saves an enquiry.

## Phase 3 — Subscription engine plugin (your Phase 3)

The engine treats every purchase as a pack of N meals and lays those meals onto a per-customer calendar with one function, `reflow()`. Skip, pause, resume, holidays and renewals only record what changed and then call `reflow()`, so the end date is always recalculated the same way and paid meals are never lost.

### Business rules (copy into CLAUDE.md)

1. **Service days** are Monday to Saturday. Sunday is always off; admin-declared holidays are also off.
2. **A plan is a pack of meals:** Trial = 1, 6-day = 6, 26-day = 26. A pack's meal type (Veg, Non-Veg, Mixed) is fixed at purchase.
3. **Cutoff:** a change for date D is allowed until 20:00 IST on the calendar day before D. After that, D is **locked**. The 20:00 time is a setting.
4. **Start date:** the customer picks it at checkout. It must be a service day that is not yet locked, within 30 days.
5. **One meal per customer per day.** The schedule table has a unique key on (customer, date), so duplicate or overlapping orders cannot exist.
6. **Skip:** any unlocked scheduled day can be skipped, and the skip can be undone until the cutoff. The meal moves to the end of the plan.
7. **Pause:** from an unlocked date, either until a chosen date or until the customer presses Resume. Paused days move to the end of the plan.
8. **Extension:** the end date is always the date of the pack's last scheduled meal, so skips, pauses and holidays extend it automatically.
9. **Renewal:** buying while a pack is active creates a **queued** pack that starts the next service day after the current pack ends, and moves if that end date moves.
10. **Trial:** sold only when the admin switch is on, once per customer (checked by account, mobile number and address).
11. **Daily orders** are created only at the cutoff for the next day, from locked schedule rows. Skipped, paused, cancelled and completed days never become orders.

### How reflow() works

For one customer, from the first unlocked service day `S`:

1. Delete that customer's rows with status `scheduled` or `paused` on or after `S`. Locked, delivered and skipped rows are never touched.
2. For each pack in order (active first, then queued by purchase time), work out remaining meals = pack size − meals already locked or delivered.
3. Walk forward day by day from `S` (or the pack's chosen start, if later). Sunday or holiday → next day. Inside a pause range → write a `paused` row (for the calendar) and go on. Skipped row → go on. Otherwise write a `scheduled` row for this pack and reduce its remaining count.
4. An open-ended pause stops the walk; the pack stays `paused` with its remaining meals saved.
5. Save each pack's new start date, end date and status, and write one change-log entry with old and new end dates.

Everything runs inside a database transaction that locks the customer's rows, so a skip at 19:59 and the 20:00 lock job can't collide.

&#91;embedded content: Pack lifecycle · 6 statuses\]

Skips, holidays and dated pauses never change a pack's status; they only push its end date out through `reflow()`.

### Tables

WordPress prefix shown as `wp_`. Meals use a custom post type so photos and descriptions use the normal media library and editor; everything else is a custom table.

| Table | Holds | Written by | Read by |
| --- | --- | --- | --- |
| `wp_pz_subscriptions` | One row per purchased pack | Activation, reflow, admin | Customer dashboard, admin, reports |
| `wp_pz_schedule` | One row per customer per service day | reflow, skip, lock job | Calendar, kitchen, reports |
| `wp_pz_pauses` | Pause ranges | Pause, resume | reflow |
| `wp_pz_daily_orders` | Frozen kitchen orders for a date | 20:00 lock job | Kitchen portal, delivery, reports |
| `wp_pz_holidays` | Kitchen closed dates | Admin | reflow, calendar |
| `wp_pz_change_log` | Every change with before/after values | All services | Admin history, support |
| `wp_pz_customer_profiles` | Mobile, dietary preference, instructions, zone | Customer, admin | Kitchen, admin |
| `wp_pz_customer_allergens` | Customer ↔ allergen, with severity | Customer, admin | Kitchen alerts |
| `wp_pz_allergens` | Allergen list (peanut, gluten, dairy, egg, shellfish ...) | Admin | Everywhere |
| `wp_pz_ingredients` | Ingredient list | Admin | Menu, kitchen |
| `wp_pz_ingredient_allergens` | Ingredient ↔ allergen map | Admin | Conflict check |
| `wp_pz_meal_ingredients` | Meal ↔ ingredient with grams | Admin | Production quantities |
| `wp_pz_menu_templates` | Weekly fixed menu: weekday × meal type → meals | Admin | Menu generator |
| `wp_pz_menus`, `wp_pz_menu_items` | Menu for a real date × meal type, draft or published | Admin, menu generator | Customer, kitchen |
| `wp_pz_substitutions` | Requested meal swaps and approval | Kitchen, admin | Menu, change log |
| `wp_pz_enquiries` | Storefront enquiries | Enquiry form | Admin |
| `wp_pz_support_tickets`, `wp_pz_support_messages` | Customer support threads | Customer, admin | Both |

Key columns:

```sql
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

Meals (`pz_meal` post type) carry meta for kcal, protein, carbs, fat, fibre, suitable meal types and an "unavailable" flag. Settings (cutoff time, trial on/off, trial price, max pause days, delivery pincodes) live in one `pz_settings` option.

### Scheduled jobs

Use Action Scheduler (bundled with WooCommerce), triggered by a real server cron every 5 minutes rather than by site visits.

| Time (IST) | Job | What it does |
| --- | --- | --- |
| 20:00 daily | Lock tomorrow | Tomorrow's `scheduled` rows → `locked`; create `wp_pz_daily_orders` with address, allergen and menu snapshots. Safe to rerun. |
| 20:05 daily | Kitchen summary | Email tomorrow's counts to kitchen and admin. |
| 23:30 daily | Close today | Today's locked orders not marked failed → `delivered`; add to `consumed_meals`. |
| 00:10 daily | Pack status | Packs with all meals delivered → `completed`; queued pack becomes `active`; dated pauses that ended → resume + reflow. |
| 09:00 daily | Reminders | "2 meals left" renewal reminder; "paused for X days" nudge. |
| Saturday 10:00 | Next week's menu | Copy the weekly template into next week's dated menus as drafts for admin review. |

### Activation after payment

1. Razorpay confirms payment; WooCommerce moves the order to Processing.
2. The plugin hook reads each plan item: plan code and meal count (product settings), meal type (variation), start date (checkout field).
3. It inserts the pack (skipped if `order_item_id` already exists, so a repeated webhook does nothing), runs `reflow()`, logs the change and sends the welcome email.
4. A refunded or cancelled order cancels the pack: unlocked days are removed and `reflow()` runs again.

**Done when:** PHPUnit tests pass for every rule above, including Sunday and holiday skipping, the 19:59 versus 20:01 cutoff, skip-then-undo, pause-then-resume, renewal queued behind a paused pack, and a repeated payment webhook.

## Phase 4 — Customer portal (your Phase 2)

The customer portal is eight editable pages under `/account`, each holding one or two `pz/*` blocks that read live data from the engine.

| Page | Slug | Blocks | What the customer sees and does |
| --- | --- | --- | --- |
| Landing | `/account` | `pz/customer-welcome`, core blocks | Greeting, current pack in one line, tomorrow's meal and whether it is locked, shortcuts. New customers see "Start with a trial" or "Choose a plan". |
| Meals & types | `/account/meals` | `pz/menu-week` | This week's fixed menu for Veg, Non-Veg and Mixed side by side, with photos, nutrition and allergens. Their own type is highlighted. |
| Plans & buy | `/account/plans` | `pz/plan-cards` (price mode) | Trial (if enabled and eligible), 6-day and 26-day cards with price per meal type. **Buy** adds the plan to the cart and goes straight to checkout. |
| Checkout | `/checkout` | WooCommerce checkout | Extra fields: start date (date picker allows only valid days), delivery address and pincode check, allergen confirmation, accept terms. One plan per order. Pays through Razorpay. |
| Dashboard | `/account/dashboard` | `pz/subscription-summary`, `pz/schedule-calendar` | Pack, meal type, meals left, start and end date, status. Calendar of upcoming days with **Skip / Undo skip** per day and **Pause / Resume** for the pack. Locked days show a lock icon and the reason. |
| Profile | `/account/profile` | `pz/profile-form` | Name, mobile, delivery address, dietary preference, allergens with severity, special instructions, password. Address and instruction changes apply from the next unlocked day. |
| Support | `/account/support` | `pz/support-tickets` | Raise a ticket (topic, message, optional date), see replies and status. |
| History | `/account/history` | `pz/order-history` | Past packs, delivered days, payments and invoices (links to WooCommerce order pages). |

Notes:

- Blocks use the Interactivity API, so Skip or Pause updates the calendar without a page reload, and the end date changes on screen immediately.
- The cutoff is shown as a live countdown on the dashboard: "Changes for tomorrow close at 8:00 PM".
- The calendar shows Sundays and holidays greyed out with a label, so customers understand why the end date moved.
- Test the Razorpay plugin with WooCommerce's block checkout; if anything misbehaves, use the classic checkout instead.

**Done when:** a test customer can buy a 6-day Veg pack in Razorpay test mode, see six correct dates, skip one, pause and resume, and watch the end date move each time.

## Phase 5 — Buttons, actions and manual flows (your Phase 4)

Every button calls one REST endpoint, and every endpoint checks role, nonce and the business rules on the server before it changes anything; the browser never decides on its own.

### Button-to-action map

| Button | Page | Role | Endpoint | Server checks | Result |
| --- | --- | --- | --- | --- | --- |
| Send enquiry | `/enquire` | Anyone | `POST /pz/v1/enquiries` | Required fields, honeypot, rate limit | Row in `wp_pz_enquiries`, admin email |
| Buy | `/account/plans` | Customer | WooCommerce add-to-cart → checkout | Trial eligibility, one plan per cart | Cart → Razorpay payment |
| Place order | `/checkout` | Customer | WooCommerce + Razorpay | Start date valid, pincode served | Order paid → pack activated |
| Skip | Dashboard calendar | Customer | `POST /pz/v1/schedule/{date}/skip` | Day is scheduled, not locked, belongs to user | Row `skipped`, reflow, end date +1 service day |
| Undo skip | Dashboard calendar | Customer | `DELETE /pz/v1/schedule/{date}/skip` | Not locked | Reflow, end date back |
| Pause | Dashboard | Customer | `POST /pz/v1/subscriptions/{id}/pause` | Start date unlocked, end date optional, max pause days | Pause row, pack `paused`, reflow |
| Resume | Dashboard | Customer | `POST /pz/v1/subscriptions/{id}/resume` | Resume date unlocked | Pause ended, reflow, new end date |
| Save profile | `/account/profile` | Customer | `PUT /pz/v1/me/profile` | Valid pincode, allergen list | Profile saved; applies from next unlocked day |
| Raise ticket | `/account/support` | Customer | `POST /pz/v1/support/tickets` | Required fields | Ticket, admin email |
| Mark packed / out for delivery / delivered / failed | Kitchen dashboard | Kitchen | `POST /pz/v1/kitchen/orders/{id}/status` | Order is today's | Status updated, logged |
| Request substitution | Kitchen allergens page | Kitchen | `POST /pz/v1/substitutions` | Menu exists | Pending request for admin |
| Approve / reject substitution | Admin menu page | Admin | `POST /pz/v1/substitutions/{id}/decision` | Before 20:00 lock for that date (else warns) | Menu updated, change log |
| Publish / unpublish menu | Admin menu page | Admin | `POST /pz/v1/menus/{id}/publish` | All 3 meal types have meals | Customers see it |
| Convert enquiry | Admin enquiries | Admin | `POST /pz/v1/enquiries/{id}/convert` | — | Creates customer account + sends set-password email |

### Manual admin flows (needed for real operations)

| Flow | Why you need it | How it works |
| --- | --- | --- |
| Grant a trial | Admin-controlled trial for a lead or a complaint | Admin picks customer + date → ₹0 order → normal activation |
| Record an offline payment | Customer paid by UPI to your number or in cash | Admin creates a WooCommerce order, marks it paid → activation runs as usual |
| Declare a holiday | Festival, kitchen closure, rain | Add date in Settings → every affected customer's day becomes `holiday_cancelled` → reflow → email to affected customers |
| Skip or pause on behalf of a customer | Phone or WhatsApp requests | Same endpoints as the customer, logged with the admin's name; admin may override the cutoff with a reason |
| Credit back a failed delivery | Kitchen or delivery fault | Mark the day failed → "Credit meal" adds one meal to the pack → reflow |
| Change meal type | Customer changes diet | Admin only: changes the pack's type for unlocked days; locked days stay |
| Cancel and refund | Customer leaves | Refund in WooCommerce (Razorpay refunds through the plugin) → pack cancelled, unlocked days removed. Refund amount for meals already eaten follows your refund policy. |
| Re-run tonight's lock | Server was down at 20:00 | "Run lock now" button in Settings; the job is safe to rerun |
| Export | Accounts or delivery partner | CSV export of daily orders, customers, enquiries |

## Phase 6 — Notifications (new)

Each event in the engine sends one email through WooCommerce's email system, so you can edit subjects and text in WooCommerce → Settings → Emails.

| Event | To | When |
| --- | --- | --- |
| Enquiry received | Admin (+ thank-you to visitor) | Instantly |
| Pack activated (with dates) | Customer | After payment |
| Skip / undo skip / pause / resume confirmed, with new end date | Customer | Instantly |
| Tomorrow's meal and lock reminder | Customer | 18:00, two hours before the cutoff (optional per customer) |
| 2 meals left — renew | Customer | 09:00 |
| Pack completed | Customer | 00:10 job |
| Holiday declared, new end date | Affected customers | When admin saves |
| Ticket reply | Customer / admin | Instantly |
| Tomorrow's kitchen summary | Kitchen + admin | 20:05 |

WhatsApp messages come later through a WhatsApp Business API provider; the same events feed them, so no engine change is needed.

## Phase 7 — Kitchen portal (your Phase 5)

The kitchen sees only frozen daily orders after the 20:00 lock (plus a live preview of tomorrow before it), so skipped, paused, completed and not-yet-started packs are excluded by design, not by filters.

### Page 1 — Dashboard (`/kitchen`)

| Figure | Source and rule |
| --- | --- |
| Today's confirmed orders | `wp_pz_daily_orders` for today |
| Tomorrow's confirmed orders | Before 20:00: preview count from `scheduled` rows (marked "provisional"); after 20:00: locked daily orders |
| Veg / Non-Veg / Mixed totals | Same rows grouped by meal type |
| Meal-wise production quantities | Order count per meal type × that day's published menu items; ingredient totals in kg from `wp_pz_meal_ingredients` |
| Skipped, paused excluded | Those days never become daily orders; shown as small "excluded" counts for reference |
| Expired / completed excluded | Packs with no meals left have no rows |
| Renewals included only when eligible | A queued pack has rows only from its own start date |
| Allergen alerts | Count of tomorrow's orders with a conflict (link to Page 2) |
| Order status board | Today's orders by status with Packed / Out for delivery / Delivered / Failed buttons |

### Page 2 — Allergens & special instructions (`/kitchen/allergens`)

- **Customer alerts:** every order whose customer has allergens, with name, meal type and severity.
- **Conflicts:** the customer's allergens matched against that day's menu ingredients for their meal type (through `wp_pz_ingredient_allergens`). A match shows in red with the ingredient that causes it.
- **Special preparation instructions:** free-text instructions from the profile, frozen at the lock.
- **Cross-contact indicator:** severe allergies are flagged "prepare and pack separately".
- **Request substitution** button for a conflict, which goes to the admin for approval.

### Added pages

- **Production sheet** (`/kitchen/production`): printable A4 for the day: meals, portions, ingredient totals.
- **Packing labels and delivery list** (`/kitchen/dispatch`): printable labels (name, area, meal type, allergen flag) and a list grouped by area for the delivery person.

**Done when:** with seeded data, tomorrow's counts match a hand count of the schedule after skips, pauses and a renewal, and an allergen conflict is flagged correctly.

## Phase 8 — Admin portal (your Phase 6)

The admin portal lives at `/manage` as normal pages with `pz/*` blocks, so day-to-day staff never need `wp-admin`; you keep `wp-admin` as the full-power backup.

### Page 1 — Dashboard (`/manage`)

Agree these definitions before Claude builds the queries:

| Figure | Definition |
| --- | --- |
| Total customers | Accounts with the customer role |
| Active customers | Customers with a pack that is active or paused |
| New customers | Registered in the selected period (default: last 7 days) |
| Total / active / paused / completed subscriptions | Packs by status |
| Today's and tomorrow's orders | Same numbers as the kitchen dashboard |
| Veg / Non-Veg / Mixed demand | Tomorrow plus the next 7 days from `wp_pz_schedule` |
| Skipped days | Skips in the period, and the % of scheduled days skipped |
| Renewals | Packs with `renewed_from_id` set, in the period |
| Revenue: daily, weekly, monthly | Paid WooCommerce orders minus refunds, by payment date |

Add a date range filter and a CSV download on every card.

### Page 2 — Menu & meal management (`/manage/menu`, `/manage/meals`)

- Meals: add and edit with photo (WordPress media library), description, nutrition values, suitable meal types, and an "unavailable" switch.
- Ingredients and allergens: lists you manage, plus the ingredient → allergen map and meal → ingredient grams.
- Weekly fixed menu: a Monday–Saturday × Veg / Non-Veg / Mixed grid of meals.
- Dated menus: generated from the weekly grid as drafts, edited per date if needed, then published. Unpublished dates show "Menu coming soon" to customers.
- Unavailable meals: switching a meal off lists every future date that uses it and asks for a substitute.
- Substitutions: queue of kitchen requests with approve/reject.
- Menu change history: every edit and publish from `wp_pz_change_log`.

### Page 3 — Customer management (`/manage/customers`, `/manage/enquiries`)

- Search by name, mobile, email, area or status.
- Customer page with tabs: contact details, delivery addresses, dietary preference and allergens, packs (active and past, with calendar), daily order history, payments (WooCommerce orders), support tickets, change history.
- Actions: the manual flows from Phase 5, plus activate/deactivate account.
- Enquiries: list with status (new, contacted, converted, closed), assignee, notes and **Convert to customer**.

### Page 4 — Users & roles (`/manage/users`)

- List internal users (admins and kitchen in-charge), create, edit, deactivate. A deactivated user can't sign in, but their history stays.
- Role permissions shown as a read-only matrix (role × capability). Changing permissions stays a code change, which is safer.
- Secure access: 2FA required for admin and kitchen, strong passwords, auto sign-out after inactivity.
- User activity log: sign-ins and every logged change, filterable by user.

### Added pages

- **Subscriptions** (`/manage/subscriptions`): all packs with filters, and the overrides from Phase 5.
- **Settings** (`/manage/settings`): cutoff time, trial switch and price, max pause days, delivery pincodes, holidays, "Run lock now".

**Done when:** every dashboard number matches a direct database count on seeded data, and a kitchen user gets blocked from every `/manage` page and endpoint.

## Phase 9 — QA, security and performance (new)

Test the rules with code, the flows in a browser, and the money end to end in Razorpay test mode before any real customer pays.

- [ ] PHPUnit tests for the engine (Phase 3 list) running on every commit.
- [ ] Playwright scripts for the main journeys: enquiry, register, buy, skip, undo, pause, resume, renewal, kitchen status, admin holiday.
- [ ] "Time travel" test setting that fakes today's date and time on the local site, so the 20:00 cutoff and the overnight jobs can be tested in minutes.
- [ ] Role test: each role tries every page and endpoint of the other portals and gets refused.
- [ ] Security: PHPStan, nonce and capability on every endpoint, prepared SQL, escaped output, 2FA, rate limits on sign-in and enquiry.
- [ ] Performance: indexes on date and status columns, portal pages excluded from cache, dashboard queries under 300 ms with 1,000 seeded customers.
- [ ] Accessibility and mobile: every portal page usable on a phone, since most customers will skip or pause from their phone.

## Phase 10 — Go-live (new)

- [ ] Razorpay KYC approved; live keys on production; webhook set; one real ₹1 payment and refund.
- [ ] Real menu, prices and photos loaded on production; test data removed.
- [ ] Scheduled jobs and customer emails switched on in production.
- [ ] Uptime monitor and an alert if the 20:00 lock job didn't run.
- [ ] Soft launch: 10–20 friendly customers for two weeks before opening to all.
- [ ] First week: compare the kitchen counts with what was actually cooked and delivered, every night.

## Later, and migration readiness

- WhatsApp notifications, delivery partner app or tracking, wallet credits, referrals, corporate group billing.
- One-off meals and packed foods: these are plain WooCommerce products and don't touch the engine.
- More meal slots (breakfast, dinner): add a `slot` column to schedule and daily orders now, defaulting to `lunch`, so this later needs no migration.
- Mobile app or custom front end: reuse the `/pz/v1` REST API as is.
- Moving off WordPress: all business data sits in the `wp_pz_*` tables plus WooCommerce orders, documented in `docs/DATA-MODEL.md`, so it can be exported to any database.

## Inputs needed and open decisions

Claude can start Phase 0 today; the items below are needed by the phase shown, and each decision has a default the plan uses unless you change it.

### Inputs from you

- [ ] Your premium HTML homepage file, logo, brand colours and fonts (Phase 0–1)
- [ ] Food and kitchen photos (Phase 1)
- [ ] About text, contact details, kitchen address, FAQ questions and answers (Phase 1)
- [ ] Terms, privacy, refund and delivery policy texts, or approval to draft them (Phase 1)
- [ ] Prices for each plan × meal type, and the trial price (Phase 4)
- [ ] Delivery pincodes or areas (Phase 4)
- [ ] Meals, ingredients, allergens and the first weekly menu (Phase 7–8)
- [ ] Business documents for Razorpay KYC, and an email domain (Phase 2)

### Decisions

| # | Question | Default in this plan |
| --- | --- | --- |
| 1 | Monday's cutoff: Sunday 8 PM or Saturday 8 PM (if the kitchen buys on Sunday)? | Sunday 8 PM (the rule as written) |
| 2 | Longest allowed pause, and what happens after it? | 30 days, then auto-resume with an email |
| 3 | Limit on skips per pack? | No limit |
| 4 | Trial: free or paid, and does admin approve each one? | Admin switch on/off, set price (can be ₹0), no approval step |
| 5 | Can one account get two meals a day (e.g. family)? | No, one meal per customer per day |
| 6 | What does "Mixed" mean: its own menu, or alternating Veg / Non-Veg days? | Its own menu per day |
| 7 | Can a renewal use a different meal type? | Yes |
| 8 | Refund for unused meals when a customer cancels? | Per-meal price × meals left, minus a fee you set |
| 9 | Failed delivery: automatic meal credit, or admin decides? | Admin decides |
| 10 | If the kitchen doesn't mark a delivery, is it counted as delivered at 23:30? | Yes |
| 11 | Revenue: cash collected, or value of meals delivered? | Cash collected, plus a second "earned" figure |
| 12 | One header with four menus, or a different header design per portal? | One header, four menus |

## Sources

- [WordPress agent skills (official)](https://github.com/WordPress/agent-skills) · [skill list and install commands](https://raw.githubusercontent.com/wordpress/agent-skills/master/README.md)
- [WordPress MCP Adapter, GitHub](https://github.com/WordPress/mcp-adapter) · [introduction on the WordPress developer blog](https://developer.wordpress.org/news/2026/02/from-abilities-to-ai-agents-introducing-the-wordpress-mcp-adapter/)
- [Razorpay for WooCommerce](https://wordpress.org/plugins/woo-razorpay/) · [plugin changelog](https://fastly.jsdelivr.net/wp/woo-razorpay/trunk/readme.txt)
- [Razorpay Subscriptions for WooCommerce](https://wordpress.org/plugins/razorpay-subscriptions-for-woocommerce/)
- [Razorpay business website details](https://razorpay.com/docs/payments/dashboard/account-settings/business-website-details.md)
- [Hostinger India web hosting plans](https://www.hostinger.com/in/web-hosting) · [Hostinger WordPress staging](https://support.hostinger.com/en/articles/5720286-how-to-create-a-wordpress-staging-environment) · [Hostinger SSH access](https://www.hostinger.com/support/1583245-how-to-connect-to-a-hosting-plan-via-ssh/)
