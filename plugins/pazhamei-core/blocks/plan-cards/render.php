<?php
/**
 * Front-end output of the pz/plan-cards block.
 *
 * One cream card per plan (Trial, 6-day, 26-day) from Domain\Plans: name, chip,
 * summary, a tick list and the actions. Visitors get an Enquire button to the
 * enquiry form on /about-us/#contact, with the plan pre-selected. No prices.
 *
 * Each card has fixed slots, so Phase 4 can add the signed-in "price mode"
 * without changing the design:
 * - .pz-plan-card__price   (empty for visitors; Phase 4 prints the price here)
 * - .pz-plan-card__actions (Enquire now; Phase 4 adds Buy next to it)
 * The wrapper's data-mode says which mode the cards are in ("enquire" or, later, "buy").
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\Plans;
use Pazhamei\Install\MediaImporter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pz_mode        = 'enquire'; // Phase 4: 'buy' for signed-in customers.
$pz_show_images = ! empty( $attributes['showImages'] );
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-plan-cards' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?> data-mode="<?php echo esc_attr( $pz_mode ); ?>">
	<?php foreach ( Plans::all() as $pz_slug => $pz_plan ) : ?>
		<article class="pz-plan-card pz-hover-lift" data-plan="<?php echo esc_attr( $pz_slug ); ?>">
			<?php if ( $pz_show_images ) : ?>
				<?php $pz_image = MediaImporter::find( $pz_plan['image'] ); ?>
				<?php if ( $pz_image > 0 ) : ?>
					<?php echo wp_get_attachment_image( $pz_image, 'large', false, array( 'class' => 'pz-plan-card__image' ) ); ?>
				<?php endif; ?>
			<?php endif; ?>

			<div class="pz-plan-card__head">
				<h3 class="pz-plan-card__name"><?php echo esc_html( $pz_plan['name'] ); ?></h3>
				<p class="pz-plan-card__chip"><?php echo esc_html( $pz_plan['label'] ); ?></p>
			</div>

			<p class="pz-plan-card__summary"><?php echo esc_html( $pz_plan['summary'] ); ?></p>

			<ul class="pz-plan-card__includes">
				<?php foreach ( $pz_plan['includes'] as $pz_item ) : ?>
					<li><?php echo esc_html( $pz_item ); ?></li>
				<?php endforeach; ?>
			</ul>

			<div class="pz-plan-card__price"></div>

			<div class="pz-plan-card__actions wp-block-buttons">
				<div class="wp-block-button">
					<a class="wp-block-button__link wp-element-button pz-plan-card__enquire" href="<?php echo esc_url( add_query_arg( 'plan', $pz_slug, home_url( '/about-us/' ) ) . '#contact' ); ?>">
						<?php echo esc_html__( 'Enquire', 'pazhamei-core' ); ?>
						<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: plan name. */ __( 'about %s', 'pazhamei-core' ), $pz_plan['name'] ) ); ?></span>
					</a>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
</div>
