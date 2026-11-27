<?php
/**
 * DailyOrderRepository: wp_pz_daily_orders in MySQL. Frozen kitchen orders,
 * one per locked schedule row (UNIQUE schedule_id).
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\DailyOrderStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily orders, stored in MySQL.
 */
final class DailyOrderRepository implements DailyOrderStore {

	/** Columns written by insert_if_missing(), in order. */
	private const COLUMNS = array( 'schedule_id', 'subscription_id', 'customer_id', 'service_date', 'meal_type', 'quantity', 'menu_id', 'address_json', 'allergens_json', 'instructions', 'status', 'generated_at', 'updated_at' );

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'daily_orders' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  schedule_id bigint(20) unsigned NOT NULL,
  subscription_id bigint(20) unsigned NOT NULL,
  customer_id bigint(20) unsigned NOT NULL,
  service_date date NOT NULL,
  meal_type varchar(10) NOT NULL,
  quantity smallint(5) unsigned NOT NULL DEFAULT 1,
  menu_id bigint(20) unsigned DEFAULT NULL,
  address_json text NOT NULL,
  allergens_json text NOT NULL,
  instructions text NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'confirmed',
  generated_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY schedule_id (schedule_id),
  KEY date_meal (service_date,meal_type),
  KEY customer_id (customer_id)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert_if_missing( array $order ): bool {
		$values = array();
		$marks  = array();
		foreach ( self::COLUMNS as $column ) {
			$value = $order[ $column ] ?? null;
			if ( null === $value ) {
				$marks[] = 'NULL';
				continue;
			}
			$marks[]  = is_int( $value ) ? '%d' : '%s';
			$values[] = $value;
		}

		// "ON DUPLICATE KEY UPDATE id = id" does nothing when the schedule row already has an order.
		$sql = Db::wpdb()->prepare(
			'INSERT INTO %i (' . implode( ', ', self::COLUMNS ) . ') VALUES (' . implode( ', ', $marks ) . ') ON DUPLICATE KEY UPDATE id = id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
			self::table(),
			...$values
		);
		return 1 === Db::query( $sql );
	}

	/** {@inheritDoc} */
	public function find_by_day( int $schedule_id ): ?array {
		return Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE schedule_id = %d', self::table(), $schedule_id ) );
	}

	/** {@inheritDoc} */
	public function set_status( int $schedule_id, string $status, string $now ): void {
		Db::update(
			self::table(),
			array(
				'status'     => $status,
				'updated_at' => $now,
			),
			array( 'schedule_id' => $schedule_id )
		);
	}

	/** {@inheritDoc} */
	public function for_date( string $date ): array {
		return Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE service_date = %s ORDER BY meal_type, customer_id', self::table(), $date ) );
	}
}
