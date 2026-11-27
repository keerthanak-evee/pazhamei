<?php
/**
 * Title: Plans: Hero
 * Slug: pazhamei/page-plans-hero
 * Categories: pazhamei-pages
 * Description: Big title and intro on the left, a lunch photo in an arch frame on the right.
 * Viewport Width: 1400
 *
 * @package pazhamei-theme
 */

?>
<!-- wp:group {"align":"full","className":"pz-page-hero","style":{"spacing":{"padding":{"top":"var:preset|spacing|60","bottom":"var:preset|spacing|60"}}},"layout":{"type":"constrained"}} -->
<div class="wp-block-group alignfull pz-page-hero" style="padding-top:var(--wp--preset--spacing--60);padding-bottom:var(--wp--preset--spacing--60)"><!-- wp:columns {"verticalAlignment":"center","align":"wide","style":{"spacing":{"blockGap":{"left":"8%"}}}} -->
<div class="wp-block-columns alignwide are-vertically-aligned-center"><!-- wp:column {"verticalAlignment":"center","width":"56%","className":"pz-reveal"} -->
<div class="wp-block-column is-vertically-aligned-center pz-reveal" style="flex-basis:56%"><!-- wp:heading {"level":1} -->
<h1 class="wp-block-heading">Thoughtfully planned meals,<br><em>tracked to the macro.</em></h1>
<!-- /wp:heading -->

<!-- wp:paragraph -->
<p>Every lunch from our Guindy kitchen pairs heirloom grains with a portion of protein, vegetables and a daily calorie breakdown.</p>
<!-- /wp:paragraph --></div>
<!-- /wp:column -->

<!-- wp:column {"verticalAlignment":"center","width":"44%"} -->
<div class="wp-block-column is-vertically-aligned-center" style="flex-basis:44%"><?php echo pz_theme_image_block( 'plans-hero', 'is-style-pz-arch' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Escaped inside the helper. ?></div>
<!-- /wp:column --></div>
<!-- /wp:columns --></div>
<!-- /wp:group -->
