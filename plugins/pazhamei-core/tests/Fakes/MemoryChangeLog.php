<?php
/**
 * MemoryChangeLog: in-memory ChangeLogStore for tests.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\ChangeLogStore;

/**
 * Change log in memory.
 */
final class MemoryChangeLog implements ChangeLogStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( array $entry ): int {
		return $this->db->insert( 'change_log', $entry );
	}

	public function for_entity( string $entity_type, int $entity_id ): array {
		return $this->db->rows( 'change_log', static fn( $r ) => $r['entity_type'] === $entity_type && (int) $r['entity_id'] === $entity_id );
	}

	public function latest( int $limit ): array {
		return array_slice( array_reverse( $this->db->rows( 'change_log' ) ), 0, $limit );
	}

	/**
	 * Every action name logged, in order (test helper).
	 *
	 * @return string[]
	 */
	public function actions(): array {
		return array_map( static fn( $r ) => (string) $r['action'], $this->db->rows( 'change_log' ) );
	}
}
