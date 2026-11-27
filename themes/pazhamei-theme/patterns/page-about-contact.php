<?php
/**
 * Title: About Us: Contact (Talk to our kitchen)
 * Slug: pazhamei/page-about-contact
 * Categories: pazhamei-pages
 * Description: Address, phone and email on the left; the enquiry form in a card on the right. Anchor #contact.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

?>
<!-- wp:group {"anchor":"contact","align":"full","className":"pz-contact pz-paper-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"layout":{"type":"constrained"}} -->
<div id="contact" class="wp-block-group alignfull pz-contact pz-paper-section" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:columns {"align":"wide","style":{"spacing":{"blockGap":{"left":"clamp(24px, 6vw, 92px)"}}}} -->
<div class="wp-block-columns alignwide"><!-- wp:column {"className":"pz-reveal"} -->
<div class="wp-block-column pz-reveal"><!-- wp:heading {"className":"pz-contact-title"} -->
<h2 class="wp-block-heading pz-contact-title">Talk to <em>our kitchen.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Planning lunch for one desk or a whole office? Ask us about meal plans, ingredients or delivery in Chennai.</p>
<!-- /wp:paragraph -->

<?php echo pz_theme_image_block( 'contact-spices', 'is-style-pz-leaf-corner pz-contact-photo' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:table {"hasFixedLayout":false,"className":"is-style-pz-address"} -->
<figure class="wp-block-table is-style-pz-address"><table><tbody><tr><td>Guindy kitchen</td><td>[EDIT] Plot 42, SIDCO Industrial Estate, Guindy, Chennai 600032</td></tr><tr><td>Call or WhatsApp</td><td>[EDIT] <a href="tel:+919840972110">+91 98409 72110</a> · <a href="https://wa.me/919840972110">WhatsApp</a><br>Monday to Saturday, 7 am to 9 pm [EDIT]</td></tr><tr><td>Email</td><td>[EDIT] <a href="mailto:care@pazhameifuelmeals.in">care@pazhameifuelmeals.in</a></td></tr><tr><td>Lunch delivery</td><td>Monday to Saturday, noon to 1 pm. Closed on Sundays and kitchen holidays.</td></tr><tr><td>Changes for tomorrow</td><td>Until 8 pm today (Sunday 8 pm for Monday’s lunch)</td></tr><tr><td>Delivery areas</td><td>[EDIT: list the areas and pincodes you deliver to.] Across Chennai, including Guindy and OMR.</td></tr><tr><td>Map</td><td>[EDIT: link to the kitchen on Google Maps]</td></tr></tbody></table></figure>
<!-- /wp:table --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-pz-enquiry-sheet","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-enquiry-sheet"><!-- wp:heading -->
<h2 class="wp-block-heading">Your enquiry</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Tell us a little about you and the plan you’re interested in. We’ll call you within one working day to answer your questions and share prices.</p>
<!-- /wp:paragraph -->

<!-- wp:pz/enquiry-form /-->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">We use these details only to reply to your enquiry. See our <a href="/privacy-policy/">privacy policy</a>.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
