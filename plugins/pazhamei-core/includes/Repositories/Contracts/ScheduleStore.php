<?php
/**
 * ScheduleStore: one row per customer per date (wp_pz_schedule).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

use Pazhamei\Domain\Day;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for schedule rows.
 */
interface ScheduleStore {

	/**
	 * Save a new row.
	 *
	 * @param Day $day Row with id 0.
	 * @return int New ID.
	 * @throws \Pazhamei\Repositories\DuplicateKey When the customer already has a row on that date (rule 5).
	 */
	public function insert( Day $day ): int;

	/**
	 * @param int $id Row ID.
	 */
	public function find( int $id ): ?Day;

	/**
	 * @param int    $customer_id Customer.
	 * @param string $date        'Y-m-d'.
	 */
	public function find_by_date( int $customer_id, string $date ): ?Day;

	/**
	 * All of a customer's rows, by date. With $for_update, the rows are
	 * locked until the transaction ends (SELECT ... FOR UPDATE).
	 *
	 * @param int  $customer_id Customer.
	 * @param bool $for_update  Lock the rows.
	 * @return Day[]
	 */
	public function for_customer( int $customer_id, bool $for_update = false ): array;

	/**
	 * Rows of every customer between two dates (both included) with one of these statuses.
	 *
	 * @param string   $from     'Y-m-d'.
	 * @param string   $to       'Y-m-d'.
	 * @param string[] $statuses Statuses.
	 * @return Day[]
	 */
	public function in_range( string $from, string $to, array $statuses ): array;

	/**
	 * Customers who have any row on a date.
	 *
	 * @param string $date 'Y-m-d'.
	 * @return int[]
	 */
	public function customer_ids_on( string $date ): array;

	/**
	 * Number of a pack's rows with one of these statuses.
	 *
	 * @param int      $subscription_id Pack.
	 * @param string[] $statuses        Statuses.
	 */
	public function count_for_pack( int $subscription_id, array $statuses ): int;

	/**
	 * Change some columns of one row.
	 *
	 * @param int                  $id     Row ID.
	 * @param array<string, mixed> $fields Column => value.
	 * @throws \Pazhamei\Repositories\DuplicateKey When the change breaks UNIQUE (customer, date).
	 */
	public function update( int $id, array $fields ): void;

	/**
	 * Delete rows.
	 *
	 * @param int[] $ids Row IDs.
	 */
	public function delete( array $ids ): void;
}
