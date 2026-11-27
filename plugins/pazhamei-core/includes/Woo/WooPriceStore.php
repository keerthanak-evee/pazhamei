<?php
/**
 * WooPriceStore: plan prices kept on the WooCommerce plan variations.
 *
 * WooCommerce is the only source of prices (owner decision, Phase 4). A
 * price is the variation's regular price; any sale price is cleared, so the
 * checkout charges exactly what Plans & Prices shows.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Services\PriceStore;
use WC_Product_Variable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Prices on WooCommerce variations.
 */
final class WooPriceStore implements PriceStore {

	/**
	 * {@inheritDoc}
	 *
	 * @param string $plan      Plan code.
	 * @param string $meal_type Meal type code.
	 */
	public function get( string $plan, string $meal_type ): ?float {
		$variation = wc_get_product( PlanProducts::find_variation( $plan, $meal_type ) );
		if ( ! $variation ) {
			return null;
		}
		$price = (string) $variation->get_price( 'edit' );
		return '' === $price ? null : (float) $price;
	}

	/**
	 * {@inheritDoc}
	 *
	 * @param string $plan      Plan code.
	 * @param string $meal_type Meal type code.
	 * @param float  $amount    Rupees.
	 */
	public function set( string $plan, string $meal_type, float $amount ): bool {
		$variation = wc_get_product( PlanProducts::find_variation( $plan, $meal_type ) );
		if ( ! $variation ) {
			return false;
		}
		$text = wc_format_decimal( $amount, 2, true );
		$variation->set_regular_price( $text );
		$variation->set_sale_price( '' );
		$variation->set_price( $text );
		$variation->save();
		WC_Product_Variable::sync( $variation->get_parent_id() );
		return true;
	}
}
