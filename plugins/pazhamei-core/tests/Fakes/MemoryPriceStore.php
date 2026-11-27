<?php
/**
 * MemoryPriceStore: plan prices in an array, standing in for the WooCommerce variations.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Services\PriceStore;

/**
 * In-memory prices. Only plan variations marked in $exists can be read or saved.
 */
final class MemoryPriceStore implements PriceStore {

	/** @var array<string, float> "PLAN.TYPE" => rupees. */
	public array $prices = array();

	/** @var array<string, bool> "PLAN.TYPE" => the variation exists. */
	public array $exists = array();

	/**
	 * Every plan × meal type exists, all priced 0 to start.
	 */
	public function __construct() {
		foreach ( array( 'TRIAL', 'D6', 'D26' ) as $plan ) {
			foreach ( array( 'VEG', 'NONVEG', 'MIXED' ) as $type ) {
				$this->exists[ "$plan.$type" ] = true;
				$this->prices[ "$plan.$type" ] = 0.0;
			}
		}
	}

	public function get( string $plan, string $meal_type ): ?float {
		return empty( $this->exists[ "$plan.$meal_type" ] ) ? null : $this->prices[ "$plan.$meal_type" ];
	}

	public function set( string $plan, string $meal_type, float $amount ): bool {
		if ( empty( $this->exists[ "$plan.$meal_type" ] ) ) {
			return false;
		}
		$this->prices[ "$plan.$meal_type" ] = $amount;
		return true;
	}
}
