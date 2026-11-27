<?php
/**
 * Title: About Us: From our kitchen to your table
 * Slug: pazhamei/page-about-route
 * Categories: pazhamei-pages
 * Description: Heading on the left; three small round icons joined by a thin line on the right.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_steps = array(
	array( 'journey-select', 'Fresh sourcing' ),
	array( 'journey-kitchen', 'Morning cooking' ),
	array( 'journey-delivery', 'Daily delivery' ),
);
?>
<!-- wp:group {"align":"full","className":"pz-paper-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-paper-section" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"36%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:36%"><!-- wp:heading {"className":"pz-section-title"} -->
<h2 class="wp-block-heading pz-section-title">From our Guindy kitchen<br>to your table</h2>
<!-- /wp:heading --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"64%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:64%"><!-- wp:columns {"isStackedOnMobile":false,"className":"is-style-pz-route pz-reveal-stagger"} -->
<div class="wp-block-columns is-not-stacked-on-mobile is-style-pz-route pz-reveal-stagger"><?php foreach ( $pz_steps as $pz_step ) : ?><!-- wp:column -->
<div class="wp-block-column"><?php echo pz_theme_image_block( $pz_step[0], 'is-style-pz-route-icon', '-' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:paragraph -->
<p><?php echo esc_html( $pz_step[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column --><?php endforeach; ?></div>
<!-- /wp:columns --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
