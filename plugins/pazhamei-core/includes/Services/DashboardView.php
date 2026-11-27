<?php
/**
 * DashboardView: everything the customer dashboard shows, worked out on the server.
 *
 * The dashboard blocks (pz/subscription-summary, pz/schedule-calendar) only
 * display this: which days have a lunch, which are Sundays or holidays,
 * which are locked and why, where Skip or Undo is allowed, whether Pause or
 * Resume is offered, and the cutoff countdown. No business rule is repeated
 * in JavaScript: after every action the screen asks for this view again.
 *
 * Plain PHP on top of the Engine, so it is unit-tested.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\Day;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\Plans;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The customer dashboard's data.
 */
final class DashboardView {

	/** Show at least two weeks, and never more than this many days ahead. */
	private const MIN_DAYS = 13;
	private const MAX_DAYS = 70;

	/** Words for each day status. */
	private const DAY_LABELS = array(
		DayStatus::SCHEDULED         => 'Lunch',
		DayStatus::SKIPPED           => 'Skipped',
		DayStatus::PAUSED            => 'Paused',
		DayStatus::LOCKED            => 'Lunch',
		DayStatus::DELIVERED         => 'Delivered',
		DayStatus::FAILED            => 'Not delivered',
		DayStatus::HOLIDAY_CANCELLED => 'Kitchen holiday',
	);

	/** Words for each pack status. */
	private const PACK_LABELS = array(
		PackStatus::ACTIVE    => 'Active',
		PackStatus::PAUSED    => 'Paused',
		PackStatus::QUEUED    => 'Starts after your current pack',
		PackStatus::COMPLETED => 'Completed',
		PackStatus::CANCELLED => 'Cancelled',
		PackStatus::REFUNDED  => 'Refunded',
	);

	/**
	 * Build the view.
	 *
	 * @param Engine $engine      Engine.
	 * @param int    $customer_id Customer.
	 * @param bool   $confirming  A paid order is still waiting for payment confirmation.
	 * @return array<string, mixed>
	 */
	public static function build( Engine $engine, int $customer_id, bool $confirming = false ): array {
		$stores  = $engine->stores;
		$packs   = $stores->subscriptions->for_customer( $customer_id, PackStatus::OPEN );
		$current = null;
		$queued  = array();
		foreach ( $packs as $pack ) {
			if ( in_array( $pack->status, PackStatus::CURRENT, true ) && null === $current ) {
				$current = $pack;
			} elseif ( PackStatus::QUEUED === $pack->status ) {
				$queued[] = $pack;
			}
		}

		$pause = self::live_pause( $stores->pauses->for_customer( $customer_id ), $engine->first_unlocked() );
		$days  = self::days( $engine, $stores->schedule->for_customer( $customer_id ), $packs );

		return array(
			'today'      => $engine->today(),
			'cutoff'     => self::cutoff( $engine ),
			'current'    => $current ? self::pack( $current ) : null,
			'queued'     => array_map( array( self::class, 'pack' ), $queued ),
			'pause'      => $pause ? self::pause( $pause, $engine->first_unlocked() ) : null,
			'canPause'   => null !== $current && null === $pause,
			'canResume'  => null !== $pause,
			'hasPacks'   => (bool) $packs,
			'confirming' => $confirming,
			'days'       => $days,
			'pauseStarts' => self::pause_starts( $days ),
		);
	}

	/**
	 * The cutoff line and countdown: "Changes for tomorrow close at 8:00 PM".
	 *
	 * @param Engine $engine Engine.
	 * @return array<string, mixed>
	 */
	private static function cutoff( Engine $engine ): array {
		$next    = $engine->calendar()->first_service_day_from( $engine->first_unlocked() ); // Never a Sunday or holiday.
		$locks   = $engine->cutoff()->locks_at( $next );
		$today   = $engine->today();
		$time    = $locks->format( 'g:i A' );
		$for     = Dates::add_days( $today, 1 ) === $next ? 'tomorrow' : Dates::weekday_name( $next ) . ' ' . Dates::display( $next );
		$when    = Dates::date_of( $locks ) === $today ? 'today at ' . $time : 'at ' . $time . ' on ' . Dates::weekday_name( Dates::date_of( $locks ) ) . ' ' . Dates::display( Dates::date_of( $locks ) );
		$seconds = max( 0, $locks->getTimestamp() - $engine->now()->getTimestamp() );

		return array(
			'time'        => $time,
			'nextDate'    => $next,
			'text'        => 'tomorrow' === $for && 0 === strpos( $when, 'today' ) ? 'Changes for tomorrow close at ' . $time : 'Changes for ' . $for . ' close ' . $when,
			'secondsLeft' => $seconds,
		);
	}

	/**
	 * One pack for the summary.
	 *
	 * @param Pack $pack Pack.
	 * @return array<string, mixed>
	 */
	public static function pack( Pack $pack ): array {
		$slug = Plans::slug_for_code( $pack->plan_code );
		$plan = Plans::all()[ $slug ] ?? array(
			'name'  => $pack->plan_code,
			'label' => '',
		);
		return array(
			'id'          => $pack->id,
			'name'        => $plan['name'],
			'label'       => $plan['label'],
			'mealType'    => Plans::MEAL_TYPES[ $pack->meal_type ] ?? $pack->meal_type,
			'status'      => $pack->status,
			'statusLabel' => self::PACK_LABELS[ $pack->status ] ?? $pack->status,
			'total'       => $pack->total_meals,
			'eaten'       => $pack->consumed_meals,
			'left'        => max( 0, $pack->total_meals - $pack->consumed_meals ),
			'start'       => null === $pack->start_date ? 'Not set yet' : Dates::weekday_name( $pack->start_date ) . ' ' . Dates::display( $pack->start_date ),
			'end'         => null === $pack->end_date ? 'Not set yet' : Dates::weekday_name( $pack->end_date ) . ' ' . Dates::display( $pack->end_date ),
		);
	}

