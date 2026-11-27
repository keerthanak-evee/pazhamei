<?php
/**
 * Events: how the engine tells other modules that something happened,
 * without depending on them. On the live site each event becomes a
 * WordPress action named pz_<event> (see WpEvents):
 *
 *   pz_pack_activated  ( int $pack_id )                 welcome email (Phase 6)
 *   pz_pause_ongoing   ( int $pause_id, int $customer_id, int $days )
 *                      fired daily by the 00:10 job for each open-ended
 *                      pause; for the "paused a while" reminder (Phase 6)
 *   pz_holiday_added   ( string $date, int[] $customer_ids )
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Event hooks.
 */
interface Events {

	/**
	 * @param string            $name Event name without the pz_ prefix.
	 * @param array<int, mixed> $args Arguments.
	 */
	public function emit( string $name, array $args ): void;
}
