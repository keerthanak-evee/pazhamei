# Phase 4 prompt — Customer portal

Phase 4: Customer portal. Read CLAUDE.md, PHASES.md and the Phase 4 section of docs/build-plan.md first. Phase 2 is still on hold; everything is local.

## Owner decisions (record them in PHASES.md)

- Claude's Phase 3 assumptions are accepted:
  - a dated pause includes its end date
  - resume restarts on the first unlocked day
  - cancelling still delivers an already-locked meal
  - a cancelled trial still counts as "already had a trial". An admin "allow another trial" action can come later; note it in PHASES.md under Later.
- Prices must be editable by the owner without touching code.
- Trial price has ONE source: the Trial row in the Plans & Prices grid (the WooCommerce Trial variation prices). Only the trial on/off switch stays a setting.
- Register includes Delivery Pincode, below Phone Number.
- Sign in by "Email or phone number" is allowed. Each mobile number belongs to one account, so Register refuses a mobile that's already used.
- The Sign-in / Register restyle must match design/Images/Login&RegisterPAgeReference.jpg (already committed), with the same rules as the other prototype pages: core blocks, theme styles, no prototype CSS, weak image crops marked [REPLACE].

## Working mode

Merge this with the plan you already proposed (your Sign-in / Register design, WooCommerce settings step, tickets table and version bump are all good). Show me the merged step list briefly and wait for "OK". After that, don't stop for questions; list assumptions at the end.

## Build

0. **Checkout first.** Before building the pages, open /checkout in headless Chrome as a signed-in customer with a plan in the cart, using Test Pay (step 4). Use whichever checkout is active (block or classic). Confirm the start-date field shows and validates, and that an order completes and activates a pack. Fix any problem there first.

1. **Plans & Prices screen:** wp-admin page "Pazhamei → Plans & Prices" (administrator only).
   - One grid: Trial, 6-day, 26-day rows × Veg, Non-Veg, Mixed columns, in ₹.
   - Also on the screen: the trial on/off switch, and each plan's "what's included" text. There is no separate trial price field.
   - Saving writes to the WooCommerce variation prices (WooCommerce is the only source of prices) and validates input.
   - If pz_settings['trial_price'] is used anywhere, read the price from the Trial variations instead, or remove the setting.
   - The plan cards and checkout read their prices from here.
   - Add a test that the plan cards, checkout and the saved variation prices always show the same numbers.

2. **Sign-in / Register restyle** at /sign-in, matching the reference image.
   - Register fields: full name, email, phone (stored as 10 digits, unique), delivery pincode, password and confirm password.
   - Forgot-password keeps working.
   - After sign-in, each role goes to its landing page.
   - Turn on WooCommerce registration on My Account, let customers choose their own password, and switch guest checkout off.

