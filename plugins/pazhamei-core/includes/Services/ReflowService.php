<?php
/**
 * ReflowService: runs Reflow::plan() for one customer and saves the result
 * (business rule 12).
 *
 * Always call it inside Engine::customer_tx(), so it runs in the same
 * transaction as the change that triggered it, with the customer's rows
 * locked (SELECT ... FOR UPDATE).
 *
 * Before planning it starts the next queued pack if the customer has no
 * current pack any more (the previous one completed or was cancelled).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Reflow;
use Pazhamei\Domain\ReflowPlan;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Applies a reflow to the database.
 */
final class ReflowService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Recalculate and save one customer's schedule.
	 *
	 * @param int    $customer_id Customer.
	 * @param Actor  $actor       Who triggered it.
	 * @param string $reason      Why (for the change log).
	 * @param string $source      Source for new rows: initial, reflow or admin.
	 */
	public function run( int $customer_id, Actor $actor, string $reason, string $source = 'reflow' ): ReflowPlan {
		$stores = $this->engine->stores;
		$now    = $this->engine->stamp();

		$this->start_next_queued( $customer_id, $actor );

		$packs  = $stores->subscriptions->for_customer( $customer_id, PackStatus::OPEN );
		$days   = $stores->schedule->for_customer( $customer_id, true );
		$pauses = $stores->pauses->for_customer( $customer_id );

		$plan = Reflow::plan( $customer_id, $this->engine->first_unlocked(), $this->engine->calendar(), $packs, $days, $pauses, $source );

		// Delete first, so the UNIQUE (customer, date) key never sees two rows for one date.
		$stores->schedule->delete( $plan->delete_ids );
		foreach ( $plan->updates as $id => $fields ) {
			$stores->schedule->update( $id, $fields + array( 'updated_at' => $now ) );
		}
		foreach ( $plan->inserts as $day ) {
			$day->created_at = $now;
			$day->updated_at = $now;
			$stores->schedule->insert( $day );
		}

		// Save pack dates and status where they changed.
		$before = array();
		$after  = array();
		foreach ( $packs as $pack ) {
			$old = array(
				'start_date' => $pack->start_date,
				'end_date'   => $pack->end_date,
				'status'     => $pack->status,
			);
			$new = $plan->packs[ $pack->id ];
			if ( $old !== $new ) {
				$stores->subscriptions->update( $pack->id, $new + array( 'updated_at' => $now ) );
				$before[ $pack->id ] = $old;
				$after[ $pack->id ]  = $new;
			}
		}

		if ( $before || $plan->changes_rows() ) {
			$this->engine->log(
				'customer',
				$customer_id,
				'reflow',
				array( 'packs' => $before ),
				array(
					'packs' => $after,
					'rows'  => array(
						'deleted'  => count( $plan->delete_ids ),
						'updated'  => count( $plan->updates ),
						'inserted' => count( $plan->inserts ),
					),
				),
				$actor,
				$reason
			);
		}

		return $plan;
	}

	/**
	 * If the customer has no current pack, the oldest queued pack becomes active.
	 *
	 * @param int   $customer_id Customer.
	 * @param Actor $actor       Who triggered it.
	 */
	private function start_next_queued( int $customer_id, Actor $actor ): void {
		$open = Reflow::in_walk_order( $this->engine->stores->subscriptions->for_customer( $customer_id, PackStatus::OPEN ) );
		if ( ! $open || PackStatus::is_current( $open[0]->status ) ) {
			return;
		}

		$next = $open[0];
		$this->engine->stores->subscriptions->update(
			$next->id,
			array(
				'status'     => PackStatus::ACTIVE,
				'updated_at' => $this->engine->stamp(),
			)
		);
		$this->engine->log( 'subscription', $next->id, 'started', array( 'status' => $next->status ), array( 'status' => PackStatus::ACTIVE ), $actor, 'Previous pack finished' );
	}
}
