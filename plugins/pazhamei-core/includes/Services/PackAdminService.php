<?php
/**
 * PackAdminService: admin actions on one pack.
 *
 *  - cancel()           Rule 13. Removes the pack's unlocked days, sets the
 *                       status (cancelled or refunded) and logs it. Locked
 *                       days stay. No refund calculation: refunds are offline.
 *                       Also used for WooCommerce refunds and cancellations.
 *  - credit_meal()      Rule 14. Adds one meal to the pack and reflows. A
 *                       completed pack becomes active again.
 *  - change_meal_type() Admin only. Unlocked days get the new type; locked
 *                       days keep the old one.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Plans;
use Pazhamei\Domain\RuleViolation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cancel, credit a meal, change meal type.
 */
final class PackAdminService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Cancel a pack. Safe to call twice: an already cancelled pack is left alone.
	 *
	 * @param int    $pack_id Pack.
	 * @param string $status  PackStatus::CANCELLED or PackStatus::REFUNDED.
	 * @param Actor  $actor   Who.
	 * @param string $reason  Why.
	 * @return bool True if the pack was cancelled now.
	 * @throws RuleViolation When the pack is missing or already completed.
	 */
	public function cancel( int $pack_id, string $status, Actor $actor, string $reason = '' ): bool {
		$pack = $this->find( $pack_id );
		if ( in_array( $pack->status, array( PackStatus::CANCELLED, PackStatus::REFUNDED ), true ) ) {
			return false;
		}
		if ( ! PackStatus::is_open( $pack->status ) ) {
			throw new RuleViolation( 'not_open', 'This pack is already ' . $pack->status . '.' );
		}

		return $this->engine->customer_tx(
			$pack->customer_id,
			function () use ( $pack, $status, $actor, $reason ) {
				$stores = $this->engine->stores;
				$from   = $this->engine->first_unlocked();

				// Remove the pack's unlocked days (scheduled, paused, skipped). Locked days stay.
				$removed = array();
				$kept    = array();
				foreach ( $stores->schedule->for_customer( $pack->customer_id, true ) as $day ) {
					if ( $day->subscription_id !== $pack->id ) {
						continue;
					}
					if ( $day->service_date >= $from && in_array( $day->status, array( DayStatus::SCHEDULED, DayStatus::PAUSED, DayStatus::SKIPPED ), true ) ) {
						$removed[] = $day->id;
					} elseif ( DayStatus::uses_meal( $day->status ) ) {
						$kept[] = $day->service_date;
					}
				}
				$stores->schedule->delete( $removed );

				$fields = array(
					'status'     => $status,
					'start_date' => $kept ? min( $kept ) : null,
					'end_date'   => $kept ? max( $kept ) : null,
					'updated_at' => $this->engine->stamp(),
				);
				$stores->subscriptions->update( $pack->id, $fields );
				$this->engine->pauses()->end_for_pack( $pack->id, $pack->customer_id, $actor );

				$this->engine->log(
					'subscription',
					$pack->id,
					$status,
					array( 'status' => $pack->status, 'end_date' => $pack->end_date ),
					array( 'status' => $status, 'end_date' => $fields['end_date'], 'days_removed' => count( $removed ) ),
					$actor,
					$reason
				);
				$this->engine->reflow()->run( $pack->customer_id, $actor, 'Pack #' . $pack->id . ' ' . $status, 'admin' );
				return true;
			}
		);
	}

	/**
	 * Add one meal to a pack (e.g. after a failed delivery).
	 *
	 * @param int    $pack_id Pack.
	 * @param Actor  $actor   Admin.
	 * @param string $reason  Why.
	 * @return Pack The pack after the change.
	 * @throws RuleViolation When the pack is cancelled or refunded.
	 */
	public function credit_meal( int $pack_id, Actor $actor, string $reason = '' ): Pack {
		$pack = $this->find( $pack_id );
		if ( ! PackStatus::is_open( $pack->status ) && PackStatus::COMPLETED !== $pack->status ) {
			throw new RuleViolation( 'not_open', 'Meals can only be credited to an open or completed pack.' );
		}

		return $this->engine->customer_tx(
			$pack->customer_id,
			function () use ( $pack, $actor, $reason ) {
				$fields = array(
					'total_meals' => $pack->total_meals + 1,
					'updated_at'  => $this->engine->stamp(),
				);
				if ( PackStatus::COMPLETED === $pack->status ) {
					$fields['status'] = PackStatus::ACTIVE;
				}
				$this->engine->stores->subscriptions->update( $pack->id, $fields );
				$this->engine->log(
					'subscription',
					$pack->id,
					'meal_credited',
					array( 'total_meals' => $pack->total_meals, 'status' => $pack->status ),
					array( 'total_meals' => $fields['total_meals'], 'status' => $fields['status'] ?? $pack->status ),
					$actor,
					$reason
				);
				$this->engine->reflow()->run( $pack->customer_id, $actor, 'Meal credited to pack #' . $pack->id, 'admin' );
				return $this->engine->stores->subscriptions->find( $pack->id );
			}
		);
	}

	/**
	 * Change a pack's meal type for its unlocked days.
	 *
	 * @param int    $pack_id   Pack.
	 * @param string $meal_type VEG, NONVEG or MIXED.
	 * @param Actor  $actor     Admin.
	 * @return Pack The pack after the change.
	 * @throws RuleViolation For an unknown type or a closed pack.
	 */
	public function change_meal_type( int $pack_id, string $meal_type, Actor $actor ): Pack {
		if ( ! Plans::is_meal_type( $meal_type ) ) {
			throw new RuleViolation( 'unknown_meal_type', 'Choose Veg, Non-Veg or Mixed.' );
		}
		$pack = $this->find( $pack_id );
		if ( ! PackStatus::is_open( $pack->status ) ) {
			throw new RuleViolation( 'not_open', 'Only an open pack can change meal type.' );
		}

		return $this->engine->customer_tx(
			$pack->customer_id,
			function () use ( $pack, $meal_type, $actor ) {
				$this->engine->stores->subscriptions->update(
					$pack->id,
					array(
						'meal_type'  => $meal_type,
						'updated_at' => $this->engine->stamp(),
					)
				);
				$this->engine->log( 'subscription', $pack->id, 'meal_type_changed', array( 'meal_type' => $pack->meal_type ), array( 'meal_type' => $meal_type ), $actor );
				$this->engine->reflow()->run( $pack->customer_id, $actor, 'Meal type ' . $meal_type, 'admin' );
				return $this->engine->stores->subscriptions->find( $pack->id );
			}
		);
	}

	/**
	 * Load a pack or refuse.
	 *
	 * @param int $pack_id Pack.
	 * @throws RuleViolation When it does not exist.
	 */
	private function find( int $pack_id ): Pack {
		$pack = $this->engine->stores->subscriptions->find( $pack_id );
		if ( ! $pack ) {
			throw new RuleViolation( 'not_found', "Pack #{$pack_id} does not exist." );
		}
		return $pack;
	}
}
