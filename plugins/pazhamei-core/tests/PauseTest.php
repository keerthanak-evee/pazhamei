<?php
/**
 * Tests for pause and resume (rules 7, 9; owner decisions 2 and 10).
 *
 * Clock: Friday 27 Nov 2026, 10:00 IST. Pack: 6-day Veg from Monday 30 Nov.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Pause rules.
 */
final class PauseTest extends TestCase {

	private TestKit $kit;
	private int $pack;

	protected function setUp(): void {
		$this->kit  = new TestKit( '2026-11-27 10:00:00' );
		$this->pack = $this->kit->buy( 7, 'D6' )->id;
	}

	public function test_dated_pause_moves_meals_to_the_end(): void {
		// Friday 4 Dec to the following Tuesday 8 Dec (both paused).
		$this->kit->engine->pauses()->pause( 7, '2026-12-04', '2026-12-08', $this->kit->customer( 7 ) );

		$this->assertSame( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-09', '2026-12-10' ), $this->kit->meals( 7 ) );
		$calendar = $this->kit->calendar( 7 );
		foreach ( array( '2026-12-04', '2026-12-05', '2026-12-07', '2026-12-08' ) as $date ) {
			$this->assertSame( DayStatus::PAUSED, $calendar[ $date ], $date );
		}
		$this->assertSame( '2026-12-10', $this->kit->pack( $this->pack )->end_date );
		$this->assertSame( PackStatus::ACTIVE, $this->kit->pack( $this->pack )->status, 'A dated pause never changes the status' );
	}

	public function test_open_ended_pause_then_resume(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );

		$pack = $this->kit->pack( $this->pack );
		$this->assertSame( PackStatus::PAUSED, $pack->status );
		$this->assertSame( array( '2026-11-30', '2026-12-01' ), $this->kit->meals( 7 ) );
		$this->assertSame( '2026-12-01', $pack->end_date );

		// A week later (Wed 9 Dec, 10:00) the customer resumes: meals restart Thu 10 Dec.
		$this->kit->at( '2026-12-09 10:00:00' );
		$pause = $this->kit->engine->pauses()->resume( 7, $this->kit->customer( 7 ) );

		$this->assertSame( Pause::ENDED, $pause->status );
		$this->assertSame( '2026-12-09', $pause->end_date );
		$pack = $this->kit->pack( $this->pack );
		$this->assertSame( PackStatus::ACTIVE, $pack->status );
		$this->assertSame( array( '2026-11-30', '2026-12-01', '2026-12-10', '2026-12-11', '2026-12-12', '2026-12-14' ), $this->kit->meals( 7 ) );
		$this->assertSame( '2026-12-14', $pack->end_date );
	}

	public function test_open_ended_pause_then_renewal_then_resume(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );

		// Buying while paused: the renewal waits with no dates (decision 10).
		$renewal = $this->kit->buy( 7, 'D6', 'NONVEG', '2026-11-30' );
		$this->assertSame( PackStatus::QUEUED, $renewal->status );
		$this->assertNull( $renewal->start_date );
		$this->assertNull( $renewal->end_date );
		$this->assertSame( array(), $this->kit->meals( 7, $renewal->id ) );
		$this->assertSame( PackStatus::PAUSED, $this->kit->pack( $this->pack )->status );

		// Resume on Wed 9 Dec: first pack finishes, then the renewal follows. No overlap.
		$this->kit->at( '2026-12-09 10:00:00' );
		$this->kit->engine->pauses()->resume( 7, $this->kit->customer( 7 ) );

		$first   = $this->kit->pack( $this->pack );
		$renewal = $this->kit->pack( $renewal->id );
		$this->assertSame( PackStatus::ACTIVE, $first->status );
		$this->assertSame( '2026-12-14', $first->end_date );
		$this->assertSame( PackStatus::QUEUED, $renewal->status );
		$this->assertSame( '2026-12-15', $renewal->start_date );
		$this->assertSame( '2026-12-21', $renewal->end_date );
		$this->assertSame( array(), array_intersect( $this->kit->meals( 7, $first->id ), $this->kit->meals( 7, $renewal->id ) ) );
		$this->assertCount( 12, $this->kit->meals( 7 ) );
	}

	public function test_pause_must_start_on_an_unlocked_date(): void {
		$this->kit->at( '2026-12-01 20:01:00' );
		$this->expectException( RuleViolation::class );
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );
	}

	public function test_overlapping_pauses_are_refused(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', '2026-12-04', $this->kit->customer( 7 ) );
		$this->expectException( RuleViolation::class );
		$this->kit->engine->pauses()->pause( 7, '2026-12-04', null, $this->kit->customer( 7 ) );
	}

	public function test_no_time_limit_on_a_pause(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', '2027-06-30', $this->kit->customer( 7 ) );
		$this->assertSame( '2027-07-05', $this->kit->pack( $this->pack )->end_date, 'Seven months later: the last 4 meals on Thu 1, Fri 2, Sat 3, Mon 5 Jul 2027' );
	}

	public function test_resume_before_the_pause_begins_withdraws_it(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-03', null, $this->kit->customer( 7 ) );
		$pause = $this->kit->engine->pauses()->resume( 7, $this->kit->customer( 7 ) );

		$this->assertSame( Pause::CANCELLED, $pause->status );
		$this->assertSame( '2026-12-05', $this->kit->pack( $this->pack )->end_date );
	}

	public function test_dated_pause_can_be_ended_early(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-01', '2026-12-31', $this->kit->customer( 7 ) );
		$this->kit->at( '2026-12-03 10:00:00' );
		$pause = $this->kit->engine->pauses()->resume( 7, $this->kit->customer( 7 ) );

		$this->assertSame( '2026-12-03', $pause->end_date );
		$this->assertSame( array( '2026-11-30', '2026-12-04', '2026-12-05', '2026-12-07', '2026-12-08', '2026-12-09' ), $this->kit->meals( 7 ) );
	}

	public function test_resume_with_no_pause_is_refused(): void {
		$this->expectException( RuleViolation::class );
		$this->kit->engine->pauses()->resume( 7, $this->kit->customer( 7 ) );
	}

	public function test_admin_can_resume_for_the_customer(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );
		$this->kit->engine->pauses()->resume( 7, $this->kit->admin() );

		$entries = $this->kit->stores->change_log->for_entity( 'pause', 1 );
		$this->assertSame( 'admin', end( $entries )['actor_role'] );
	}
}
