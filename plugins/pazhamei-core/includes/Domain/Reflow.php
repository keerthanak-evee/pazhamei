<?php
/**
 * Reflow: lays a customer's paid meals onto the calendar (business rules 6–9, 12).
 *
 * This is the only code that decides dates. Skip, pause, resume, holidays,
 * renewals, cancellations and admin changes record what changed and then
 * call reflow for that customer (see Services\ReflowService, which runs it
 * inside a transaction and saves the result).
 *
 * The walk, for one customer, from the first unlocked date S:
 *  1. Rows with status scheduled or paused on or after S may be rewritten.
 *     Every other row (locked, delivered, failed, skipped, holiday_cancelled,
 *     or anything before S) is kept and never touched.
 *  2. Packs in order: current (active/paused) first, then queued, each by
 *     purchase time. Remaining meals = pack size − kept rows that use a meal.
 *  3. Walk forward day by day from S (or the pack's chosen start, if later).
 *     Sunday, holiday or a kept row → next day. Inside a dated pause → a
 *     `paused` row. Otherwise a `scheduled` row for this pack.
 *  4. An open-ended pause stops the walk. The pack it stops is `paused`;
 *     queued packs behind it stay `queued` with no rows and no dates.
 *  5. Each pack's start and end date = its first and last meal (rule 8).
 *
 * Plain PHP with no database: it returns a ReflowPlan saying which rows to
 * delete, insert and update. That makes every rule testable in PHPUnit.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The reflow algorithm.
 */
final class Reflow {

	/** Safety limit for one walk (about three years), so bad data can never loop forever. */
	public const MAX_DAYS = 1100;

	/**
	 * Work out the new schedule for one customer.
	 *
	 * @param int      $customer_id Customer (WordPress user ID).
	 * @param string   $from        First unlocked date S ('Y-m-d').
	 * @param Calendar $calendar    Service days and holidays.
	 * @param Pack[]   $packs       The customer's open packs (active, paused, queued).
	 * @param Day[]    $days        All of the customer's schedule rows.
	 * @param Pause[]  $pauses      The customer's pauses.
	 * @param string   $source      Source written on new rows: initial, reflow or admin.
	 * @throws RuntimeException If a walk runs past MAX_DAYS.
	 */
	public static function plan( int $customer_id, string $from, Calendar $calendar, array $packs, array $days, array $pauses, string $source = 'reflow' ): ReflowPlan {
		$plan = new ReflowPlan();

		// Step 1: sort existing rows into "may be rewritten" and "kept".
		$rewritable = array(); // date => Day.
		$occupied   = array(); // date => true, for kept rows on or after S.
		$used       = array(); // pack id => meals used by kept rows.
		$meal_dates = array(); // pack id => dates of kept rows that use a meal.

		foreach ( $days as $day ) {
			if ( $day->service_date >= $from && in_array( $day->status, DayStatus::REPLACEABLE, true ) ) {
				$rewritable[ $day->service_date ] = $day;
				continue;
			}
			if ( $day->service_date >= $from ) {
				$occupied[ $day->service_date ] = true;
			}
			if ( DayStatus::uses_meal( $day->status ) ) {
				$used[ $day->subscription_id ]         = ( $used[ $day->subscription_id ] ?? 0 ) + 1;
				$meal_dates[ $day->subscription_id ][] = $day->service_date;
			}
		}

		$open_ended_from = self::open_ended_start( $pauses );
		$dated_pauses    = array_filter( $pauses, static fn( Pause $p ) => null !== $p->end_date && Pause::CANCELLED !== $p->status );

		// Steps 2–4: walk the packs in order.
		$wanted     = array(); // date => array{pack: Pack, status: string}.
		$cursor     = $from;
		$stopped_at = null;

		foreach ( self::in_walk_order( $packs ) as $pack ) {
			$remaining = max( 0, $pack->total_meals - ( $used[ $pack->id ] ?? 0 ) );

			if ( null === $stopped_at && $remaining > 0 ) {
				$date  = null !== $pack->requested_start ? Dates::max( $cursor, $pack->requested_start ) : $cursor;
				$steps = 0;

				while ( $remaining > 0 ) {
					if ( ++$steps > self::MAX_DAYS ) {
						throw new RuntimeException( "Reflow for customer {$customer_id} walked more than " . self::MAX_DAYS . ' days.' );
					}
					if ( null !== $open_ended_from && $date >= $open_ended_from ) {
						$stopped_at = $date; // Step 4: an open-ended pause stops the walk.
						break;
					}
					if ( ! $calendar->is_service_day( $date ) || isset( $occupied[ $date ] ) ) {
						$date = Dates::add_days( $date, 1 );
						continue;
					}
					if ( self::in_dated_pause( $dated_pauses, $date ) ) {
						$wanted[ $date ] = array( 'pack' => $pack, 'status' => DayStatus::PAUSED );
						$date            = Dates::add_days( $date, 1 );
						continue;
					}

					$wanted[ $date ]           = array( 'pack' => $pack, 'status' => DayStatus::SCHEDULED );
					$meal_dates[ $pack->id ][] = $date;
					--$remaining;
					$date = Dates::add_days( $date, 1 );
				}
				$cursor = $date;
			}

			// Step 5: dates and status for this pack.
			$dates  = $meal_dates[ $pack->id ] ?? array();
			$status = $pack->status;
			if ( PackStatus::is_current( $pack->status ) ) {
				$status = ( null !== $stopped_at && $remaining > 0 ) ? PackStatus::PAUSED : PackStatus::ACTIVE;
			}

			$plan->packs[ $pack->id ] = array(
				'start_date' => $dates ? min( $dates ) : null,
				'end_date'   => $dates ? max( $dates ) : null,
				'status'     => $status,
			);
		}

		$plan->stopped_at = $stopped_at;
		self::diff( $plan, $customer_id, $rewritable, $wanted, $source );

		return $plan;
	}

