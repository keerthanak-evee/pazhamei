<?php
/**
 * TrialClaimStore: who has had a trial, by account, mobile and address
 * (wp_pz_trial_claims, rule 10).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for trial claims.
 */
interface TrialClaimStore {

	/**
	 * @param int    $customer_id     Customer.
	 * @param int    $subscription_id Trial pack.
	 * @param string $mobile_key      TrialIdentity::mobile_key().
	 * @param string $address_key     TrialIdentity::address_key().
	 * @param string $now             'Y-m-d H:i:s' IST.
	 * @return int New ID.
	 */
	public function insert( int $customer_id, int $subscription_id, string $mobile_key, string $address_key, string $now ): int;

	/**
	 * @param int $customer_id Customer.
	 */
	public function exists_for_customer( int $customer_id ): bool;

	/**
	 * Has another customer had a trial with this mobile key?
	 *
	 * @param string $mobile_key Key.
	 * @param int    $except     Customer to ignore.
	 */
	public function exists_with_mobile( string $mobile_key, int $except ): bool;

	/**
	 * Has another customer had a trial at this address key?
	 *
	 * @param string $address_key Key.
	 * @param int    $except      Customer to ignore.
	 */
	public function exists_with_address( string $address_key, int $except ): bool;
}
