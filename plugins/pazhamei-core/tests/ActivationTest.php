<?php
/**
 * Tests for activation: paid order line → pack, idempotent on order_item_id,
 * renewals queued behind the current pack (rules 2, 9).
 *
 * Clock: Friday 27 Nov 2026, 10:00 IST. Monday is 30 Nov.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Repositories\Contracts\SubscriptionStore;
use Pazhamei\Repositories\Stores;
use Pazhamei\Services\Engine;
use Pazhamei\Services\PackRequest;
use Pazhamei\Tests\Fakes\MemorySubscriptions;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Activation and renewal.
 */
final class ActivationTest extends TestCase {

	public function test_six_day_veg_pack_starting_monday(): void {
		$kit  = new TestKit();
		$pack = $kit->buy( 7, 'D6', 'VEG', '2026-11-30' );

		$this->assertSame( PackStatus::ACTIVE, $pack->status );
		$this->assertSame( 6, $pack->total_meals );
		$this->assertSame( '2026-11-30', $pack->start_date );
		$this->assertSame( '2026-12-05', $pack->end_date );
		$this->assertSame( array( '2026-11-30', '2026-12-01', '2026-12-02', '2026-12-03', '2026-12-04', '2026-12-05' ), $kit->meals( 7 ) );
		$this->assertSame( array( 'pack_activated' ), $kit->event_names() );
	}

	public function test_repeated_payment_webhook_creates_one_pack(): void {
		$kit   = new TestKit();
		$first = $kit->buy( 7, 'D6', 'VEG', '2026-11-30', 4242 );
		$again = $kit->buy( 7, 'D6', 'VEG', '2026-11-30', 4242 );

		$this->assertSame( $first->id, $again->id );
		$this->assertCount( 1, $kit->db->rows( 'subscriptions' ) );
		$this->assertCount( 6, $kit->db->rows( 'schedule' ) );
		$this->assertSame( array( 'pack_activated' ), $kit->event_names(), 'Welcome email only once' );
	}

	public function test_simultaneous_webhooks_are_stopped_by_the_unique_key(): void {
		// Simulate the race: the second request's "already exists?" check misses
		// the first pack, so its insert hits UNIQUE (order_item_id).
		$kit = new TestKit();
		$kit->buy( 7, 'D6', 'VEG', '2026-11-30', 4242 );

		$blind  = new class( new MemorySubscriptions( $kit->db ) ) implements SubscriptionStore {
			private MemorySubscriptions $inner;
			public function __construct( MemorySubscriptions $inner ) {
				$this->inner = $inner;
			}
			public function insert( Pack $pack ): int {
				return $this->inner->insert( $pack );
			}
			public function find( int $id ): ?Pack {
				return $this->inner->find( $id );
			}
			private int $misses = 2;
			public function find_by_order_item( int $order_item_id ): ?Pack {
				return $this->misses-- > 0 ? null : $this->inner->find_by_order_item( $order_item_id );
			}
			public function for_order( int $order_id ): array {
				return $this->inner->for_order( $order_id );
			}
			public function for_customer( int $customer_id, array $statuses = array() ): array {
				return $this->inner->for_customer( $customer_id, $statuses );
			}
			public function update( int $id, array $fields ): void {
				$this->inner->update( $id, $fields );
			}
			public function customer_ids( array $statuses ): array {
				return $this->inner->customer_ids( $statuses );
			}
			public function has_plan( int $customer_id, string $plan_code ): bool {
				return $this->inner->has_plan( $customer_id, $plan_code );
			}
		};
		$stores = new Stores( $blind, $kit->stores->schedule, $kit->stores->pauses, $kit->stores->holidays, $kit->stores->daily_orders, $kit->stores->change_log, $kit->stores->profiles, $kit->stores->trial_claims, $kit->db );
		$engine = new Engine( $stores, $kit->engine->config, fn() => $kit->now, $kit, $kit );

		$request                  = new PackRequest();
		$request->customer_id     = 7;
		$request->order_item_id   = 4242;
		$request->plan_code       = 'D6';
		$request->meal_type       = 'VEG';
		$request->requested_start = '2026-11-30';

		$result = $engine->activation()->activate( $request, Actor::system() );

		$this->assertFalse( $result['created'] );
		$this->assertCount( 1, $kit->db->rows( 'subscriptions' ) );
		$this->assertCount( 6, $kit->db->rows( 'schedule' ) );
	}

	public function test_renewal_is_queued_behind_the_active_pack_with_no_overlap(): void {
		$kit     = new TestKit();
		$first   = $kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
		$renewal = $kit->buy( 7, 'D6', 'NONVEG', '2026-11-30' ); // Different meal type allowed (decision 7).

		$this->assertSame( PackStatus::QUEUED, $renewal->status );
		$this->assertSame( $first->id, $renewal->renewed_from_id );
		$this->assertSame( '2026-12-07', $renewal->start_date, 'Next service day after Sat 5 Dec (Sunday skipped)' );
		$this->assertSame( '2026-12-12', $renewal->end_date );
		$this->assertSame( array(), array_intersect( $kit->meals( 7, $first->id ), $kit->meals( 7, $renewal->id ) ) );
		$this->assertCount( 12, array_unique( $kit->meals( 7 ) ) );

		foreach ( $kit->stores->schedule->for_customer( 7 ) as $day ) {
			$this->assertSame( $day->subscription_id === $renewal->id ? 'NONVEG' : 'VEG', $day->meal_type );
		}
	}

	public function test_renewal_moves_when_the_current_pack_end_date_moves(): void {
		$kit     = new TestKit();
		$first   = $kit->buy( 7, 'D6' );
		$renewal = $kit->buy( 7, 'D6' );

		$kit->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) );

		$this->assertSame( '2026-12-07', $kit->pack( $first->id )->end_date );
		$this->assertSame( '2026-12-08', $kit->pack( $renewal->id )->start_date );
	}

	public function test_trial_pack_has_one_meal_and_records_a_claim(): void {
		$kit  = new TestKit();
		$pack = $kit->buy( 7, 'TRIAL', 'MIXED', '2026-11-28' );

		$this->assertSame( 1, $pack->total_meals );
		$this->assertSame( array( '2026-11-28' ), $kit->meals( 7 ) );
		$this->assertCount( 1, $kit->db->rows( 'trial_claims' ) );
	}

	public function test_late_payment_slides_the_start_to_the_first_unlocked_day(): void {
		// Picked Saturday 28 Nov, but the payment only lands Friday 21:00 (after the cutoff).
		$kit  = ( new TestKit() )->at( '2026-11-27 21:00:00' );
		$pack = $kit->buy( 7, 'D6', 'VEG', '2026-11-28' );

		$this->assertSame( '2026-11-30', $pack->start_date );
	}

	public function test_invalid_order_lines_are_refused(): void {
		$kit = new TestKit();
		$this->expectException( RuleViolation::class );
		$kit->buy( 7, 'D99' );
	}

	public function test_guest_orders_are_refused(): void {
		$kit = new TestKit();
		$this->expectException( RuleViolation::class );
		$kit->buy( 0, 'D6' );
	}
}
