<?php
/**
 * MemoryTrialClaims: in-memory TrialClaimStore for tests.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\TrialClaimStore;

/**
 * Trial claims in memory.
 */
final class MemoryTrialClaims implements TrialClaimStore {

	private MemoryDb $db;

	public function __construct( MemoryDb $db ) {
		$this->db = $db;
	}

	public function insert( int $customer_id, int $subscription_id, string $mobile_key, string $address_key, string $now ): int {
		return $this->db->insert(
			'trial_claims',
			array(
				'customer_id'     => $customer_id,
				'subscription_id' => $subscription_id,
				'mobile_key'      => $mobile_key,
				'address_key'     => $address_key,
				'created_at'      => $now,
			)
		);
	}

	public function exists_for_customer( int $customer_id ): bool {
		return (bool) $this->db->rows( 'trial_claims', static fn( $r ) => (int) $r['customer_id'] === $customer_id );
	}

	public function exists_with_mobile( string $mobile_key, int $except ): bool {
		return '' !== $mobile_key && (bool) $this->db->rows( 'trial_claims', static fn( $r ) => $r['mobile_key'] === $mobile_key && (int) $r['customer_id'] !== $except );
	}

	public function exists_with_address( string $address_key, int $except ): bool {
		return '' !== $address_key && (bool) $this->db->rows( 'trial_claims', static fn( $r ) => $r['address_key'] === $address_key && (int) $r['customer_id'] !== $except );
	}
}
