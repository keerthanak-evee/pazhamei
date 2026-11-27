# Pazhamei build phases

Full detail for each phase: `docs/build-plan.md`.

## Phase 0: Foundation

- [x] `CLAUDE.md`, `.gitignore`, WordPress agent skills
- [x] Build plan in `docs/build-plan.md`
- [x] Theme `pazhamei-theme`: `theme.json` with brand colours, self-hosted fonts and spacing from the storefront design
- [x] Template parts `header.html` and `footer.html`
- [x] Templates `front-page.html`, `page.html`, `page-portal.html`, `404.html` (plus `index.html` fallback)
- [x] Plugin `pazhamei-core` skeleton: main file, version constant, autoloader, activation/deactivation/uninstall, module folders
- [x] `pz_settings` option with defaults
- [x] Roles `pz_kitchen`, `pz_admin`; `pz_customer` on `customer`; all `pz_*` on `administrator`
- [x] `_pz_portal` page setting with a sidebar dropdown
- [x] Portal guard, sign-in redirects, wp-admin and admin bar lockdown
- [x] Block `pz/portal-nav`
- [x] `wp pz install-pages`: pages (owned by the first administrator), four Navigation menus, front page, WooCommerce My Account → `/sign-in`
- [x] `wp pz seed`: `test_customer`, `test_kitchen`, `test_admin`
- [x] PHPUnit tests for the portal rules (`composer test` in the plugin folder)
- [x] `docs/DATA-MODEL.md` (tables planned)
- [x] WooCommerce: My Account = `/sign-in` (old `/my-account` trashed and redirected), shop/product → `/plans`, cart → `/checkout`, "Go to my dashboard" button on the order-received page, HPOS compatibility declared
- [x] Done-when checks passed over HTTP for a visitor and all three test users
- [x] Site Editor checked by eye (header, footer, four menus and the Portal dropdown open without block errors)
- [x] Committed as `Phase 0: foundation`, tagged `phase-0-done`

Decisions:
- [x] Test users: `wp pz seed` makes three (customer, kitchen, pz_admin). The fourth role, administrator, is the owner's own `WpAdmin` account; no test administrator is seeded.
- The `phase-0-done` tag stays on the "Phase 0: foundation" commit; later fixes come after it.
- Developer tools (`wp pz seed`, `PZ_FAKE_NOW`) run only on `local`; on staging and production they refuse or are ignored.
- WooCommerce store visibility stays "Coming soon" until Phase 10.

Later:
- [ ] Customers, meals and menus in `wp pz seed` (added in later phases, once the tables exist)

## Phase 1: Storefront

- [x] Home built from 11 theme patterns (`themes/pazhamei-theme/patterns/home-*.php`), all core blocks: hero, heritage strip, our vision, Veg / Non-Veg / Mixed, plans teaser, what's cooking, balance in every bite, how the plans work, testimonials, FAQ teaser, call to action
- [x] Storefront photos compressed (WebP, max 1600 px) and imported to the Media Library by `wp pz install-pages`, tagged with `_pz_media_key`; decorative textures, paper, frame and border in the theme's `assets/images/`
- [x] Prototype look rebuilt with `theme.json` colours and block styles only (Paper card, Banana leaf, Framed paper sheet, Heritage border, Leaf corner, Round seal, Cream / Outline buttons, Testimonial, Journey steps). Tailwind and the prototype stylesheets are not loaded anywhere
- [x] Motion: `assets/css/motion.css` + `assets/js/motion.js` (IntersectionObserver) with classes `pz-reveal`, `pz-reveal-stagger`, `pz-hover-lift`, `pz-hover-zoom`, `pz-arrow`, `pz-hero`; all off with prefers-reduced-motion
- [x] Block `pz/plan-cards` on `/plans`: Trial, 6-day, 26-day, what's included, Enquire → `/enquire?plan=<plan>`, no prices (design update: now `/about-us/?plan=<plan>#contact`)
- [x] `/about-contact`, `/faq` (10 Details blocks), `/enquire` (design update: About & Contact became `/about-us`; the enquiry form moved there and `/enquire` redirects), and four policy drafts (`/terms`, `/privacy-policy`, `/refund-policy`, `/delivery-policy`), each policy marked "DRAFT – review before launch"
- [x] WordPress's own privacy page (Settings > Privacy) reused as `/privacy-policy`: storefront portal, published, no duplicate
- [x] Block `pz/enquiry-form` (Interactivity API): fresh nonce from `GET /pz/v1/enquiries/nonce`, `POST /pz/v1/enquiries`, honeypot `company_url`, 5 per 15 minutes per salted IP hash, admin email, thank-you message, `?plan=` pre-select
- [x] Table `wp_pz_enquiries` (dbDelta), plugin 0.1.0 → 0.2.0, version check on `plugins_loaded`; documented in `docs/DATA-MODEL.md`
- [x] Visitors never see prices: WooCommerce price HTML and product structured data `offers` removed for visitors (`Woo/HidePrices`)
- [x] Page templates: title and content share one constrained layout, so they line up (`page.html`, `page-portal.html`)
- [x] PHPUnit: enquiry validation, honeypot, rate limit, IP hashing, plan catalogue (92 tests)
- [x] Checked in headless Chrome: every page opens in the editor with 0 invalid blocks; motion and reduced motion; plan pre-select; form submit; 375 px with no horizontal scroll

