<?php
/**
 * Tests for the scheduled jobs: 20:00 lock (rule 11), 23:30 close day
 * (rule 15) and 00:10 pack status.
 *
 * Pack: 6-day Veg from Monday 30 Nov 2026.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\EngineConfig;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Lock, close day, pack status.
 */
final class JobsTest extends TestCase {

	private function kit( bool $auto_mark = true ): TestKit {
		$config                      = EngineConfig::defaults();
		$config->auto_mark_delivered = $auto_mark;
		$kit                         = new TestKit( '2026-11-27 10:00:00', $config );
		$kit->buy( 7, 'D6' );
		$kit->buy( 8, 'D6', 'NONVEG' );
		return $kit;
	}

	public function test_lock_at_sunday_2001_locks_monday_and_creates_orders(): void {
		$kit    = $this->kit();
		$result = $kit->at( '2026-11-29 20:01:00' )->engine->lock_job()->run( Actor::system() );

		$this->assertSame( array( 'from' => '2026-11-29', 'to' => '2026-11-30', 'locked' => 2, 'orders_created' => 2 ), $result );
		$this->assertSame( DayStatus::LOCKED, $kit->calendar( 7 )['2026-11-30'] );
		$this->assertSame( DayStatus::SCHEDULED, $kit->calendar( 7 )['2026-12-01'] );

		$orders = $kit->stores->daily_orders->for_date( '2026-11-30' );
		$this->assertCount( 2, $orders );
		$this->assertSame( 'confirmed', $orders[0]['status'] );
		$this->assertSame( 1, $orders[0]['quantity'] );
		$this->assertStringContainsString( 'Test Street', $orders[0]['address_json'] );
	}

	public function test_lock_job_rerun_creates_no_duplicates(): void {
		$kit = $this->kit()->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );
		$again = $kit->engine->lock_job()->run( Actor::system() );

