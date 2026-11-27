<?php
/**
 * SchemaRepository: checks the storage engine of every wp_pz_* table.
 *
 * Transactions and SELECT ... FOR UPDATE only work on InnoDB. dbDelta never
 * changes the engine of an existing table, so after creating tables the
 * installer calls convert_to_innodb() for any table that is not InnoDB yet.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Table engine checks.
 */
final class SchemaRepository {

	/**
	 * Every wp_pz_* table with its engine and approximate row count.
	 *
	 * @return array<int, array{table: string, engine: string, rows: int}>
	 */
	public static function tables(): array {
		$db   = Db::wpdb();
		$like = $db->esc_like( $db->prefix . 'pz_' ) . '%';
		$rows = Db::results(
			$db->prepare(
				'SELECT TABLE_NAME AS name, ENGINE AS engine, TABLE_ROWS AS row_count FROM information_schema.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME LIKE %s ORDER BY TABLE_NAME',
				$like
			)
		);
		return array_map(
			static fn( array $r ) => array(
				'table'  => (string) $r['name'],
				'engine' => (string) $r['engine'],
				'rows'   => (int) $r['row_count'],
			),
			$rows
		);
	}

	/**
	 * Convert any wp_pz_* table that is not InnoDB.
	 *
	 * @return string[] Tables that were converted.
	 */
	public static function convert_to_innodb(): array {
		$converted = array();
		foreach ( self::tables() as $table ) {
			if ( 'innodb' !== strtolower( $table['engine'] ) ) {
				Db::query( Db::wpdb()->prepare( 'ALTER TABLE %i ENGINE=InnoDB', $table['table'] ) );
				$converted[] = $table['table'];
			}
		}
		return $converted;
	}
}
