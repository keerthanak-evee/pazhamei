<?php
/**
 * Tests for StartDate: an unlocked service day within 30 days (rule 4).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Pazhamei\Domain\Calendar;
use Pazhamei\Domain\Cutoff;
use Pazhamei\Domain\StartDate;
use PHPUnit\Framework\TestCase;

/**
 * Start-date validation.
 */
final class StartDateTest extends TestCase {

	private DateTimeImmutable $now;
	private Calendar $calendar;
	private Cutoff $cutoff;

	protected function setUp(): void {
		$ist            = new DateTimeZone( 'Asia/Kolkata' );
		$this->now      = new DateTimeImmutable( '2026-11-27 10:00:00', $ist ); // Friday.
		$this->calendar = new Calendar( array( '2026-12-02' ) );
		$this->cutoff   = new Cutoff( '20:00', $ist );
	}

	private function error( string $date ): ?string {
		return StartDate::error( $date, $this->now, $this->calendar, $this->cutoff );
	}

	public function test_next_monday_is_fine(): void {
		$this->assertNull( $this->error( '2026-11-30' ) );
		$this->assertNull( $this->error( '2026-11-28' ), 'Tomorrow before 20:00' );
	}

	public function test_refused_dates(): void {
		$this->assertNotNull( $this->error( '2026-11-27' ), 'Today is locked' );
		$this->assertStringContainsString( 'Sunday', (string) $this->error( '2026-11-29' ) );
		$this->assertStringContainsString( 'closed', (string) $this->error( '2026-12-02' ) );
		$this->assertStringContainsString( '30 days', (string) $this->error( '2026-12-28' ) );
		$this->assertNotNull( $this->error( 'tomorrow' ) );
	}

	public function test_thirty_days_ahead_is_the_limit(): void {
		$this->assertNull( $this->error( '2026-12-26' ), '29 days (Saturday)' );
		$this->assertNotNull( $this->error( '2026-12-28' ), '31 days' );
	}

	/**
	 * The checkout picker greys out Sundays, holidays and locked days, and
	 * offers exactly the dates error() accepts.
	 */
	public function test_picker_options_match_the_rule(): void {
		$options = StartDate::options( $this->now, $this->calendar, $this->cutoff );
		$status  = array_column( $options, 'status', 'date' );

		$this->assertSame( '2026-11-27', $options[0]['date'], 'Starts today' );
		$this->assertSame( '2026-12-27', end( $options )['date'], 'Ends 30 days ahead' );
		$this->assertSame( 'locked', $status['2026-11-27'] );
		$this->assertSame( 'ok', $status['2026-11-28'] );
		$this->assertSame( 'sunday', $status['2026-11-29'] );
		$this->assertSame( 'ok', $status['2026-11-30'] );
		$this->assertSame( 'holiday', $status['2026-12-02'] );

		foreach ( $options as $day ) {
			$this->assertSame( 'ok' === $day['status'], null === $this->error( $day['date'] ), $day['date'] );
		}
	}

	public function test_picker_locks_tomorrow_after_the_cutoff(): void {
		$late   = new DateTimeImmutable( '2026-11-27 20:01:00', new DateTimeZone( 'Asia/Kolkata' ) );
		$status = array_column( StartDate::options( $late, $this->calendar, $this->cutoff ), 'status', 'date' );

		$this->assertSame( 'locked', $status['2026-11-28'] );
		$this->assertSame( 'sunday', $status['2026-11-29'] );
		$this->assertSame( 'ok', $status['2026-11-30'] );
	}

	public function test_tomorrow_after_cutoff_is_refused_with_the_earliest_date(): void {
		$late  = new DateTimeImmutable( '2026-11-27 20:01:00', new DateTimeZone( 'Asia/Kolkata' ) );
		$error = StartDate::error( '2026-11-28', $late, $this->calendar, $this->cutoff );
		$this->assertStringContainsString( '30 Nov 2026', (string) $error );
	}
}