	/**
	 * The pause the customer can still resume or withdraw: open-ended, or a
	 * dated pause that hasn't finished before the first unlocked day.
	 *
	 * @param Pause[] $pauses All pauses.
	 * @param string  $from   First unlocked date.
	 */
	private static function live_pause( array $pauses, string $from ): ?Pause {
		foreach ( $pauses as $pause ) {
			if ( Pause::ACTIVE !== $pause->status ) {
				continue;
			}
			if ( null === $pause->end_date || $pause->end_date >= $from ) {
				return $pause;
			}
		}
		return null;
	}

	/**
	 * The pause for the summary.
	 *
	 * @param Pause  $pause Pause.
	 * @param string $from  First unlocked date.
	 * @return array<string, mixed>
	 */
	private static function pause( Pause $pause, string $from ): array {
		$start = Dates::weekday_name( $pause->start_date ) . ' ' . Dates::display( $pause->start_date );
		if ( null === $pause->end_date ) {
			$text = 'Paused from ' . $start . ' until you resume.';
		} else {
			$text = 'Paused from ' . $start . ' to ' . Dates::weekday_name( $pause->end_date ) . ' ' . Dates::display( $pause->end_date ) . ' (last day without meals).';
		}
		return array(
			'text'       => $text,
			'openEnded'  => null === $pause->end_date,
			'notStarted' => $pause->start_date >= $from,
		);
	}

	/**
	 * Dates a pause can start on: unlocked days with a lunch (not skipped, not off).
	 *
	 * @param array<int, array<string, mixed>> $days Calendar days.
	 * @return array<int, array{date: string, display: string}>
	 */
	private static function pause_starts( array $days ): array {
		$out = array();
		foreach ( $days as $day ) {
			if ( $day['canSkip'] ) {
				$out[] = array(
					'date'    => $day['date'],
					'display' => $day['display'],
				);
			}
		}
		return $out;
	}

	/**
	 * Every date from today to a little after the last meal, with what the calendar shows.
	 *
	 * @param Engine $engine Engine.
	 * @param Day[]  $rows   The customer's schedule rows.
	 * @param Pack[] $packs  Open packs.
	 * @return array<int, array<string, mixed>>
	 */
	private static function days( Engine $engine, array $rows, array $packs ): array {
		$today    = $engine->today();
		$calendar = $engine->calendar();
		$by_date  = array();
		$last     = Dates::add_days( $today, self::MIN_DAYS );

		foreach ( $rows as $row ) {
			if ( $row->service_date >= $today ) {
				$by_date[ $row->service_date ] = $row;
				$last                          = max( $last, $row->service_date );
			}
		}
		foreach ( $packs as $pack ) {
			if ( null !== $pack->end_date ) {
				$last = max( $last, $pack->end_date );
			}
		}
		$last = min( $last, Dates::add_days( $today, self::MAX_DAYS ) );

		$days = array();
		for ( $date = $today; $date <= $last; $date = Dates::add_days( $date, 1 ) ) {
			$row    = $by_date[ $date ] ?? null;
			$locked = $engine->is_locked( $date );
			$day    = array(
				'date'       => $date,
				'weekday'    => Dates::weekday_name( $date ),
				'dayNum'     => (string) (int) substr( $date, 8 ),
				'col'        => (string) ( ( (int) gmdate( 'N', (int) strtotime( $date . ' 00:00:00 UTC' ) ) ) ), // 1 = Monday … 7 = Sunday.
				'month'      => substr( Dates::display( $date ), -8, 3 ),
				'display'    => Dates::weekday_name( $date ) . ' ' . Dates::display( $date ),
				'kind'       => 'none',
				'label'      => 'No lunch',
				'mealType'   => '',
				'locked'     => false,
				'lockReason' => '',
				'canSkip'    => false,
				'canUndo'    => false,
			);

			if ( $calendar->is_sunday( $date ) ) {
				$day['kind']  = 'off';
				$day['label'] = 'Sunday · no delivery';
			} elseif ( $calendar->is_holiday( $date ) && ( null === $row || DayStatus::HOLIDAY_CANCELLED === $row->status ) ) {
				$day['kind']  = 'off';
				$day['label'] = 'Kitchen holiday';
			} elseif ( null !== $row ) {
				$day['kind']     = $row->status;
				$day['label']    = self::DAY_LABELS[ $row->status ] ?? $row->status;
				$day['mealType'] = Plans::MEAL_TYPES[ $row->meal_type ] ?? '';
				$day['locked']   = $locked;
				if ( $locked && in_array( $row->status, array( DayStatus::SCHEDULED, DayStatus::LOCKED, DayStatus::SKIPPED, DayStatus::PAUSED ), true ) ) {
					$day['lockReason'] = $engine->locked_message( $date );
				}
				$day['canSkip'] = ! $locked && DayStatus::SCHEDULED === $row->status;
				$day['canUndo'] = ! $locked && DayStatus::SKIPPED === $row->status;
			}
			$days[] = $day;
		}
		return $days;
	}
}
