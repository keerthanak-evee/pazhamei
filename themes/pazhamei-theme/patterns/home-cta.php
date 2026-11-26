<?php
/**
 * Title: Home: Call to action
 * Slug: pazhamei/home-cta
 * Categories: pazhamei-home
 * Description: "A taste of tradition" on a banana-leaf band, with a round feast illustration and a button.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

?>
<!-- wp:group {"align":"full","className":"is-style-pz-banana-leaf","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-pz-banana-leaf" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","className":"pz-reveal-stagger"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center pz-reveal-stagger"><!-- wp:column {"verticalAlignment":"center","width":"55%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:55%"><!-- wp:heading {"style":{"typography":{"fontSize":"clamp(2.6rem, 5.2vw, 5rem)","lineHeight":"1","fontWeight":"500"}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(2.6rem, 5.2vw, 5rem);font-weight:500;line-height:1">A Taste of <em>Tradition</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#f3eddb"}}} -->
<p class="has-text-color" style="color:#f3eddb">Rice for the kuzhambu, poriyal for crunch, curd to finish. Find the lunch plan that fits your week.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-pz-cream pz-arrow"} -->
<div class="wp-block-button is-style-pz-cream pz-arrow"><a class="wp-block-button__link wp-element-button" href="/plans">Explore plans</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-pz-ghost-light"} -->
<div class="wp-block-button is-style-pz-ghost-light"><a class="wp-block-button__link wp-element-button" href="/enquire">Send an enquiry</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"45%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:45%"><?php echo pz_theme_image_block( 'cta-feast', 'is-style-pz-seal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
