<?php
/**
 * DailyOrderStore: frozen kitchen orders (wp_pz_daily_orders).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for daily orders.
 */
interface DailyOrderStore {

	/**
	 * Insert an order unless one already exists for its schedule row
	 * (UNIQUE schedule_id), so the lock job is safe to rerun.
	 *
	 * @param array<string, mixed> $order Column => value.
	 * @return bool True if a new order was created.
	 */
	public function insert_if_missing( array $order ): bool;

	/**
	 * @param int $schedule_id Schedule row.
	 * @return array<string, mixed>|null
	 */
	public function find_by_day( int $schedule_id ): ?array;

	/**
	 * @param int    $schedule_id Schedule row.
	 * @param string $status      New status.
	 * @param string $now         'Y-m-d H:i:s' IST.
	 */
	public function set_status( int $schedule_id, string $status, string $now ): void;

	/**
	 * Orders for one date.
	 *
	 * @param string $date 'Y-m-d'.
	 * @return array<int, array<string, mixed>>
	 */
	public function for_date( string $date ): array;
}
