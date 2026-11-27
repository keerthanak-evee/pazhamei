<?php
/**
 * Tests for Reflow::plan(), the pure scheduling walk (rules 1, 6–9, 12).
 *
 * Dates: 30 Nov 2026 is a Monday; 6 and 13 Dec are Sundays.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Calendar;
use Pazhamei\Domain\Day;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\Reflow;
use Pazhamei\Domain\ReflowPlan;
use PHPUnit\Framework\TestCase;

/**
 * The reflow walk.
 */
final class ReflowTest extends TestCase {

	private const S = '2026-11-30';

	private function pack( int $id, int $meals, string $status = PackStatus::ACTIVE, string $start = self::S, string $meal_type = 'VEG' ): Pack {
		$pack                  = new Pack();
		$pack->id              = $id;
		$pack->customer_id     = 7;
		$pack->total_meals     = $meals;
		$pack->status          = $status;
		$pack->requested_start = $start;
		$pack->meal_type       = $meal_type;
		$pack->created_at      = '2026-11-2' . $id . ' 10:00:00';
		return $pack;
	}

	private function day( int $id, string $date, int $pack, string $status ): Day {
		$day                  = new Day();
		$day->id              = $id;
		$day->customer_id     = 7;
		$day->service_date    = $date;
		$day->subscription_id = $pack;
		$day->meal_type       = 'VEG';
		$day->status          = $status;
		return $day;
	}

	private function pause( string $start, ?string $end ): Pause {
		$pause             = new Pause();
		$pause->id          = 1;
		$pause->customer_id = 7;
		$pause->start_date  = $start;
		$pause->end_date    = $end;
		return $pause;
	}

	/**
	 * Scheduled dates per pack from a plan.
	 *
	 * @return array<int, string[]>
	 */
	private function meals( ReflowPlan $plan, string $status = DayStatus::SCHEDULED ): array {
		$out = array();
		foreach ( $plan->inserts as $day ) {
			if ( $status === $day->status ) {
				$out[ $day->subscription_id ][] = $day->service_date;
			}
		}
		return $out;
	}

