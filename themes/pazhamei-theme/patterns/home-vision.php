<?php
/**
 * Title: Home: Our vision
 * Slug: pazhamei/home-vision
 * Categories: pazhamei-home
 * Description: Kitchen illustration beside the "More than a meal" story (with the temple sketch), a leaf divider and three illustrated pillars.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"parchment","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-parchment-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"42%","className":"pz-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center pz-reveal" style="flex-basis:42%"><?php echo pz_theme_image_block( 'vision-kitchen', 'is-style-pz-leaf-corner pz-hover-zoom' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"58%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:58%"><!-- wp:paragraph {"style":{"typography":{"textTransform":"uppercase","letterSpacing":"0.15em","fontWeight":"700"},"color":{"text":"#765234"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:#765234;font-weight:700;letter-spacing:0.15em;text-transform:uppercase">Our vision</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"className":"pz-temple-sketch","textColor":"oxblood","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 3rem)","lineHeight":"1.05"}}} -->
<h2 class="wp-block-heading pz-temple-sketch has-oxblood-color has-text-color" style="font-size:clamp(1.9rem, 3vw, 3rem);line-height:1.05">More than a meal.<br><em>A connection to our roots.</em></h2>
<!-- /wp:heading -->

<!-- wp:separator {"className":"is-style-pz-leaf"} -->
<hr class="wp-block-separator has-alpha-channel-opacity is-style-pz-leaf"/>
<!-- /wp:separator -->

<!-- wp:paragraph {"style":{"color":{"text":"#513c2f"}}} -->
<p class="has-text-color" style="color:#513c2f">A good Tamil lunch has its own rhythm. Rice to catch the gravy, poriyal for crunch, and curd to finish. We bring that familiar balance to a working day.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"className":"pz-divided pz-reveal-stagger","style":{"border":{"top":{"color":"#d8c6a7","width":"1px"}},"spacing":{"padding":{"top":"var:preset|spacing|30"}}}} -->
<div class="wp-block-columns pz-divided pz-reveal-stagger" style="border-top-color:#d8c6a7;border-top-width:1px;padding-top:var(--wp--preset--spacing--30)"><!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"pz-icon-roots","style":{"color":{"text":"#4d2b1e"}}} -->
<h3 class="wp-block-heading pz-icon-roots has-text-color" style="color:#4d2b1e">Our roots</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Tamil home cooking, from the spice box to the lunch box.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"pz-icon-ingredients","style":{"color":{"text":"#4d2b1e"}}} -->
<h3 class="wp-block-heading pz-icon-ingredients has-text-color" style="color:#4d2b1e">Our ingredients</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Heirloom rice, pulses and vegetables chosen for each dish.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column -->
<div class="wp-block-column"><!-- wp:heading {"level":3,"className":"pz-icon-philosophy","style":{"color":{"text":"#4d2b1e"}}} -->
<h3 class="wp-block-heading pz-icon-philosophy has-text-color" style="color:#4d2b1e">Our philosophy</h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">Nutritionist-planned portions, with calories and macros you can track.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
