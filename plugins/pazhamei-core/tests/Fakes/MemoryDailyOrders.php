<?php
/**
 * MemoryDailyOrders: in-memory DailyOrderStore for tests (UNIQUE schedule_id).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\DailyOrderStore;
use Pazhamei\Repositories\DuplicateKey;

/**
 * Daily orders in memory.
 */
final class MemoryDailyOrders implements DailyOrderStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert_if_missing( array $order ): bool {
		// Same outcome as MySQL's "ON DUPLICATE KEY UPDATE id = id": the clash is ignored.
		try {
			$this->db->insert( 'daily_orders', $order );
			return true;
		} catch ( DuplicateKey $duplicate ) {
			return false;
		}
	}

	public function find_by_day( int $schedule_id ): ?array {
		$rows = $this->db->rows( 'daily_orders', static fn( $r ) => (int) $r['schedule_id'] === $schedule_id );
		return $rows[0] ?? null;
	}

	public function set_status( int $schedule_id, string $status, string $now ): void {
		foreach ( $this->db->rows( 'daily_orders', static fn( $r ) => (int) $r['schedule_id'] === $schedule_id ) as $row ) {
			$this->db->update(
				'daily_orders',
				(int) $row['id'],
				array(
					'status'     => $status,
					'updated_at' => $now,
				)
			);
		}
	}

	public function for_date( string $date ): array {
		return $this->db->rows( 'daily_orders', static fn( $r ) => $r['service_date'] === $date );
	}
}
