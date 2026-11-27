<?php
/**
 * Front-end output of the pz/plan-cards block.
 *
 * One cream card per plan (Trial, 6-day, 26-day) from Domain\Plans: name, chip,
 * summary, the "what's included" list (edited in Plans & Prices) and the actions.
 *
 * Two modes (the wrapper's data-mode):
 * - "enquire" (visitors): an Enquire button to /about-us/?plan=<plan>#contact. No prices.
 * - "buy" (signed-in customers): a Veg / Non-Veg / Mixed choice, the price of
 *   the chosen type (from Plans & Prices, i.e. the WooCommerce variations) and
 *   a Buy button that goes straight to the checkout (Woo\BuyLink), next to
 *   Enquire. The Trial card shows only when trials are on and the customer
 *   can still have one. Switching the meal type updates the price and the
 *   Buy link without a reload (view.js, Interactivity API).
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\Plans;
use Pazhamei\Install\MediaImporter;
use Pazhamei\Services\Engine;
use Pazhamei\Services\PriceBook;
use Pazhamei\Settings;
use Pazhamei\Woo\BuyLink;
use Pazhamei\Woo\PlanProducts;
use Pazhamei\Woo\WooPriceStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pz_customer    = is_user_logged_in() && current_user_can( 'pz_customer' ) && ! wp_is_serving_rest_request();
$pz_mode        = $pz_customer ? 'buy' : 'enquire';
$pz_show_images = ! empty( $attributes['showImages'] );
$pz_book        = new PriceBook( new WooPriceStore() );

// The meal type chosen at first: the customer's dietary preference, else their latest pack's type, else Veg.
$pz_default = 'VEG';
if ( $pz_customer ) {
	$pz_engine  = Engine::live();
	$pz_user_id = get_current_user_id();
	$pz_profile = $pz_engine->stores->profiles->get( $pz_user_id );
	$pz_packs   = $pz_engine->stores->subscriptions->for_customer( $pz_user_id );
	$pz_pref    = (string) ( $pz_profile['dietary_preference'] ?? '' );
	if ( Plans::is_meal_type( $pz_pref ) ) {
		$pz_default = $pz_pref;
	} elseif ( $pz_packs ) {
		$pz_default = end( $pz_packs )->meal_type;
	}
	$pz_trial_ok = (bool) Settings::get( 'trial_enabled' ) && null === BuyLink::trial_refusal( $pz_user_id );
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-plan-cards' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?> data-mode="<?php echo esc_attr( $pz_mode ); ?>" <?php echo $pz_customer ? 'data-wp-interactive="pz/plan-cards"' : ''; ?>>
	<?php
	if ( $pz_customer && function_exists( 'woocommerce_output_all_notices' ) ) {
		woocommerce_output_all_notices(); // e.g. "You have already had a trial." from a Buy link.
	}
	?>
	<?php foreach ( Plans::all() as $pz_slug => $pz_plan ) : ?>
		<?php
		if ( $pz_customer && 'TRIAL' === $pz_plan['code'] && ! $pz_trial_ok ) {
			continue; // Trial only when it is on and the customer is eligible.
		}

		$pz_context = array();
		if ( $pz_customer ) {
			$pz_prices = $pz_book->card_prices( $pz_plan['code'] );
			$pz_links  = array();
			foreach ( array_keys( $pz_prices ) as $pz_type ) {
				$pz_links[ $pz_type ] = BuyLink::url( PlanProducts::find_variation( $pz_plan['code'], $pz_type ) );
			}
			$pz_type    = isset( $pz_prices[ $pz_default ] ) ? $pz_default : (string) array_key_first( $pz_prices );
			$pz_context = array(
				'type'   => $pz_type,
				'prices' => array_map( static fn( $p ) => $p['display'], $pz_prices ),
				'links'  => $pz_links,
			);
		}
		?>
		<article class="pz-plan-card pz-hover-lift" data-plan="<?php echo esc_attr( $pz_slug ); ?>" <?php echo $pz_customer ? wp_interactivity_data_wp_context( $pz_context ) : ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
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
				<?php foreach ( Settings::plan_includes( $pz_slug ) as $pz_item ) : ?>
					<li><?php echo esc_html( $pz_item ); ?></li>
				<?php endforeach; ?>
			</ul>

			<?php if ( $pz_customer && $pz_context['prices'] ) : ?>
				<fieldset class="pz-plan-card__types">
					<legend class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: plan name. */ __( 'Meal type for %s', 'pazhamei-core' ), $pz_plan['name'] ) ); ?></legend>
					<?php foreach ( array_keys( $pz_context['prices'] ) as $pz_type ) : ?>
						<label class="pz-plan-card__type">
							<input type="radio" name="<?php echo esc_attr( 'pz-type-' . $pz_slug ); ?>" value="<?php echo esc_attr( $pz_type ); ?>" <?php checked( $pz_context['type'], $pz_type ); ?> data-wp-on--change="actions.choose" />
							<span><?php echo esc_html( PlanProducts::OPTIONS[ $pz_type ] ); ?></span>
						</label>
					<?php endforeach; ?>
				</fieldset>
				<div class="pz-plan-card__price">
					<span class="pz-plan-card__amount" data-wp-text="state.price"><?php echo esc_html( $pz_context['prices'][ $pz_context['type'] ] ); ?></span>
					<span class="pz-plan-card__per"><?php echo esc_html( 1 === $pz_plan['meals'] ? __( 'for 1 lunch', 'pazhamei-core' ) : sprintf( /* translators: %d: number of lunches. */ __( 'for %d lunches', 'pazhamei-core' ), $pz_plan['meals'] ) ); ?></span>
				</div>
			<?php else : ?>
				<div class="pz-plan-card__price"></div>
			<?php endif; ?>

			<div class="pz-plan-card__actions wp-block-buttons">
				<?php if ( $pz_customer && $pz_context['prices'] ) : ?>
					<div class="wp-block-button">
						<a class="wp-block-button__link wp-element-button pz-plan-card__buy" href="<?php echo esc_url( $pz_context['links'][ $pz_context['type'] ] ); ?>" data-wp-bind--href="state.link">
							<?php echo esc_html__( 'Buy', 'pazhamei-core' ); ?>
							<span class="screen-reader-text"><?php echo esc_html( $pz_plan['name'] ); ?></span>
						</a>
					</div>
				<?php endif; ?>
				<div class="wp-block-button<?php echo $pz_customer ? ' is-style-outline' : ''; ?>">
					<a class="wp-block-button__link wp-element-button pz-plan-card__enquire" href="<?php echo esc_url( add_query_arg( 'plan', $pz_slug, home_url( '/about-us/' ) ) . '#contact' ); ?>">
						<?php echo esc_html__( 'Enquire', 'pazhamei-core' ); ?>
						<span class="screen-reader-text"><?php echo esc_html( sprintf( /* translators: %s: plan name. */ __( 'about %s', 'pazhamei-core' ), $pz_plan['name'] ) ); ?></span>
					</a>
				</div>
			</div>
		</article>
	<?php endforeach; ?>
</div>
