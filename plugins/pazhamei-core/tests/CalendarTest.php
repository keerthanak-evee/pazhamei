<?php
/**
 * Tests for Calendar: service days are Monday to Saturday, minus holidays (rule 1).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Calendar;
use Pazhamei\Domain\Dates;
use PHPUnit\Framework\TestCase;

/**
 * Service days.
 */
final class CalendarTest extends TestCase {

	public function test_monday_to_saturday_are_service_days_and_sunday_is_not(): void {
		$calendar = new Calendar();

		// 30 Nov 2026 is a Monday.
		foreach ( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05' ) as $date ) {
			$this->assertTrue( $calendar->is_service_day( $date ), $date );
		}
		$this->assertFalse( $calendar->is_service_day( '2026-12-06' ), 'Sunday' );
	}

	public function test_holidays_are_not_service_days(): void {
		$calendar = new Calendar( array( '2026-12-02' ) );

		$this->assertFalse( $calendar->is_service_day( '2026-12-02' ) );
		$this->assertTrue( $calendar->is_holiday( '2026-12-02' ) );
		$this->assertTrue( $calendar->is_service_day( '2026-12-03' ) );
	}

	public function test_next_service_day_skips_sunday_and_holidays(): void {
		$calendar = new Calendar( array( '2026-12-07' ) ); // Monday holiday.

		// Saturday -> (Sunday off, Monday holiday) -> Tuesday.
		$this->assertSame( '2026-12-08', $calendar->next_service_day_after( '2026-12-05' ) );
		$this->assertSame( '2026-12-08', $calendar->first_service_day_from( '2026-12-06' ) );
		$this->assertSame( '2026-12-05', $calendar->first_service_day_from( '2026-12-05' ) );
	}

	public function test_date_helpers(): void {
		$this->assertSame( '2027-01-01', Dates::add_days( '2026-12-31', 1 ) );
		$this->assertSame( '2026-02-28', Dates::add_days( '2026-03-01', -1 ) );
		$this->assertSame( 7, Dates::weekday( '2026-12-06' ) );
		$this->assertFalse( Dates::is_valid( '2026-02-30' ) );
		$this->assertTrue( Dates::is_valid( '2026-12-06' ) );
		$this->assertSame( '26 Nov 2026', Dates::display( '2026-11-26' ) );
		$this->assertSame( 3, Dates::days_between( '2026-11-30', '2026-12-03' ) );
	}
}
