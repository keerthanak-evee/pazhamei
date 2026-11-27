<?php
/**
 * ChangeLogRepository: wp_pz_change_log in MySQL. Every change, with
 * before/after values, who made it and why.
 *
 * See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\ChangeLogStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Change log, stored in MySQL.
 */
final class ChangeLogRepository implements ChangeLogStore {

	/**
	 * Full table name.
	 */
	public static function table(): string {
		return Db::table( 'change_log' );
	}

	/**
	 * CREATE TABLE for dbDelta.
	 */
	public static function schema(): string {
		$table = self::table();
		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  entity_type varchar(20) NOT NULL,
  entity_id bigint(20) unsigned NOT NULL DEFAULT 0,
  action varchar(40) NOT NULL,
  old_json longtext NULL,
  new_json longtext NULL,
  actor_id bigint(20) unsigned NOT NULL DEFAULT 0,
  actor_role varchar(20) NOT NULL DEFAULT '',
  reason varchar(255) NOT NULL DEFAULT '',
  ip varchar(45) NOT NULL DEFAULT '',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY entity (entity_type,entity_id),
  KEY created_at (created_at)
) " . Db::table_options() . ';';
	}

	/** {@inheritDoc} */
	public function insert( array $entry ): int {
		return Db::insert( self::table(), $entry );
	}

	/** {@inheritDoc} */
	public function for_entity( string $entity_type, int $entity_id ): array {
		return Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE entity_type = %s AND entity_id = %d ORDER BY id', self::table(), $entity_type, $entity_id ) );
	}

	/** {@inheritDoc} */
	public function latest( int $limit ): array {
		return Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i ORDER BY id DESC LIMIT %d', self::table(), $limit ) );
	}
}
