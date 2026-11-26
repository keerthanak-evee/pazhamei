<?php
/**
 * EnquiryRepository: reads and writes the wp_pz_enquiries table.
 *
 * All SQL for enquiries lives here, including the table definition that the
 * installer passes to dbDelta. See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for storefront enquiries.
 */
final class EnquiryRepository {

	/**
	 * Full table name, with the site's prefix.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'pz_enquiries';
	}

	/**
	 * CREATE TABLE statement in the exact format dbDelta needs
	 * (two spaces after PRIMARY KEY, one column per line).
	 */
	public static function schema(): string {
		global $wpdb;
		$table   = self::table();
		$charset = $wpdb->get_charset_collate();

		return "CREATE TABLE {$table} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  mobile varchar(10) NOT NULL,
  email varchar(100) NOT NULL DEFAULT '',
  area varchar(100) NOT NULL DEFAULT '',
  pincode varchar(6) NOT NULL DEFAULT '',
  plan_interest varchar(20) NOT NULL DEFAULT '',
  meal_type varchar(10) NOT NULL DEFAULT '',
  preferred_start date DEFAULT NULL,
  message text NOT NULL,
  source_page varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'new',
  assigned_to bigint(20) unsigned DEFAULT NULL,
  notes text NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY status_created (status,created_at),
  KEY mobile (mobile)
) {$charset};";
	}

	/**
	 * Save a new enquiry.
	 *
	 * @param array<string, string|null> $data       Cleaned values from EnquiryValidator.
	 * @param string                     $source_page Page the form was on.
	 * @param string                     $created_at  Time in IST, Y-m-d H:i:s.
	 * @return int New row ID, or 0 if the insert failed.
	 */
	public static function insert( array $data, string $source_page, string $created_at ): int {
		global $wpdb;

		// $wpdb->insert() prepares the query with these formats, so values are escaped.
		$ok = $wpdb->insert( // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery -- Custom table.
			self::table(),
			array(
				'name'            => (string) $data['name'],
				'mobile'          => (string) $data['mobile'],
				'email'           => (string) $data['email'],
				'area'            => (string) $data['area'],
				'pincode'         => (string) $data['pincode'],
				'plan_interest'   => (string) $data['plan_interest'],
				'meal_type'       => (string) $data['meal_type'],
				'preferred_start' => $data['preferred_start'],
				'message'         => (string) $data['message'],
				'source_page'     => $source_page,
				'status'          => 'new',
				'created_at'      => $created_at,
			),
			array( '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
		);

		return $ok ? (int) $wpdb->insert_id : 0;
	}

	/**
	 * Enquiries, newest first, optionally only one status.
	 *
	 * @param string $status Status to show, or '' for all.
	 * @param int    $limit  Rows per page.
	 * @param int    $offset Rows to skip.
	 * @return array<int, array<string, string|null>>
	 */
	public static function list( string $status, int $limit, int $offset ): array {
		global $wpdb;
		$table = self::table();

		if ( '' === $status ) {
			$sql = $wpdb->prepare( 'SELECT * FROM %i ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', $table, $limit, $offset );
		} else {
			$sql = $wpdb->prepare( 'SELECT * FROM %i WHERE status = %s ORDER BY created_at DESC, id DESC LIMIT %d OFFSET %d', $table, $status, $limit, $offset );
		}

		return (array) $wpdb->get_results( $sql, ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery, WordPress.DB.PreparedSQL.NotPrepared -- Prepared above.
	}

	/**
	 * How many enquiries have each status.
	 *
	 * @return array<string, int> Status => count.
	 */
	public static function count_by_status(): array {
		global $wpdb;
		$rows = $wpdb->get_results( $wpdb->prepare( 'SELECT status, COUNT(*) AS total FROM %i GROUP BY status', self::table() ), ARRAY_A ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery

		$counts = array();
		foreach ( (array) $rows as $row ) {
			$counts[ (string) $row['status'] ] = (int) $row['total'];
		}
		return $counts;
	}

	/**
	 * Change one enquiry's status.
	 *
	 * @param int    $id     Enquiry ID.
	 * @param string $status New status (already validated).
	 * @return bool Whether a row was found.
	 */
	public static function update_status( int $id, string $status ): bool {
		global $wpdb;
		$result = $wpdb->update( self::table(), array( 'status' => $status ), array( 'id' => $id ), array( '%s' ), array( '%d' ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
		return false !== $result;
	}

	/**
	 * Number of saved enquiries (used by checks and, later, the admin dashboard).
	 */
	public static function count(): int {
		global $wpdb;
		$table = self::table();
		return (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM %i', $table ) ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery
	}
}
