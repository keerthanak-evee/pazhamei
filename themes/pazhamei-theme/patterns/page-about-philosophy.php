<?php
/**
 * Title: About Us: The Pazhamei philosophy
 * Slug: pazhamei/page-about-philosophy
 * Categories: pazhamei-pages
 * Description: Wine band with a still-life photo, a kolam lace and three cream tiles with icons.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_tiles = array(
	array( 'bowl', 'Heirloom ingredients', 'Seeraga Samba, red rice and millets give each lunch its own texture.' ),
	array( 'stopwatch', 'Daily morning prep', 'We grind spices and cook the day’s dishes in our Guindy kitchen.' ),
	array( 'plate', 'A balanced Tamil plate', 'Grains, protein, gravy and vegetables, with curd or fruit to finish.' ),
);
?>
<!-- wp:group {"align":"full","className":"pz-philosophy","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|60"}}},"backgroundColor":"hero-maroon","textColor":"parchment","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-philosophy has-parchment-color has-hero-maroon-background-color has-text-color has-background" style="padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:group {"align":"wide","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide"><?php echo pz_theme_image_block( 'about-spices', 'pz-still pz-reveal' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:group -->

<!-- wp:heading {"textAlign":"center","align":"wide","className":"pz-section-title","textColor":"cream"} -->
<h2 class="wp-block-heading alignwide has-text-align-center pz-section-title has-cream-color has-text-color">The Pazhamei philosophy</h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","className":"pz-reveal-stagger","style":{"spacing":{"blockGap":"clamp(10px, 1.2vw, 18px)"}},"layout":{"type":"grid","columnCount":3,"minimumColumnWidth":"15rem"}} -->
<div class="wp-block-group alignwide pz-reveal-stagger"><?php foreach ( $pz_tiles as $pz_tile ) : ?><!-- wp:group {"className":"is-style-pz-tile pz-icon-<?php echo esc_attr( $pz_tile[0] ); ?>","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-tile pz-icon-<?php echo esc_attr( $pz_tile[0] ); ?>"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $pz_tile[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p><?php echo esc_html( $pz_tile[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --><?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
