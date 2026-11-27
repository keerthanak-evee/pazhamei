<?php
/**
 * Title: About Us: Hero
 * Slug: pazhamei/page-about-hero
 * Categories: pazhamei-pages
 * Description: Big title on the left; a tall arch photo on the right that hangs over the band below.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

?>
<!-- wp:group {"align":"full","className":"pz-page-hero pz-hang-over-next","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-page-hero pz-hang-over-next" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"8%"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"58%","className":"pz-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center pz-reveal" style="flex-basis:58%"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Rooted in Tamil culinary heritage,<br><em>crafted for today.</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Prepared fresh each morning in our Guindy kitchen and delivered across Chennai.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"42%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:42%"><?php echo pz_theme_image_block( 'about-vessels', 'is-style-pz-arch pz-arch-tall' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
