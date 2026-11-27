<?php
/**
 * Tests for admin actions: credit meal (rule 14), holidays (rule 1),
 * cancel (rule 13) and change meal type.
 *
 * Pack: 6-day Veg from Monday 30 Nov 2026 (ends Sat 5 Dec).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Admin actions.
 */
final class AdminActionsTest extends TestCase {

	private TestKit $kit;

	protected function setUp(): void {
		$this->kit = new TestKit( '2026-11-27 10:00:00' );
		$this->kit->buy( 7, 'D6' );
	}

	public function test_credit_meal_adds_one_meal_at_the_end(): void {
		$pack = $this->kit->engine->packs()->credit_meal( 1, $this->kit->admin(), 'Failed delivery on 30 Nov' );

		$this->assertSame( 7, $pack->total_meals );
		$this->assertSame( '2026-12-07', $pack->end_date );
		$this->assertCount( 7, $this->kit->meals( 7 ) );
	}

	public function test_credit_reopens_a_completed_pack(): void {
		$this->kit->stores->subscriptions->update( 1, array( 'status' => PackStatus::COMPLETED ) );
		$pack = $this->kit->engine->packs()->credit_meal( 1, $this->kit->admin() );

		$this->assertSame( PackStatus::ACTIVE, $pack->status );
	}

	public function test_holiday_cancels_scheduled_days_and_extends_the_pack(): void {
		$this->kit->engine->holidays()->add( '2026-12-02', 'Karthigai Deepam', $this->kit->admin() );

		$this->assertArrayNotHasKey( '2026-12-02', $this->kit->calendar( 7 ) );
		$this->assertSame( '2026-12-07', $this->kit->pack( 1 )->end_date );
		$this->assertCount( 6, $this->kit->meals( 7 ) );
	}

	public function test_holiday_on_a_locked_day_cancels_it_and_gives_the_meal_back(): void {
		$this->kit->at( '2026-11-29 20:01:00' )->engine->lock_job()->run( Actor::system() );
		$this->kit->engine->holidays()->add( '2026-11-30', 'Kitchen closed', $this->kit->admin() );

		$day = $this->kit->stores->schedule->find_by_date( 7, '2026-11-30' );
		$this->assertSame( DayStatus::HOLIDAY_CANCELLED, $day->status );
		$this->assertSame( 'cancelled', $this->kit->stores->daily_orders->find_by_day( $day->id )['status'] );
		$this->assertSame( '2026-12-07', $this->kit->pack( 1 )->end_date );
		$this->assertCount( 6, $this->kit->meals( 7 ) );
	}

	public function test_removing_a_holiday_gives_the_day_back(): void {
		$this->kit->engine->holidays()->add( '2026-12-02', 'Mistake', $this->kit->admin() );
		$this->kit->engine->holidays()->remove( '2026-12-02', $this->kit->admin() );

		$this->assertSame( DayStatus::SCHEDULED, $this->kit->calendar( 7 )['2026-12-02'] );
		$this->assertSame( '2026-12-05', $this->kit->pack( 1 )->end_date );
	}

	public function test_past_holidays_and_duplicates_are_refused(): void {
		$this->kit->engine->holidays()->add( '2026-12-02', 'One', $this->kit->admin() );
		try {
			$this->kit->engine->holidays()->add( '2026-12-02', 'Again', $this->kit->admin() );
			$this->fail( 'Duplicate holiday accepted' );
		} catch ( RuleViolation $refused ) {
			$this->assertSame( 'exists', $refused->rule() );
		}
		$this->expectException( RuleViolation::class );
		$this->kit->engine->holidays()->add( '2026-11-20', 'Past', $this->kit->admin() );
	}

	public function test_cancel_removes_only_unlocked_days(): void {
		// Sunday 20:01: Monday is locked. Tue 1 Dec onward is unlocked.
		$this->kit->at( '2026-11-29 20:01:00' )->engine->lock_job()->run( Actor::system() );
		$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );

		$this->assertTrue( $this->kit->engine->packs()->cancel( 1, PackStatus::CANCELLED, $this->kit->admin(), 'Customer moved away' ) );

		$this->assertSame( array( '2026-11-30' => DayStatus::LOCKED ), $this->kit->calendar( 7 ), 'Locked Monday stays; skipped Wednesday removed too' );
		$pack = $this->kit->pack( 1 );
		$this->assertSame( PackStatus::CANCELLED, $pack->status );
		$this->assertSame( '2026-11-30', $pack->end_date );
		$this->assertNotNull( $this->kit->stores->daily_orders->find_by_day( $this->kit->stores->schedule->find_by_date( 7, '2026-11-30' )->id ) );
	}

	public function test_cancel_twice_does_nothing_the_second_time(): void {
		$this->kit->engine->packs()->cancel( 1, PackStatus::REFUNDED, Actor::system(), 'WooCommerce refund' );
		$this->assertFalse( $this->kit->engine->packs()->cancel( 1, PackStatus::REFUNDED, Actor::system(), 'Webhook again' ) );
		$this->assertSame( PackStatus::REFUNDED, $this->kit->pack( 1 )->status );
	}

	public function test_cancelling_the_current_pack_starts_the_queued_renewal(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );
		$renewal = $this->kit->buy( 7, 'D6', 'MIXED' );
		$this->assertNull( $renewal->start_date );

		$this->kit->engine->packs()->cancel( 1, PackStatus::CANCELLED, $this->kit->admin() );

		$renewal = $this->kit->pack( $renewal->id );
		$this->assertSame( PackStatus::ACTIVE, $renewal->status );
		$this->assertSame( '2026-11-30', $renewal->start_date, 'The pause ended with the cancelled pack, so the renewal starts on its chosen date' );
		$this->assertSame( Pause::CANCELLED, $this->kit->stores->pauses->for_customer( 7 )[0]->status );
	}

	public function test_change_meal_type_changes_unlocked_days_only(): void {
		$this->kit->at( '2026-11-29 20:01:00' )->engine->lock_job()->run( Actor::system() );
		$this->kit->engine->packs()->change_meal_type( 1, 'NONVEG', $this->kit->admin() );

		$types = array();
		foreach ( $this->kit->stores->schedule->for_customer( 7 ) as $day ) {
			$types[ $day->service_date ] = $day->meal_type;
		}
		$this->assertSame( 'VEG', $types['2026-11-30'], 'Locked Monday keeps its type' );
		$this->assertSame( 'NONVEG', $types['2026-12-01'] );
		$this->assertSame( 'NONVEG', $this->kit->pack( 1 )->meal_type );
	}
}
