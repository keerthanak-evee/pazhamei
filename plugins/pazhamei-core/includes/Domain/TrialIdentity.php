<?php
/**
 * TrialIdentity: turns a mobile number and an address into comparison keys
 * for the one-trial-per-customer rule (business rule 10).
 *
 * Mobile: digits only, last 10 digits. So "+91 98765 43210", "098765-43210"
 * and "9876543210" all give "9876543210".
 *
 * Address: pincode + house or flat number. Punctuation and filler words
 * ("no", "flat", "door" ...) are removed, so "Flat No. 4-B, Lotus Apts"
 * and "4B Lotus Apartments" at the same pincode give the same key.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Normalisation for trial eligibility.
 */
final class TrialIdentity {

	/** Words that only describe the number and are removed before comparing. */
	public const FILLER_WORDS = array( 'no', 'nos', 'num', 'number', 'flat', 'door', 'house', 'h', 'apt', 'apartment' );

	/**
	 * Mobile key: the last 10 digits, or '' if there are fewer than 10.
	 *
	 * @param string $mobile Any format.
	 */
	public static function mobile_key( string $mobile ): string {
		$digits = (string) preg_replace( '/\D+/', '', $mobile );
		return strlen( $digits ) >= 10 ? substr( $digits, -10 ) : '';
	}

	/**
	 * The house or flat number from an address line: the first word that
	 * contains a digit, after filler words and punctuation are removed.
	 * If no word has a digit, the whole cleaned line is used.
	 *
	 * @param string $address_line First address line, e.g. "Door No. 12/3, Gandhi Street".
	 */
	public static function house_number( string $address_line ): string {
		$words = self::words( $address_line );

		foreach ( $words as $word ) {
			if ( preg_match( '/\d/', $word ) ) {
				return $word;
			}
		}
		return implode( '', $words );
	}

	/**
	 * Address key: "pincode|house", or '' if either part is missing.
	 *
	 * @param string $pincode      Postcode, any spacing.
	 * @param string $address_line First address line.
	 */
	public static function address_key( string $pincode, string $address_line ): string {
		$pin   = (string) preg_replace( '/\D+/', '', $pincode );
		$house = self::house_number( $address_line );

		if ( '' === $pin || '' === $house ) {
			return '';
		}
		return $pin . '|' . $house;
	}

	/**
	 * Lower-case words with punctuation removed inside each word and filler words dropped.
	 * Separators are spaces and commas; "12/3" and "4-B" stay one word ("123", "4b").
	 *
	 * @param string $text Address text.
	 * @return string[]
	 */
	private static function words( string $text ): array {
		$parts = preg_split( '/[\s,;]+/', strtolower( $text ) );
		$words = array();

		foreach ( (array) $parts as $part ) {
			// "no.12" becomes "no12": split a filler word stuck to a number.
			$part = (string) preg_replace( '/^(' . implode( '|', self::FILLER_WORDS ) . ')\.(?=\w)/', '', (string) $part );
			$part = (string) preg_replace( '/[^a-z0-9]+/', '', $part );

			if ( '' !== $part && ! in_array( $part, self::FILLER_WORDS, true ) ) {
				$words[] = $part;
			}
		}
		return $words;
	}
}
