<?php
/**
 * Clock: the single source of "now" for all date and time rules.
 *
 * Always returns the time in the site timezone (Asia/Kolkata). For testing
 * time-based rules on a local site there are two overrides:
 *
 *  - `wp pz time "2026-11-29 20:01:00"` stores the fake time in the option
 *    `pz_fake_now`, so it lasts across separate WP-CLI commands and page
 *    loads. `wp pz time --clear` removes it.
 *  - `define( 'PZ_FAKE_NOW', '2026-11-26 19:59:00' );` in wp-config.php.
 *    The constant wins over the option.
 *
 * Both are ignored, with a warning in the PHP error log, everywhere except
 * the "local" environment, so they can never change real cutoffs on staging
 * or production.
 *
 * @package Pazhamei
 */

namespace Pazhamei;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Current date and time, with an optional local-only override.
 */
final class Clock {

	/** Format the fake time must use. */
	public const FAKE_FORMAT = 'Y-m-d H:i:s';

	/** Option written by `wp pz time`. */
	public const OPTION = 'pz_fake_now';

	/** Warn only once per request. */
	private static bool $warned = false;

	/**
	 * The current date and time in the site timezone.
	 */
	public static function now(): DateTimeImmutable {
		$fake        = self::configured_fake();
		$environment = wp_get_environment_type();

		if ( self::is_ignored( $fake, $environment ) && ! self::$warned ) {
			self::$warned = true;
			error_log( "Pazhamei: a fake time ({$fake}) is set but ignored because the environment is \"{$environment}\". Remove PZ_FAKE_NOW / the pz_fake_now option." ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- Deliberate warning.
		}

		return self::resolve( $fake, $environment, current_datetime(), wp_timezone() );
	}

	/**
	 * The fake time that is set (constant first, then option), or null.
	 */
	public static function configured_fake(): ?string {
		$constant = defined( 'PZ_FAKE_NOW' ) ? (string) constant( 'PZ_FAKE_NOW' ) : null;
		$option   = get_option( self::OPTION, '' );

		return self::pick_fake( $constant, is_string( $option ) ? $option : null );
	}

	/**
	 * Which fake time applies: the PZ_FAKE_NOW constant overrides the option.
	 *
	 * @param string|null $constant PZ_FAKE_NOW value, or null.
	 * @param string|null $option   pz_fake_now option value, or null.
	 */
	public static function pick_fake( ?string $constant, ?string $option ): ?string {
		if ( null !== $constant && '' !== $constant ) {
			return $constant;
		}
		if ( null !== $option && '' !== $option ) {
			return $option;
		}
		return null;
	}

	/**
	 * Is a fake time set but not allowed here (so it will be ignored)?
	 *
	 * @param string|null $fake             Fake time, or null.
	 * @param string      $environment_type Value of wp_get_environment_type().
	 */
	public static function is_ignored( ?string $fake, string $environment_type ): bool {
		return null !== $fake && '' !== $fake && ! Environment::allows_dev_tools( $environment_type );
	}

	/**
	 * Pick the real time or the fake time. Plain PHP, so it can be unit-tested.
	 *
	 * @param string|null       $fake             Fake time, or null if not set.
	 * @param string            $environment_type Value of wp_get_environment_type().
	 * @param DateTimeImmutable $real             The real current time.
	 * @param DateTimeZone      $timezone         Site timezone.
	 * @throws InvalidArgumentException When a fake time is set on a local site but is not in 'Y-m-d H:i:s' format.
	 */
	public static function resolve( ?string $fake, string $environment_type, DateTimeImmutable $real, DateTimeZone $timezone ): DateTimeImmutable {
		// Not set, or not a local site: always the real time.
		if ( null === $fake || '' === $fake || ! Environment::allows_dev_tools( $environment_type ) ) {
			return $real->setTimezone( $timezone );
		}

		$parsed = self::parse( $fake, $timezone );
		if ( null === $parsed ) {
			throw new InvalidArgumentException( "The fake time must look like 2026-11-26 19:59:00, got '{$fake}'." );
		}

		return $parsed;
	}

	/**
	 * Parse 'Y-m-d H:i:s' in the timezone, or null if it isn't in that exact format.
	 *
	 * @param string       $value    Text.
	 * @param DateTimeZone $timezone Timezone.
	 */
	public static function parse( string $value, DateTimeZone $timezone ): ?DateTimeImmutable {
		$parsed = DateTimeImmutable::createFromFormat( '!' . self::FAKE_FORMAT, $value, $timezone );
		return ( false !== $parsed && $parsed->format( self::FAKE_FORMAT ) === $value ) ? $parsed : null;
	}
}
