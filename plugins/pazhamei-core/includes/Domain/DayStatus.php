<?php
/**
 * DayStatus: the statuses of one schedule row (one customer, one date).
 *
 *   scheduled          a meal is planned; can still change (unlocked)
 *   skipped            the customer skipped it; the meal moved to the end
 *   paused             inside a dated pause (shown on the calendar)
 *   locked             past the cutoff; a daily order exists for the kitchen
 *   delivered          delivered (marked, or closed at 23:30 when auto_mark_delivered is on)
 *   failed             delivery failed; no automatic credit (rule 14)
 *   holiday_cancelled  a locked day the admin later declared a holiday; the meal goes back to the pack
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule row status values.
 */
final class DayStatus {

	public const SCHEDULED         = 'scheduled';
	public const SKIPPED           = 'skipped';
	public const PAUSED            = 'paused';
	public const LOCKED            = 'locked';
	public const DELIVERED         = 'delivered';
	public const FAILED            = 'failed';
	public const HOLIDAY_CANCELLED = 'holiday_cancelled';

	/** Rows that use up one of the pack's meals. */
	public const USES_MEAL = array( self::SCHEDULED, self::LOCKED, self::DELIVERED, self::FAILED );

	/** Rows reflow() may delete and rewrite, on unlocked dates only. */
	public const REPLACEABLE = array( self::SCHEDULED, self::PAUSED );

	/** Rows that count as eaten (consumed_meals). */
	public const CONSUMED = array( self::DELIVERED, self::FAILED );

	/**
	 * Does a row with this status use up a meal?
	 *
	 * @param string $status Row status.
	 */
	public static function uses_meal( string $status ): bool {
		return in_array( $status, self::USES_MEAL, true );
	}
}
