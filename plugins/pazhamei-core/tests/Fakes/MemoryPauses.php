<?php
/**
 * MemoryPauses: in-memory PauseStore for tests.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Domain\Pause;
use Pazhamei\Repositories\Contracts\PauseStore;
use Pazhamei\Repositories\Rows;

/**
 * Pauses in memory.
 */
final class MemoryPauses implements PauseStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( Pause $pause ): int {
		return $this->db->insert( 'pauses', Rows::from_pause( $pause ) );
	}

	public function update( int $id, array $fields ): void {
		$this->db->update( 'pauses', $id, $fields );
	}

	public function for_customer( int $customer_id ): array {
		return $this->sorted( $this->db->rows( 'pauses', static fn( $r ) => (int) $r['customer_id'] === $customer_id ) );
	}

	public function active(): array {
		return $this->sorted( $this->db->rows( 'pauses', static fn( $r ) => Pause::ACTIVE === $r['status'] ) );
	}

	/**
	 * @param array<int, array<string, mixed>> $rows Rows.
	 * @return Pause[]
	 */
	private function sorted( array $rows ): array {
		usort( $rows, static fn( $a, $b ) => array( $a['start_date'], $a['id'] ) <=> array( $b['start_date'], $b['id'] ) );
		return array_map( array( Rows::class, 'pause' ), $rows );
	}
}
