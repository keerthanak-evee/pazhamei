<?php
/**
 * PackStatusJob: the 00:10 pack-status job.
 *
 *  1. Dated pauses whose end date has passed → `ended`.
 *  2. Open-ended pauses still in force → the pz_pause_ongoing event (days
 *     paused so far), for the Phase 6 "paused a while" reminder. There is
 *     no auto-resume (owner decision 2).
 *  3. Current packs with every meal delivered or failed → `completed`.
 *  4. Reflow each customer with an open pack, which starts a queued pack
 *     when the previous one has finished.
 *
 * Uses the IST date when it runs, so a late run still does the right thing.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cron;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Services\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily pack housekeeping.
 */
final class PackStatusJob {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Run the job.
	 *
	 * @param Actor $actor Usually Actor::system().
	 * @return array{pauses_ended: int, pauses_ongoing: int, completed: int, customers: int}
	 */
	public function run( Actor $actor ): array {
		$today   = $this->engine->today();
		$stores  = $this->engine->stores;
		$summary = array(
			'pauses_ended'   => 0,
			'pauses_ongoing' => 0,
			'completed'      => 0,
			'customers'      => 0,
		);

		foreach ( $stores->pauses->active() as $pause ) {
			if ( null !== $pause->end_date && $pause->end_date < $today ) {
				$stores->pauses->update( $pause->id, array( 'status' => Pause::ENDED ) );
				$this->engine->log( 'pause', $pause->id, 'pause_ended', array( 'status' => Pause::ACTIVE ), array( 'status' => Pause::ENDED ), $actor, 'End date passed' );
				++$summary['pauses_ended'];
			} elseif ( $pause->is_open_ended() && $pause->start_date <= $today ) {
				$this->engine->emit( 'pause_ongoing', array( $pause->id, $pause->customer_id, Dates::days_between( $pause->start_date, $today ) ) );
				++$summary['pauses_ongoing'];
			}
		}

		foreach ( $stores->subscriptions->customer_ids( PackStatus::OPEN ) as $customer_id ) {
			$summary['completed'] += (int) $this->engine->customer_tx(
				$customer_id,
				function () use ( $customer_id, $actor, $stores ) {
					$completed = 0;
					foreach ( $stores->subscriptions->for_customer( $customer_id, PackStatus::CURRENT ) as $pack ) {
						$this->engine->deliveries()->recount( $pack->id );
						$fresh = $stores->subscriptions->find( $pack->id );
						if ( $fresh && $fresh->consumed_meals >= $fresh->total_meals ) {
							$stores->subscriptions->update(
								$pack->id,
								array(
									'status'     => PackStatus::COMPLETED,
									'updated_at' => $this->engine->stamp(),
								)
							);
							$this->engine->log( 'subscription', $pack->id, 'completed', array( 'status' => $pack->status ), array( 'status' => PackStatus::COMPLETED ), $actor, 'All meals delivered' );
							++$completed;
						}
					}
					$this->engine->reflow()->run( $customer_id, $actor, 'Daily status check' );
					return $completed;
				}
			);
			++$summary['customers'];
		}

		$this->engine->log( 'job', 0, 'pack_status', null, $summary, $actor );
		return $summary;
	}
}
