<?php
/**
 * Clock: the single source of "now" for all date and time rules.
 *
 * Always returns the time in the site timezone (Asia/Kolkata). For testing
 * time-based rules on a local site, add this to wp-config.php:
 *
 *     define( 'PZ_FAKE_NOW', '2026-11-26 19:59:00' );
 *
 * The override is ignored everywhere except the "local" environment, so it
 * can never change real cutoffs on staging or production.
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

	/** Format PZ_FAKE_NOW must use. */
	public const FAKE_FORMAT = 'Y-m-d H:i:s';

	/**
	 * The current date and time in the site timezone.
	 */
	public static function now(): DateTimeImmutable {
		$fake = defined( 'PZ_FAKE_NOW' ) ? (string) constant( 'PZ_FAKE_NOW' ) : null;

		return self::resolve( $fake, wp_get_environment_type(), current_datetime(), wp_timezone() );
	}

	/**
	 * Pick the real time or the fake time. Plain PHP, so it can be unit-tested.
	 *
	 * @param string|null       $fake             PZ_FAKE_NOW value, or null if not set.
	 * @param string            $environment_type Value of wp_get_environment_type().
	 * @param DateTimeImmutable $real             The real current time.
	 * @param DateTimeZone      $timezone         Site timezone.
	 * @throws InvalidArgumentException When PZ_FAKE_NOW is set on a local site but is not in 'Y-m-d H:i:s' format.
	 */
	public static function resolve( ?string $fake, string $environment_type, DateTimeImmutable $real, DateTimeZone $timezone ): DateTimeImmutable {
		// Not set, or not a local site: always the real time.
		if ( null === $fake || '' === $fake || ! Environment::allows_dev_tools( $environment_type ) ) {
			return $real->setTimezone( $timezone );
		}

		$parsed = DateTimeImmutable::createFromFormat( '!' . self::FAKE_FORMAT, $fake, $timezone );
		if ( false === $parsed || $parsed->format( self::FAKE_FORMAT ) !== $fake ) {
			throw new InvalidArgumentException( "PZ_FAKE_NOW must look like 2026-11-26 19:59:00, got '{$fake}'." );
		}

		return $parsed;
	}
}
