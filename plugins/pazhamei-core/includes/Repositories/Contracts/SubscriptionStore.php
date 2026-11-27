<?php
/**
 * SubscriptionStore: packs (wp_pz_subscriptions).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

use Pazhamei\Domain\Pack;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for packs.
 */
interface SubscriptionStore {

	/**
	 * Save a new pack.
	 *
	 * @param Pack $pack Pack with id 0.
	 * @return int New ID.
	 * @throws \Pazhamei\Repositories\DuplicateKey When the order item already has a pack.
	 */
	public function insert( Pack $pack ): int;

	/**
	 * @param int $id Pack ID.
	 */
	public function find( int $id ): ?Pack;

	/**
	 * @param int $order_item_id WooCommerce order item ID.
	 */
	public function find_by_order_item( int $order_item_id ): ?Pack;

	/**
	 * @param int $order_id WooCommerce order ID.
	 * @return Pack[]
	 */
	public function for_order( int $order_id ): array;

	/**
	 * A customer's packs, oldest first.
	 *
	 * @param int      $customer_id Customer.
	 * @param string[] $statuses    Only these statuses; empty for all.
	 * @return Pack[]
	 */
	public function for_customer( int $customer_id, array $statuses = array() ): array;

	/**
	 * Change some columns of one pack.
	 *
	 * @param int                  $id     Pack ID.
	 * @param array<string, mixed> $fields Column => value.
	 */
	public function update( int $id, array $fields ): void;

	/**
	 * Customers who have a pack in one of these statuses.
	 *
	 * @param string[] $statuses Statuses.
	 * @return int[]
	 */
	public function customer_ids( array $statuses ): array;

	/**
	 * Has the customer ever had a pack of this plan (any status)?
	 *
	 * @param int    $customer_id Customer.
	 * @param string $plan_code   TRIAL, D6 or D26.
	 */
	public function has_plan( int $customer_id, string $plan_code ): bool;
}
