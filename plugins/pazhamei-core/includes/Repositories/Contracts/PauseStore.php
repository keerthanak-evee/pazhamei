<?php
/**
 * PauseStore: pause ranges (wp_pz_pauses).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

use Pazhamei\Domain\Pause;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for pauses.
 */
interface PauseStore {

	/**
	 * @param Pause $pause Pause with id 0.
	 * @return int New ID.
	 */
	public function insert( Pause $pause ): int;

	/**
	 * @param int                  $id     Pause ID.
	 * @param array<string, mixed> $fields Column => value.
	 */
	public function update( int $id, array $fields ): void;

	/**
	 * A customer's pauses, oldest first.
	 *
	 * @param int $customer_id Customer.
	 * @return Pause[]
	 */
	public function for_customer( int $customer_id ): array;

	/**
	 * Every pause with status active, for all customers.
	 *
	 * @return Pause[]
	 */
	public function active(): array;
}
