<?php
/**
 * Title: Plans: Sample menu (Veg, Non-Veg, Mixed tabs)
 * Slug: pazhamei/page-plans-menu
 * Categories: pazhamei-pages
 * Description: Three meal types as tabs, each with a sample menu table (Monday and Saturday). Sample only: Phase 8 replaces it with the real weekly menu (pz/menu-week).
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

// Sample lunches from the prototype (week 1): day, grain, protein, gravy, vegetables, fruit or probiotic.
$pz_menus = array(
	'Vegetarian'     => array(
		array( 'Monday', 'Jeera Rice', 'Konda Kadalai Sundal', 'Soya Varutharacha Curry', 'Yellow Pumpkin Poriyal', 'Papaya' ),
		array( 'Saturday', 'Ponni White Rice', 'Rajma Masala', 'Kerala Theeyal', 'Cucumber Salad', 'Pickled Pineapple' ),
	),
	'Non-Vegetarian' => array(
		array( 'Monday', 'Jeera Rice', 'Chicken Tikka', 'Vegetable Kurma', 'Yellow Pumpkin Poriyal', 'Papaya' ),
		array( 'Saturday', 'Ponni White Rice', 'Fish Lemon Garlic', 'Kerala Theeyal', 'Cucumber & Tomato Salad', 'Pickled Pineapple' ),
	),
	'Mixed'          => array(
		array( 'Monday', 'Jeera Rice', 'Chicken Tikka', 'Soya Varutharacha Curry', 'Yellow Pumpkin Poriyal', 'Papaya' ),
		array( 'Saturday', 'Ponni White Rice', 'Soya Chunks Pepper Fry', 'Kerala Theeyal', 'Cucumber Salad', 'Pickled Pineapple' ),
	),
);
$pz_columns = array( 'Day', 'Grain', 'Protein', 'Gravy', 'Vegetables', 'Fruit / probiotic' );
?>
<!-- wp:group {"align":"full","className":"pz-paper-section","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-paper-section" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","align":"wide","className":"pz-section-title"} -->
<h2 class="wp-block-heading alignwide has-text-align-center pz-section-title">Select your dietary preference &amp; explore the menu</h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><span class="pz-stamp">Sample menu</span></p>
<!-- /wp:paragraph -->

<!-- wp:group {"align":"wide","className":"is-style-pz-tabs pz-reveal","layout":{"type":"default"}} -->
<div class="wp-block-group alignwide is-style-pz-tabs pz-reveal"><?php foreach ( $pz_menus as $pz_type => $pz_rows ) : ?><!-- wp:group {"layout":{"type":"default"}} -->
<div class="wp-block-group"><!-- wp:heading {"level":3} -->
<h3 class="wp-block-heading"><?php echo esc_html( $pz_type ); ?></h3>
<!-- /wp:heading -->

<!-- wp:table {"className":"is-style-pz-menu-table"} -->
<figure class="wp-block-table is-style-pz-menu-table"><table class="has-fixed-layout"><thead><tr><?php foreach ( $pz_columns as $pz_column ) : ?><th><?php echo esc_html( $pz_column ); ?></th><?php endforeach; ?></tr></thead><tbody><?php foreach ( $pz_rows as $pz_row ) : ?><tr><?php foreach ( $pz_row as $pz_cell ) : ?><td><?php echo esc_html( $pz_cell ); ?></td><?php endforeach; ?></tr><?php endforeach; ?></tbody></table><figcaption class="wp-element-caption">Sample menu: <?php echo esc_html( strtolower( $pz_type ) ); ?> lunches for a Monday and a Saturday. The menu changes every week.</figcaption></figure>
<!-- /wp:table --></div>
<!-- /wp:group --><?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:group -->