- [x] Follow-up: wp-admin screen Pazhamei → Enquiries (administrators only): newest first, filter by status, change status (new, contacted, converted, closed); otherwise read-only. Test for the permission rule (`EnquiryStatus::can_manage`)

- [x] Follow-up: Home matched to the prototype (`design/homepage.html`) and reference image: carved pillar and kolam on the hero, wave and lace on the trust strip with illustrated icons, temple sketch, leaf dividers, kolam corners on the header and the cooking band, oxblood meal-plans band with flourished heading, gold chips, tick lists and oxblood buttons, icons on the vision pillars and the five plate parts, diamonds on the journey line, oxblood footer with a diamond border, serif menu on a cream header, tighter section spacing, phone layouts for the strip and the journey. Ornaments live in `themes/pazhamei-theme/assets/css/ornaments.css` (CSS classes, so pages stay editable). Before/after screenshots in `design/compare/`.
- [x] Installer: storefront pages remember a fingerprint of the starter content they were given (`_pz_starter_hash`), so `wp pz install-pages` can refresh a page that nobody has edited when its patterns change. Edited pages are never touched.

[REPLACE] ornaments cropped from `design/Images/ProtypeReferenceImage.jpg` (720 x 1280, enlarged 4x). Send higher-quality originals (transparent PNG or SVG) and drop them in with the same names in `themes/pazhamei-theme/assets/images/ornaments/`:
- [ ] [REPLACE] `hero-pillar.webp`: carved wooden pillar (hero, left edge)
- [ ] [REPLACE] `icon-temple.webp`, `icon-pot.webp`, `icon-scooter.webp`: trust-strip icons
- [ ] [REPLACE] `temple-sketch.webp`: sepia temple tower, palms and village house ("More than a meal")
- [ ] [REPLACE] `icon-roots.webp`, `icon-ingredients.webp`, `icon-philosophy.webp`: vision pillar icons
- [ ] [REPLACE] `icon-grains.webp`, `icon-protein.webp`, `icon-vegetables.webp`, `icon-gravy.webp`, `icon-finish.webp`: plate-part icons
- Redrawn as SVG (sharp at any size; replace only if you want the exact artwork): `kolam-motif.svg`, `kolam-corner.svg`, `kolam-corner-light.svg`, `kolam-lace.svg`, `leaf-divider.svg`, `hero-wave.svg`, `flourish.svg`

