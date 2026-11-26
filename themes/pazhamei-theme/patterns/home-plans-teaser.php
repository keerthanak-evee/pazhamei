<?php
/**
 * Title: Home: Meal plans teaser
 * Slug: pazhamei/home-plans-teaser
 * Categories: pazhamei-home
 * Description: Oxblood band with the three packs (Trial, 6-day, 26-day) as cream cards with Enquire buttons. No prices.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_plans = array(
	array( 'plan-tasting', 'The Tasting Experience', '1-day plan', 'Experience Pazhamei for a day before settling into a subscription.', array( 'One lunch in the noon to 1 pm delivery window', 'Choose vegetarian, non-vegetarian or mixed' ), 'trial' ),
	array( 'plan-weekly', 'The Weekly Table', '6-day plan', 'Six thoughtfully planned lunches, from Monday to Saturday.', array( 'Six lunches on our Monday to Saturday schedule', 'See the calories and macros for each lunch' ), '6-day' ),
	array( 'plan-monthly', 'The Monthly Tradition', '26-day plan', 'Your everyday lunch routine, taken care of. Keep your place at the table even when your schedule changes.', array( 'Twenty-six lunches on service days', 'Skip or pause by 8 pm the day before' ), '26-day' ),
);
?>
<!-- wp:group {"align":"full","className":"is-style-pz-heritage-top pz-plans-band","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"backgroundColor":"oxblood","layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull is-style-pz-heritage-top pz-plans-band has-oxblood-background-color has-background" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:heading {"textAlign":"center","className":"pz-flourish","textColor":"cream","style":{"typography":{"fontSize":"clamp(2.2rem, 3.4vw, 3.4rem)"}}} -->
<h2 class="wp-block-heading has-text-align-center pz-flourish has-cream-color has-text-color" style="font-size:clamp(2.2rem, 3.4vw, 3.4rem)">Our meal plans</h2>
<!-- /wp:heading -->

<!-- wp:columns {"align":"wide","className":"pz-reveal-stagger"} -->
<div class="wp-block-columns alignwide pz-reveal-stagger"><?php foreach ( $pz_plans as $pz_plan ) : ?><!-- wp:column -->
<div class="wp-block-column"><!-- wp:group {"className":"is-style-pz-paper-card pz-hover-lift pz-hover-zoom","layout":{"type":"default"}} -->
<div class="wp-block-group is-style-pz-paper-card pz-hover-lift pz-hover-zoom"><?php echo pz_theme_image_block( $pz_plan[0] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?>

<!-- wp:heading {"level":3,"style":{"typography":{"fontSize":"clamp(1.45rem, 2.2vw, 2rem)"}}} -->
<h3 class="wp-block-heading" style="font-size:clamp(1.45rem, 2.2vw, 2rem)"><?php echo esc_html( $pz_plan[1] ); ?></h3>
<!-- /wp:heading -->

<!-- wp:paragraph {"className":"pz-chip","style":{"typography":{"fontWeight":"700"}},"fontSize":"small"} -->
<p class="pz-chip has-small-font-size" style="font-weight:700">✧ <?php echo esc_html( $pz_plan[2] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:paragraph {"fontSize":"small"} -->
<p class="has-small-font-size"><?php echo esc_html( $pz_plan[3] ); ?></p>
<!-- /wp:paragraph -->

<!-- wp:list {"className":"pz-checklist","fontSize":"small"} -->
<ul class="wp-block-list pz-checklist has-small-font-size"><?php foreach ( $pz_plan[4] as $pz_item ) : ?><!-- wp:list-item -->
<li><?php echo esc_html( $pz_item ); ?></li>
<!-- /wp:list-item --><?php endforeach; ?></ul>
<!-- /wp:list -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"pz-arrow pz-button-oxblood"} -->
<div class="wp-block-button pz-arrow pz-button-oxblood"><a class="wp-block-button__link wp-element-button" href="/enquire?plan=<?php echo esc_attr( $pz_plan[5] ); ?>">Enquire</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div>
<!-- /wp:column -->

<?php endforeach; ?></div>
<!-- /wp:columns -->

<!-- wp:paragraph {"align":"center"} -->
<p class="has-text-align-center"><a href="/plans">Compare all plans</a></p>
<!-- /wp:paragraph --></div>
<!-- /wp:group -->
