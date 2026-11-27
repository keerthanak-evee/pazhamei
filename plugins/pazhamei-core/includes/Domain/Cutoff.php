<?php
/**
 * Cutoff: when a date becomes locked (business rule 3).
 *
 * A change for date D is allowed until the cutoff time (20:00 IST, from
 * pz_settings) on the calendar day before D. At that moment D is locked.
 * Monday is no exception: its cutoff is Sunday 20:00.
 *
 * Always pass the current IST time (Clock::now()). Plain PHP, so the 19:59
 * and 20:01 cases can be tested without WordPress.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lock-time rules for one cutoff time.
 */
final class Cutoff {

	private string $time;
	private DateTimeZone $timezone;

	/**
	 * @param string       $time     Cutoff as 'HH:MM' (24-hour), e.g. '20:00'.
	 * @param DateTimeZone $timezone Site timezone (Asia/Kolkata).
	 * @throws InvalidArgumentException When the time is not 'HH:MM'.
	 */
	public function __construct( string $time, DateTimeZone $timezone ) {
		if ( ! preg_match( '/^([01]\d|2[0-3]):[0-5]\d$/', $time ) ) {
			throw new InvalidArgumentException( "Cutoff time must look like 20:00, got '{$time}'." );
		}
		$this->time     = $time;
		$this->timezone = $timezone;
	}

	/**
	 * The moment date D locks: the cutoff time on the day before D.
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function locks_at( string $date ): DateTimeImmutable {
		return new DateTimeImmutable( Dates::add_days( $date, -1 ) . ' ' . $this->time . ':00', $this->timezone );
	}

	/**
	 * Is date D locked at this moment? Exactly 20:00:00 counts as locked.
	 *
	 * @param string            $date 'Y-m-d'.
	 * @param DateTimeImmutable $now  Current time.
	 */
	public function is_locked( string $date, DateTimeImmutable $now ): bool {
		return $now >= $this->locks_at( $date );
	}

	/**
	 * The earliest date that can still be changed (it may be a Sunday or holiday).
	 * Before the cutoff that is tomorrow; after it, the day after tomorrow.
	 *
	 * @param DateTimeImmutable $now Current time.
	 */
	public function first_unlocked_date( DateTimeImmutable $now ): string {
		$today = Dates::date_of( $now->setTimezone( $this->timezone ) );
		$date  = Dates::add_days( $today, 1 );

		while ( $this->is_locked( $date, $now ) ) {
			$date = Dates::add_days( $date, 1 );
		}
		return $date;
	}

	/**
	 * Today's date in the site timezone.
	 *
	 * @param DateTimeImmutable $now Current time.
	 */
	public function today( DateTimeImmutable $now ): string {
		return Dates::date_of( $now->setTimezone( $this->timezone ) );
	}
}
