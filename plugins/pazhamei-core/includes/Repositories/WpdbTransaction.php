<?php
/**
 * WpdbTransaction: MySQL transactions through $wpdb.
 *
 * START TRANSACTION ... COMMIT around the work, ROLLBACK if anything throws.
 * Nested calls join the outer transaction (MySQL has no real nesting).
 * Needs InnoDB tables; the installer makes sure of that.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\Transaction;
use Throwable;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transaction runner for the live database.
 */
final class WpdbTransaction implements Transaction {

	/** How many run() calls are open right now. */
	private static int $depth = 0;

	/**
	 * Run the work inside one transaction.
	 *
	 * @param callable $work Work to do.
	 * @return mixed
	 * @throws Throwable Whatever the work threw, after rolling back.
	 */
	public function run( callable $work ) {
		if ( self::$depth > 0 ) {
			++self::$depth;
			try {
				return $work();
			} finally {
				--self::$depth;
			}
		}

		Db::query( 'START TRANSACTION' );
		self::$depth = 1;

		try {
			$result = $work();
			Db::query( 'COMMIT' );
			return $result;
		} catch ( Throwable $error ) {
			Db::wpdb()->query( 'ROLLBACK' );
			throw $error;
		} finally {
			self::$depth = 0;
		}
	}
}
