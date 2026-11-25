# Phase 0 prompt — paste this into Claude Code

Paste everything below the line into Claude Code, started from LocalWP's "Open site shell" inside the `wp-content` folder.

---

We are starting Phase 0 (Foundation) of the Pazhamei web app. Read `CLAUDE.md` first and follow it.

**Goal for this session:** a working local site with the shared header and footer, four portals with their own menus, four roles with access control, an empty but well-structured `pazhamei-core` plugin, and a page installer. No business features yet.

**Build these, in this order:**

1. **Theme `themes/pazhamei-theme`** (block theme)
   - `theme.json`: colours, fonts, spacing and layout widths. If `design/homepage.html` exists, take the brand colours and fonts from it. Otherwise use a warm, food-friendly placeholder palette and tell me.
   - Template parts: `parts/header.html` (logo, site title, `pz/portal-nav` block, account button) and `parts/footer.html` (address, phone, email, links to the legal pages, copyright).
   - Templates: `front-page.html`, `page.html`, `page-portal.html` (wider content for dashboards), `404.html`. All of them use the same header and footer parts.

2. **Plugin `plugins/pazhamei-core`** (skeleton)
   - Main file with plugin header, version constant, PSR-4 style autoloader for the `Pazhamei\` namespace, activation and deactivation hooks.
   - Empty module folders as listed in `CLAUDE.md`, each with a short README line saying what will go there.
   - `pz_settings` option created on activation with defaults:
     - `cutoff_time`: `20:00`
     - `trial_enabled`: false
     - `trial_price`: 0
     - `max_pause_days`: 30
     - `service_days`: Mon–Sat
     - `delivery_pincodes`: empty list

3. **Roles and capabilities** (created on activation, removed cleanly on uninstall)
   - Roles `pz_kitchen` and `pz_admin`, plus the `pz_*` capabilities from `CLAUDE.md`.
   - Add `pz_customer` to WooCommerce's `customer` role.
   - Give `administrator` every `pz_*` capability.

4. **Access control** (`includes/Access`)
   - A `_pz_portal` page setting, editable as a dropdown in the page sidebar.
   - A portal guard on `template_redirect`:
     - A logged-out user on a customer, kitchen or admin page goes to `/sign-in?redirect_to=...`.
     - A logged-in user on a portal page their role can't open goes to their own landing page.
   - After sign-in, send each role to its landing page.
   - Hide the admin bar, and block `wp-admin`, for everyone except `administrator`. Keep `admin-ajax.php` working.

5. **Block `pz/portal-nav`**
   - Renders the Navigation menu for the current page's portal.
   - Falls back to the Storefront menu on pages with no portal set.
   - Shows Sign in or Sign out, plus "My account", "Kitchen" or "Manage" depending on the role.

6. **WP-CLI command `wp pz install-pages`**
   - Creates the pages below if missing, with the right parent, slug, `_pz_portal` value and template.
   - Content: a heading plus one paragraph saying which phase fills the page.
   - Never overwrites an existing page.
   - Also creates the four Navigation menus, sets Home as the front page, and sets WooCommerce's My Account page to `/sign-in`.

   | Portal | Pages (slug) |
   |---|---|
   | Storefront | `home` (front page), `sign-in`, `plans`, `about-contact`, `faq`, `enquire`, `terms`, `privacy-policy`, `refund-policy`, `delivery-policy` |
   | Customer | `account`, plus children `meals`, `plans`, `dashboard`, `profile`, `support`, `history` |
   | Kitchen | `kitchen`, plus children `allergens`, `production`, `dispatch` |
   | Admin | `manage`, plus children `menu`, `meals`, `customers`, `enquiries`, `subscriptions`, `users`, `settings` |

7. **WP-CLI command `wp pz seed`**
   - Creates three test users: `test_customer`, `test_kitchen`, `test_admin`.
   - Gives each a random password and prints the passwords in the terminal only. Never write them to a file.

8. **Docs**
   - Create `docs/DATA-MODEL.md` with the table list from the plan, marked "planned".
   - Create `docs/PHASES.md` with a checklist of Phase 0 items.

**Constraints**
- Follow `CLAUDE.md` exactly.
- Before writing code, show me your plan as a short list of files you will create, and wait for my OK.
- Keep each PHP file small and commented for a beginner.

**Done when** (check each one yourself, then tell me how to check it):
- [ ] `wp pz install-pages` runs twice without errors or duplicate pages.
- [ ] Every page shows the same header and footer.
- [ ] Logged out: I see the Storefront menu, and opening `/account/dashboard`, `/kitchen` or `/manage` sends me to `/sign-in`.
- [ ] As `test_customer`: I see the Customer menu and am blocked from `/kitchen` and `/manage`.
- [ ] As `test_kitchen`: I see the Kitchen menu and am blocked from `/manage` and `/account/...`.
- [ ] As `test_admin`: I see the Admin menu and can open `/kitchen`.
- [ ] I can edit the header and footer in Appearance → Editor, and the four menus in Appearance → Editor → Navigation.
- [ ] `git status` shows only the theme, the plugin, `CLAUDE.md`, `docs/` and `.claude/`.

When everything passes, commit with the message `Phase 0: foundation` and tag it `phase-0-done`.
