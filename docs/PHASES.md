# Pazhamei build phases

Full detail for each phase: `docs/build-plan.pdf`.

## Phase 0: Foundation

- [x] `CLAUDE.md`, `.gitignore`, WordPress agent skills
- [x] Build plan copied to `docs/build-plan.pdf`
- [x] Theme `pazhamei-theme`: `theme.json` with brand colours, self-hosted fonts and spacing from the storefront design
- [x] Template parts `header.html` and `footer.html`
- [x] Templates `front-page.html`, `page.html`, `page-portal.html`, `404.html` (plus `index.html` fallback)
- [x] Plugin `pazhamei-core` skeleton: main file, version constant, autoloader, activation/deactivation/uninstall, module folders
- [x] `pz_settings` option with defaults
- [x] Roles `pz_kitchen`, `pz_admin`; `pz_customer` on `customer`; all `pz_*` on `administrator`
- [x] `_pz_portal` page setting with a sidebar dropdown
- [x] Portal guard, sign-in redirects, wp-admin and admin bar lockdown
- [x] Block `pz/portal-nav`
- [x] `wp pz install-pages`: pages, four Navigation menus, front page, WooCommerce My Account → `/sign-in`
- [x] `wp pz seed`: `test_customer`, `test_kitchen`, `test_admin`
- [x] PHPUnit tests for the portal rules (`composer test` in the plugin folder)
- [x] `docs/DATA-MODEL.md` (tables planned)
- [x] WooCommerce: My Account = `/sign-in` (old `/my-account` trashed and redirected), shop/product → `/plans`, cart → `/checkout`, "Go to my dashboard" button on the order-received page, HPOS compatibility declared
- [x] Done-when checks passed over HTTP for a visitor and all three test users
- [ ] Site Editor checked by eye (header, footer and four menus open without block errors)
- [x] Committed as `Phase 0: foundation`, tagged `phase-0-done`

Left for later (listed in the plan's Phase 0, not in this session's brief):
- [ ] `wp pz seed` sample data: 20 customers, sample meals, a week of menus (needs Phase 2 tables)
- [ ] MCP Adapter connected

## Phase 1: Storefront
## Phase 2: Subscription engine
## Phase 3: Customer portal
## Phase 4: Actions and manual flows
## Phase 5: Notifications
## Phase 6: Kitchen portal
## Phase 7: Admin portal
## Phase 8: QA, security, performance
## Phase 9: Go-live and operations