	public function test_six_day_pack_from_monday_skips_sunday(): void {
		$plan = Reflow::plan( 7, self::S, new Calendar(), array( $this->pack( 1, 6 ) ), array(), array() );

		$this->assertSame( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05' ), $this->meals( $plan )[1] );
		$this->assertSame( '2026-12-05', $plan->packs[1]['end_date'] );

		$plan = Reflow::plan( 7, '2026-12-02', new Calendar(), array( $this->pack( 1, 6, PackStatus::ACTIVE, '2026-12-02' ) ), array(), array() );
		$this->assertSame( array( '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05', '2026-12-07', '2026-12-08' ), $this->meals( $plan )[1], 'Sunday 6 Dec skipped' );
	}

	public function test_holidays_are_skipped(): void {
		$plan = Reflow::plan( 7, self::S, new Calendar( array( '2026-12-02' ) ), array( $this->pack( 1, 6 ) ), array(), array() );

		$this->assertNotContains( '2026-12-02', $this->meals( $plan )[1] );
		$this->assertSame( '2026-12-07', $plan->packs[1]['end_date'] );
	}

	public function test_skipped_row_is_kept_and_the_meal_moves_to_the_end(): void {
		$days = array( $this->day( 50, '2026-12-02', 1, DayStatus::SKIPPED ) );
		$plan = Reflow::plan( 7, self::S, new Calendar(), array( $this->pack( 1, 6 ) ), $days, array() );

		$this->assertNotContains( 50, $plan->delete_ids, 'Skipped rows are never touched' );
		$this->assertSame( '2026-12-07', $plan->packs[1]['end_date'] );
	}

	public function test_locked_and_delivered_rows_count_and_are_kept(): void {
		// Now is Tue 1 Dec after 20:00: Mon delivered, Tue locked... S = Thu 3 Dec, Wed locked too.
		$days = array(
			$this->day( 1, '2026-11-30', 1, DayStatus::DELIVERED ),
			$this->day( 2, '2026-12-01', 1, DayStatus::LOCKED ),
			$this->day( 3, '2026-12-02', 1, DayStatus::SCHEDULED ), // Before S: kept and counted.
			$this->day( 4, '2026-12-03', 1, DayStatus::SCHEDULED ), // On/after S: may be rewritten.
		);
		$plan = Reflow::plan( 7, '2026-12-03', new Calendar(), array( $this->pack( 1, 6 ) ), $days, array() );

		$this->assertSame( array(), $plan->delete_ids );
		$this->assertSame( array(), $plan->updates, 'Row 4 is unchanged, so it stays as it is' );
		$this->assertSame( array( '2026-12-04', '2026-12-05' ), $this->meals( $plan )[1] );
		$this->assertSame( '2026-11-30', $plan->packs[1]['start_date'] );
		$this->assertSame( '2026-12-05', $plan->packs[1]['end_date'] );
	}

	public function test_dated_pause_writes_paused_rows_and_pushes_the_end(): void {
		// Pause Fri 4 Dec to Tue 8 Dec (both included).
		$plan = Reflow::plan( 7, self::S, new Calendar(), array( $this->pack( 1, 6 ) ), array(), array( $this->pause( '2026-12-04', '2026-12-08' ) ) );

		$this->assertSame( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-09', '2026-12-10' ), $this->meals( $plan )[1] );
		$this->assertSame( array( '2026-12-04', '2026-12-05', '2026-12-07', '2026-12-08' ), $this->meals( $plan, DayStatus::PAUSED )[1] );
		$this->assertSame( PackStatus::ACTIVE, $plan->packs[1]['status'], 'Dated pauses never change the status' );
	}

	public function test_open_ended_pause_stops_the_walk_and_pauses_the_pack(): void {
		$plan = Reflow::plan( 7, self::S, new Calendar(), array( $this->pack( 1, 6 ) ), array(), array( $this->pause( '2026-12-02', null ) ) );

		$this->assertSame( array( '2026-11-30', '2026-12-01' ), $this->meals( $plan )[1] );
		$this->assertSame( PackStatus::PAUSED, $plan->packs[1]['status'] );
		$this->assertSame( '2026-12-01', $plan->packs[1]['end_date'] );
		$this->assertSame( '2026-12-02', $plan->stopped_at );
	}

	public function test_renewal_queues_behind_the_active_pack_without_overlap(): void {
		$packs = array( $this->pack( 1, 6 ), $this->pack( 2, 6, PackStatus::QUEUED, self::S, 'NONVEG' ) );
		$plan  = Reflow::plan( 7, self::S, new Calendar(), $packs, array(), array() );
		$meals = $this->meals( $plan );

		$this->assertSame( '2026-12-05', $plan->packs[1]['end_date'] );
		$this->assertSame( '2026-12-07', $plan->packs[2]['start_date'], 'Next service day after the current pack ends' );
		$this->assertSame( array(), array_intersect( $meals[1], $meals[2] ) );
		$this->assertSame( PackStatus::QUEUED, $plan->packs[2]['status'] );
		$this->assertSame( 'NONVEG', $plan->inserts[6]->meal_type );
	}

	public function test_renewal_behind_an_open_ended_pause_gets_no_dates(): void {
		$packs = array( $this->pack( 1, 6 ), $this->pack( 2, 6, PackStatus::QUEUED ) );
		$plan  = Reflow::plan( 7, self::S, new Calendar(), $packs, array(), array( $this->pause( '2026-12-02', null ) ) );

		$this->assertArrayNotHasKey( 2, $this->meals( $plan ) );
		$this->assertNull( $plan->packs[2]['start_date'] );
		$this->assertNull( $plan->packs[2]['end_date'] );
		$this->assertSame( PackStatus::QUEUED, $plan->packs[2]['status'] );
		$this->assertSame( PackStatus::PAUSED, $plan->packs[1]['status'] );
	}

	public function test_later_chosen_start_is_respected(): void {
		$packs = array( $this->pack( 1, 1 ), $this->pack( 2, 1, PackStatus::QUEUED, '2026-12-10' ) );
		$plan  = Reflow::plan( 7, self::S, new Calendar(), $packs, array(), array() );

		$this->assertSame( '2026-12-10', $plan->packs[2]['start_date'] );
	}

	public function test_rows_are_rewritten_with_the_smallest_change(): void {
		// Old layout had a meal on Wed 2 Dec; now Wed is a holiday.
		$days = array(
			$this->day( 10, '2026-11-30', 1, DayStatus::SCHEDULED ),
			$this->day( 11, '2026-12-02', 1, DayStatus::SCHEDULED ),
		);
		$plan = Reflow::plan( 7, self::S, new Calendar( array( '2026-12-02' ) ), array( $this->pack( 1, 2 ) ), $days, array() );

		$this->assertSame( array( 11 ), $plan->delete_ids );
		$this->assertSame( array( '2026-12-01' ), $this->meals( $plan )[1] );
		$this->assertSame( array(), $plan->updates );
	}

	public function test_meal_type_change_updates_rows_in_place(): void {
		$days = array( $this->day( 10, '2026-11-30', 1, DayStatus::SCHEDULED ) );
		$plan = Reflow::plan( 7, self::S, new Calendar(), array( $this->pack( 1, 1, PackStatus::ACTIVE, self::S, 'MIXED' ) ), $days, array() );

		$this->assertSame( 'MIXED', $plan->updates[10]['meal_type'] );
		$this->assertSame( array(), $plan->inserts );
	}

	public function test_walk_order_is_current_first_then_queued_by_purchase(): void {
		$order = Reflow::in_walk_order(
			array(
				$this->pack( 3, 1, PackStatus::QUEUED ),
				$this->pack( 2, 1, PackStatus::QUEUED ),
				$this->pack( 4, 1, PackStatus::PAUSED ),
				$this->pack( 5, 1, PackStatus::COMPLETED ),
			)
		);
		$this->assertSame( array( 4, 2, 3 ), array_map( static fn( Pack $p ) => $p->id, $order ) );
	}
}
