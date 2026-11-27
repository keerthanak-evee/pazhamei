<?php
/**
 * Dates: small helpers for calendar dates stored as 'Y-m-d' strings.
 *
 * The engine stores and compares dates as plain 'Y-m-d' text (IST dates, no
 * time). Text in this format sorts the same way as the dates themselves, so
 * '2026-11-30' < '2026-12-01' works with normal string comparison.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DateTimeImmutable;
use DateTimeZone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Date arithmetic on 'Y-m-d' strings.
 */
final class Dates {

	/** Storage format for every date in the engine. */
	public const FORMAT = 'Y-m-d';

	/**
	 * Is this a real date in 'Y-m-d' format (e.g. not 2026-02-30)?
	 *
	 * @param string $date Text to check.
	 */
	public static function is_valid( string $date ): bool {
		$parsed = DateTimeImmutable::createFromFormat( '!' . self::FORMAT, $date, new DateTimeZone( 'UTC' ) );
		return false !== $parsed && $parsed->format( self::FORMAT ) === $date;
	}

	/**
	 * Add (or subtract) whole days.
	 *
	 * @param string $date 'Y-m-d'.
	 * @param int    $days Days to add; negative to go back.
	 */
	public static function add_days( string $date, int $days ): string {
		// UTC has no daylight saving, so adding days never lands on the wrong date.
		$day = new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( 'UTC' ) );
		return $day->modify( sprintf( '%+d days', $days ) )->format( self::FORMAT );
	}

	/**
	 * Day of the week: 1 = Monday ... 7 = Sunday.
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public static function weekday( string $date ): int {
		return (int) ( new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( 'UTC' ) ) )->format( 'N' );
	}

	/**
	 * Short weekday name, e.g. "Mon".
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public static function weekday_name( string $date ): string {
		return ( new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( 'UTC' ) ) )->format( 'D' );
	}

	/**
	 * The calendar date of a moment, in that moment's own timezone.
	 * Pass a time from Clock::now() (IST) to get today's IST date.
	 *
	 * @param DateTimeImmutable $moment A point in time.
	 */
	public static function date_of( DateTimeImmutable $moment ): string {
		return $moment->format( self::FORMAT );
	}

	/**
	 * Number of days from $from to $to (negative if $to is earlier).
	 *
	 * @param string $from 'Y-m-d'.
	 * @param string $to   'Y-m-d'.
	 */
	public static function days_between( string $from, string $to ): int {
		$utc = new DateTimeZone( 'UTC' );
		$a   = new DateTimeImmutable( $from . ' 00:00:00', $utc );
		$b   = new DateTimeImmutable( $to . ' 00:00:00', $utc );
		return (int) $a->diff( $b )->format( '%r%a' );
	}

	/**
	 * The later of two dates.
	 *
	 * @param string $a 'Y-m-d'.
	 * @param string $b 'Y-m-d'.
	 */
	public static function max( string $a, string $b ): string {
		return $a >= $b ? $a : $b;
	}

	/**
	 * Display format used across the site, e.g. "26 Nov 2026".
	 *
	 * @param string|null $date 'Y-m-d' or null.
	 */
	public static function display( ?string $date ): string {
		if ( null === $date || ! self::is_valid( $date ) ) {
			return '—';
		}
		return ( new DateTimeImmutable( $date . ' 00:00:00', new DateTimeZone( 'UTC' ) ) )->format( 'j M Y' );
	}
}
