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

Decisions:
- [x] Test users: `wp pz seed` makes three (customer, kitchen, pz_admin). The fourth role, administrator, is the owner's own `WpAdmin` account; no test administrator is seeded.
- The `phase-0-done` tag stays on the "Phase 0: foundation" commit; later fixes come after it.
- Developer tools (`wp pz seed`, `PZ_FAKE_NOW`) run only on `local`; on staging and production they refuse or are ignored.
- WooCommerce store visibility stays "Coming soon" until Phase 10.

Later:
- [ ] Customers, meals and menus in `wp pz seed` (added in later phases, once the tables exist)

## Phase 1: Storefront
## Phase 2: Deployment setup
## Phase 3: Subscription engine
## Phase 4: Customer portal
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
