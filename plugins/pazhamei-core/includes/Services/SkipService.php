<?php
/**
 * SkipService: skip a day and undo a skip (business rule 6).
 *
 * Skip: an unlocked `scheduled` day becomes `skipped`; reflow() moves the
 * meal to the end of the plan. Undo: an unlocked `skipped` day is removed
 * and reflow() puts a meal back on it. No limit on skips per pack.
 *
 * The cutoff is checked against the current IST time, inside the customer's
 * lock, so a request that waited for the 20:00 lock job sees the locked row.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\Day;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\RuleViolation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Skip and undo skip.
 */
final class SkipService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Skip one day.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $date        'Y-m-d'.
	 * @param Actor  $actor       Who.
	 * @return Day The skipped row.
	 * @throws RuleViolation When the day is locked or has no scheduled meal.
	 */
	public function skip( int $customer_id, string $date, Actor $actor ): Day {
		return $this->engine->customer_tx(
			$customer_id,
			function () use ( $customer_id, $date, $actor ) {
				$this->guard_unlocked( $date );

				$day = $this->engine->stores->schedule->find_by_date( $customer_id, $date );
				if ( ! $day || DayStatus::SCHEDULED !== $day->status ) {
					throw new RuleViolation( 'not_scheduled', 'There is no meal scheduled on ' . Dates::display( $date ) . ' to skip.' );
				}

				$this->engine->stores->schedule->update(
					$day->id,
					array(
						'status'     => DayStatus::SKIPPED,
						'updated_at' => $this->engine->stamp(),
					)
				);
				$this->engine->log( 'schedule', $day->id, 'skipped', array( 'status' => $day->status ), array( 'status' => DayStatus::SKIPPED ), $actor, $date );
				$this->engine->reflow()->run( $customer_id, $actor, 'Skip ' . $date );

				return $this->engine->stores->schedule->find( $day->id );
			}
		);
	}

	/**
	 * Undo a skip, until the cutoff for that day.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $date        'Y-m-d'.
	 * @param Actor  $actor       Who.
	 * @throws RuleViolation When the day is locked or not skipped.
	 */
	public function undo( int $customer_id, string $date, Actor $actor ): void {
		$this->engine->customer_tx(
			$customer_id,
			function () use ( $customer_id, $date, $actor ) {
				$this->guard_unlocked( $date );

				$day = $this->engine->stores->schedule->find_by_date( $customer_id, $date );
				if ( ! $day || DayStatus::SKIPPED !== $day->status ) {
					throw new RuleViolation( 'not_skipped', Dates::display( $date ) . ' is not skipped.' );
				}

				$this->engine->stores->schedule->delete( array( $day->id ) );
				$this->engine->log( 'schedule', $day->id, 'skip_undone', $day->to_array(), null, $actor, $date );
				$this->engine->reflow()->run( $customer_id, $actor, 'Undo skip ' . $date );
			}
		);
	}

	/**
	 * Refuse dates past their cutoff.
	 *
	 * @param string $date 'Y-m-d'.
	 * @throws RuleViolation When locked or not a date.
	 */
	private function guard_unlocked( string $date ): void {
		if ( ! Dates::is_valid( $date ) ) {
			throw new RuleViolation( 'bad_date', 'Please choose a valid date.' );
		}
		if ( $this->engine->is_locked( $date ) ) {
			throw new RuleViolation( 'locked', $this->engine->locked_message( $date ) );
		}
	}
}
