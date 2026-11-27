<?php
/**
 * DeliveryService: marks a locked day delivered or failed (rules 14, 15).
 *
 * Used by the 23:30 close-day job now, and by the kitchen portal in
 * Phase 7. A failed day still uses up its meal; the admin decides whether
 * to credit one back (PackAdminService::credit_meal()).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Day;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\RuleViolation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivered / failed.
 */
final class DeliveryService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Mark a locked day delivered.
	 *
	 * @param int   $day_id Schedule row.
	 * @param Actor $actor  Who.
	 * @throws RuleViolation When the day is not locked.
	 */
	public function mark_delivered( int $day_id, Actor $actor ): void {
		$this->mark( $day_id, DayStatus::DELIVERED, array( DayStatus::LOCKED ), $actor, '' );
	}

	/**
	 * Mark a locked (or wrongly delivered) day failed.
	 *
	 * @param int    $day_id Schedule row.
	 * @param Actor  $actor  Who.
	 * @param string $reason What went wrong.
	 * @throws RuleViolation When the day is not locked or delivered.
	 */
	public function mark_failed( int $day_id, Actor $actor, string $reason = '' ): void {
		$this->mark( $day_id, DayStatus::FAILED, array( DayStatus::LOCKED, DayStatus::DELIVERED ), $actor, $reason );
	}

	/**
	 * Recount a pack's consumed meals (delivered + failed rows).
	 *
	 * @param int $pack_id Pack.
	 */
	public function recount( int $pack_id ): void {
		$pack = $this->engine->stores->subscriptions->find( $pack_id );
		if ( ! $pack ) {
			return;
		}
		$consumed = $this->engine->stores->schedule->count_for_pack( $pack_id, DayStatus::CONSUMED );
		if ( $consumed !== $pack->consumed_meals ) {
			$this->engine->stores->subscriptions->update(
				$pack_id,
				array(
					'consumed_meals' => $consumed,
					'updated_at'     => $this->engine->stamp(),
				)
			);
		}
	}

	/**
	 * Change a day's status, its daily order and the pack's count.
	 *
	 * @param int      $day_id  Schedule row.
	 * @param string   $status  New status.
	 * @param string[] $allowed Statuses it may change from.
	 * @param Actor    $actor   Who.
	 * @param string   $reason  Why.
	 * @throws RuleViolation When the day is missing or in the wrong status.
	 */
	private function mark( int $day_id, string $status, array $allowed, Actor $actor, string $reason ): void {
		$day = $this->engine->stores->schedule->find( $day_id );
		if ( ! $day ) {
			throw new RuleViolation( 'not_found', "Day #{$day_id} does not exist." );
		}

		$this->engine->customer_tx(
			$day->customer_id,
			function () use ( $day_id, $status, $allowed, $actor, $reason ) {
				$day = $this->engine->stores->schedule->find( $day_id ); // Fresh, under the lock.
				if ( ! $day instanceof Day || ! in_array( $day->status, $allowed, true ) ) {
					throw new RuleViolation( 'wrong_status', 'Only a locked day can be marked ' . $status . '.' );
				}

				$now = $this->engine->stamp();
				$this->engine->stores->schedule->update(
					$day->id,
					array(
						'status'     => $status,
						'updated_at' => $now,
					)
				);
				$this->engine->stores->daily_orders->set_status( $day->id, $status, $now );
				$this->engine->log( 'schedule', $day->id, $status, array( 'status' => $day->status ), array( 'status' => $status ), $actor, $reason );
				$this->recount( $day->subscription_id );
			}
		);
	}
}
