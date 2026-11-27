<?php
/**
 * CustomerInfo: where the engine gets a pack's delivery address for the
 * daily order snapshot. The live site reads it from the WooCommerce order
 * (Woo\OrderAddress); tests use a fixed address.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Pack;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivery address lookup.
 */
interface CustomerInfo {

	/**
	 * The delivery address for a pack.
	 *
	 * @param Pack $pack Pack.
	 * @return array<string, string> name, phone, address_1, address_2, city, postcode.
	 */
	public function delivery_address( Pack $pack ): array;
}
