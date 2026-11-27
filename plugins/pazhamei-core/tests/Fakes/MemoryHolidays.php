<?php
/**
 * MemoryHolidays: in-memory HolidayStore for tests (UNIQUE holiday_date).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\HolidayStore;

/**
 * Holidays in memory.
 */
final class MemoryHolidays implements HolidayStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( string $date, string $label, int $created_by, string $now ): int {
		return $this->db->insert(
			'holidays',
			array(
				'holiday_date' => $date,
				'label'        => $label,
				'created_by'   => $created_by,
				'created_at'   => $now,
			)
		);
	}

	public function delete( string $date ): bool {
		$rows = $this->db->rows( 'holidays', static fn( $r ) => $r['holiday_date'] === $date );
		foreach ( $rows as $row ) {
			$this->db->delete( 'holidays', (int) $row['id'] );
		}
		return (bool) $rows;
	}

	public function list_from( string $date ): array {
		$rows = $this->db->rows( 'holidays', static fn( $r ) => $r['holiday_date'] >= $date );
		usort( $rows, static fn( $a, $b ) => $a['holiday_date'] <=> $b['holiday_date'] );
		return array_map(
			static fn( $r ) => array(
				'id'           => (int) $r['id'],
				'holiday_date' => (string) $r['holiday_date'],
				'label'        => (string) $r['label'],
			),
			$rows
		);
	}
}
