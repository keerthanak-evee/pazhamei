<?php
/**
 * Honeypot: a hidden form field that people never see, but spam bots fill in.
 *
 * The enquiry form has a field named "company_url", hidden from people and
 * screen readers. If it comes back with anything in it, the sender is a bot:
 * we reply with a normal success message but save and send nothing, so the
 * bot learns nothing.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Honeypot check.
 */
final class Honeypot {

	/** Name of the hidden field. */
	public const FIELD = 'company_url';

	/**
	 * Did a bot fill in the hidden field?
	 *
	 * @param array<string, mixed> $input Submitted form values.
	 */
	public static function is_tripped( array $input ): bool {
		if ( ! array_key_exists( self::FIELD, $input ) ) {
			return false;
		}

		$value = $input[ self::FIELD ];

		// Anything that isn't a plain value (an array, for example) is a bot too.
		if ( ! is_scalar( $value ) ) {
			return true;
		}

		return '' !== trim( (string) $value );
	}
}
