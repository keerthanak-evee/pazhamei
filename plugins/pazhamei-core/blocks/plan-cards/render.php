<?php
/**
 * Front-end output of the pz/plan-cards block.
 *
 * One card per plan (Trial, 6-day, 26-day) from Domain\Plans, with a photo,
 * what is included and an Enquire button to /enquire?plan=<plan>.
 * No prices: signed-in customers get prices and a Buy button in Phase 4.
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
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-plan-cards' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
	<?php foreach ( Plans::all() as $pz_slug => $pz_plan ) : ?>
		<?php $pz_image = MediaImporter::find( $pz_plan['image'] ); ?>
		<article class="pz-plan-card pz-hover-lift pz-hover-zoom">
			<?php if ( $pz_image > 0 ) : ?>
				<?php echo wp_get_attachment_image( $pz_image, 'large', false, array( 'class' => 'pz-plan-card__image' ) ); ?>
			<?php endif; ?>
			<div class="pz-plan-card__body">
				<h2 class="pz-plan-card__name"><?php echo esc_html( $pz_plan['name'] ); ?></h2>
				<p class="pz-plan-card__label">✧ <?php echo esc_html( $pz_plan['label'] ); ?></p>
				<p class="pz-plan-card__summary"><?php echo esc_html( $pz_plan['summary'] ); ?></p>
				<ul class="pz-plan-card__includes">
					<?php foreach ( $pz_plan['includes'] as $pz_item ) : ?>
						<li><?php echo esc_html( $pz_item ); ?></li>
					<?php endforeach; ?>
				</ul>
				<div class="wp-block-buttons">
					<div class="wp-block-button pz-arrow">
						<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( add_query_arg( 'plan', $pz_slug, home_url( '/enquire/' ) ) ); ?>">
							<?php echo esc_html__( 'Enquire', 'pazhamei-core' ); ?>
							<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: plan name. */ __( 'about %s', 'pazhamei-core' ), $pz_plan['name'] ) ); ?></span>
						</a>
					</div>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
</div>
