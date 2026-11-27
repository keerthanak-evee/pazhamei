<?php
/**
 * PriceBook: the one place that saves and reads plan prices.
 *
 * - The Plans & Prices screen saves through save().
 * - The plan cards show card_price().
 * - The checkout charges the WooCommerce variation price, which is the same
 *   stored value (PriceStore), so the three can never disagree.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Money;
use Pazhamei\Domain\Plans;
use Pazhamei\Domain\PriceGrid;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plan prices.
 */
final class PriceBook {

	private PriceStore $store;

	/**
	 * @param PriceStore $store Where prices live.
	 */
	public function __construct( PriceStore $store ) {
		$this->store = $store;
	}

	/**
	 * Check and save the whole grid. Nothing is saved if any cell is wrong.
	 *
	 * @param array<string, mixed> $input price[PLAN][MEAL_TYPE] => text.
	 * @return array<string, string> Errors keyed "PLAN.MEAL_TYPE" (empty when saved).
	 */
	public function save( array $input ): array {
		$checked = PriceGrid::check_prices( $input );
		if ( $checked['errors'] ) {
			return $checked['errors'];
		}

		$errors = array();
		foreach ( $checked['prices'] as $plan => $by_type ) {
			foreach ( $by_type as $type => $amount ) {
				if ( ! $this->store->set( $plan, $type, $amount ) ) {
					$errors[ $plan . '.' . $type ] = 'This plan product is missing. Run wp pz install-products.';
				}
			}
		}
		return $errors;
	}

	/**
	 * The stored price, or null.
	 *
	 * @param string $plan      Plan code.
	 * @param string $meal_type Meal type code.
	 */
	public function price( string $plan, string $meal_type ): ?float {
		return $this->store->get( $plan, $meal_type );
	}

	/**
	 * Prices for one plan card: meal type => amount and display text ("₹900").
	 * Meal types without a product are left out.
	 *
	 * @param string $plan Plan code.
	 * @return array<string, array{amount: float, display: string}>
	 */
	public function card_prices( string $plan ): array {
		$out = array();
		foreach ( array_keys( Plans::MEAL_TYPES ) as $type ) {
			$amount = $this->store->get( $plan, $type );
			if ( null !== $amount ) {
				$out[ $type ] = array(
					'amount'  => $amount,
					'display' => Money::rupees( $amount ),
				);
			}
		}
		return $out;
	}

	/**
	 * The whole grid, for the admin screen: plan => meal type => amount (null if missing).
	 *
	 * @return array<string, array<string, ?float>>
	 */
	public function grid(): array {
		$out = array();
		foreach ( PriceGrid::PLANS as $plan ) {
			foreach ( array_keys( Plans::MEAL_TYPES ) as $type ) {
				$out[ $plan ][ $type ] = $this->store->get( $plan, $type );
			}
		}
		return $out;
	}
}
