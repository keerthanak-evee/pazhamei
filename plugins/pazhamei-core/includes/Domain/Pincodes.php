<?php
/**
 * Pincodes: may we deliver to this pincode?
 *
 * The served pincodes are the `delivery_pincodes` setting. [EDIT] While that
 * list is empty, every valid pincode is accepted (owner decision, Phase 4),
 * so checkout works before the delivery areas are entered.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivery pincode check.
 */
final class Pincodes {

	/**
	 * Why we can't deliver to this pincode, or null if we can.
	 *
	 * @param string   $pincode Delivery pincode as typed.
	 * @param string[] $served  Served pincodes; empty = all.
	 */
	public static function refusal( string $pincode, array $served ): ?string {
		$pincode = trim( $pincode );
		if ( ! Registration::is_pincode( $pincode ) ) {
			return 'Please enter a valid 6-digit delivery pincode.';
		}

		$served = self::clean( $served );
		if ( $served && ! in_array( $pincode, $served, true ) ) {
			return 'Sorry, we don’t deliver to ' . $pincode . ' yet. Talk to the kitchen and we’ll let you know when we do.';
		}
		return null;
	}

	/**
	 * The served list as clean 6-digit strings (the setting may hold numbers or spaces).
	 *
	 * @param array<int, mixed> $served Setting value.
	 * @return string[]
	 */
	public static function clean( array $served ): array {
		$out = array();
		foreach ( $served as $pincode ) {
			$pincode = trim( (string) $pincode );
			if ( Registration::is_pincode( $pincode ) ) {
				$out[] = $pincode;
			}
		}
		return array_values( array_unique( $out ) );
	}
}
