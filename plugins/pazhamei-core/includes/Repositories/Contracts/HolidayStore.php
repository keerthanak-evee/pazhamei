<?php
/**
 * HolidayStore: kitchen-closed dates (wp_pz_holidays).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for holidays.
 */
interface HolidayStore {

	/**
	 * @param string $date       'Y-m-d'.
	 * @param string $label      E.g. "Pongal".
	 * @param int    $created_by User ID.
	 * @param string $now        'Y-m-d H:i:s' IST.
	 * @return int New ID.
	 * @throws \Pazhamei\Repositories\DuplicateKey When the date is already a holiday.
	 */
	public function insert( string $date, string $label, int $created_by, string $now ): int;

	/**
	 * @param string $date 'Y-m-d'.
	 * @return bool True if a holiday was removed.
	 */
	public function delete( string $date ): bool;

	/**
	 * Holidays on or after a date, by date.
	 *
	 * @param string $date 'Y-m-d'.
	 * @return array<int, array{id: int, holiday_date: string, label: string}>
	 */
	public function list_from( string $date ): array;
}
