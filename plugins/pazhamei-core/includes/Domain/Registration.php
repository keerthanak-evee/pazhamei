<?php
/**
 * Registration: the rules for the Register form on /sign-in.
 *
 * Fields: full name, email, phone, delivery pincode, password and confirm
 * password (owner decision, Phase 4). WooCommerce itself checks the email
 * (valid, not already registered); these rules check the rest:
 * - Full name: 2 to 100 characters.
 * - Phone: an Indian mobile number. Stored as its 10 digits (+91 or a
 *   leading 0 are dropped). Each mobile belongs to one account.
 * - Delivery pincode: 6 digits, not starting with 0.
 * - Password: at least 8 characters; Confirm must match.
 *
 * Plain PHP, unit-tested. Messages are shown to the customer as they are.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register form rules.
 */
final class Registration {

	/** Shortest password accepted. */
	public const MIN_PASSWORD = 8;

	/** Shown when the mobile is already used by another account. */
	public const MOBILE_TAKEN = 'This phone number is already linked to an account. Please sign in instead, or use another number.';

	/**
	 * Every problem with the form, keyed by field name.
	 *
	 * @param array<string, mixed> $input        Posted fields: pz_full_name, email, pz_mobile, pz_pincode, password, pz_password2.
	 * @param callable             $mobile_taken fn( string $mobile10 ): bool. Is this mobile on another account?
	 * @return array<string, string>
	 */
	public static function errors( array $input, callable $mobile_taken ): array {
		$errors = array();

		$name = self::text( $input, 'pz_full_name' );
		if ( mb_strlen( $name ) < 2 ) {
			$errors['pz_full_name'] = 'Please enter your full name.';
		} elseif ( mb_strlen( $name ) > 100 ) {
			$errors['pz_full_name'] = 'Please keep your name under 100 characters.';
		}

		$mobile = self::mobile( self::text( $input, 'pz_mobile' ) );
		if ( '' === $mobile ) {
			$errors['pz_mobile'] = 'Please enter a 10-digit Indian mobile number, like 98765 43210.';
		} elseif ( $mobile_taken( $mobile ) ) {
			$errors['pz_mobile'] = self::MOBILE_TAKEN;
		}

		if ( ! self::is_pincode( self::text( $input, 'pz_pincode' ) ) ) {
			$errors['pz_pincode'] = 'Please enter your 6-digit delivery pincode, like 600032.';
		}

		$password = (string) ( $input['password'] ?? '' );
		if ( strlen( $password ) < self::MIN_PASSWORD ) {
			$errors['password'] = 'Please choose a password of at least ' . self::MIN_PASSWORD . ' characters.';
		} elseif ( (string) ( $input['pz_password2'] ?? '' ) !== $password ) {
			$errors['pz_password2'] = 'The two passwords don’t match.';
		}

		return $errors;
	}

	/**
	 * An Indian mobile number as its 10 digits, or '' if it isn't one.
	 * Accepts spaces, dashes, brackets, +91, 91 or a leading 0.
	 *
	 * @param string $value What the customer typed.
	 */
	public static function mobile( string $value ): string {
		if ( preg_match( '/[^\d\s\-\+\(\)\.]/', $value ) ) {
			return ''; // Letters or other characters: not a phone number.
		}
		$digits = (string) preg_replace( '/\D+/', '', $value );

		if ( 12 === strlen( $digits ) && str_starts_with( $digits, '91' ) ) {
			$digits = substr( $digits, 2 );
		} elseif ( 11 === strlen( $digits ) && str_starts_with( $digits, '0' ) ) {
			$digits = substr( $digits, 1 );
		}

		return preg_match( '/^[6-9]\d{9}$/', $digits ) ? $digits : '';
	}

	/**
	 * Does a sign-in name look like a phone number (rather than an email or username)?
	 *
	 * @param string $login What was typed in "Email or phone number".
	 */
	public static function is_mobile_login( string $login ): bool {
		return '' !== self::mobile( trim( $login ) );
	}

	/**
	 * Is this a valid Indian pincode (6 digits, not starting with 0)?
	 *
	 * @param string $value Pincode.
	 */
	public static function is_pincode( string $value ): bool {
		return 1 === preg_match( '/^[1-9]\d{5}$/', trim( $value ) );
	}

	/**
	 * Split a full name into first and last name at the first space.
	 * "Arun Kumar Raj" → ["Arun", "Kumar Raj"]; "Arun" → ["Arun", ""].
	 *
	 * @param string $full_name Full name.
	 * @return array{0: string, 1: string}
	 */
	public static function split_name( string $full_name ): array {
		$parts = preg_split( '/\s+/', trim( $full_name ), 2 );
		$parts = false === $parts ? array( '' ) : $parts;
		return array( (string) $parts[0], (string) ( $parts[1] ?? '' ) );
	}

	/**
	 * A trimmed text field from the input.
	 *
	 * @param array<string, mixed> $input Posted fields.
	 * @param string               $key   Field name.
	 */
	private static function text( array $input, string $key ): string {
		$value = $input[ $key ] ?? '';
		return is_scalar( $value ) ? trim( (string) $value ) : '';
	}
}
