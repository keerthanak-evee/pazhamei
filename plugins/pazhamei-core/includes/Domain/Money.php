<?php
/**
 * Money: shows rupee amounts the Indian way, e.g. ₹1,23,456 or ₹99.50.
 *
 * Indian digit grouping: the last three digits, then groups of two
 * (1,00,000 = one lakh). Whole amounts have no decimals.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rupee formatting.
 */
final class Money {

	/**
	 * Format rupees for display.
	 *
	 * @param float $amount Amount in rupees.
	 */
	public static function rupees( float $amount ): string {
		$negative = $amount < 0;
		$amount   = round( abs( $amount ), 2 );
		$whole    = (int) floor( $amount );
		$paise    = (int) round( ( $amount - $whole ) * 100 );

		$text = self::group( (string) $whole );
		if ( $paise > 0 ) {
			$text .= '.' . str_pad( (string) $paise, 2, '0', STR_PAD_LEFT );
		}
		return ( $negative ? '-' : '' ) . '₹' . $text;
	}

	/**
	 * Add Indian commas to a string of digits: 123456 → 1,23,456.
	 *
	 * @param string $digits Digits only.
	 */
	private static function group( string $digits ): string {
		if ( strlen( $digits ) <= 3 ) {
			return $digits;
		}
		$last3 = substr( $digits, -3 );
		$rest  = substr( $digits, 0, -3 );
		$rest  = (string) preg_replace( '/\B(?=(\d{2})+(?!\d))/', ',', $rest );
		return $rest . ',' . $last3;
	}
}
