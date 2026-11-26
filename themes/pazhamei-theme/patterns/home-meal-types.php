<?php
/**
 * Title: Home: Veg, Non-Veg and Mixed
 * Slug: pazhamei/home-meal-types
 * Categories: pazhamei-home
 * Description: Three cards explaining the meal types a customer chooses at purchase.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_types = array(
	array( 'meal-veg', 'Vegetarian', 'Sambar, kootu, poriyal and the grain of the day, with paneer, dal and greens for protein.' ),
	array( 'meal-nonveg', 'Non-vegetarian', 'Chicken, fish or egg in place of the vegetarian main, with the same Tamil sides.' ),
	array( 'meal-mixed', 'Mixed', 'A menu of its own, bringing vegetarian and non-vegetarian dishes through the week.' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|70","bottom":"var:preset|spacing|70"}}},"backgroundColor":"kora","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-kora-background-color has-background" style="padding-top:var(--wp--preset--spacing--70);padding-bottom:var(--wp--preset--spacing--70)"><!-- wp:heading {"textAlign":"center","textColor":"oxblood"} -->
<h2 class="wp-block-heading has-text-align-center has-oxblood-color has-text-color">Veg, Non-Veg or Mixed</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center">Choose your meal type when you buy a pack. It stays the same for that pack, and you can choose again when you renew.</p>
<!-- /wp:paragraph -->

<!-- wp:columns {"align":"wide","className":"pz-reveal-stagger"} -->
<div class="wp-block-columns alignwide pz-reveal-stagger"><?php foreach ( $pz_types as $pz_type ) : ?><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-pz-paper-card pz-hover-lift pz-hover-zoom","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-paper-card pz-hover-lift pz-hover-zoom"><?php echo pz_theme_image_block( $pz_type[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $pz_type[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php echo esc_html( $pz_type[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<?php endforeach; ?></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
