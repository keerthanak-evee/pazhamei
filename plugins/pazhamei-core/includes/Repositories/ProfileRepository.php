<?php
/**
 * ProfileRepository: wp_pz_customer_profiles in MySQL, plus the customer's
 * allergens (wp_pz_customer_allergens joined to wp_pz_allergens).
 *
 * The profile row doubles as a per-customer lock for every schedule change.
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\ProfileStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer profiles, stored in MySQL.
 */
final class ProfileRepository implements ProfileStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'customer_profiles' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  mobile varchar(10) NOT NULL DEFAULT '',
  dietary_preference varchar(10) NOT NULL DEFAULT '',
  instructions text NULL,
  zone varchar(50) NOT NULL DEFAULT '',
  pincode varchar(6) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY customer_id (customer_id),
  KEY mobile (mobile)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function ensure( int $customer_id, string $now ): void {
		// Does nothing if the customer already has a profile (UNIQUE customer_id).
		Db::query(
			Db::wpdb()->prepare(
				'INSERT INTO %i (customer_id, created_at, updated_at) VALUES (%d, %s, %s) ON DUPLICATE KEY UPDATE customer_id = customer_id',
				self::table(),
				$customer_id,
				$now,
				$now
			)
		);
	}

	/** {@inheritDoc} */
	public function lock( int $customer_id ): void {
		Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE customer_id = %d FOR UPDATE', self::table(), $customer_id ) );
	}

	/** {@inheritDoc} */
	public function get( int $customer_id ): ?array {
		return Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE customer_id = %d', self::table(), $customer_id ) );
	}

	/** {@inheritDoc} */
	public function update( int $customer_id, array $fields ): void {
		Db::update( self::table(), $fields, array( 'customer_id' => $customer_id ) );
	}

	/** {@inheritDoc} */
	public function allergens( int $customer_id ): array {
		$rows = Db::results(
			Db::wpdb()->prepare(
				'SELECT a.name, ca.severity FROM %i ca JOIN %i a ON a.id = ca.allergen_id WHERE ca.customer_id = %d ORDER BY a.name',
				Db::table( 'customer_allergens' ),
				Db::table( 'allergens' ),
				$customer_id
			)
		);
		return array_map(
			static fn( array $r ) => array(
				'name'     => (string) $r['name'],
				'severity' => (string) $r['severity'],
			),
			$rows
		);
	}
}
