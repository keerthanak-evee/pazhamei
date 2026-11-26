<?php
/**
 * Title: Home: Hero
 * Slug: pazhamei/home-hero
 * Categories: pazhamei-home
 * Description: Maroon hero with the banana-leaf lunch photo, headline and two buttons.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

$pz_hero = pz_theme_image( 'hero-lunch', 'full' );
$pz_grad = 'linear-gradient(90deg,rgb(71,13,13) 0%,rgb(71,13,13) 34%,rgba(71,13,13,0.9) 44%,rgba(61,17,10,0.35) 58%,rgba(61,17,10,0) 72%)';
$pz_attr = array(
	'dimRatio'       => 100,
	'customGradient' => $pz_grad,
	'focalPoint'     => array(
		'x' => 0.75,
		'y' => 0.45,
	),
	'minHeight'      => 560,
	'isDark'         => true,
	'align'          => 'full',
	'className'      => 'pz-hero',
	'layout'         => array( 'type' => 'constrained' ),
);
if ( $pz_hero['id'] > 0 ) {
	$pz_attr = array(
		'url' => $pz_hero['url'],
		'id'  => $pz_hero['id'],
		'alt' => 'A banana-leaf lunch with rice, fish fry and curries',
	) + $pz_attr;
}
?>
<!-- wp:cover <?php echo wp_json_encode( $pz_attr, JSON_UNESCAPED_SLASHES ); ?> -->
<div class="wp-block-cover alignfull pz-hero" style="min-height:560px"><span aria-hidden="true" class="wp-block-cover__background has-background-dim-100 has-background-dim<?php echo $pz_hero['id'] > 0 ? ' wp-block-cover__gradient-background' : ''; ?> has-background-gradient" style="background:<?php echo esc_attr( $pz_grad ); ?>"></span><?php if ( $pz_hero['id'] > 0 ) : ?><img class="wp-block-cover__image-background wp-image-<?php echo (int) $pz_hero['id']; ?>" alt="A banana-leaf lunch with rice, fish fry and curries" src="<?php echo esc_url( $pz_hero['url'] ); ?>" style="object-position:75% 45%" data-object-fit="cover" data-object-position="75% 45%"/><?php endif; ?><div class="wp-block-cover__inner-container"><!-- wp:group {"align":"wide","layout":{"type":"constrained","contentSize":"560px","justifyContent":"left"}} -->
<div class="wp-block-group alignwide"><!-- wp:paragraph {"style":{"typography":{"letterSpacing":"0.22em","fontWeight":"700"}},"textColor":"gold-light","fontSize":"small","fontFamily":"brand"} -->
<p class="has-gold-light-color has-text-color has-brand-font-family has-small-font-size" style="font-weight:700;letter-spacing:0.22em">PAZHAMEI</p>
<!-- /wp:paragraph -->

<!-- wp:heading {"level":1,"style":{"typography":{"fontSize":"clamp(2.4rem, 4.6vw, 4.5rem)","lineHeight":"1","letterSpacing":"-0.03em","fontWeight":"600"}},"textColor":"cream"} -->
<h1 class="wp-block-heading has-cream-color has-text-color" style="font-size:clamp(2.4rem, 4.6vw, 4.5rem);font-weight:600;letter-spacing:-0.03em;line-height:1">A taste of Tamil heritage, <em class="has-inline-color has-gold-light-color">thoughtfully reimagined for everyday living</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph {"style":{"color":{"text":"#f6e7d6"}}} -->
<p class="has-text-color" style="color:#f6e7d6">From traditional Tamil kitchens to your everyday lunch table. Prepared each morning in Guindy and delivered across Chennai.</p>
<!-- /wp:paragraph -->

<!-- wp:buttons -->
<div class="wp-block-buttons"><!-- wp:button {"className":"is-style-pz-cream pz-arrow"} -->
<div class="wp-block-button is-style-pz-cream pz-arrow"><a class="wp-block-button__link wp-element-button" href="/plans">Explore meal plans</a></div>
<!-- /wp:button -->

<!-- wp:button {"className":"is-style-pz-ghost-light"} -->
<div class="wp-block-button is-style-pz-ghost-light"><a class="wp-block-button__link wp-element-button" href="/about-contact">Discover our story</a></div>
<!-- /wp:button --></div>
<!-- /wp:buttons --></div>
<!-- /wp:group --></div></div>
<!-- /wp:cover -->
