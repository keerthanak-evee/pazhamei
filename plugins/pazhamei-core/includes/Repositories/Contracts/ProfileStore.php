<?php
/**
 * ProfileStore: customer profiles (wp_pz_customer_profiles) and allergens.
 *
 * The profile row also serves as the customer's lock: every change to a
 * customer's schedule first locks this row (SELECT ... FOR UPDATE), so a
 * skip at 19:59 and the 20:00 lock job can never run at the same time.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for customer profiles.
 */
interface ProfileStore {

	/**
	 * Create an empty profile if the customer has none.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $now         'Y-m-d H:i:s' IST.
	 */
	public function ensure( int $customer_id, string $now ): void;

	/**
	 * Lock the customer's profile row until the transaction ends.
	 *
	 * @param int $customer_id Customer.
	 */
	public function lock( int $customer_id ): void;

	/**
	 * @param int $customer_id Customer.
	 * @return array<string, mixed>|null
	 */
	public function get( int $customer_id ): ?array;

	/**
	 * @param int                  $customer_id Customer.
	 * @param array<string, mixed> $fields      Column => value.
	 */
	public function update( int $customer_id, array $fields ): void;

	/**
	 * The customer's allergens.
	 *
	 * @param int $customer_id Customer.
	 * @return array<int, array{name: string, severity: string}>
	 */
	public function allergens( int $customer_id ): array;

	/**
	 * The customer whose profile has this mobile (10 digits), or 0.
	 *
	 * @param string $mobile 10 digits.
	 */
	public function customer_by_mobile( string $mobile ): int;
}
