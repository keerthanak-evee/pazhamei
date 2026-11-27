<?php
/**
 * Calendar: which dates are service days (business rule 1).
 *
 * Service days are Monday to Saturday. Sunday is always off, and so is any
 * date the admin has declared a holiday (wp_pz_holidays). Plain PHP: the
 * caller passes in the holiday dates, so PHPUnit can test it directly.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Service-day rules for one set of holidays.
 */
final class Calendar {

	/** ISO weekday number of Sunday. */
	private const SUNDAY = 7;

	/**
	 * Holiday dates as keys, for quick lookup.
	 *
	 * @var array<string, true>
	 */
	private array $holidays = array();

	/**
	 * @param string[] $holiday_dates Holiday dates as 'Y-m-d'.
	 */
	public function __construct( array $holiday_dates = array() ) {
		foreach ( $holiday_dates as $date ) {
			$this->holidays[ (string) $date ] = true;
		}
	}

	/**
	 * Is this date a Sunday?
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function is_sunday( string $date ): bool {
		return self::SUNDAY === Dates::weekday( $date );
	}

	/**
	 * Is this date an admin-declared holiday?
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function is_holiday( string $date ): bool {
		return isset( $this->holidays[ $date ] );
	}

	/**
	 * Does the kitchen deliver on this date?
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function is_service_day( string $date ): bool {
		return ! $this->is_sunday( $date ) && ! $this->is_holiday( $date );
	}

	/**
	 * The first service day on or after a date.
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function first_service_day_from( string $date ): string {
		$day = $date;
		// A year of holidays in a row is impossible; the limit only guards against bad data.
		for ( $i = 0; $i < 366 && ! $this->is_service_day( $day ); $i++ ) {
			$day = Dates::add_days( $day, 1 );
		}
		return $day;
	}

	/**
	 * The first service day strictly after a date.
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function next_service_day_after( string $date ): string {
		return $this->first_service_day_from( Dates::add_days( $date, 1 ) );
	}
}
