<?php
/**
 * StartDate: checks the start date a customer picks at checkout (business rule 4).
 *
 * It must be a real date, a service day (not Sunday, not a holiday), not
 * locked yet, and no more than 30 days from today.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DateTimeImmutable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start-date validation.
 */
final class StartDate {

	/** Latest allowed start, in days from today. */
	public const MAX_DAYS_AHEAD = 30;

	/**
	 * Check a start date.
	 *
	 * @param string            $date     Submitted date, 'Y-m-d'.
	 * @param DateTimeImmutable $now      Current IST time.
	 * @param Calendar          $calendar Service days and holidays.
	 * @param Cutoff            $cutoff   Lock rule.
	 * @return string|null Error message for the customer, or null when the date is fine.
	 */
	public static function error( string $date, DateTimeImmutable $now, Calendar $calendar, Cutoff $cutoff ): ?string {
		if ( ! Dates::is_valid( $date ) ) {
			return 'Please choose a start date.';
		}

		$today = $cutoff->today( $now );

		if ( $cutoff->is_locked( $date, $now ) ) {
			$earliest = $calendar->first_service_day_from( $cutoff->first_unlocked_date( $now ) );
			return 'That start date is too late to change. The earliest start is ' . Dates::display( $earliest ) . '.';
		}
		if ( $calendar->is_sunday( $date ) ) {
			return 'We do not deliver on Sundays. Please choose another start date.';
		}
		if ( $calendar->is_holiday( $date ) ) {
			return 'The kitchen is closed on ' . Dates::display( $date ) . '. Please choose another start date.';
		}
		if ( $date > Dates::add_days( $today, self::MAX_DAYS_AHEAD ) ) {
			return 'Please choose a start date within the next ' . self::MAX_DAYS_AHEAD . ' days.';
		}

		return null;
	}

	/**
	 * Every date from today to the last allowed start, with whether it can be
	 * picked and why not. Used by the checkout date picker; the server still
	 * runs error() when the order is placed.
	 *
	 * Status: 'ok', 'locked' (today, or past the cutoff), 'sunday', 'holiday'.
	 *
	 * @param DateTimeImmutable $now      Current IST time.
	 * @param Calendar          $calendar Service days and holidays.
	 * @param Cutoff            $cutoff   Lock rule.
	 * @return array<int, array{date: string, status: string, reason: string}>
	 */
	public static function options( DateTimeImmutable $now, Calendar $calendar, Cutoff $cutoff ): array {
		$today = $cutoff->today( $now );
		$days  = array();

		for ( $i = 0; $i <= self::MAX_DAYS_AHEAD; $i++ ) {
			$date = Dates::add_days( $today, $i );

			if ( $cutoff->is_locked( $date, $now ) ) {
				$status = 'locked';
				$reason = 'Too late: this day is already locked.';
			} elseif ( $calendar->is_sunday( $date ) ) {
				$status = 'sunday';
				$reason = 'No deliveries on Sundays.';
			} elseif ( $calendar->is_holiday( $date ) ) {
				$status = 'holiday';
				$reason = 'Kitchen holiday.';
			} else {
				$status = 'ok';
				$reason = '';
			}
			$days[] = array(
				'date'   => $date,
				'status' => $status,
				'reason' => $reason,
			);
		}
		return $days;
	}
}
