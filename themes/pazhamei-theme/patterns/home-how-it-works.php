<?php
/**
 * Title: Home: How the plans work
 * Slug: pazhamei/home-how-it-works
 * Categories: pazhamei-home
 * Description: Framed paper sheet with three steps joined by a dotted line.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_steps = array(
	array( 'journey-select', 'Pick your plan', 'Try one lunch, or subscribe for 6 or 26 days. Choose vegetarian, non-vegetarian or mixed.' ),
	array( 'journey-kitchen', 'We plan the menu', 'Our chefs plan and cook a different Tamil lunch for each day of your subscription.' ),
	array( 'journey-delivery', 'Enjoy your routine', 'See when your lunch is due. If your plans change, skip a day or pause from your account by 8 pm the day before.' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50"}},"color":{"background":"#faf2df"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#faf2df;padding-top:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50)"><!-- wp:group {"align":"wide","className":"is-style-pz-journey-sheet pz-reveal","style":{"spacing":{"padding":{"top":"var:preset|spacing|50","bottom":"var:preset|spacing|50","left":"var:preset|spacing|50","right":"var:preset|spacing|50"}}},"layout":{"type":"constrained","contentSize":"1100px"}} -->
<div class="wp-block-group alignwide is-style-pz-journey-sheet pz-reveal" style="padding-top:var(--wp--preset--spacing--50);padding-right:var(--wp--preset--spacing--50);padding-bottom:var(--wp--preset--spacing--50);padding-left:var(--wp--preset--spacing--50)"><!-- wp:heading {"textAlign":"center","textColor":"oxblood","style":{"typography":{"fontSize":"clamp(2rem, 3.4vw, 3.4rem)"}}} -->
<h2 class="wp-block-heading has-text-align-center has-oxblood-color has-text-color" style="font-size:clamp(2rem, 3.4vw, 3.4rem)">❧ How the plans work ❧</h2>
<!-- /wp:heading -->

<!-- wp:columns {"className":"is-style-pz-journey pz-reveal-stagger"} -->
<div class="wp-block-columns is-style-pz-journey pz-reveal-stagger"><?php foreach ( $pz_steps as $pz_step ) : ?><!-- wp:column -->
<div class="wp-block-column"><?php echo pz_theme_image_block( $pz_step[0], 'is-style-pz-seal', '-' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading {"textAlign":"center","level":3,"style":{"color":{"text":"#581c11"},"typography":{"fontSize":"clamp(1.4rem, 2.1vw, 2rem)"}}} -->
<h3 class="wp-block-heading has-text-align-center has-text-color" style="color:#581c11;font-size:clamp(1.4rem, 2.1vw, 2rem)"><?php echo esc_html( $pz_step[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center","style":{"color":{"text":"#544032"}},"fontSize":"small"} -->
<p class="has-text-align-center has-text-color has-small-font-size" style="color:#544032"><?php echo esc_html( $pz_step[2] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<?php endforeach; ?></div>
<!-- /wp:columns --></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
