<?php
/**
 * PriceStore: where plan prices are kept.
 *
 * On the live site that is WooCommerce (Woo\WooPriceStore: the regular price
 * of each plan variation), so the checkout always charges exactly what is
 * stored. Tests use an in-memory store.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Read and write one price per plan and meal type.
 */
interface PriceStore {

	/**
	 * The price in rupees, or null if that plan variation doesn't exist.
	 *
	 * @param string $plan      TRIAL, D6 or D26.
	 * @param string $meal_type VEG, NONVEG or MIXED.
	 */
	public function get( string $plan, string $meal_type ): ?float;

	/**
	 * Save a price. Returns false if that plan variation doesn't exist.
	 *
	 * @param string $plan      TRIAL, D6 or D26.
	 * @param string $meal_type VEG, NONVEG or MIXED.
	 * @param float  $amount    Rupees.
	 */
	public function set( string $plan, string $meal_type, float $amount ): bool;
}
