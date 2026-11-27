<?php
/**
 * PackStatus: the six statuses a pack (wp_pz_subscriptions row) can have.
 *
 *   active     the customer's current pack (it may start in the future)
 *   queued     a renewal waiting behind the current pack (rule 9)
 *   paused     the current pack is stopped by an open-ended pause
 *   completed  every meal was delivered (or failed)
 *   cancelled  cancelled by the admin or a cancelled order
 *   refunded   cancelled by a WooCommerce refund
 *
 * Skips, holidays and dated pauses never change a pack's status.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pack status values.
 */
final class PackStatus {

	public const ACTIVE    = 'active';
	public const QUEUED    = 'queued';
	public const PAUSED    = 'paused';
	public const COMPLETED = 'completed';
	public const CANCELLED = 'cancelled';
	public const REFUNDED  = 'refunded';

	/** Packs that still have meals to schedule; reflow() works on these. */
	public const OPEN = array( self::ACTIVE, self::PAUSED, self::QUEUED );

	/** The customer's current pack (as opposed to a queued renewal). */
	public const CURRENT = array( self::ACTIVE, self::PAUSED );

	/**
	 * Is this pack still open?
	 *
	 * @param string $status Pack status.
	 */
	public static function is_open( string $status ): bool {
		return in_array( $status, self::OPEN, true );
	}

	/**
	 * Is this the current pack (active or paused)?
	 *
	 * @param string $status Pack status.
	 */
	public static function is_current( string $status ): bool {
		return in_array( $status, self::CURRENT, true );
	}
}
