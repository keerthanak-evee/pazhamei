<?php
/**
 * Tests for Scheduler::first_run(): daily jobs at an IST clock time,
 * stored as UTC timestamps.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Pazhamei\Cron\Scheduler;
use PHPUnit\Framework\TestCase;

/**
 * Job first-run times.
 */
final class SchedulerTest extends TestCase {

	private DateTimeZone $ist;

	protected function setUp(): void {
		$this->ist = new DateTimeZone( 'Asia/Kolkata' );
	}

	private function utc( int $timestamp ): string {
		return ( new DateTimeImmutable( '@' . $timestamp ) )->setTimezone( new DateTimeZone( 'UTC' ) )->format( 'Y-m-d H:i' );
	}

	public function test_2000_ist_is_1430_utc(): void {
		$now = new DateTimeImmutable( '2026-11-27 10:00:00', $this->ist );
		$this->assertSame( '2026-11-27 14:30', $this->utc( Scheduler::first_run( '20:00', $now, $this->ist ) ) );
	}

	public function test_already_past_today_rolls_to_tomorrow(): void {
		$now = new DateTimeImmutable( '2026-11-27 20:00:00', $this->ist );
		$this->assertSame( '2026-11-28 14:30', $this->utc( Scheduler::first_run( '20:00', $now, $this->ist ) ) );
	}

	public function test_other_jobs(): void {
		$now = new DateTimeImmutable( '2026-11-27 10:00:00', $this->ist );
		$this->assertSame( '2026-11-27 18:00', $this->utc( Scheduler::first_run( '23:30', $now, $this->ist ) ) );
		$this->assertSame( '2026-11-27 18:40', $this->utc( Scheduler::first_run( '00:10', $now, $this->ist ) ), '00:10 IST on 28 Nov is 18:40 UTC on 27 Nov' );
	}

	public function test_a_utc_now_is_read_in_ist(): void {
		// 15:00 UTC is 20:30 IST: today's 20:00 has passed.
		$now = new DateTimeImmutable( '2026-11-27 15:00:00', new DateTimeZone( 'UTC' ) );
		$this->assertSame( '2026-11-28 14:30', $this->utc( Scheduler::first_run( '20:00', $now, $this->ist ) ) );
	}

	public function test_bad_time_is_refused(): void {
		$this->expectException( InvalidArgumentException::class );
		Scheduler::first_run( '8 pm', new DateTimeImmutable( 'now', $this->ist ), $this->ist );
	}
}
