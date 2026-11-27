<?php
/**
 * MemoryProfiles: in-memory ProfileStore for tests (UNIQUE customer_id).
 * lock() only counts calls; tests use it to check that changes take the
 * customer lock inside a transaction.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\ProfileStore;
use Pazhamei\Repositories\DuplicateKey;

/**
 * Profiles in memory.
 */
final class MemoryProfiles implements ProfileStore {

	private MemoryDb $db;

	/** Customer locks taken inside a transaction. */
	public int $locks = 0;

	/**
	 * Allergens per customer (test data).
	 *
	 * @var array<int, array<int, array{name: string, severity: string}>>
	 */
	public array $allergen_data = array();

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function ensure( int $customer_id, string $now ): void {
		try {
			$this->db->insert(
				'customer_profiles',
				array(
					'customer_id'  => $customer_id,
					'mobile'       => '',
					'instructions' => null,
					'pincode'      => '',
					'created_at'   => $now,
					'updated_at'   => $now,
				)
			);
		} catch ( DuplicateKey $exists ) {
			// Same as ON DUPLICATE KEY UPDATE: nothing to do.
			unset( $exists );
		}
	}

	public function lock( int $customer_id ): void {
		if ( $this->db->in_transaction() ) {
			++$this->locks;
		}
	}

	public function get( int $customer_id ): ?array {
		$rows = $this->db->rows( 'customer_profiles', static fn( $r ) => (int) $r['customer_id'] === $customer_id );
		return $rows[0] ?? null;
	}

	public function update( int $customer_id, array $fields ): void {
		$row = $this->get( $customer_id );
		if ( $row ) {
			$this->db->update( 'customer_profiles', (int) $row['id'], $fields );
		}
	}

	public function allergens( int $customer_id ): array {
		return $this->allergen_data[ $customer_id ] ?? array();
	}
}
