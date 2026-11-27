<?php
/**
 * HolidayService: the admin adds or removes kitchen-closed dates (rule 1).
 *
 * Adding a holiday: any day already locked for that date becomes
 * `holiday_cancelled` (its daily order is cancelled and the meal goes back
 * to the pack); then every customer with an open pack is reflowed, which
 * moves unlocked meals off the holiday.
 *
 * Removing a holiday: only while the date is still unlocked; every customer
 * with an open pack is reflowed so meals can use the date again.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Repositories\DuplicateKey;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holidays.
 */
final class HolidayService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Declare a holiday.
	 *
	 * @param string $date  'Y-m-d' (today or later).
	 * @param string $label E.g. "Pongal".
	 * @param Actor  $actor Admin.
	 * @return int[] Customers whose schedule was looked at.
	 * @throws RuleViolation For past dates or an existing holiday.
	 */
	public function add( string $date, string $label, Actor $actor ): array {
		if ( ! Dates::is_valid( $date ) || $date < $this->engine->today() ) {
			throw new RuleViolation( 'bad_date', 'Holidays must be today or later.' );
		}

		$stores    = $this->engine->stores;
		$customers = $stores->transaction->run(
			function () use ( $date, $label, $actor, $stores ) {
				try {
					$id = $stores->holidays->insert( $date, $label, $actor->id, $this->engine->stamp() );
				} catch ( DuplicateKey $duplicate ) {
					throw new RuleViolation( 'exists', Dates::display( $date ) . ' is already a holiday.' );
				}
				$this->engine->log( 'holiday', $id, 'holiday_added', null, array( 'date' => $date, 'label' => $label ), $actor );

				$customers = array_unique( array_merge( $stores->subscriptions->customer_ids( PackStatus::OPEN ), $stores->schedule->customer_ids_on( $date ) ) );
				sort( $customers );
				foreach ( $customers as $customer_id ) {
					$this->engine->customer_tx(
						$customer_id,
						function () use ( $customer_id, $date, $actor ) {
							$this->cancel_locked_day( $customer_id, $date, $actor );
							$this->engine->reflow()->run( $customer_id, $actor, 'Holiday ' . $date );
						}
					);
				}
				return $customers;
			}
		);

		$this->engine->emit( 'holiday_added', array( $date, $customers ) );
		return $customers;
	}

	/**
	 * Remove a holiday while its date is still unlocked.
	 *
	 * @param string $date  'Y-m-d'.
	 * @param Actor  $actor Admin.
	 * @throws RuleViolation When locked or not a holiday.
	 */
	public function remove( string $date, Actor $actor ): void {
		if ( ! Dates::is_valid( $date ) ) {
			throw new RuleViolation( 'bad_date', 'Please choose a valid date.' );
		}
		if ( $this->engine->is_locked( $date ) ) {
			throw new RuleViolation( 'locked', 'Too late to remove this holiday: ' . $this->engine->locked_message( $date ) );
		}

		$stores = $this->engine->stores;
		$stores->transaction->run(
			function () use ( $date, $actor, $stores ) {
				if ( ! $stores->holidays->delete( $date ) ) {
					throw new RuleViolation( 'not_holiday', Dates::display( $date ) . ' is not a holiday.' );
				}
				$this->engine->log( 'holiday', 0, 'holiday_removed', array( 'date' => $date ), null, $actor );

				foreach ( $stores->subscriptions->customer_ids( PackStatus::OPEN ) as $customer_id ) {
					$this->engine->customer_tx(
						$customer_id,
						fn() => $this->engine->reflow()->run( $customer_id, $actor, 'Holiday removed ' . $date )
					);
				}
			}
		);
	}

	/**
	 * A day locked before the holiday was declared: cancel it and its kitchen order.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $date        Holiday.
	 * @param Actor  $actor       Admin.
	 */
	private function cancel_locked_day( int $customer_id, string $date, Actor $actor ): void {
		$day = $this->engine->stores->schedule->find_by_date( $customer_id, $date );
		if ( ! $day || DayStatus::LOCKED !== $day->status ) {
			return;
		}
		$now = $this->engine->stamp();
		$this->engine->stores->schedule->update(
			$day->id,
			array(
				'status'     => DayStatus::HOLIDAY_CANCELLED,
				'updated_at' => $now,
			)
		);
		$this->engine->stores->daily_orders->set_status( $day->id, 'cancelled', $now );
		$this->engine->log( 'schedule', $day->id, 'holiday_cancelled', array( 'status' => $day->status ), array( 'status' => DayStatus::HOLIDAY_CANCELLED ), $actor, $date );
	}
}