Decisions and assumptions (owner approved the plan; low-intervention mode):
- `design/Images/` committed as reference only; menu PDF and Tailwind removed by the owner.
- Never load Tailwind, `heritage-storefront.css` or `pazhamei.css`; the look comes from `theme.json`, block styles and the motion files.
- No prices anywhere for visitors, including the Home teaser and text taken from the prototype or menu.
- The four extra prototype sections (heritage strip, our vision, what's cooking, balance) are kept as patterns alongside the seven requested ones.
- The enquiry REST route has no capability check, because visitors have none. Nonce + honeypot + rate limit protect it. This is the one exception to "every route checks a capability".
- Rate limiting counts every submission that passes the nonce check (valid, invalid or honeypot). Refused attempts aren't counted.
- Enquiry email is optional; name, mobile, pincode and plan are required. Preferred start: after today, within 60 days, not a Sunday.
- The Trial card shows on `/plans` even while `trial_enabled` is off, because it only leads to an enquiry. The Buy button (Phase 4) will respect the switch.
- Testimonials are placeholders marked [EDIT]; real quotes need the customer's permission.
- No map embed on About & Contact (no third-party embeds); a Google Maps link is an [EDIT] item.
- Photos live in the Media Library, imported from `design/Images` on local. `design/` is never deployed, so staging and production get the photos with the content migration (Phase 2 / 10).
- FluentSMTP was not set up yet. On local only, a "PHP mail()" connection with email logging was added so enquiry emails show in its log (LocalWP's Mailpit catches them). Real SMTP is set up in Phase 2.

Later:
- [ ] Replace every [EDIT] and review every DRAFT before Phase 10
- [ ] FAQ structured data via the SEO plugin (build plan), when the SEO plugin is chosen
- [ ] Trusted proxy header in `Services/ClientIp` once Hostinger's setup is known (Phase 2)
- Sign-in / Register restyle moved to Phase 4 (owner decision).

## Phase 2: Deployment setup
## Phase 3: Subscription engine

Phase 2 (deployment) is on hold; Phase 3 is built and tested locally only.

Decisions (owner, Phase 3 kickoff):
1. Monday's cutoff is Sunday 20:00, the same rule as every day: changes for date D close at 20:00 on D-1 (rule 3).
2. Pause has no time limit. Dated pauses end on their date; open-ended pauses last until the customer (or admin) resumes. No auto-resume. A hook (`pz_pause_ongoing`) is left for a Phase 6 "you've been paused a while" reminder (rule 7). `max_pause_days` removed from settings.
3. No limit on skips per pack (rule 6).
4. Trial: admin switch on/off and an admin-set price (can be 0); no approval step. Eligibility uses the normalised mobile and address rule (rule 10).
5. **Open:** one meal per account per day for now. `UNIQUE (customer, date)` stays; `quantity` column (default 1, not exposed) on subscriptions and daily orders so allowing more later is cheap (rule 5).
6. Refunds are handled offline by the owner. Cancelling a pack (admin action, or a WooCommerce refund/cancellation) only removes unlocked days, sets the status and logs it. No refund calculation (rule 13).
7. A renewal may choose a different meal type (rule 9).
8. **Open:** failed delivery. A `failed` status plus an admin "Credit meal" action (adds a meal and reflows). No automatic crediting (rule 14).
9. **Open:** auto-marking delivered. Setting `auto_mark_delivered`, default ON: locked days not marked failed by 23:30 count as delivered. When OFF they stay locked until someone marks them (rule 15).
10. Renewal during an open-ended pause waits (`queued`, no dates) until the customer resumes (rule 9). Phase 4 checkout warns: "Your plan is paused. Resume it to start your new pack."
11. (Kickoff additions) The in-memory test stores enforce the same constraints as MySQL and fail the same way; every new table is InnoDB, checked in the CLI walkthrough.

Built (plugin 0.3.0):
- [x] 17 new tables (dbDelta, all InnoDB; installer converts any that aren't): subscriptions, schedule (`UNIQUE customer+date`), pauses, daily_orders (`UNIQUE schedule_id`), holidays, change_log, customer_profiles, trial_claims, and the 9 meal/menu/allergen tables. `quantity` (default 1) on subscriptions and daily_orders. Documented in `docs/DATA-MODEL.md`
- [x] Domain rules (plain PHP): `Calendar`, `Cutoff`, `StartDate`, `TrialIdentity`, `Reflow`
- [x] `reflow()` for one customer across all packs (current first, then queued), inside a transaction with the customer's profile row and schedule rows locked (`SELECT … FOR UPDATE`)
- [x] Services: activation (idempotent on order_item_id), skip/undo, pause (dated or open-ended)/resume, holiday add/remove, cancel, credit meal, change meal type (admin), delivered/failed, trial eligibility, checkout rules
- [x] WooCommerce: three variable plan products (Veg/Non-Veg/Mixed), `publish` + catalogue visibility `hidden`; start-date field for block and classic checkout with the plan rules before payment; activate on payment complete/processing/completed; cancel on cancelled/refunded. HPOS-safe
- [x] Action Scheduler jobs: 20:00 lock (safe to rerun), 23:30 close day (respects `auto_mark_delivered`), 00:10 pack status. First run computed in IST, stored as UTC, daily
- [x] REST (`/pz/v1`): `me/subscription`, `me/schedule`, `schedule/{date}/skip` (POST/DELETE), `me/pause`, `me/resume`; `admin/holidays`, `admin/subscriptions/{id}/credit|cancel|meal-type`. Capability + `wp_rest` nonce on every route
- [x] Settings: `trial_enabled`, `trial_price`, `cutoff_time`, `auto_mark_delivered` (`max_pause_days` removed)
- [x] WP-CLI: `schedule`, `run-lock`, `run-close`, `run-status`, `tables`, `jobs`, `install-products`; local only: `time` (option `pz_fake_now`, `PZ_FAKE_NOW` wins), `simulate-order`, `skip`, `undo-skip`, `pause`, `resume`, `holiday`, `credit`, `cancel`, `meal-type`, `mark-failed`
- [x] PHPUnit: 206 tests (110 new), all passing. In-memory stores (`tests/Fakes`) enforce the same UNIQUE and NOT NULL constraints with MySQL's error message, and roll back on failure
- [x] CLI walkthrough on the local database (6-day Veg from Mon 30 Nov → skip Wed → pause Fri–Tue → renewal → open-ended pause + resume → 20:00 lock with fake time, rerun, late skip refused); all 18 `wp_pz_*` tables InnoDB
- [x] Live checks: REST permissions (no nonce / wrong role → 403, after cutoff → 422), block checkout field registered and rules refuse guest / no date / Sunday / trial while off, replayed payment hook makes no second pack, full refund cancels the pack

Assumptions (low-intervention mode; change any of them by telling me):
- A dated pause's end date is the **last paused day** (included): "Fri to Tue" pauses Fri, Sat, Mon and Tue; meals restart Wed.
- A pause covers all of the customer's deliveries (it is recorded against the current pack). So the REST routes are `/me/pause` and `/me/resume`, not per pack; the build plan's Phase 5 table now says so.
- Resume restarts meals on the first unlocked day; locked days stay as they are. Resuming a pause that hasn't begun withdraws it (`cancelled`). Overlapping pauses are refused. Skipped days inside a later pause stay skipped.
- A first pack is `active` as soon as it's paid, even if it starts later. A queued pack becomes `active` when the customer has no current pack (00:10 job, or straight away when the current pack is cancelled).
- A renewal starts on the next free service day after the current pack's last meal, or on its own chosen start date if that is later.
- If payment arrives after the chosen start date has locked, the pack starts on the first unlocked service day.
- A failed day still uses its meal (`consumed_meals` = delivered + failed). "Credit meal" adds a meal and reopens a completed pack.
- Holiday on a day that is already locked: the day becomes `holiday_cancelled`, its daily order `cancelled`, and the meal goes back to the pack. Holidays can be added for today or later, and removed only while the date is unlocked.
- Cancel removes the pack's scheduled, paused and skipped days on unlocked dates; locked days stay and are delivered. Pauses made for that pack end with it, so a queued renewal can start. Cancelled order → `cancelled`; full refund → `refunded`; partial refunds change nothing.
- Trial: one per account (any earlier trial pack, even cancelled). Mobile = last 10 digits. Address = pincode + the first word with a digit in address line 1, after removing punctuation and the filler words no, nos, num, number, flat, door, house, h, apt, apartment. Each trial sold is recorded in a new table, `wp_pz_trial_claims`, so changing a phone number later can't unlock a second trial.
- The lock job locks every `scheduled` day from today up to the last locked date (normally just tomorrow), so a late run catches up. Close day runs through today after 23:30, otherwise through yesterday.
- Plan products are virtual and sold individually; the delivery address is the billing address unless a shipping address is given.
- Block checkout start date is a text field (YYYY-MM-DD) for now; Phase 4 adds the date picker. A classic-checkout fallback is included.
- Statuses are `varchar`, not `ENUM`, so new values need no table rebuild. The change log stores the client IP for web requests.
- Customer lock = `SELECT … FOR UPDATE` on the customer's profile row (created on first change).
- `run-lock`, `run-close`, `run-status`, `tables`, `jobs` and `install-products` work on every environment (safe to rerun); the other helpers are local only.
- Holidays need `pz_manage_settings`; pack actions need `pz_manage_subscriptions`. Staff can call the customer routes with `customer_id` to act for a customer.

Open decisions:
- [ ] (5) Meals per account per day: one for now; `quantity` column ready.
- [ ] (8) Failed delivery: `failed` status + admin "Credit meal"; no automatic credit.
- [ ] (9) `auto_mark_delivered`: default on.
- [ ] Should a cancelled or refunded trial still count as "had a trial"? (Now: yes.)

[EDIT] before selling (Phase 4 / go-live):
- [ ] [EDIT] Plan prices (placeholders on the products): 6-day Veg ₹900, Non-Veg ₹1,080, Mixed ₹990; 26-day Veg ₹3,640, Non-Veg ₹4,420, Mixed ₹4,030. Product short descriptions start with "[EDIT] Placeholder prices."
- [ ] [EDIT] `trial_price` (now 0) and `trial_enabled` (now off) in `pz_settings`.

How to test:
- `composer test` in `plugins/pazhamei-core` (206 tests).
- `wp pz tables` (all InnoDB), `wp pz jobs`, `wp pz schedule demo_customer`.
- Walkthrough: `wp pz time 2026-11-27 16:30`, `wp pz simulate-order <user> 6-day veg 2026-11-30`, `wp pz skip <user> 2026-12-02`, `wp pz pause <user> 2026-12-04 2026-12-08`, `wp pz simulate-order <user> 6-day nonveg 2026-11-30`, `wp pz pause <user> 2026-12-10`, `wp pz resume <user>`, `wp pz time 2026-11-29 20:01`, `wp pz run-lock` (twice), `wp pz time --clear`. In Git Bash, pass date and time as two arguments (the `wp` launcher breaks quoted arguments that contain spaces).

Local test data: `demo_customer` (#5, packs #1 and #2, Monday 30 Nov locked with daily order #1) and `demo_refund` (#6, pack #3 refunded), test orders #130 to #132. Local only.

Later:
- [ ] Phase 4: start-date picker that only offers valid days; show `CheckoutCheck::paused_notice()` at checkout; browser check of the block checkout with Razorpay test mode
- [ ] Phase 6: welcome email on `pz_pack_activated`; "paused a while" reminder on `pz_pause_ongoing` (fired daily by the 00:10 job with the days paused so far)
- [ ] Phase 7–8: `pz_meal` post type, menu snapshot (`menu_id`) on daily orders, kitchen marking via `DeliveryService`
- [ ] Phase 2 (on hold): real server cron for Action Scheduler on Hostinger

## Design update: FAQ, About Us, Plans (before Phase 4)

Source: `design/prototype-v2.html`, the owner's updated prototype (`design/homepage.html` is the older one). Screenshots in `design/compare/design-update/`: `before/`, `prototype/` and `after/`, at 1440 and 375 px.

Decisions (owner):
- About & Contact becomes one page, "About Us", at `/about-us`. It is the same page (ID kept), with the About sections first and then Contact with the enquiry form at `#contact`. `/about-contact` redirects to `/about-us/` (301).
- Storefront menu: one "About Us" item replaces "About & Contact" (which the owner had relabelled "ContactUs"), and Enquire is removed. The menu is now Home, Plans, About Us, FAQ.
- The enquiry form lives only on `/about-us/#contact`:
  - Plan-card Enquire buttons go to `/about-us/?plan=<plan>#contact`, with the plan pre-selected.
  - `/enquire` redirects to `/about-us/#contact` (301) and keeps `?plan=`.
  - The `/enquire` page is in the Trash and out of the installer's page list.
  - `pz/enquiry-form` and `POST /pz/v1/enquiries` stay.
- Every "Talk to the kitchen" button goes to `/about-us/#contact`.
- The menu table on `/plans` is labelled "Sample menu". The real weekly menu (`pz/menu-week`) replaces it in Phase 8.
- Prototype text that contradicts current decisions is not used:
  - "Two skip extensions": there's no skip limit.
  - "Paused up to 30 days": there's no pause limit.
  - "Refunded pro-rata via Razorpay": refunds are handled offline.
  - "Allergens lock packing until QC": not decided.

Built:
- [x] **Plans:**
  - Hero with an arch photo.
  - "Choose your subscription duration" wine band with the restyled `pz/plan-cards`.
  - "Sample menu" with Veg / Non-Veg / Mixed tabs.
  - "Built for health & calorie clarity" banana-leaf band with four icon tiles.
- [x] **`pz/plan-cards` 0.3.0:**
  - Cream cards without photos; an editor switch "Show plan photos" brings them back.
  - Each card has a chip, a tick list and "Enquire ↗".
  - Slots for Phase 4: `.pz-plan-card__price` (empty for visitors) and `.pz-plan-card__actions` (Buy goes next to Enquire). The wrapper has `data-mode="enquire"`.
  - Card texts follow the current rules.
- [x] **About Us:**
  - Hero with a tall arch photo that hangs over the next band.
  - "The Pazhamei philosophy": still photo, kolam lace, three icon tiles.
  - "From our Guindy kitchen to your table" route.
  - Green "Experience a taste of tradition" band.
  - Contact (`#contact`): "Talk to our kitchen", a photo, address rows, and the enquiry form in a card.
- [x] **FAQ:**
  - Centred title with kolam corners.
  - 12 questions on a paper sheet with + / −.
  - "The 8 pm rule" panel with the clock and "Talk to the kitchen".
- [x] **Theme 0.4.0:**
  - Patterns `page-plans-*`, `page-about-*` and `page-faq`.
  - Template `page-landing` (full width, no title).
  - `assets/css/pages.css`.
  - `assets/js/tabs.js`, loaded only where a Tabs group is used.
  - New block styles: Tile, FAQ sheet, Wine panel with gold frame, Enquiry card and Tabs (group); Arch frame and Small round icon (image); Route steps (columns); Menu table and Address rows (table).
- [x] **Installer (`Install/MovedPagesMigration`):**
  - Renames `/about-contact`, trashes the untouched `/enquire`, and fixes the Storefront menu.
  - Points old links at the new addresses in Site Editor template parts, menus and edited pages.
  - Sets page templates together with the starter content.
  - Never falls back to the placeholder when a page's theme patterns are missing.
- [x] **Redirects:** `Access/MovedPages` (301), with `MovedPagesTest` (8 tests).
- [x] **Home:** only links changed ("Discover our story" → `/about-us/`, "Send an enquiry" → `/about-us/#contact`, plan Enquire buttons).
- [x] **Footer:** the About Us and Enquire links changed, in the theme file and in the owner's Site Editor copy. The owner's other footer edits are kept.
- [x] **Checked:**
  - Editor: 0 invalid blocks on Home, Plans, About Us and FAQ; the footer and Storefront menu parse cleanly.
  - 375 px: no sideways scroll.
  - Reduced motion: nothing animates and nothing stays hidden.
  - Tabs work by click and with the arrow keys.
  - `?plan=` pre-selects the plan; the 301s work; visitors see no prices.
  - 214 PHPUnit tests pass.

FAQ answers, re-checked against the current rules:
- **Cutoff:** 8 pm IST the evening before; Monday's lunch locks at 8 pm on Sunday.
- **Skip:** the lunch moves to the end of the plan. No limit; undo until the cutoff.
- **Pause:** until a chosen date or until Resume, with no time limit. A pack bought during a pause waits until the customer resumes.
- **Trial:** only when trials are open; one per customer, checked by account, mobile and address. No price shown.
- **Meal type:** fixed per pack, and the next pack can differ. Staff can switch unlocked days (rule 2).
- **Cancel and refund:** a locked lunch is still delivered and the rest are removed. The team settles refunds offline. Marked DRAFT, with a link to the refund policy.
- **Allergens:** the profile is shown to the kitchen with every order. Cross-contamination wording is [EDIT]. No QC packing lock.
- **Still DRAFT or [EDIT]:** delivery areas, allergen wording, refund terms, phone hours.

Assumptions:
- **Contact rows:** they keep the old About & Contact facts (delivery areas, hours, map link) with their [EDIT] markers. The old [EDIT] story paragraphs were dropped: the new design has no story block, and the hero and philosophy sections carry the story.
- **Contact details:** the prototype's address (Plot 42, SIDCO Industrial Estate, Guindy) and phone hours (Mon–Sat, 7 am–9 pm) are used, marked [EDIT] until the owner confirms them.
- **Enquiry form:** it keeps its own fields and REST submission (stored, emailed, rate-limited). The prototype's "Compose email" (mailto) button is not used.
- **Headings:** Contact is an H2 on About Us, because the page's H1 is the About hero. The page titles ("About Us", "Plans", "FAQ") aren't shown on the page; each hero holds the H1.
- **Footer:** the "Enquire" link stays, pointing to `/about-us/#contact`.
- **Unconfirmed tile texts:** "Separate veg and non-veg prep lines" and "calories … listed for each lunch" come from the prototype. Confirm them before launch.
- **Ornaments:** the FAQ's square kolam corner tiles are drawn with our `kolam-corner.svg` (a quarter lace), and the philosophy band uses `kolam-lace.svg`. Both are SVGs, so they stay sharp, but they don't match the prototype's artwork exactly.
- **Address changes:** the FAQ's "address changes" wording is kept. The 8 pm lock snapshots the address, so a later change applies from the next unlocked day.

[REPLACE] (weak images; drop better ones into `design/Images` with the same names, delete the Media Library item and run `wp pz install-pages`):
- [ ] [REPLACE] `plans-hero-tray.jpg` (Media Library `plans-hero`): 290×224, extracted from prototype-v2, shown up to 435 px wide.
- [ ] [REPLACE] `story-vessels.jpg` (`about-vessels`): 291×394, shown up to 392 px wide.
- [ ] [REPLACE] `story-spice-table.jpg` (`about-spices`): 299×185, shown up to 430 px wide.
- [ ] [REPLACE] `faq-clock.jpg` (`faq-clock`): 153×153; fine on normal screens, soft on retina.
- [ ] [REPLACE] Theme icons `assets/images/ornaments/icon-bowl.webp`, `icon-plate.webp`, `icon-stopwatch.webp`: 29×28 from prototype-v2. Send SVG or 2× PNG.
- `member-brass-tiffin.jpg` (173×379) is committed for the Phase 4 customer dashboard and isn't used yet.

Home differences in prototype-v2 (listed only, not changed):
- **Header:** logo only (no "PAZHAMEI" wordmark), menu "Home, Meal Plans, FAQ, About Us", no Sign in button.
- **Hero:** a different photo (rice and curries in brass bowls).
- **Vision:** a real photo (a pot on a wood fire) instead of the illustrated kitchen.
- **Removed sections:** no "Veg, Non-Veg or Mixed" section, and no "Questions, answered" FAQ teaser.
- **Meal plans band:** no "Compare all plans" link.
- **Balance section:** a different photo (bowls of grains and lentils).
- **Testimonials:** one quote with a "Read community stories" link.
- **Closing band:** a single "Explore Now" button (no "Send an enquiry").
- **Footer:**
  - Logo emblem and an italic tagline.
  - Columns are Contact the kitchen / Quick links / Accounts (Sign in, Customer portal, Kitchen, Admin) instead of Policies.
  - A "Photography" credits fold, and no copyright line.

Noticed (not changed):
- **Footer email:** in the owner's Site Editor footer, the email text says `support@pazhamei.in` but the link opens `care@pazhameifuelmeals.in`. Fix it in the Site Editor.
- **Razorpay warnings:** the Razorpay plugin prints "Undefined array key description" warnings above wp-admin pages (`woo-razorpay/checkout-block.php` line 42) until Razorpay is configured. That's a third-party plugin, so we leave it alone.

## Phase 4: Customer portal

- [ ] Sign-in / Register restyle: WooCommerce account form with name, email, mobile, password, delivery pincode and "forgot password" (moved from Phase 1)

## Phase 5: Actions and manual flows
## Phase 6: Notifications
## Phase 7: Kitchen portal
## Phase 8: Admin portal

Decision (Phase 0 review):
- V1 admin is wp-admin screens plus a dashboard, used only by the owner as `administrator`.
- wp-admin stays administrator-only; the lockdown is not relaxed for `pz_admin`.
- `pz_admin` staff accounts are created only after the custom `/manage` portal exists.

Later:
- [ ] Replace the "Sample menu" on `/plans` (pattern `page-plans-menu`) with the real weekly menu, `pz/menu-week` (owner, design update).

## Phase 9: QA, security, performance
## Phase 10: Go-live
