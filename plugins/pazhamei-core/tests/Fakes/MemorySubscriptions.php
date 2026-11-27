<?php
/**
 * MemorySubscriptions: in-memory SubscriptionStore for tests.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Domain\Pack;
use Pazhamei\Repositories\Contracts\SubscriptionStore;
use Pazhamei\Repositories\Rows;

/**
 * Packs in memory.
 */
final class MemorySubscriptions implements SubscriptionStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( Pack $pack ): int {
		return $this->db->insert( 'subscriptions', Rows::from_pack( $pack ) );
	}

	public function find( int $id ): ?Pack {
		$row = $this->db->find( 'subscriptions', $id );
		return $row ? Rows::pack( $row ) : null;
	}

	public function find_by_order_item( int $order_item_id ): ?Pack {
		$rows = $this->db->rows( 'subscriptions', static fn( $r ) => (int) $r['order_item_id'] === $order_item_id );
		return $rows ? Rows::pack( $rows[0] ) : null;
	}

	public function for_order( int $order_id ): array {
		return array_map( array( Rows::class, 'pack' ), $this->db->rows( 'subscriptions', static fn( $r ) => (int) $r['order_id'] === $order_id ) );
	}

	public function for_customer( int $customer_id, array $statuses = array() ): array {
		$rows = $this->db->rows(
			'subscriptions',
			static fn( $r ) => (int) $r['customer_id'] === $customer_id && ( ! $statuses || in_array( $r['status'], $statuses, true ) )
		);
		usort( $rows, static fn( $a, $b ) => array( $a['created_at'], $a['id'] ) <=> array( $b['created_at'], $b['id'] ) );
		return array_map( array( Rows::class, 'pack' ), $rows );
	}

	public function update( int $id, array $fields ): void {
		$this->db->update( 'subscriptions', $id, $fields );
	}

	public function customer_ids( array $statuses ): array {
		$ids = array_map( static fn( $r ) => (int) $r['customer_id'], $this->db->rows( 'subscriptions', static fn( $r ) => in_array( $r['status'], $statuses, true ) ) );
		$ids = array_values( array_unique( $ids ) );
		sort( $ids );
		return $ids;
	}

	public function has_plan( int $customer_id, string $plan_code ): bool {
		return (bool) $this->db->rows( 'subscriptions', static fn( $r ) => (int) $r['customer_id'] === $customer_id && $r['plan_code'] === $plan_code );
	}
}
