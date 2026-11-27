<?php
/**
 * SubscriptionRepository: wp_pz_subscriptions in MySQL. One row per pack.
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Domain\Pack;
use Pazhamei\Repositories\Contracts\SubscriptionStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Packs, stored in MySQL.
 */
final class SubscriptionRepository implements SubscriptionStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'subscriptions' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  order_id bigint(20) unsigned NOT NULL DEFAULT 0,
  order_item_id bigint(20) unsigned NOT NULL,
  product_id bigint(20) unsigned NOT NULL DEFAULT 0,
  plan_code varchar(10) NOT NULL,
  meal_type varchar(10) NOT NULL,
  quantity smallint(5) unsigned NOT NULL DEFAULT 1,
  total_meals smallint(5) unsigned NOT NULL,
  consumed_meals smallint(5) unsigned NOT NULL DEFAULT 0,
  requested_start date DEFAULT NULL,
  start_date date DEFAULT NULL,
  end_date date DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'active',
  renewed_from_id bigint(20) unsigned DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY order_item_id (order_item_id),
  KEY customer_status (customer_id,status),
  KEY order_id (order_id)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( Pack $pack ): int {
		return Db::insert( self::table(), Rows::from_pack( $pack ) );
	}

	/** {@inheritDoc} */
	public function find( int $id ): ?Pack {
		$row = Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE id = %d', self::table(), $id ) );
		return $row ? Rows::pack( $row ) : null;
	}

	/** {@inheritDoc} */
	public function find_by_order_item( int $order_item_id ): ?Pack {
		$row = Db::row( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE order_item_id = %d', self::table(), $order_item_id ) );
		return $row ? Rows::pack( $row ) : null;
	}

	/** {@inheritDoc} */
	public function for_order( int $order_id ): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE order_id = %d ORDER BY id', self::table(), $order_id ) );
		return array_map( array( Rows::class, 'pack' ), $rows );
	}

	/** {@inheritDoc} */
	public function for_customer( int $customer_id, array $statuses = array() ): array {
		$db = Db::wpdb();
		if ( $statuses ) {
			$sql = $db->prepare(
				'SELECT * FROM %i WHERE customer_id = %d AND status IN (' . Db::placeholders( $statuses ) . ') ORDER BY created_at, id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
				self::table(),
				$customer_id,
				...$statuses
			);
		} else {
			$sql = $db->prepare( 'SELECT * FROM %i WHERE customer_id = %d ORDER BY created_at, id', self::table(), $customer_id );
		}
		return array_map( array( Rows::class, 'pack' ), Db::results( $sql ) );
	}

	/** {@inheritDoc} */
	public function update( int $id, array $fields ): void {
		Db::update( self::table(), $fields, array( 'id' => $id ) );
	}

	/** {@inheritDoc} */
	public function customer_ids( array $statuses ): array {
		return Db::ints(
			Db::wpdb()->prepare(
				'SELECT DISTINCT customer_id FROM %i WHERE status IN (' . Db::placeholders( $statuses ) . ') ORDER BY customer_id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built above.
				self::table(),
				...$statuses
			)
		);
	}

	/** {@inheritDoc} */
	public function has_plan( int $customer_id, string $plan_code ): bool {
		return null !== Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE customer_id = %d AND plan_code = %s LIMIT 1', self::table(), $customer_id, $plan_code ) );
	}
}
