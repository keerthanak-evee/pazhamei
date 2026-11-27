<?php
/**
 * PriceGrid: checks what the owner typed into the Plans & Prices screen.
 *
 * The grid has one price per plan (TRIAL, D6, D26) and meal type (VEG,
 * NONVEG, MIXED), in rupees:
 * - Every cell is required: whole rupees or up to 2 decimals, no letters.
 * - Trial prices can be 0 (a free trial). 6-day and 26-day must be above 0.
 * - Nothing above MAX (catches a typo like an extra zero).
 *
 * The "what's included" text: one point per line, 1 to MAX_LINES lines,
 * each at most MAX_LINE_LENGTH characters.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Price grid validation.
 */
final class PriceGrid {

	/** Highest price accepted for one pack, in rupees. */
	public const MAX = 200000;

	/** "What's included" limits. */
	public const MAX_LINES       = 6;
	public const MAX_LINE_LENGTH = 120;

	/** Plan codes in display order. */
	public const PLANS = array( 'TRIAL', 'D6', 'D26' );

	/**
	 * Check the submitted prices.
	 *
	 * @param array<string, mixed> $input price[PLAN][MEAL_TYPE] => text.
	 * @return array{prices: array<string, array<string, float>>, errors: array<string, string>}
	 *         Clean prices, and errors keyed "PLAN.MEAL_TYPE".
	 */
	public static function check_prices( array $input ): array {
		$prices = array();
		$errors = array();

		foreach ( self::PLANS as $plan ) {
			foreach ( array_keys( Plans::MEAL_TYPES ) as $type ) {
				$raw = $input[ $plan ][ $type ] ?? '';
				$raw = is_scalar( $raw ) ? trim( str_replace( array( ',', '₹', ' ' ), '', (string) $raw ) ) : '';
				$key = $plan . '.' . $type;

				if ( '' === $raw ) {
					$errors[ $key ] = 'Enter a price.';
					continue;
				}
				if ( ! preg_match( '/^\d+(\.\d{1,2})?$/', $raw ) ) {
					$errors[ $key ] = 'Use numbers only, like 900 or 99.50.';
					continue;
				}

				$amount = (float) $raw;
				if ( 'TRIAL' !== $plan && $amount <= 0 ) {
					$errors[ $key ] = 'Must be more than ₹0.';
				} elseif ( $amount > self::MAX ) {
					$errors[ $key ] = 'Must be at most ' . Money::rupees( self::MAX ) . '.';
				} else {
					$prices[ $plan ][ $type ] = $amount;
				}
			}
		}
		return array(
			'prices' => $prices,
			'errors' => $errors,
		);
	}

	/**
	 * Check one plan's "what's included" text.
	 *
	 * @param string $text One point per line.
	 * @return array{lines: string[], error: ?string}
	 */
	public static function check_includes( string $text ): array {
		$split = preg_split( '/\R/', $text );
		$lines = array_values( array_filter( array_map( 'trim', false === $split ? array() : $split ), 'strlen' ) );

		if ( ! $lines ) {
			return array( 'lines' => array(), 'error' => 'Add at least one line.' );
		}
		if ( count( $lines ) > self::MAX_LINES ) {
			return array( 'lines' => $lines, 'error' => 'Use at most ' . self::MAX_LINES . ' lines.' );
		}
		foreach ( $lines as $line ) {
			if ( mb_strlen( $line ) > self::MAX_LINE_LENGTH ) {
				return array( 'lines' => $lines, 'error' => 'Keep each line under ' . self::MAX_LINE_LENGTH . ' characters.' );
			}
		}
		return array( 'lines' => $lines, 'error' => null );
	}
}
