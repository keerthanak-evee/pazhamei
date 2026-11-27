<?php
/**
 * PauseService: pause and resume (business rule 7).
 *
 * Pause from an unlocked date, either until a chosen date (the last paused
 * day) or open-ended. No time limit and no auto-resume (owner decision 2).
 * A dated pause ends by itself after its end date; an open-ended pause
 * lasts until the customer or the admin resumes.
 *
 * Resume ends the pause on the day before the first unlocked date, so meals
 * restart as soon as the cutoff allows. A pause that had not begun yet is
 * cancelled instead.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\Reflow;
use Pazhamei\Domain\RuleViolation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pause and resume.
 */
final class PauseService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Pause deliveries.
	 *
	 * @param int         $customer_id Customer.
	 * @param string      $start       First paused day, 'Y-m-d' (must be unlocked).
	 * @param string|null $end         Last paused day, or null for open-ended.
	 * @param Actor       $actor       Who.
	 * @return Pause The new pause.
	 * @throws RuleViolation When the dates are invalid, locked or overlap another pause.
	 */
	public function pause( int $customer_id, string $start, ?string $end, Actor $actor ): Pause {
		if ( ! Dates::is_valid( $start ) || ( null !== $end && ! Dates::is_valid( $end ) ) ) {
			throw new RuleViolation( 'bad_date', 'Please choose valid pause dates.' );
		}
		if ( null !== $end && $end < $start ) {
			throw new RuleViolation( 'bad_range', 'The pause must end on or after the day it starts.' );
		}

		return $this->engine->customer_tx(
			$customer_id,
			function () use ( $customer_id, $start, $end, $actor ) {
				$stores = $this->engine->stores;

				if ( $this->engine->is_locked( $start ) ) {
					throw new RuleViolation( 'locked', $this->engine->locked_message( $start ) );
				}

				$open = Reflow::in_walk_order( $stores->subscriptions->for_customer( $customer_id, PackStatus::OPEN ) );
				if ( ! $open ) {
					throw new RuleViolation( 'no_pack', 'You have no plan to pause.' );
				}

				foreach ( $stores->pauses->for_customer( $customer_id ) as $other ) {
					if ( $this->overlaps( $other, $start, $end ) ) {
						throw new RuleViolation( 'overlap', 'This pause overlaps a pause you already have. Resume or change that one first.' );
					}
				}

				$pause                  = new Pause();
				$pause->subscription_id = $open[0]->id;
				$pause->customer_id     = $customer_id;
				$pause->start_date      = $start;
				$pause->end_date        = $end;
				$pause->status          = Pause::ACTIVE;
				$pause->created_by      = $actor->id;
				$pause->created_at      = $this->engine->stamp();
				$pause->id              = $stores->pauses->insert( $pause );

				$this->engine->log( 'pause', $pause->id, 'paused', null, $pause->to_array(), $actor, null === $end ? 'Open-ended' : 'Until ' . $end );
				$this->engine->reflow()->run( $customer_id, $actor, 'Pause from ' . $start );

				return $pause;
			}
		);
	}

	/**
	 * Resume: end the customer's open-ended pause, or the current or upcoming dated pause.
	 *
	 * @param int   $customer_id Customer.
	 * @param Actor $actor       Who (customer or admin).
	 * @return Pause The pause as it is now.
	 * @throws RuleViolation When there is nothing to resume.
	 */
	public function resume( int $customer_id, Actor $actor ): Pause {
		return $this->engine->customer_tx(
			$customer_id,
			function () use ( $customer_id, $actor ) {
				$from  = $this->engine->first_unlocked();
				$pause = $this->resumable( $customer_id, $from );
				if ( ! $pause ) {
					throw new RuleViolation( 'not_paused', 'You have no pause to resume.' );
				}

				$old = $pause->to_array();
				if ( $pause->start_date >= $from ) {
					// It had not begun yet: withdraw it.
					$pause->status = Pause::CANCELLED;
				} else {
					// Locked days stay as they are; meals restart on the first unlocked day.
					$pause->end_date = Dates::add_days( $from, -1 );
					$pause->status   = Pause::ENDED;
				}

				$this->engine->stores->pauses->update(
					$pause->id,
					array(
						'end_date' => $pause->end_date,
						'status'   => $pause->status,
					)
				);
				$this->engine->log( 'pause', $pause->id, 'resumed', $old, $pause->to_array(), $actor, 'Meals from ' . $from );
				$this->engine->reflow()->run( $customer_id, $actor, 'Resume' );

				return $pause;
			}
		);
	}

	/**
	 * End or withdraw every pause made for one pack (used when the pack is cancelled).
	 *
	 * @param int   $subscription_id Pack.
	 * @param int   $customer_id     Customer.
	 * @param Actor $actor           Who.
	 */
	public function end_for_pack( int $subscription_id, int $customer_id, Actor $actor ): void {
		$from = $this->engine->first_unlocked();

		foreach ( $this->engine->stores->pauses->for_customer( $customer_id ) as $pause ) {
			if ( $pause->subscription_id !== $subscription_id || Pause::ACTIVE !== $pause->status ) {
				continue;
			}
			if ( null !== $pause->end_date && $pause->end_date < $from ) {
				continue; // Already over.
			}
			$old    = $pause->to_array();
			$fields = $pause->start_date >= $from
				? array( 'status' => Pause::CANCELLED )
				: array(
					'status'   => Pause::ENDED,
					'end_date' => Dates::add_days( $from, -1 ),
				);
			$this->engine->stores->pauses->update( $pause->id, $fields );
			$this->engine->log( 'pause', $pause->id, 'ended_with_pack', $old, $fields, $actor, 'Pack #' . $subscription_id . ' cancelled' );
		}
	}

	/**
	 * The pause that Resume should end: the open-ended one, else the earliest
	 * active dated pause that still covers the first unlocked day or later.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $from        First unlocked date.
	 */
	private function resumable( int $customer_id, string $from ): ?Pause {
		$dated = null;
		foreach ( $this->engine->stores->pauses->for_customer( $customer_id ) as $pause ) {
			if ( $pause->is_open_ended() ) {
				return $pause;
			}
			if ( Pause::ACTIVE === $pause->status && null !== $pause->end_date && $pause->end_date >= $from && null === $dated ) {
				$dated = $pause;
			}
		}
		return $dated;
	}

	/**
	 * Does an existing pause overlap the new range? Pauses that are over,
	 * cancelled, or ended before the new start never overlap.
	 *
	 * @param Pause       $other Existing pause.
	 * @param string      $start New start.
	 * @param string|null $end   New end, or null for open-ended.
	 */
	private function overlaps( Pause $other, string $start, ?string $end ): bool {
		if ( Pause::CANCELLED === $other->status ) {
			return false;
		}
		if ( null === $other->end_date && Pause::ACTIVE !== $other->status ) {
			return false;
		}
		$other_end = $other->end_date ?? '9999-12-31';
		$new_end   = $end ?? '9999-12-31';
		return $other->start_date <= $new_end && $start <= $other_end;
	}
}
