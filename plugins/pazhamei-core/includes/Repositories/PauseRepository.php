<?php
/**
 * PauseRepository: wp_pz_pauses in MySQL.
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Domain\Pause;
use Pazhamei\Repositories\Contracts\PauseStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pauses, stored in MySQL.
 */
final class PauseRepository implements PauseStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'pauses' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  subscription_id bigint(20) unsigned NOT NULL,
  customer_id bigint(20) unsigned NOT NULL,
  start_date date NOT NULL,
  end_date date DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY customer_id (customer_id),
  KEY status (status)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( Pause $pause ): int {
		return Db::insert( self::table(), Rows::from_pause( $pause ) );
	}

	/** {@inheritDoc} */
	public function update( int $id, array $fields ): void {
		Db::update( self::table(), $fields, array( 'id' => $id ) );
	}

	/** {@inheritDoc} */
	public function for_customer( int $customer_id ): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE customer_id = %d ORDER BY start_date, id', self::table(), $customer_id ) );
		return array_map( array( Rows::class, 'pause' ), $rows );
	}

	/** {@inheritDoc} */
	public function active(): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY start_date, id', self::table(), Pause::ACTIVE ) );
		return array_map( array( Rows::class, 'pause' ), $rows );
	}
}
