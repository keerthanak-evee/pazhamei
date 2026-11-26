# Phase 1 prompt — paste this into Claude Code

Paste everything below the line into Claude Code, started from LocalWP's "Open site shell" inside the `wp-content` folder.

Owner's notes given with the approval (follow them too):
1. Commit `design/Images/` in its own commit.
2. Never load Tailwind, `heritage-storefront.css` or `pazhamei.css` anywhere in the theme or plugin. Recreate the look through `theme.json`, block styles and the motion files only.
3. No prices anywhere for visitors, including the Home plans teaser and any text taken from the prototype or the menu PDF.
4. Remove the menu PDF.

---

Phase 1: Storefront. Read CLAUDE.md, PHASES.md and the Phase 1 section of docs/build-plan.md first.

Low-intervention mode: don't stop for questions; make reasonable assumptions, list them at the end, and only stop if truly blocked. Still show me a short file plan first and wait for "OK".

Build:
1. Home: convert design/homepage.html into block patterns (hero, how it works, Veg / Non-Veg / Mixed, plans teaser, testimonials, FAQ teaser, call to action) made of core blocks, so every heading, image and button is editable.
   - Images: use the originals in design/images/ if that folder exists. Otherwise extract the images embedded in homepage.html. Import content images into the Media Library; keep decorative ones (backgrounds, icons) in the theme's assets. Compress to web size.
   - Animations: keep the prototype's animations. Rebuild simple ones (scroll reveal, hover, fades) as a small theme CSS file and a vanilla JS file using IntersectionObserver, applied to blocks through CSS classes so pages stay editable. Respect prefers-reduced-motion. Don't copy the prototype's raw stylesheet or scripts. If an effect can't be rebuilt cleanly (carousel or heavy JS), rebuild it with blocks or list it for me.
   - Fonts are already self-hosted from Phase 0; reuse them.
2. /plans: block pz/plan-cards showing Trial, 6-day and 26-day cards with what's included and no prices. The button is "Enquire", linking to /enquire?plan=<plan>. (Signed-in price/Buy mode comes in Phase 4.)
3. /about-contact: About story, kitchen address, phone, email, WhatsApp link, delivery areas and hours, as placeholder text marked [EDIT].
4. /faq: 8–10 likely questions (skip, pause, cutoff, Sunday, trial, delivery areas, payment, allergens) as core Details blocks, with draft answers based on the business rules in CLAUDE.md.
5. /enquire: block pz/enquiry-form with the fields from the plan.
   - Table wp_pz_enquiries, created by the installer with dbDelta. Bump the plugin version constant (e.g. 0.1.0 → 0.2.0) and run the version check on plugins_loaded, so the table appears automatically on any site without deactivating the plugin. Document it in DATA-MODEL.md.
   - REST endpoint POST /pz/v1/enquiries.
   - Security: the form fetches a fresh nonce from a small REST GET when the page loads, so page caching can't break it.
   - Honeypot field named "company_url", hidden from people. If it's filled, return a normal success response without saving or emailing.
   - Rate limit: max 5 submissions per 15 minutes per IP, stored in a transient keyed on a salted hash of the IP (never store raw IPs). Read the IP from REMOTE_ADDR only. Put the IP lookup in one function, so we can add a trusted proxy header in Phase 2 once Hostinger's setup is known.
   - Email the site admin; show a thank-you message; pre-select the plan from ?plan=.
6. Policy pages (terms, privacy-policy, refund-policy, delivery-policy): draft text that fits the business rules, with "DRAFT – review before launch" at the top. For privacy, use the page set in get_option('wp_page_for_privacy_policy'): make sure its slug is privacy-policy, set _pz_portal to storefront, and publish it. Confirm there's no duplicate page or /privacy-policy-2 URL. Link all four in the footer.
7. Fix the layout issue from Phase 0: the page title and content must line up in both page templates.
8. Every page stays editable in the block editor with no block errors, and has the same header and footer.

Tests: PHPUnit tests for enquiry validation, the honeypot, the rate limit and IP hashing.

Done when (check these yourself and tell me how to verify):
- All storefront pages load as a visitor, with no prices visible anywhere.
- An enquiry submits, is saved in wp_pz_enquiries, and the admin email is sent (check FluentSMTP's email log).
- A filled honeypot shows success but saves nothing; a 6th submission within 15 minutes is refused.
- The Enquire button on each plan pre-selects that plan.
- Animations work, and are off with reduced motion turned on.
- Pages open in the editor without block errors.
- Mobile width (375px) looks right.

Finish: list your assumptions, every [EDIT] or DRAFT item, and any animation you couldn't keep. Update PHASES.md, commit "Phase 1: storefront", tag phase-1-done, and push main and tags.
