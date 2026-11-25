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
- [ ] Site Editor checked by eye (header, footer and four menus open without block errors)
- [x] Committed as `Phase 0: foundation`, tagged `phase-0-done`

Open points from the plan's Phase 0:
- [ ] `wp pz seed`: the plan asks for four test users (one per role); three exist now (no test administrator)
- [ ] Customers, meals and menus in `wp pz seed` (added in later phases, once the tables exist)

## Phase 1: Storefront
## Phase 2: Deployment setup
## Phase 3: Subscription engine
## Phase 4: Customer portal
## Phase 5: Actions and manual flows
## Phase 6: Notifications
## Phase 7: Kitchen portal
## Phase 8: Admin portal
## Phase 9: QA, security, performance
## Phase 10: Go-live
