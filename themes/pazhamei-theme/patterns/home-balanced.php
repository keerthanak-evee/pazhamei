<?php
/**
 * Title: Home: Balance in every bite
 * Slug: pazhamei/home-balanced
 * Categories: pazhamei-home
 * Description: Grain photo beside the five parts of a Pazhamei lunch.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_parts = array(
	array( 'Wholesome grains', 'Ponni, red rice, millets and more' ),
	array( 'Protein-rich mains', 'Vegetarian and non-vegetarian' ),
	array( 'Seasonal vegetables', 'Poriyal or salad' ),
	array( 'Traditional gravies', 'Sambar, kuzhambu, kootu and more' ),
	array( 'To finish', 'Fruit, curd or buttermilk' ),
);
?>
<!-- wp:group {"align":"full","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}},"color":{"background":"#f6ead3"}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull has-background" style="background-color:#f6ead3;padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"var:preset|spacing|60"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"35%","className":"pz-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center pz-reveal" style="flex-basis:35%"><?php echo pz_theme_image_block( 'balance-grains', 'is-style-pz-leaf-corner pz-hover-zoom' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"65%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:65%"><!-- wp:heading {"textColor":"oxblood","style":{"typography":{"fontSize":"clamp(1.9rem, 3vw, 3rem)","lineHeight":"1.05"}}} -->
<h2 class="wp-block-heading has-oxblood-color has-text-color" style="font-size:clamp(1.9rem, 3vw, 3rem);line-height:1.05">Tradition on your plate.<br><em>Balance in every bite.</em></h2>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#614e3c"}}} -->
<p class="has-text-color" style="color:#614e3c">Five parts come together in a Pazhamei lunch. The grains and sides change through the week.</p>
<!-- /wp:paragraph -->

<!-- wp:group {"className":"pz-divided pz-reveal-stagger","style":{"spacing":{"blockGap":"0"}},"layout":{"type":"grid","minimumColumnWidth":"8rem"}} -->
<div class="wp-block-group pz-divided pz-reveal-stagger"><?php foreach ( $pz_parts as $pz_part ) : ?><!-- wp:group {"style":{"spacing":{"padding":{"left":"0.5rem","right":"0.5rem"},"blockGap":"0.2rem"}},"layout":{"type":"default"}} -->
<div class="wp-block-group" style="padding-right:0.5rem;padding-left:0.5rem"><!-- wp:paragraph {"align":"center","style":{"typography":{"fontWeight":"700","lineHeight":"1.15"},"color":{"text":"#4d2b1e"}},"fontSize":"large","fontFamily":"heading"} -->
<p class="has-text-align-center has-text-color has-heading-font-family has-large-font-size" style="color:#4d2b1e;font-weight:700;line-height:1.15"><?php echo esc_html( $pz_part[0] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"align":"center","fontSize":"small"} -->
<p class="has-text-align-center has-small-font-size"><?php echo esc_html( $pz_part[1] ); ?></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->

<?php endforeach; ?></div>
<!-- /wp:group --></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
