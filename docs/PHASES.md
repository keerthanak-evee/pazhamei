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
- [x] Block `pz/plan-cards` on `/plans`: Trial, 6-day, 26-day, what's included, Enquire → `/enquire?plan=<plan>`, no prices
- [x] `/about-contact`, `/faq` (10 Details blocks), `/enquire`, and four policy drafts (`/terms`, `/privacy-policy`, `/refund-policy`, `/delivery-policy`), each policy marked "DRAFT – review before launch"
- [x] WordPress's own privacy page (Settings > Privacy) reused as `/privacy-policy`: storefront portal, published, no duplicate
- [x] Block `pz/enquiry-form` (Interactivity API): fresh nonce from `GET /pz/v1/enquiries/nonce`, `POST /pz/v1/enquiries`, honeypot `company_url`, 5 per 15 minutes per salted IP hash, admin email, thank-you message, `?plan=` pre-select
- [x] Table `wp_pz_enquiries` (dbDelta), plugin 0.1.0 → 0.2.0, version check on `plugins_loaded`; documented in `docs/DATA-MODEL.md`
- [x] Visitors never see prices: WooCommerce price HTML and product structured data `offers` removed for visitors (`Woo/HidePrices`)
- [x] Page templates: title and content share one constrained layout, so they line up (`page.html`, `page-portal.html`)
- [x] PHPUnit: enquiry validation, honeypot, rate limit, IP hashing, plan catalogue (92 tests)
- [x] Checked in headless Chrome: every page opens in the editor with 0 invalid blocks; motion and reduced motion; plan pre-select; form submit; 375 px with no horizontal scroll

- [x] Follow-up: wp-admin screen Pazhamei → Enquiries (administrators only): newest first, filter by status, change status (new, contacted, converted, closed); otherwise read-only. Test for the permission rule (`EnquiryStatus::can_manage`)

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

## Phase 9: QA, security, performance
## Phase 10: Go-live
