<?php
/**
 * ProfileInput: the rules for the customer's Profile page.
 *
 * Fields: full name, mobile (10 digits, one account per mobile), delivery
 * address (line 1, line 2, city, pincode we serve), dietary preference
 * (Veg, Non-Veg, Mixed or none), allergens with severity (mild, moderate,
 * severe) from the allergen list, and special instructions.
 *
 * Plain PHP, unit-tested. check() returns the cleaned values and the errors
 * keyed by field; nothing is saved unless there are no errors.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile form rules.
 */
final class ProfileInput {

	/** Allergen severities, in order. */
	public const SEVERITIES = array(
		'mild'     => 'Mild',
		'moderate' => 'Moderate',
		'severe'   => 'Severe',
	);

	/** Longest special instructions. */
	public const MAX_INSTRUCTIONS = 500;

	/**
	 * Check the submitted profile.
	 *
	 * @param array<string, mixed> $input        name, mobile, address_1, address_2, city, pincode, dietary, allergens (id => severity|''), instructions.
	 * @param int[]                $allergen_ids Allergens that exist.
	 * @param string[]             $served       Served pincodes (empty = all).
	 * @param callable             $mobile_taken fn( string $mobile10 ): bool. Used by another account?
	 * @return array{values: array<string, mixed>, errors: array<string, string>}
	 */
	public static function check( array $input, array $allergen_ids, array $served, callable $mobile_taken ): array {
		$text   = static fn( string $key ) => is_scalar( $input[ $key ] ?? '' ) ? trim( (string) ( $input[ $key ] ?? '' ) ) : '';
		$errors = array();

		$name = $text( 'name' );
		if ( mb_strlen( $name ) < 2 || mb_strlen( $name ) > 100 ) {
			$errors['name'] = 'Please enter your full name.';
		}

		$mobile = Registration::mobile( $text( 'mobile' ) );
		if ( '' === $mobile ) {
			$errors['mobile'] = 'Please enter a 10-digit Indian mobile number, like 98765 43210.';
		} elseif ( $mobile_taken( $mobile ) ) {
			$errors['mobile'] = 'This phone number is already linked to another account.';
		}

		$address_1 = $text( 'address_1' );
		if ( mb_strlen( $address_1 ) < 3 || mb_strlen( $address_1 ) > 200 ) {
			$errors['address_1'] = 'Please enter your house or flat number and street.';
		}
		$address_2 = mb_substr( $text( 'address_2' ), 0, 200 );
		$city      = $text( 'city' );
		if ( '' === $city || mb_strlen( $city ) > 100 ) {
			$errors['city'] = 'Please enter your city.';
		}

		$pincode       = $text( 'pincode' );
		$pincode_error = Pincodes::refusal( $pincode, $served );
		if ( null !== $pincode_error ) {
			$errors['pincode'] = $pincode_error;
		}

		$dietary = $text( 'dietary' );
		if ( '' !== $dietary && ! Plans::is_meal_type( $dietary ) ) {
			$errors['dietary'] = 'Please choose Veg, Non-Veg, Mixed or no preference.';
		}

		$allergens = array();
		$posted    = is_array( $input['allergens'] ?? null ) ? $input['allergens'] : array();
		foreach ( $posted as $id => $severity ) {
			$severity = is_scalar( $severity ) ? (string) $severity : '';
			if ( '' === $severity ) {
				continue; // Not allergic.
			}
			if ( ! in_array( (int) $id, $allergen_ids, true ) || ! isset( self::SEVERITIES[ $severity ] ) ) {
				$errors['allergens'] = 'Please choose a severity from the list for each allergy.';
				continue;
			}
			$allergens[ (int) $id ] = $severity;
		}

		$instructions = $text( 'instructions' );
		if ( mb_strlen( $instructions ) > self::MAX_INSTRUCTIONS ) {
			$errors['instructions'] = 'Please keep special instructions under ' . self::MAX_INSTRUCTIONS . ' characters.';
		}

		return array(
			'values' => array(
				'name'         => $name,
				'mobile'       => $mobile,
				'address_1'    => $address_1,
				'address_2'    => $address_2,
				'city'         => $city,
				'pincode'      => $pincode,
				'dietary'      => $dietary,
				'allergens'    => $allergens,
				'instructions' => $instructions,
			),
			'errors' => $errors,
		);
	}

	/**
	 * Check a password change.
	 *
	 * @param string $new     New password.
	 * @param string $confirm The same again.
	 * @return string|null Error, or null when fine. (The current password is checked by WordPress.)
	 */
	public static function password_error( string $new, string $confirm ): ?string {
		if ( strlen( $new ) < Registration::MIN_PASSWORD ) {
			return 'Please choose a password of at least ' . Registration::MIN_PASSWORD . ' characters.';
		}
		if ( $new !== $confirm ) {
			return 'The two new passwords don’t match.';
		}
		return null;
	}
}
