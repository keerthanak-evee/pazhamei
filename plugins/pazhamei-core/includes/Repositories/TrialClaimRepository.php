<?php
/**
 * TrialClaimRepository: wp_pz_trial_claims in MySQL. One row per trial
 * sold, with the normalised mobile and address keys at that moment, so a
 * later change of phone number cannot unlock a second trial (rule 10).
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\TrialClaimStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trial claims, stored in MySQL.
 */
final class TrialClaimRepository implements TrialClaimStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'trial_claims' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  subscription_id bigint(20) unsigned NOT NULL DEFAULT 0,
  mobile_key varchar(10) NOT NULL DEFAULT '',
  address_key varchar(120) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY customer_id (customer_id),
  KEY mobile_key (mobile_key),
  KEY address_key (address_key)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( int $customer_id, int $subscription_id, string $mobile_key, string $address_key, string $now ): int {
		return Db::insert(
			self::table(),
			array(
				'customer_id'     => $customer_id,
				'subscription_id' => $subscription_id,
				'mobile_key'      => $mobile_key,
				'address_key'     => $address_key,
				'created_at'      => $now,
			)
		);
	}

	/** {@inheritDoc} */
	public function exists_for_customer( int $customer_id ): bool {
		return null !== Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE customer_id = %d LIMIT 1', self::table(), $customer_id ) );
	}

	/** {@inheritDoc} */
	public function exists_with_mobile( string $mobile_key, int $except ): bool {
		if ( '' === $mobile_key ) {
			return false;
		}
		return null !== Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE mobile_key = %s AND customer_id <> %d LIMIT 1', self::table(), $mobile_key, $except ) );
	}

	/** {@inheritDoc} */
	public function exists_with_address( string $address_key, int $except ): bool {
		if ( '' === $address_key ) {
			return false;
		}
		return null !== Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE address_key = %s AND customer_id <> %d LIMIT 1', self::table(), $address_key, $except ) );
	}
}
