<?php
/**
 * LockJob: the 20:00 lock-and-generate job (business rule 11).
 *
 * Every `scheduled` day whose cutoff has passed (today up to the day
 * before the first unlocked date, so normally just tomorrow) becomes
 * `locked`, and gets one frozen daily order for the kitchen with address,
 * allergen and instruction snapshots.
 *
 * Safe to rerun: already-locked days are left alone and daily orders are
 * unique per schedule row. It works out "today" and the lock range from
 * the IST time when it actually runs, so a late run still locks the right
 * days.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cron;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\Pack;
use Pazhamei\Services\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Lock tomorrow and create daily orders.
 */
final class LockJob {

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
	 * @return array{from: string, to: string, locked: int, orders_created: int}
	 */
	public function run( Actor $actor ): array {
		$from = $this->engine->today();
		$to   = Dates::add_days( $this->engine->first_unlocked(), -1 );

		$locked  = $this->lock_days( $from, $to, $actor );
		$created = $this->create_orders( $from, $to );

		$summary = array(
			'from'           => $from,
			'to'             => $to,
			'locked'         => $locked,
			'orders_created' => $created,
		);
		$this->engine->log( 'job', 0, 'lock_run', null, $summary, $actor );
		return $summary;
	}

	/**
	 * Scheduled → locked, one customer at a time under that customer's lock.
	 *
	 * @param string $from  First date.
	 * @param string $to    Last date.
	 * @param Actor  $actor Who.
	 */
	private function lock_days( string $from, string $to, Actor $actor ): int {
		$stores    = $this->engine->stores;
		$customers = array_unique( array_map( static fn( $day ) => $day->customer_id, $stores->schedule->in_range( $from, $to, array( DayStatus::SCHEDULED ) ) ) );
		$count     = 0;

		foreach ( $customers as $customer_id ) {
			$count += (int) $this->engine->customer_tx(
				$customer_id,
				function () use ( $customer_id, $from, $to, $actor, $stores ) {
					$locked = 0;
					foreach ( $stores->schedule->for_customer( $customer_id, true ) as $day ) {
						if ( DayStatus::SCHEDULED !== $day->status || $day->service_date < $from || $day->service_date > $to ) {
							continue;
						}
						$stores->schedule->update(
							$day->id,
							array(
								'status'     => DayStatus::LOCKED,
								'updated_at' => $this->engine->stamp(),
							)
						);
						$this->engine->log( 'schedule', $day->id, 'locked', array( 'status' => DayStatus::SCHEDULED ), array( 'status' => DayStatus::LOCKED ), $actor, $day->service_date );
						++$locked;
					}
					return $locked;
				}
			);
		}
		return $count;
	}

	/**
	 * One daily order per locked day that has none yet.
	 *
	 * @param string $from First date.
	 * @param string $to   Last date.
	 */
	private function create_orders( string $from, string $to ): int {
		$stores  = $this->engine->stores;
		$packs   = array();
		$created = 0;

		foreach ( $stores->schedule->in_range( $from, $to, array( DayStatus::LOCKED ) ) as $day ) {
			if ( $stores->daily_orders->find_by_day( $day->id ) ) {
				continue;
			}
			$packs[ $day->subscription_id ] ??= $stores->subscriptions->find( $day->subscription_id );
			$pack    = $packs[ $day->subscription_id ];
			$profile = $stores->profiles->get( $day->customer_id ) ?? array();
			$now     = $this->engine->stamp();

			$inserted = $stores->daily_orders->insert_if_missing(
				array(
					'schedule_id'     => $day->id,
					'subscription_id' => $day->subscription_id,
					'customer_id'     => $day->customer_id,
					'service_date'    => $day->service_date,
					'meal_type'       => $day->meal_type,
					'quantity'        => $pack instanceof Pack ? $pack->quantity : 1,
					'menu_id'         => null, // Menus arrive in Phases 7–8.
					'address_json'    => (string) json_encode( $pack instanceof Pack ? $this->engine->customers->delivery_address( $pack ) : array(), JSON_UNESCAPED_UNICODE ),
					'allergens_json'  => (string) json_encode( $stores->profiles->allergens( $day->customer_id ), JSON_UNESCAPED_UNICODE ),
					'instructions'    => (string) ( $profile['instructions'] ?? '' ),
					'status'          => 'confirmed',
					'generated_at'    => $now,
					'updated_at'      => $now,
				)
			);
			$created += $inserted ? 1 : 0;
		}
		return $created;
	}
}
