<?php
/**
 * Db: a thin wrapper around $wpdb used by every engine repository.
 *
 * $wpdb returns false (and may print HTML) when a statement fails. Inside a
 * transaction that is dangerous: the code would carry on and commit half a
 * change. These helpers hide the printed error and throw instead:
 * DuplicateKey for a UNIQUE violation, StorageError for anything else.
 *
 * Every query that contains values must already be prepared with
 * $wpdb->prepare() by the caller (or use insert/update/delete here, which
 * prepare for you).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use wpdb;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Error-checked database calls.
 */
final class Db {

	/**
	 * The global $wpdb object.
	 */
	public static function wpdb(): wpdb {
		global $wpdb;
		return $wpdb;
	}

	/**
	 * Full table name, e.g. Db::table('schedule') => 'wp_pz_schedule'.
	 *
	 * @param string $name Table name without the wp_pz_ prefix.
	 */
	public static function table( string $name ): string {
		return self::wpdb()->prefix . 'pz_' . $name;
	}

	/**
	 * Charset and collation for CREATE TABLE, with the InnoDB engine
	 * (transactions and SELECT ... FOR UPDATE need it).
	 */
	public static function table_options(): string {
		return 'ENGINE=InnoDB ' . self::wpdb()->get_charset_collate();
	}

	/**
	 * Insert one row and return its new ID.
	 *
	 * @param string               $table Full table name.
	 * @param array<string, mixed> $data  Column => value (null becomes NULL).
	 */
	public static function insert( string $table, array $data ): int {
		$db = self::wpdb();
		self::guard( static fn() => $db->insert( $table, $data ) );
		return (int) $db->insert_id;
	}

	/**
	 * Update rows; returns the number of rows changed.
	 *
	 * @param string               $table Full table name.
	 * @param array<string, mixed> $data  Column => new value.
	 * @param array<string, mixed> $where Column => value to match.
	 */
	public static function update( string $table, array $data, array $where ): int {
		$db = self::wpdb();
		return (int) self::guard( static fn() => $db->update( $table, $data, $where ) );
	}

	/**
	 * Delete rows; returns the number deleted.
	 *
	 * @param string               $table Full table name.
	 * @param array<string, mixed> $where Column => value to match.
	 */
	public static function delete( string $table, array $where ): int {
		$db = self::wpdb();
		return (int) self::guard( static fn() => $db->delete( $table, $where ) );
	}

	/**
	 * Run a prepared statement that returns no rows.
	 *
	 * @param string $sql Prepared SQL.
	 */
	public static function query( string $sql ): int {
		$db = self::wpdb();
		return (int) self::guard( static fn() => $db->query( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared by the caller.
	}

	/**
	 * Run a prepared SELECT and return all rows as arrays.
	 *
	 * @param string $sql Prepared SQL.
	 * @return array<int, array<string, mixed>>
	 */
	public static function results( string $sql ): array {
		$db = self::wpdb();
		return (array) self::guard( static fn() => $db->get_results( $sql, ARRAY_A ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared by the caller.
	}

	/**
	 * Run a prepared SELECT and return the first row, or null.
	 *
	 * @param string $sql Prepared SQL.
	 * @return array<string, mixed>|null
	 */
	public static function row( string $sql ): ?array {
		$rows = self::results( $sql );
		return $rows[0] ?? null;
	}

	/**
	 * Run a prepared SELECT and return the first column of the first row.
	 *
	 * @param string $sql Prepared SQL.
	 * @return string|null
	 */
	public static function value( string $sql ): ?string {
		$db  = self::wpdb();
		$var = self::guard( static fn() => $db->get_var( $sql ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared by the caller.
		return null === $var ? null : (string) $var;
	}

	/**
	 * Run a prepared SELECT and return its first column as integers.
	 *
	 * @param string $sql Prepared SQL.
	 * @return int[]
	 */
	public static function ints( string $sql ): array {
		$db = self::wpdb();
		return array_map( 'intval', (array) self::guard( static fn() => $db->get_col( $sql ) ) ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- Prepared by the caller.
	}

	/**
	 * Placeholders for an IN (...) list, e.g. '%s,%s,%s'. Use with prepare().
	 *
	 * @param array<int, mixed> $values Values.
	 * @param string            $format '%s' or '%d'.
	 */
	public static function placeholders( array $values, string $format = '%s' ): string {
		return implode( ',', array_fill( 0, max( 1, count( $values ) ), $format ) );
	}

	/**
	 * Run a $wpdb call with printed errors switched off; throw if it failed.
	 *
	 * @param callable $call The $wpdb call.
	 * @return mixed What $wpdb returned.
	 * @throws DuplicateKey On a UNIQUE key violation.
	 * @throws StorageError On any other database error.
	 */
	private static function guard( callable $call ) {
		$db             = self::wpdb();
		$was_suppressed = $db->suppress_errors( true );
		$db->last_error = '';

		try {
			$result = $call();
		} finally {
			$db->suppress_errors( $was_suppressed );
		}

		if ( '' !== $db->last_error || false === $result ) {
			$message = '' !== $db->last_error ? $db->last_error : 'Database statement failed.';
			if ( false !== stripos( $message, 'Duplicate entry' ) ) {
				throw new DuplicateKey( $message );
			}
			throw new StorageError( $message );
		}
		return $result;
	}
}
