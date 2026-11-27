<?php
/**
 * MemorySchedule: in-memory ScheduleStore for tests. UNIQUE (customer_id,
 * service_date) is enforced by MemoryDb.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Domain\Day;
use Pazhamei\Repositories\Contracts\ScheduleStore;
use Pazhamei\Repositories\Rows;

/**
 * Schedule rows in memory.
 */
final class MemorySchedule implements ScheduleStore {

	private MemoryDb $db;

	/** How many times rows were read FOR UPDATE inside a transaction. */
	public int $locked_reads = 0;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( Day $day ): int {
		return $this->db->insert( 'schedule', Rows::from_day( $day ) );
	}

	public function find( int $id ): ?Day {
		$row = $this->db->find( 'schedule', $id );
		return $row ? Rows::day( $row ) : null;
	}

	public function find_by_date( int $customer_id, string $date ): ?Day {
		$rows = $this->db->rows( 'schedule', static fn( $r ) => (int) $r['customer_id'] === $customer_id && $r['service_date'] === $date );
		return $rows ? Rows::day( $rows[0] ) : null;
	}

	public function for_customer( int $customer_id, bool $for_update = false ): array {
		if ( $for_update && $this->db->in_transaction() ) {
			++$this->locked_reads;
		}
		return $this->sorted( $this->db->rows( 'schedule', static fn( $r ) => (int) $r['customer_id'] === $customer_id ) );
	}

	public function in_range( string $from, string $to, array $statuses ): array {
		return $this->sorted(
			$this->db->rows(
				'schedule',
				static fn( $r ) => $r['service_date'] >= $from && $r['service_date'] <= $to && in_array( $r['status'], $statuses, true )
			)
		);
	}

	public function customer_ids_on( string $date ): array {
		return array_values( array_unique( array_map( static fn( $r ) => (int) $r['customer_id'], $this->db->rows( 'schedule', static fn( $r ) => $r['service_date'] === $date ) ) ) );
	}

	public function count_for_pack( int $subscription_id, array $statuses ): int {
		return count( $this->db->rows( 'schedule', static fn( $r ) => (int) $r['subscription_id'] === $subscription_id && in_array( $r['status'], $statuses, true ) ) );
	}

	public function update( int $id, array $fields ): void {
		$this->db->update( 'schedule', $id, $fields );
	}

	public function delete( array $ids ): void {
		foreach ( $ids as $id ) {
			$this->db->delete( 'schedule', (int) $id );
		}
	}

	/**
	 * Rows by date, then customer.
	 *
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @return Day[]
	 */
	private function sorted( array $rows ): array {
		usort( $rows, static fn( $a, $b ) => array( $a['service_date'], $a['customer_id'] ) <=> array( $b['service_date'], $b['customer_id'] ) );
		return array_map( array( Rows::class, 'day' ), $rows );
	}
}