	/**
	 * Current packs first, then queued; each group by purchase time (then ID).
	 *
	 * @param Pack[] $packs Open packs.
	 * @return Pack[]
	 */
	public static function in_walk_order( array $packs ): array {
		$open = array_values( array_filter( $packs, static fn( Pack $p ) => PackStatus::is_open( $p->status ) ) );

		usort(
			$open,
			static function ( Pack $a, Pack $b ): int {
				$group = (int) ! PackStatus::is_current( $a->status ) <=> (int) ! PackStatus::is_current( $b->status );
				return 0 !== $group ? $group : array( $a->created_at, $a->id ) <=> array( $b->created_at, $b->id );
			}
		);
		return $open;
	}

	/**
	 * Start date of the earliest open-ended pause still in force, or null.
	 *
	 * @param Pause[] $pauses Pauses.
	 */
	private static function open_ended_start( array $pauses ): ?string {
		$start = null;
		foreach ( $pauses as $pause ) {
			if ( $pause->is_open_ended() && ( null === $start || $pause->start_date < $start ) ) {
				$start = $pause->start_date;
			}
		}
		return $start;
	}

	/**
	 * Is the date inside any dated pause?
	 *
	 * @param Pause[] $pauses Dated pauses.
	 * @param string  $date   'Y-m-d'.
	 */
	private static function in_dated_pause( array $pauses, string $date ): bool {
		foreach ( $pauses as $pause ) {
			if ( $pause->covers( $date ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Compare the wanted rows with the rewritable rows and fill the plan with the
	 * smallest set of changes: unchanged rows stay, changed rows are updated in
	 * place, new dates are inserted and the rest are deleted.
	 *
	 * @param ReflowPlan                                    $plan        Plan to fill.
	 * @param int                                           $customer_id Customer.
	 * @param array<string, Day>                            $rewritable  Existing rows that may change.
	 * @param array<string, array{pack: Pack, status: string}> $wanted   New layout.
	 * @param string                                        $source      Source for new rows.
	 */
	private static function diff( ReflowPlan $plan, int $customer_id, array $rewritable, array $wanted, string $source ): void {
		ksort( $wanted );

		foreach ( $wanted as $date => $want ) {
			$pack = $want['pack'];

			if ( isset( $rewritable[ $date ] ) ) {
				$row = $rewritable[ $date ];
				unset( $rewritable[ $date ] );

				if ( $row->subscription_id !== $pack->id || $row->status !== $want['status'] || $row->meal_type !== $pack->meal_type ) {
					$plan->updates[ $row->id ] = array(
						'subscription_id' => $pack->id,
						'meal_type'       => $pack->meal_type,
						'status'          => $want['status'],
						'source'          => $source,
					);
				}
				continue;
			}

			$day                  = new Day();
			$day->customer_id     = $customer_id;
			$day->service_date    = $date;
			$day->subscription_id = $pack->id;
			$day->meal_type       = $pack->meal_type;
			$day->status          = $want['status'];
			$day->source          = $source;
			$plan->inserts[]      = $day;
		}

		foreach ( $rewritable as $row ) {
			$plan->delete_ids[] = $row->id;
		}
	}
}
