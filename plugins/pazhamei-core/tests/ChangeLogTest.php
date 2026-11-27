<?php
/**
 * Tests that every change writes a change log entry, with who, when and
 * the values before and after.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Change log coverage.
 */
final class ChangeLogTest extends TestCase {

	/**
	 * Actions logged since a given entry count.
	 *
	 * @return string[]
	 */
	private function new_actions( TestKit $kit, int $since ): array {
		return array_slice( $kit->stores->change_log->actions(), $since );
	}

	public function test_every_action_is_logged(): void {
		$kit = new TestKit( '2026-11-27 10:00:00' );
		$log = $kit->stores->change_log;

		$expect = function ( callable $action, array $actions ) use ( $kit, $log ) {
			$since = count( $log->actions() );
			$action();
			foreach ( $actions as $name ) {
				$this->assertContains( $name, $this->new_actions( $kit, $since ), "Missing '{$name}' in the change log" );
			}
		};

		$expect( fn() => $kit->buy( 7, 'D6' ), array( 'activated', 'reflow' ) );
		$expect( fn() => $kit->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) ), array( 'skipped', 'reflow' ) );
		$expect( fn() => $kit->engine->skips()->undo( 7, '2026-12-02', $kit->customer( 7 ) ), array( 'skip_undone', 'reflow' ) );
		$expect( fn() => $kit->engine->pauses()->pause( 7, '2026-12-03', null, $kit->customer( 7 ) ), array( 'paused', 'reflow' ) );
		$expect( fn() => $kit->buy( 7, 'D6', 'NONVEG' ), array( 'activated' ) );
		$expect( fn() => $kit->engine->pauses()->resume( 7, $kit->customer( 7 ) ), array( 'resumed', 'reflow' ) );
		$expect( fn() => $kit->engine->holidays()->add( '2026-12-04', 'Holiday', $kit->admin() ), array( 'holiday_added', 'reflow' ) );
		$expect( fn() => $kit->engine->holidays()->remove( '2026-12-04', $kit->admin() ), array( 'holiday_removed', 'reflow' ) );
		$expect( fn() => $kit->engine->packs()->change_meal_type( 1, 'MIXED', $kit->admin() ), array( 'meal_type_changed', 'reflow' ) );
		$expect( fn() => $kit->engine->packs()->credit_meal( 1, $kit->admin(), 'Late delivery' ), array( 'meal_credited', 'reflow' ) );
		$expect( fn() => $kit->at( '2026-11-29 20:01:00' )->engine->lock_job()->run( Actor::system() ), array( 'locked', 'lock_run' ) );
		$expect( fn() => $kit->at( '2026-11-30 23:31:00' )->engine->close_day_job()->run( Actor::system() ), array( 'delivered', 'close_day' ) );
		$expect( fn() => $kit->at( '2026-12-01 00:10:00' )->engine->pack_status_job()->run( Actor::system() ), array( 'pack_status' ) );
		$expect( fn() => $kit->at( '2026-12-01 10:00:00' )->engine->packs()->cancel( 2, PackStatus::CANCELLED, $kit->admin(), 'Test' ), array( 'cancelled' ) );
	}

	public function test_entries_record_who_when_why_and_values(): void {
		$kit = new TestKit( '2026-11-27 10:00:00' );
		$kit->buy( 7, 'D6' );
		$kit->at( '2026-11-28 09:15:00' )->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) );

		$entry = $kit->stores->change_log->for_entity( 'schedule', $kit->stores->schedule->find_by_date( 7, '2026-12-02' )->id );
		$entry = end( $entry );

		$this->assertSame( 'skipped', $entry['action'] );
		$this->assertSame( 7, $entry['actor_id'] );
		$this->assertSame( 'customer', $entry['actor_role'] );
		$this->assertSame( '127.0.0.1', $entry['ip'] );
		$this->assertSame( '2026-11-28 09:15:00', $entry['created_at'], 'IST time from the engine clock' );
		$this->assertSame( '{"status":"scheduled"}', $entry['old_json'] );
		$this->assertSame( '{"status":"skipped"}', $entry['new_json'] );

		$reflow = $kit->stores->change_log->for_entity( 'customer', 7 );
		$reflow = json_decode( (string) end( $reflow )['new_json'], true );
		$this->assertSame( '2026-12-07', $reflow['packs'][1]['end_date'], 'Reflow entry carries the new end date' );
	}

	public function test_refused_actions_leave_no_entry(): void {
		$kit   = new TestKit( '2026-11-27 10:00:00' );
		$kit->buy( 7, 'D6' );
		$count = count( $kit->stores->change_log->actions() );

		try {
			$kit->at( '2026-12-01 20:30:00' )->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) );
		} catch ( \Pazhamei\Domain\RuleViolation $refused ) {
			unset( $refused );
		}
		$this->assertCount( $count, $kit->stores->change_log->actions() );
	}
}
