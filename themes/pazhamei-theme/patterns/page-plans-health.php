<?php
/**
 * Title: Plans: Built for health and calorie clarity
 * Slug: pazhamei/page-plans-health
 * Categories: pazhamei-pages
 * Description: Banana-leaf band with four cream tiles, each with a small icon.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_tiles = array(
	array( 'bowl', 'Calorie tracking', 'Calories, protein, carbs and fibre are listed for each lunch.' ),
	array( 'plate', 'Heirloom grains', 'Seeraga Samba, red rice and millet share the week with chapathi.' ),
	array( 'stopwatch', 'Kitchen standards', 'Separate veg and non-veg prep lines, with allergens listed for each meal.' ),
	array( 'bowl', 'Curd & seasonal fruit', 'Finish lunch with curd, mor kuzhambu or fruit from the day’s menu.' ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-pz-banana-leaf","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-pz-banana-leaf" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","align":"wide","className":"pz-section-title"} -->
<h2 class="wp-block-heading alignwide has-text-align-center pz-section-title">Built for health &amp; calorie clarity</h2>
<!-- /wp:heading -->

<!-- wp:group {"align":"wide","className":"pz-reveal-stagger","style":{"spacing":{"blockGap":"clamp(10px, 1.2vw, 18px)"}},"layout":{"type":"grid","columnCount":4,"minimumColumnWidth":"9rem"}} -->
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
