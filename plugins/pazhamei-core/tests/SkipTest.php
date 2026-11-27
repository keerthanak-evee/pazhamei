<?php
/**
 * Tests for skip and undo skip (rules 3, 6).
 *
 * Pack: 6-day Veg from Monday 30 Nov 2026 → Mon–Sat, ends Sat 5 Dec.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Skip rules.
 */
final class SkipTest extends TestCase {

	private TestKit $kit;
	private int $pack;

	protected function setUp(): void {
		$this->kit  = new TestKit( '2026-11-27 10:00:00' );
		$this->pack = $this->kit->buy( 7, 'D6' )->id;
	}

	public function test_skip_then_undo(): void {
		$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );

		$this->assertSame( DayStatus::SKIPPED, $this->kit->calendar( 7 )['2026-12-02'] );
		$this->assertSame( '2026-12-07', $this->kit->pack( $this->pack )->end_date, 'Meal moved to Monday 7 Dec' );
		$this->assertCount( 6, $this->kit->meals( 7 ) );

		$this->kit->engine->skips()->undo( 7, '2026-12-02', $this->kit->customer( 7 ) );

		$this->assertSame( DayStatus::SCHEDULED, $this->kit->calendar( 7 )['2026-12-02'] );
		$this->assertSame( '2026-12-05', $this->kit->pack( $this->pack )->end_date, 'End date back' );
		$this->assertArrayNotHasKey( '2026-12-07', $this->kit->calendar( 7 ) );
	}

	public function test_skip_at_1959_is_allowed(): void {
		$this->kit->at( '2026-12-01 19:59:00' );
		$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );
		$this->assertSame( DayStatus::SKIPPED, $this->kit->calendar( 7 )['2026-12-02'] );
	}

	public function test_skip_at_2001_is_refused(): void {
		$this->kit->at( '2026-12-01 20:01:00' );
		try {
			$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );
			$this->fail( 'Expected the cutoff to refuse it' );
		} catch ( RuleViolation $refused ) {
			$this->assertSame( 'locked', $refused->rule() );
			$this->assertStringContainsString( '20:00', $refused->getMessage() );
		}
		$this->assertSame( DayStatus::SCHEDULED, $this->kit->calendar( 7 )['2026-12-02'] );
	}

	public function test_monday_closes_sunday_2000(): void {
		$this->kit->at( '2026-11-29 19:59:00' );
		$this->kit->engine->skips()->skip( 7, '2026-11-30', $this->kit->customer( 7 ) );

		$this->kit->engine->skips()->undo( 7, '2026-11-30', $this->kit->customer( 7 ) );
		$this->kit->at( '2026-11-29 20:00:00' );

		$this->expectException( RuleViolation::class );
		$this->kit->engine->skips()->skip( 7, '2026-11-30', $this->kit->customer( 7 ) );
	}

	public function test_undo_after_the_cutoff_is_refused(): void {
		$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );
		$this->kit->at( '2026-12-01 20:01:00' );

		$this->expectException( RuleViolation::class );
		$this->kit->engine->skips()->undo( 7, '2026-12-02', $this->kit->customer( 7 ) );
	}

	public function test_only_scheduled_days_can_be_skipped(): void {
		$this->expectException( RuleViolation::class );
		$this->kit->engine->skips()->skip( 7, '2026-12-06', $this->kit->customer( 7 ) ); // Sunday: no meal.
	}

	public function test_no_limit_on_skips(): void {
		foreach ( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04' ) as $date ) {
			$this->kit->engine->skips()->skip( 7, $date, $this->kit->customer( 7 ) );
		}
		$this->assertCount( 6, $this->kit->meals( 7 ), 'Still six paid meals' );
		$this->assertSame( '2026-12-11', $this->kit->pack( $this->pack )->end_date, 'Sat 5, then Mon 7 to Fri 11 Dec' );
	}
}
