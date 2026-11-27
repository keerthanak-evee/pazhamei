<?php
/**
 * Tests for Cutoff: changes for date D close at 20:00 IST on D-1 (rule 3),
 * including Monday, whose cutoff is Sunday 20:00 (owner decision 1).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Pazhamei\Domain\Cutoff;
use PHPUnit\Framework\TestCase;

/**
 * Lock times.
 */
final class CutoffTest extends TestCase {

	private DateTimeZone $ist;
	private Cutoff $cutoff;

	protected function setUp(): void {
		$this->ist    = new DateTimeZone( 'Asia/Kolkata' );
		$this->cutoff = new Cutoff( '20:00', $this->ist );
	}

	private function at( string $time ): DateTimeImmutable {
		return new DateTimeImmutable( $time, $this->ist );
	}

	public function test_1959_is_open_and_2001_is_locked(): void {
		// Thursday 3 Dec 2026; the day before is Wednesday 2 Dec.
		$this->assertFalse( $this->cutoff->is_locked( '2026-12-03', $this->at( '2026-12-02 19:59:00' ) ) );
		$this->assertTrue( $this->cutoff->is_locked( '2026-12-03', $this->at( '2026-12-02 20:01:00' ) ) );
	}

	public function test_exactly_2000_counts_as_locked(): void {
		$this->assertTrue( $this->cutoff->is_locked( '2026-12-03', $this->at( '2026-12-02 20:00:00' ) ) );
		$this->assertFalse( $this->cutoff->is_locked( '2026-12-03', $this->at( '2026-12-02 19:59:59' ) ) );
	}

	public function test_monday_locks_at_sunday_2000(): void {
		// Monday 7 Dec 2026; Sunday 6 Dec 20:00 is the cutoff (not Saturday).
		$this->assertFalse( $this->cutoff->is_locked( '2026-12-07', $this->at( '2026-12-05 21:00:00' ) ), 'Saturday night: still open' );
		$this->assertFalse( $this->cutoff->is_locked( '2026-12-07', $this->at( '2026-12-06 19:59:00' ) ) );
		$this->assertTrue( $this->cutoff->is_locked( '2026-12-07', $this->at( '2026-12-06 20:00:00' ) ) );
		$this->assertSame( '2026-12-06 20:00', $this->cutoff->locks_at( '2026-12-07' )->format( 'Y-m-d H:i' ) );
	}

	public function test_today_is_always_locked(): void {
		$this->assertTrue( $this->cutoff->is_locked( '2026-12-02', $this->at( '2026-12-02 06:00:00' ) ) );
	}

	public function test_first_unlocked_date_before_and_after_cutoff(): void {
		$this->assertSame( '2026-12-03', $this->cutoff->first_unlocked_date( $this->at( '2026-12-02 19:59:00' ) ) );
		$this->assertSame( '2026-12-04', $this->cutoff->first_unlocked_date( $this->at( '2026-12-02 20:01:00' ) ) );
	}

	public function test_cutoff_time_comes_from_settings(): void {
		$early = new Cutoff( '18:30', $this->ist );
		$this->assertTrue( $early->is_locked( '2026-12-03', $this->at( '2026-12-02 18:45:00' ) ) );
		$this->assertFalse( $this->cutoff->is_locked( '2026-12-03', $this->at( '2026-12-02 18:45:00' ) ) );
	}

	public function test_a_utc_clock_is_converted_to_ist(): void {
		// 14:31 UTC = 20:01 IST.
		$utc = new DateTimeImmutable( '2026-12-02 14:31:00', new DateTimeZone( 'UTC' ) );
		$this->assertTrue( $this->cutoff->is_locked( '2026-12-03', $utc ) );
	}

	public function test_bad_cutoff_time_is_refused(): void {
		$this->expectException( InvalidArgumentException::class );
		new Cutoff( '8pm', $this->ist );
	}
}
