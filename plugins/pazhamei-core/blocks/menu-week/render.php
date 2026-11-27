<?php
/**
 * Front-end output of the pz/menu-week block (/account/meals).
 *
 * This week's menu (Domain\MenuWeek): one tab per service day, Monday to
 * Saturday, and under it Veg, Non-Veg and Mixed side by side, each with
 * photo, name, short description, calories, protein and allergens. The
 * customer's meal type (current pack, else their dietary preference) is
 * highlighted and comes first on phones. Days without a published menu say
 * "Menu coming soon" (menus are entered in Phase 8).
 *
 * The day tabs switch without a reload (view.js, store pz/menu-week).
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\MenuWeek;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Repositories\MenuRepository;
use Pazhamei\Rest\PortalState;
use Pazhamei\Services\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'This week’s menu shows here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

$pz_engine = Engine::live();
$pz_user   = get_current_user_id();
$pz_today  = $pz_engine->today();
$pz_dates  = MenuWeek::dates( $pz_today );
$pz_menus  = MenuRepository::published( $pz_dates[0], end( $pz_dates ) );

// The customer's meal type: current pack first, else their dietary preference.
$pz_mine = '';
foreach ( $pz_engine->stores->subscriptions->for_customer( $pz_user, PackStatus::CURRENT ) as $pz_pack ) {
	$pz_mine = $pz_pack->meal_type;
	break;
}
if ( '' === $pz_mine ) {
	$pz_profile = $pz_engine->stores->profiles->get( $pz_user );
	$pz_mine    = (string) ( $pz_profile['dietary_preference'] ?? '' );
}

// Meal details for every meal on these menus.
$pz_ids = array();
foreach ( $pz_menus as $pz_by_type ) {
	foreach ( $pz_by_type as $pz_list ) {
		$pz_ids = array_merge( $pz_ids, $pz_list );
	}
}
$pz_ids       = array_values( array_unique( $pz_ids ) );
$pz_allergens = MenuRepository::allergens_for( $pz_ids );
$pz_meals     = array();
foreach ( $pz_ids as $pz_id ) {
	$pz_post = get_post( $pz_id );
	if ( ! $pz_post || 'publish' !== $pz_post->post_status ) {
		continue;
	}
	$pz_meals[ $pz_id ] = array(
		'name'        => get_the_title( $pz_post ),
		'description' => (string) $pz_post->post_excerpt,
		'kcal'        => (int) get_post_meta( $pz_id, '_pz_kcal', true ),
		'protein'     => (int) get_post_meta( $pz_id, '_pz_protein_g', true ),
		'allergens'   => $pz_allergens[ $pz_id ] ?? array(),
		'image'       => (int) get_post_thumbnail_id( $pz_id ),
	);
}

$pz_week    = MenuWeek::build( $pz_today, $pz_menus, $pz_meals, $pz_mine );
$pz_current = in_array( $pz_today, $pz_dates, true ) ? $pz_today : $pz_dates[0];
?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-menu-week pz-portal-block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/menu-week"
	<?php echo wp_interactivity_data_wp_context( array( 'day' => $pz_current ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
>
	<p class="pz-menu-week__range">
		<?php echo esc_html( sprintf( /* translators: 1: first date, 2: last date. */ __( 'Week of %1$s to %2$s', 'pazhamei-core' ), Dates::display( $pz_dates[0] ), Dates::display( end( $pz_dates ) ) ) ); ?>
	</p>

	<div class="pz-menu-week__tabs" role="tablist" aria-label="<?php esc_attr_e( 'Day', 'pazhamei-core' ); ?>">
		<?php foreach ( $pz_week as $pz_day ) : ?>
			<button
				type="button"
				role="tab"
				class="pz-menu-week__tab"
				id="<?php echo esc_attr( 'pz-menu-tab-' . $pz_day['date'] ); ?>"
				aria-controls="<?php echo esc_attr( 'pz-menu-day-' . $pz_day['date'] ); ?>"
				aria-selected="<?php echo $pz_day['date'] === $pz_current ? 'true' : 'false'; ?>"
				data-date="<?php echo esc_attr( $pz_day['date'] ); ?>"
				data-wp-on--click="actions.show"
				data-wp-bind--aria-selected="state.isSelected"
			>
				<span><?php echo esc_html( $pz_day['weekday'] ); ?></span>
				<small><?php echo esc_html( (string) (int) substr( $pz_day['date'], 8 ) ); ?></small>
			</button>
		<?php endforeach; ?>
	</div>

	<?php foreach ( $pz_week as $pz_day ) : ?>
		<section
			class="pz-menu-week__day"
			role="tabpanel"
			id="<?php echo esc_attr( 'pz-menu-day-' . $pz_day['date'] ); ?>"
			aria-labelledby="<?php echo esc_attr( 'pz-menu-tab-' . $pz_day['date'] ); ?>"
			data-date="<?php echo esc_attr( $pz_day['date'] ); ?>"
			data-wp-bind--hidden="!state.isSelected"
			<?php echo $pz_day['date'] === $pz_current ? '' : 'hidden'; ?>
		>
			<h2 class="pz-menu-week__day-title"><?php echo esc_html( $pz_day['display'] ); ?><?php echo $pz_day['isToday'] ? ' · ' . esc_html__( 'Today', 'pazhamei-core' ) : ''; ?></h2>
			<div class="pz-menu-week__types">
				<?php foreach ( $pz_day['types'] as $pz_type ) : ?>
					<article class="pz-menu-type<?php echo $pz_type['isMine'] ? ' is-mine' : ''; ?>">
						<h3 class="pz-menu-type__name">
							<?php echo esc_html( $pz_type['label'] ); ?>
							<?php if ( $pz_type['isMine'] ) : ?>
								<span class="pz-menu-type__mine"><?php esc_html_e( 'Your meal type', 'pazhamei-core' ); ?></span>
							<?php endif; ?>
						</h3>
						<?php if ( $pz_type['comingSoon'] ) : ?>
							<p class="pz-menu-type__soon"><?php esc_html_e( 'Menu coming soon', 'pazhamei-core' ); ?></p>
						<?php endif; ?>
						<?php foreach ( $pz_type['meals'] as $pz_meal ) : ?>
							<div class="pz-meal">
								<?php if ( $pz_meal['image'] > 0 ) : ?>
									<?php echo wp_get_attachment_image( $pz_meal['image'], 'medium_large', false, array( 'class' => 'pz-meal__image', 'alt' => '', 'loading' => 'lazy' ) ); ?>
								<?php endif; ?>
								<p class="pz-meal__name"><?php echo esc_html( $pz_meal['name'] ); ?></p>
								<?php if ( '' !== $pz_meal['description'] ) : ?>
									<p class="pz-meal__desc"><?php echo esc_html( $pz_meal['description'] ); ?></p>
								<?php endif; ?>
								<p class="pz-meal__facts">
									<?php if ( $pz_meal['kcal'] > 0 ) : ?>
										<span><?php echo esc_html( sprintf( /* translators: %d: kilocalories. */ __( '%d kcal', 'pazhamei-core' ), $pz_meal['kcal'] ) ); ?></span>
									<?php endif; ?>
									<?php if ( $pz_meal['protein'] > 0 ) : ?>
										<span><?php echo esc_html( sprintf( /* translators: %d: grams of protein. */ __( '%d g protein', 'pazhamei-core' ), $pz_meal['protein'] ) ); ?></span>
									<?php endif; ?>
								</p>
								<p class="pz-meal__allergens">
									<?php
									echo esc_html(
										$pz_meal['allergens']
											? sprintf( /* translators: %s: allergen names. */ __( 'Contains: %s', 'pazhamei-core' ), implode( ', ', $pz_meal['allergens'] ) )
											: __( 'No listed allergens', 'pazhamei-core' )
									);
									?>
								</p>
							</div>
						<?php endforeach; ?>
					</article>
				<?php endforeach; ?>
			</div>
		</section>
	<?php endforeach; ?>
</div>
