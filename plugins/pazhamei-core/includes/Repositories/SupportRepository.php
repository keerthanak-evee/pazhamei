<?php
/**
 * SupportRepository: support tickets (wp_pz_support_tickets) and the
 * messages in each ticket (wp_pz_support_messages).
 *
 * A ticket is a thread: the customer's first message, then replies.
 * Customers raise tickets in Phase 4; admin replies come with /manage
 * (Phase 8). See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support tickets in MySQL.
 */
final class SupportRepository {

	/**
	 * CREATE TABLE statements for dbDelta.
	 *
	 * @return string[]
	 */
	public static function schemas(): array {
		$tickets  = Db::table( 'support_tickets' );
		$messages = Db::table( 'support_messages' );
		$options  = Db::table_options();

		return array(
			"CREATE TABLE {$tickets} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  topic varchar(20) NOT NULL,
  about_date date DEFAULT NULL,
  status varchar(20) NOT NULL DEFAULT 'open',
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY customer_created (customer_id,created_at),
  KEY status_created (status,created_at)
) {$options};",
			"CREATE TABLE {$messages} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ticket_id bigint(20) unsigned NOT NULL,
  author_id bigint(20) unsigned NOT NULL,
  author_role varchar(10) NOT NULL DEFAULT 'customer',
  body text NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY ticket_id (ticket_id)
) {$options};",
		);
	}

	/**
	 * Save a new ticket with its first message (one transaction).
	 *
	 * @param int         $customer_id Customer.
	 * @param string      $topic       Topic value.
	 * @param string|null $about_date  'Y-m-d' or null.
	 * @param string      $message     First message.
	 * @param string      $now         'Y-m-d H:i:s' IST.
	 * @return int Ticket ID.
	 */
	public static function create( int $customer_id, string $topic, ?string $about_date, string $message, string $now ): int {
		return (int) ( new WpdbTransaction() )->run(
			static function () use ( $customer_id, $topic, $about_date, $message, $now ) {
				$ticket_id = Db::insert(
					Db::table( 'support_tickets' ),
					array(
						'customer_id' => $customer_id,
						'topic'       => $topic,
						'about_date'  => $about_date,
						'status'      => 'open',
						'created_at'  => $now,
						'updated_at'  => $now,
					)
				);
				Db::insert(
					Db::table( 'support_messages' ),
					array(
						'ticket_id'   => $ticket_id,
						'author_id'   => $customer_id,
						'author_role' => 'customer',
						'body'        => $message,
						'created_at'  => $now,
					)
				);
				return $ticket_id;
			}
		);
	}

	/**
	 * The customer's tickets, newest first, each with its messages (oldest first).
	 *
	 * @param int $customer_id Customer.
	 * @return array<int, array<string, mixed>>
	 */
	public static function for_customer( int $customer_id ): array {
		$tickets = Db::results( Db::wpdb()->prepare( 'SELECT * FROM %i WHERE customer_id = %d ORDER BY created_at DESC, id DESC LIMIT 50', Db::table( 'support_tickets' ), $customer_id ) );
		if ( ! $tickets ) {
			return array();
		}

		$ids      = array_map( static fn( $t ) => (int) $t['id'], $tickets );
		$messages = Db::results(
			Db::wpdb()->prepare(
				'SELECT * FROM %i WHERE ticket_id IN (' . Db::placeholders( $ids, '%d' ) . ') ORDER BY created_at, id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built by Db::placeholders().
				array_merge( array( Db::table( 'support_messages' ) ), $ids )
			)
		);

		$by_ticket = array();
		foreach ( $messages as $message ) {
			$by_ticket[ (int) $message['ticket_id'] ][] = $message;
		}
		foreach ( $tickets as &$ticket ) {
			$ticket['messages'] = $by_ticket[ (int) $ticket['id'] ] ?? array();
		}
		return $tickets;
	}

	/**
	 * How many tickets the customer raised since a time (a simple flood guard).
	 *
	 * @param int    $customer_id Customer.
	 * @param string $since       'Y-m-d H:i:s'.
	 */
	public static function count_since( int $customer_id, string $since ): int {
		return (int) Db::value( Db::wpdb()->prepare( 'SELECT COUNT(*) FROM %i WHERE customer_id = %d AND created_at >= %s', Db::table( 'support_tickets' ), $customer_id, $since ) );
	}
}
