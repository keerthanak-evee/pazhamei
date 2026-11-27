<?php
/**
 * HolidayRepository: wp_pz_holidays in MySQL. One row per closed date.
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\HolidayStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Holidays, stored in MySQL.
 */
final class HolidayRepository implements HolidayStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'holidays' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  holiday_date date NOT NULL,
  label varchar(100) NOT NULL DEFAULT '',
  created_by bigint(20) unsigned NOT NULL DEFAULT 0,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY holiday_date (holiday_date)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( string $date, string $label, int $created_by, string $now ): int {
		return Db::insert(
			self::table(),
			array(
				'holiday_date' => $date,
				'label'        => $label,
				'created_by'   => $created_by,
				'created_at'   => $now,
			)
		);
	}

	/** {@inheritDoc} */
	public function delete( string $date ): bool {
		return Db::delete( self::table(), array( 'holiday_date' => $date ) ) > 0;
	}

	/** {@inheritDoc} */
	public function list_from( string $date ): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT id, holiday_date, label FROM %i WHERE holiday_date >= %s ORDER BY holiday_date', self::table(), $date ) );
		return array_map(
			static fn( array $r ) => array(
				'id'           => (int) $r['id'],
				'holiday_date' => (string) $r['holiday_date'],
				'label'        => (string) $r['label'],
			),
			$rows
		);
	}
}
