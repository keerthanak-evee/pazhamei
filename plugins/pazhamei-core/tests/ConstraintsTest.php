<?php
/**
 * Tests that the in-memory test stores enforce the same constraints as the
 * MySQL tables (UNIQUE keys, NOT NULL, transactions), and that every
 * schedule change runs in a transaction holding the customer's lock.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Day;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Repositories\DuplicateKey;
use Pazhamei\Repositories\StorageError;
use Pazhamei\Tests\Fakes\MemoryDb;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;
use RuntimeException;

/**
 * Storage constraints and locking.
 */
final class ConstraintsTest extends TestCase {

	private function day( int $customer, string $date ): Day {
		$day                  = new Day();
		$day->customer_id     = $customer;
		$day->service_date    = $date;
		$day->subscription_id = 1;
		$day->meal_type       = 'VEG';
		$day->created_at      = '2026-11-27 10:00:00';
		$day->updated_at      = '2026-11-27 10:00:00';
		return $day;
	}

	public function test_one_row_per_customer_per_date_like_mysql(): void {
		$kit = new TestKit();
		$kit->stores->schedule->insert( $this->day( 7, '2026-11-30' ) );
		$kit->stores->schedule->insert( $this->day( 8, '2026-11-30' ) ); // Another customer: fine.

		try {
			$kit->stores->schedule->insert( $this->day( 7, '2026-11-30' ) );
			$this->fail( 'Expected a duplicate key error' );
		} catch ( DuplicateKey $error ) {
			$this->assertSame( "Duplicate entry '7-2026-11-30' for key 'wp_pz_schedule.customer_date'", $error->getMessage() );
		}
		$this->assertCount( 2, $kit->db->rows( 'schedule' ), 'The failed insert changed nothing' );
	}

	public function test_moving_a_row_onto_a_taken_date_fails_too(): void {
		$kit = new TestKit();
		$kit->stores->schedule->insert( $this->day( 7, '2026-11-30' ) );
		$id = $kit->stores->schedule->insert( $this->day( 7, '2026-12-01' ) );

		$this->expectException( DuplicateKey::class );
		$kit->stores->schedule->update( $id, array( 'service_date' => '2026-11-30' ) );
	}

	public function test_other_unique_keys(): void {
		$kit = new TestKit();
		$kit->buy( 7, 'D6', 'VEG', '2026-11-30', 99 );
		$pack                = $kit->pack( 1 );
		$pack->id            = 0;
		$pack->order_item_id = 99;

		$this->expectException( DuplicateKey::class );
		$kit->stores->subscriptions->insert( $pack );
	}

	public function test_explicit_null_in_a_not_null_column_fails(): void {
		$db = new MemoryDb();
		$this->expectException( StorageError::class );
		$db->insert( 'schedule', array( 'customer_id' => 7, 'service_date' => null ) );
	}

	public function test_a_failed_change_rolls_everything_back(): void {
		$kit = new TestKit();
		$kit->buy( 7, 'D6' );
		$before = $kit->calendar( 7 );

		try {
			$kit->engine->customer_tx(
				7,
				function () use ( $kit ) {
					$day = $kit->stores->schedule->find_by_date( 7, '2026-12-02' );
					$kit->stores->schedule->update( $day->id, array( 'status' => DayStatus::SKIPPED ) );
					$kit->engine->reflow()->run( 7, $kit->customer( 7 ), 'test' );
					throw new RuntimeException( 'Something failed half way' );
				}
			);
		} catch ( RuntimeException $expected ) {
			unset( $expected );
		}

		$this->assertSame( $before, $kit->calendar( 7 ) );
		$this->assertSame( '2026-12-05', $kit->pack( 1 )->end_date );
		$this->assertSame( 1, $kit->db->rollbacks );
	}

	public function test_skip_runs_in_a_transaction_holding_the_customer_lock(): void {
		$kit = new TestKit();
		$kit->buy( 7, 'D6' );
		$locks = $kit->stores->profiles->locks;
		$reads = $kit->stores->schedule->locked_reads;

		$kit->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) );

		$this->assertSame( $locks + 1, $kit->stores->profiles->locks );
		$this->assertGreaterThan( $reads, $kit->stores->schedule->locked_reads, 'reflow() reads the rows FOR UPDATE' );
	}
}