3. **Customer pages.** Blocks use the Interactivity API and are mobile-first, since most customers use phones.

   **/account** (pz/customer-welcome)
   - Greeting, the current pack in one line, tomorrow's meal and whether it's locked, and shortcuts.
   - A customer with no pack sees "Start with a trial" (only if the trial is on and they're eligible) or "Choose a plan".

   **/account/meals** (pz/menu-week)
   - This week's menu for Veg, Non-Veg and Mixed side by side, with the customer's own type highlighted.
   - Menus are only created in Phase 8, so show "Menu coming soon" when a date has none.
   - On local only, add sample menu data to `wp pz seed` so the page can be tested.

   **/account/plans, and /plans for signed-in customers** (pz/plan-cards in price mode)
   - Fill the card's existing price slot from Plans & Prices.
   - Add Buy next to Enquire. Enquire keeps linking to /about-us/?plan=<plan>#contact.
   - Buy adds the chosen plan and meal type to the cart and goes straight to checkout.
   - The trial card shows only if the trial is on and the customer is eligible.
   - Visitors still see no prices.

   **/checkout**
   - Works with whichever checkout is active (block or classic).
   - The start-date picker greys out Sundays, holidays (wp_pz_holidays) and locked days. The server re-checks the same rules on submit; the picker is only a convenience.
   - Delivery address, with a pincode check against the delivery_pincodes setting. If the setting is empty, accept any pincode and mark it [EDIT].
   - An allergen confirmation checkbox and a terms checkbox.
   - Guest checkout off; one plan per order.
   - If the customer's current plan is paused, show: "Your plan is paused. Resume it to start your new pack."

   **/account/dashboard** (pz/subscription-summary + pz/schedule-calendar)
   - Pack summary: plan, meal type, meals left, start and end, status. Queued renewals are shown too.
   - A calendar of upcoming days with Skip / Undo skip on each day.
   - Sundays and holidays are greyed out with a label. Locked days show a lock and the reason.
   - Pause offers two choices, "until I resume" or "until a date". For the dated choice, the label reads "Last day without meals", and the screen shows the new end date before confirming. A Resume button.
   - A live countdown: "Changes for tomorrow close at 8:00 PM".
   - While a paid order is waiting for payment confirmation, show "Confirming your payment…" and refresh every few seconds.
   - Every change updates the calendar and end date without a page reload.

   **/account/profile** (pz/profile-form)
   - Name, mobile, delivery address, dietary preference, allergens with severity (mild / moderate / severe), special instructions, and password change.
   - Seed a starter allergen list: peanut, tree nuts, milk, egg, gluten/wheat, soy, fish, shellfish, sesame, mustard.
   - Address and instruction changes apply from the next unlocked day.

   **/account/support** (pz/support-tickets)
   - Raise a ticket (topic, message, optional date), and see replies and status.
   - Admin replies come in Phase 8. For now, the admin gets an email for each new ticket.

   **/account/history** (pz/order-history)
   - Past packs, delivered days and payments, with links to the WooCommerce order pages.

4. **Payments for local testing.**
   - Add a local-only test payment method, "Pazhamei Test Pay", that marks orders as paid (processing), so the full buy flow can be tested in the browser.
   - It must not exist unless wp_get_environment_type() is "local".
   - Razorpay is tested in Phase 2 on UAT. If I add Razorpay test keys myself in WooCommerce → Payments, also check that flow. Without keys, use Test Pay.

5. All blocks use the Phase 3 REST endpoints with nonces. No business rule is duplicated in JavaScript: the server decides, and the screen shows its answer and its message.

6. Use the existing design system (theme.json, block styles, ornaments, motion classes) so the portal matches the storefront. The image member-brass-tiffin.jpg is available for the portal.

## Tests

PHPUnit tests for:
- Plans & Prices saving, including the price-consistency test
- the registration fields, phone uniqueness and phone sign-in
- the pincode check
- start-date validation (Sunday, holiday, locked day)
- the paused-plan warning logic
- the Test Pay environment guard

Browser checks (headless Chrome, at 1440 px and 375 px), as a new customer:
1. Register.
2. Sign in with phone.
3. Buy a 6-day Veg pack with Test Pay.
4. See the dashboard.
5. Skip a day, then undo it.
6. Pause until a date, then resume.
7. Buy a renewal.
8. Edit the profile.
9. Raise a ticket.
10. View the history.

Also:
- try a skip after the cutoff (using `wp pz time`) and confirm the message shows
- try picking a Sunday at checkout
- check that password reset sends an email to Mailpit

## Done when

- All tests and browser checks pass.
- Every page opens in the editor with no block errors.
- Nothing scrolls sideways at 375 px.
- Screenshots of each customer page and the sign-in/register views are saved to design/compare/phase-4/.

## Finish

1. List your assumptions, the [EDIT] items, and anything left for me to try by hand.
2. Update PHASES.md.
3. Commit in logical steps (include this prompt file).
4. Tag phase-4-done, and push main and tags.
