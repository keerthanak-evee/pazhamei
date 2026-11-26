<?php
/**
 * Title: Home: What's cooking this week
 * Slug: pazhamei/home-cooking-week
 * Categories: pazhamei-home
 * Description: Banana-leaf band with the staple grain for each day, Monday to Saturday.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_days = array(
	array( 'Monday', 'Flavoured rice', 'cooking-flavoured-rice' ),
	array( 'Tuesday', 'Biryani', 'cooking-biryani' ),
	array( 'Wednesday', 'Red rice', 'cooking-red-rice' ),
	array( 'Thursday', 'Chapati', 'cooking-chapati' ),
	array( 'Friday', 'Millet rice', 'cooking-millet-rice' ),
	array( 'Saturday', 'Ponni white rice', 'cooking-ponni-rice' ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-pz-banana-leaf pz-kolam-left","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-pz-banana-leaf pz-kolam-left" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide"} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"24%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:24%"><!-- wp:heading {"style":{"typography":{"fontSize":"clamp(1.8rem, 3vw, 3rem)","lineHeight":"1.05"}}} -->
<h2 class="wp-block-heading" style="font-size:clamp(1.8rem, 3vw, 3rem);line-height:1.05">What’s cooking at <em>Pazhamei?</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size">From fragrant biryani to millet rice, we pair each day’s staple with protein and seasonal vegetables.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"76%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:76%"><!-- wp:group {"className":"pz-day-grid pz-reveal-stagger","style":{"spacing":{"blockGap":"0.6rem"}},"layout":{"type":"grid","columnCount":6,"minimumColumnWidth":null}} -->
<div class="wp-block-group pz-day-grid pz-reveal-stagger"><?php foreach ( $pz_days as $pz_day ) : ?><!-- wp:group {"className":"pz-day-card pz-hover-lift","style":{"spacing":{"blockGap":"0.35rem","padding":{"top":"0.7rem"}}},"layout":{"type":"flex","orientation":"vertical","justifyContent":"stretch"}} -->
<div class="wp-block-group pz-day-card pz-hover-lift" style="padding-top:0.7rem"><!-- wp:paragraph {"style":{"typography":{"fontWeight":"700"},"color":{"text":"#674a2c"}},"fontSize":"small"} -->
<p class="has-text-color has-small-font-size" style="color:#674a2c;font-weight:700"><?php echo esc_html( $pz_day[0] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"style":{"typography":{"fontWeight":"700","lineHeight":"1.05"}},"fontSize":"large","fontFamily":"heading"} -->
<p class="has-heading-font-family has-large-font-size" style="font-weight:700;line-height:1.05"><?php echo esc_html( $pz_day[1] ); ?></p>
<!-- /wp:paragraph -->

<?php echo pz_theme_image_block( $pz_day[2], '', '-' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
