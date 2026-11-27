<?php
/**
 * ScheduleRepository: wp_pz_schedule in MySQL. One row per customer per date;
 * UNIQUE (customer_id, service_date) enforces one meal a day (rule 5).
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Domain\Day;
use Pazhamei\Repositories\Contracts\ScheduleStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Schedule rows, stored in MySQL.
 */
final class ScheduleRepository implements ScheduleStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'schedule' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  service_date date NOT NULL,
  subscription_id bigint(20) unsigned NOT NULL,
  meal_type varchar(10) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'scheduled',
  source varchar(10) NOT NULL DEFAULT 'reflow',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY customer_date (customer_id,service_date),
  KEY date_status (service_date,status),
  KEY subscription_id (subscription_id)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( Day $day ): int {
		return Db::insert( self::table(), Rows::from_day( $day ) );
	}

	/** {@inheritDoc} */
	public function find( int $id ): ?Day {
		$row = Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ) );
		return $row ? Rows::day( $row ) : null;
	}

	/** {@inheritDoc} */
	public function find_by_date( int $customer_id, string $date ): ?Day {
		$row = Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE customer_id = %d AND service_date = %s', self::table(), $customer_id, $date ) );
		return $row ? Rows::day( $row ) : null;
	}

	/** {@inheritDoc} */
	public function for_customer( int $customer_id, bool $for_update = false ): array {
		$sql = Db::wpdb()->prepare( 'SELECT * FROM %i WHERE customer_id = %d ORDER BY service_date', self::table(), $customer_id );
		if ( $for_update ) {
			$sql .= ' FOR UPDATE';
		}
		return array_map( array( Rows::class, 'day' ), Db::results( $sql ) );
	}

	/** {@inheritDoc} */
	public function in_range( string $from, string $to, array $statuses ): array {
		$sql = Db::wpdb()->prepare(
			'SELECT * FROM %i WHERE service_date BETWEEN %s AND %s AND status IN (' . Db::placeholders( $statuses ) . ') ORDER BY service_date, customer_id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
			self::table(),
			$from,
			$to,
			...$statuses
		);
		return array_map( array( Rows::class, 'day' ), Db::results( $sql ) );
	}

	/** {@inheritDoc} */
	public function customer_ids_on( string $date ): array {
		return Db::ints( Db::wpdb()->prepare( 'SELECT DISTINCT customer_id FROM %i WHERE service_date = %s', self::table(), $date ) );
	}

	/** {@inheritDoc} */
	public function count_for_pack( int $subscription_id, array $statuses ): int {
		return (int) Db::value(
			Db::wpdb()->prepare(
				'SELECT COUNT(*) FROM %i WHERE subscription_id = %d AND status IN (' . Db::placeholders( $statuses ) . ')', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
				self::table(),
				$subscription_id,
				...$statuses
			)
		);
	}

	/** {@inheritDoc} */
	public function update( int $id, array $fields ): void {
		Db::update( self::table(), $fields, array( 'id' => $id ) );
	}

	/** {@inheritDoc} */
	public function delete( array $ids ): void {
		$ids = array_map( 'intval', $ids );
		if ( ! $ids ) {
			return;
		}
		Db::query(
			Db::wpdb()->prepare(
				'DELETE FROM %i WHERE id IN (' . Db::placeholders( $ids, '%d' ) . ')', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
				self::table(),
				...$ids
			)
		);
	}
}