		$this->assertSame( 0, $again['locked'] );
		$this->assertSame( 0, $again['orders_created'] );
		$this->assertCount( 2, $kit->db->rows( 'daily_orders' ) );
	}

	public function test_lock_job_fills_in_orders_missing_after_a_crash(): void {
		$kit = $this->kit()->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );
		$kit->db->delete( 'daily_orders', 1 ); // As if the job died after locking.

		$again = $kit->engine->lock_job()->run( Actor::system() );
		$this->assertSame( 1, $again['orders_created'] );
		$this->assertCount( 2, $kit->db->rows( 'daily_orders' ) );
	}

	public function test_lock_job_before_the_cutoff_locks_nothing(): void {
		$kit    = $this->kit()->at( '2026-11-29 19:59:00' );
		$result = $kit->engine->lock_job()->run( Actor::system() );

		$this->assertSame( 0, $result['locked'] );
	}

	public function test_a_late_lock_job_still_locks_the_right_day(): void {
		// The 20:00 Sunday job only fires at 00:20 Monday.
		$kit    = $this->kit()->at( '2026-11-30 00:20:00' );
		$result = $kit->engine->lock_job()->run( Actor::system() );

		$this->assertSame( 2, $result['locked'] );
		$this->assertSame( DayStatus::LOCKED, $kit->calendar( 7 )['2026-11-30'] );
		$this->assertSame( DayStatus::SCHEDULED, $kit->calendar( 7 )['2026-12-01'], 'Tuesday is still open until Monday 20:00' );
	}

	public function test_skip_after_the_lock_is_refused(): void {
		$kit = $this->kit()->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );

		$this->expectException( RuleViolation::class );
		$kit->engine->skips()->skip( 7, '2026-11-30', $kit->customer( 7 ) );
	}

	public function test_auto_mark_delivered_on(): void {
		$kit = $this->kit( true )->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );

		$result = $kit->at( '2026-11-30 23:31:00' )->engine->close_day_job()->run( Actor::system() );

		$this->assertSame( 2, $result['delivered'] );
		$this->assertSame( DayStatus::DELIVERED, $kit->calendar( 7 )['2026-11-30'] );
		$this->assertSame( 'delivered', $kit->stores->daily_orders->find_by_day( $kit->stores->schedule->find_by_date( 7, '2026-11-30' )->id )['status'] );
		$this->assertSame( 1, $kit->pack( 1 )->consumed_meals );
	}

	public function test_auto_mark_delivered_off_leaves_days_locked(): void {
		$kit = $this->kit( false )->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );

		$result = $kit->at( '2026-11-30 23:31:00' )->engine->close_day_job()->run( Actor::system() );

		$this->assertSame( 0, $result['delivered'] );
		$this->assertFalse( $result['auto_mark_delivered'] );
		$this->assertSame( DayStatus::LOCKED, $kit->calendar( 7 )['2026-11-30'] );
		$this->assertSame( 0, $kit->pack( 1 )->consumed_meals );
	}

	public function test_failed_day_is_not_overwritten_by_close_day(): void {
		$kit = $this->kit()->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );
		$day = $kit->stores->schedule->find_by_date( 7, '2026-11-30' );

		$kit->at( '2026-11-30 14:00:00' )->engine->deliveries()->mark_failed( $day->id, $kit->admin(), 'Rider could not find the house' );
		$kit->at( '2026-11-30 23:31:00' )->engine->close_day_job()->run( Actor::system() );

		$this->assertSame( DayStatus::FAILED, $kit->calendar( 7 )['2026-11-30'] );
		$this->assertSame( DayStatus::DELIVERED, $kit->calendar( 8 )['2026-11-30'] );
		$this->assertSame( 1, $kit->pack( 1 )->consumed_meals, 'A failed day still uses its meal (no automatic credit)' );
		$this->assertSame( '2026-12-05', $kit->pack( 1 )->end_date );
	}

	public function test_close_day_before_2330_does_not_close_today(): void {
		$kit = $this->kit()->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( Actor::system() );

		$result = $kit->at( '2026-11-30 13:00:00' )->engine->close_day_job()->run( Actor::system() );
		$this->assertSame( '2026-11-29', $result['through'] );
		$this->assertSame( DayStatus::LOCKED, $kit->calendar( 7 )['2026-11-30'] );
	}

	public function test_pack_completes_and_queued_renewal_starts(): void {
		$kit     = new TestKit( '2026-11-27 10:00:00' );
		$trial   = $kit->buy( 7, 'TRIAL', 'VEG', '2026-11-30' );
		$renewal = $kit->buy( 7, 'D6', 'MIXED', '2026-11-30' );
		$this->assertSame( PackStatus::QUEUED, $renewal->status );
		$this->assertSame( '2026-12-01', $renewal->start_date );

		$kit->at( '2026-11-29 20:05:00' )->engine->lock_job()->run( Actor::system() );
		$kit->at( '2026-11-30 23:30:00' )->engine->close_day_job()->run( Actor::system() );
		$result = $kit->at( '2026-12-01 00:10:00' )->engine->pack_status_job()->run( Actor::system() );

		$this->assertSame( 1, $result['completed'] );
		$this->assertSame( PackStatus::COMPLETED, $kit->pack( $trial->id )->status );
		$this->assertSame( PackStatus::ACTIVE, $kit->pack( $renewal->id )->status );
		$this->assertSame( '2026-12-01', $kit->pack( $renewal->id )->start_date );
	}

	public function test_dated_pause_ends_after_its_date_and_open_pause_fires_reminder_hook(): void {
		$kit = new TestKit( '2026-11-27 10:00:00' );
		$kit->buy( 7, 'D6' );
		$kit->buy( 8, 'D6' );
		$kit->engine->pauses()->pause( 7, '2026-11-30', '2026-12-01', $kit->customer( 7 ) );
		$kit->engine->pauses()->pause( 8, '2026-11-30', null, $kit->customer( 8 ) );

		$result = $kit->at( '2026-12-02 00:10:00' )->engine->pack_status_job()->run( Actor::system() );

		$this->assertSame( 1, $result['pauses_ended'] );
		$this->assertSame( Pause::ENDED, $kit->stores->pauses->for_customer( 7 )[0]->status );
		$this->assertSame( Pause::ACTIVE, $kit->stores->pauses->for_customer( 8 )[0]->status, 'No auto-resume' );

		$ongoing = array_values( array_filter( $kit->events, static fn( $e ) => 'pause_ongoing' === $e[0] ) );
		$this->assertSame( array( 2, 8, 2 ), $ongoing[0][1], 'pause id, customer, days paused' );
	}
}
