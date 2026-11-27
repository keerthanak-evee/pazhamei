<?php
/**
 * Title: FAQ: Questions and the 8 pm rule
 * Slug: pazhamei/page-faq
 * Categories: pazhamei-pages
 * Description: Centred title with kolam corners; the questions on a paper sheet; the 8 pm rule in a wine panel beside them.
 * Viewport Width: 1400
 *
 * Answers follow the business rules in CLAUDE.md and the owner's decisions in docs/PHASES.md.
 * Text marked DRAFT or [EDIT] is not settled yet.
 *
 * @package pazhamei-theme
 */

// Question => answer (inline HTML). The first one starts open.
$pz_questions = array(
	'When can I change tomorrow’s lunch?'          => 'Skips, pauses and delivery address changes for a day close at 8 pm IST the evening before, so our kitchen can buy fresh produce for the morning without waste. Monday’s lunch locks at 8 pm on Sunday. After that, the day is locked.',
	'What happens if I skip a lunch?'              => 'The lunch moves to the end of your plan, so your end date moves one service day later and you never lose a paid lunch. There is no limit on how many days you skip, and you can undo a skip until the same 8 pm cutoff.',
	'Can I pause my plan?'                         => 'Yes. Pause from any day you can still change, either until a date you choose or until you press Resume. There is no time limit: an open-ended pause lasts until you resume. Paused days move to the end of your plan. If you buy a new pack while paused, it waits and starts after you resume.',
	'How do the plans work?'                       => 'You buy a pack of lunches: a Trial (1 lunch), 6 lunches or 26 lunches. We deliver one lunch on each service day, Monday to Saturday, from the start date you choose (any service day in the next 30 days), until the pack is used up.',
	'What time is lunch delivered across Chennai?' => 'Between noon and 1 pm, Monday to Saturday.',
	'Do you deliver on Sundays?'                   => 'No. We cook and deliver Monday to Saturday. Sundays and kitchen holidays are never counted in your plan: your plan simply runs on to the next service day.',
	'Is there a trial?'                            => 'When trials are open, you can try one lunch before you subscribe. Each customer can have one trial; we check by account, mobile number and delivery address.',
	'Which areas do you deliver to?'               => '[EDIT: list your areas and pincodes.] We deliver across Chennai, including Guindy and OMR. <a href="/about-us/#contact">Send an enquiry</a> with your pincode and we will confirm.',
	'How do I pay?'                                => 'You pay online when you buy a pack, through Razorpay: UPI, cards, net banking and wallets. Prices are shown once you sign in.',
	'Can I change between Veg, Non-Veg and Mixed?' => 'Your meal type is set when you buy a pack, and you can choose a different one for your next pack. To change the rest of a pack, talk to us: we can switch the days that are not locked yet.',
	'How are allergens handled?'                   => 'Tell us about any allergies in your account and the kitchen sees them with every order. [EDIT: our kitchen handles nuts, dairy, gluten and other common allergens, so we cannot promise any meal is completely free of them.]',
	'Can I cancel or get a refund?'                => 'Talk to us to cancel. A lunch that is already locked (after 8 pm the evening before) is still delivered; the rest are taken off your plan. Refunds are not made automatically online: our team settles them with you directly. DRAFT: see the <a href="/refund-policy/">refund policy</a>.',
);
$pz_first = true;
?>
<!-- wp:group {"align":"full","className":"pz-kolam-corners pz-paper-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-kolam-corners pz-paper-section" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:group {"align":"wide","className":"pz-faq-intro pz-reveal","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignwide pz-faq-intro pz-reveal"><!-- wp:heading {"textAlign":"center","level":1} -->
<h1 class="wp-block-heading has-text-align-center">Frequently Asked<br>Questions</h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Lunch deliveries, kitchen cutoffs and the details of your subscription.</p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size"><span class="pz-stamp">DRAFT answers – review before launch</span></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"clamp(18px, 3vw, 45px)"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"width":"64%"} -->
<div class="wp-block-column" style="flex-basis:64%"><!-- wp:group {"className":"is-style-pz-faq-sheet","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-faq-sheet"><?php foreach ( $pz_questions as $pz_question => $pz_answer ) : ?><!-- wp:details<?php echo $pz_first ? ' {"showContent":true}' : ''; ?> -->
<details class="wp-block-details"<?php echo $pz_first ? ' open' : ''; ?>><summary><?php echo esc_html( $pz_question ); ?></summary><!-- wp:paragraph -->
<p><?php echo wp_kses_post( $pz_answer ); ?></p>
<!-- /wp:paragraph --></details>
<!-- /wp:details -->

<?php $pz_first = false; ?><?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<!-- wp:column {"width":"36%"} -->
<div class="wp-block-column" style="flex-basis:36%"><!-- wp:group {"className":"is-style-pz-cutoff-note pz-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-cutoff-note pz-reveal"><!-- wp:paragraph {"className":"pz-label"} -->
<p class="pz-label">The 8 pm rule</p>
<!-- /wp:paragraph -->

<?php echo pz_theme_image_block( 'faq-clock' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading -->
<h2 class="wp-block-heading">Tomorrow locks tonight.</h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Make skips, pauses and address changes for tomorrow’s lunch by 8 pm IST today. Monday’s lunch locks at 8 pm on Sunday.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button -->
<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" href="/about-us/#contact">Talk to the kitchen</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Monday to Saturday, 7 am to 9 pm [EDIT]</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
