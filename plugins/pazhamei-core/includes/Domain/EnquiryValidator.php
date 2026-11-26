<?php
/**
 * EnquiryValidator: checks and cleans the storefront enquiry form.
 *
 * Plain PHP (no WordPress calls), so it can be unit-tested. It returns the
 * cleaned values ready to save, plus a message for each field that is wrong.
 *
 * Rules:
 * - name: required, 2 to 100 characters.
 * - mobile: required, an Indian mobile number. +91, 91 or a leading 0 is removed and
 *   the last 10 digits are kept (same idea as the trial check, business rule 10).
 * - email: optional, but must be valid if given.
 * - pincode: required, 6 digits, not starting with 0.
 * - area: optional, up to 100 characters.
 * - plan_interest: required, one of trial, 6-day, 26-day, not-sure.
 * - meal_type: optional, VEG, NONVEG or MIXED.
 * - preferred_start: optional; a real date after today, within 60 days, not a Sunday (rule 1).
 * - message: optional, up to 1000 characters.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DateTimeImmutable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Validation for one enquiry.
 */
final class EnquiryValidator {

	/** How far ahead a preferred start date may be. */
	public const MAX_START_DAYS = 60;

	/**
	 * Check every field.
	 *
	 * @param array<string, mixed> $input Raw form values.
	 * @param DateTimeImmutable    $today Today's date in IST (from Clock::now()).
	 * @return array{data: array<string, string|null>, errors: array<string, string>}
	 */
	public static function validate( array $input, DateTimeImmutable $today ): array {
		$errors = array();

		$name = self::text( $input['name'] ?? '' );
		if ( mb_strlen( $name ) < 2 ) {
			$errors['name'] = 'Please enter your name.';
		} elseif ( mb_strlen( $name ) > 100 ) {
			$errors['name'] = 'Please keep your name under 100 characters.';
		}

		$mobile = self::mobile( (string) ( $input['mobile'] ?? '' ) );
		if ( null === $mobile ) {
			$errors['mobile'] = 'Please enter a 10-digit Indian mobile number.';
		}

		$email = self::text( $input['email'] ?? '' );
		if ( '' !== $email && ( mb_strlen( $email ) > 100 || false === filter_var( $email, FILTER_VALIDATE_EMAIL ) ) ) {
			$errors['email'] = 'Please enter a valid email address, or leave it empty.';
		}

		$pincode = preg_replace( '/\s+/', '', self::text( $input['pincode'] ?? '' ) );
		if ( ! preg_match( '/^[1-9][0-9]{5}$/', $pincode ) ) {
			$errors['pincode'] = 'Please enter your 6-digit pincode.';
		}

		$area = self::text( $input['area'] ?? '' );
		if ( mb_strlen( $area ) > 100 ) {
			$errors['area'] = 'Please keep the area under 100 characters.';
		}

		$plan = self::text( $input['plan_interest'] ?? '' );
		if ( ! Plans::is_enquiry_choice( $plan ) ) {
			$errors['plan_interest'] = 'Please choose a plan.';
		}

		$meal_type = strtoupper( self::text( $input['meal_type'] ?? '' ) );
		if ( '' !== $meal_type && ! Plans::is_meal_type( $meal_type ) ) {
			$errors['meal_type'] = 'Please choose vegetarian, non-vegetarian or mixed.';
		}

		$start       = self::text( $input['preferred_start'] ?? '' );
		$start_error = '' === $start ? null : self::start_date_error( $start, $today );
		if ( null !== $start_error ) {
			$errors['preferred_start'] = $start_error;
		}

		$message = self::multiline( $input['message'] ?? '' );
		if ( mb_strlen( $message ) > 1000 ) {
			$errors['message'] = 'Please keep your message under 1000 characters.';
		}

		return array(
			'data'   => array(
				'name'            => $name,
				'mobile'          => (string) $mobile,
				'email'           => $email,
				'area'            => $area,
				'pincode'         => $pincode,
				'plan_interest'   => $plan,
				'meal_type'       => $meal_type,
				'preferred_start' => '' === $start ? null : $start,
				'message'         => $message,
			),
			'errors' => $errors,
		);
	}

	/**
	 * Clean an Indian mobile number down to its 10 digits, or null if it isn't one.
	 * Spaces, dashes, brackets, +91, 91 and a leading 0 are all accepted.
	 *
	 * @param string $raw Raw input.
	 */
	public static function mobile( string $raw ): ?string {
		$raw = trim( $raw );
		if ( '' === $raw || preg_match( '/[^0-9+\-\s().]/', $raw ) ) {
			return null; // Letters or other symbols: not a phone number.
		}

		$digits = preg_replace( '/\D/', '', $raw );
		$length = strlen( $digits );

		if ( 12 === $length && str_starts_with( $digits, '91' ) ) {
			$digits = substr( $digits, 2 );
		} elseif ( 11 === $length && str_starts_with( $digits, '0' ) ) {
			$digits = substr( $digits, 1 );
		}

		// Indian mobile numbers are 10 digits and start with 6, 7, 8 or 9.
		return preg_match( '/^[6-9][0-9]{9}$/', $digits ) ? $digits : null;
	}

	/**
	 * Why a preferred start date is not acceptable, or null if it is fine.
	 *
	 * @param string            $date  Submitted date, expected as Y-m-d.
	 * @param DateTimeImmutable $today Today in IST.
	 */
	private static function start_date_error( string $date, DateTimeImmutable $today ): ?string {
		$parsed = DateTimeImmutable::createFromFormat( '!Y-m-d', $date, $today->getTimezone() );
		if ( false === $parsed || $parsed->format( 'Y-m-d' ) !== $date ) {
			return 'Please pick a date from the calendar.';
		}

		$today_date = $today->setTime( 0, 0 );
		if ( $parsed <= $today_date ) {
			return 'Please pick a date after today.';
		}
		if ( $parsed > $today_date->modify( '+' . self::MAX_START_DAYS . ' days' ) ) {
			return 'Please pick a date within the next ' . self::MAX_START_DAYS . ' days.';
		}
		if ( '7' === $parsed->format( 'N' ) ) {
			return 'We don’t deliver on Sundays. Please pick Monday to Saturday.';
		}

		return null;
	}

	/**
	 * One line of plain text: tags removed, spaces collapsed, trimmed.
	 *
	 * @param mixed $value Raw value.
	 */
	private static function text( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = strip_tags( (string) $value ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- Plain PHP so it can be unit-tested.
		return trim( (string) preg_replace( '/\s+/u', ' ', $value ) );
	}

	/**
	 * Plain text that may have line breaks (the message box).
	 *
	 * @param mixed $value Raw value.
	 */
	private static function multiline( $value ): string {
		if ( ! is_scalar( $value ) ) {
			return '';
		}
		$value = strip_tags( str_replace( "\r\n", "\n", (string) $value ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags
		$value = preg_replace( '/[ \t]+/u', ' ', $value );
		$value = preg_replace( "/\n{3,}/", "\n\n", (string) $value );
		return trim( (string) $value );
	}
}
