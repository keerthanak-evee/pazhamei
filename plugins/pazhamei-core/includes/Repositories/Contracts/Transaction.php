<?php
/**
 * Transaction: runs a piece of work all-or-nothing.
 *
 * Calls can be nested: only the outermost call starts and commits the
 * database transaction. If anything throws, everything is rolled back and
 * the exception is passed on.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * All-or-nothing work.
 */
interface Transaction {

	/**
	 * Run the callback inside a transaction and return its result.
	 *
	 * @param callable $work Work to do.
	 * @return mixed
	 */
	public function run( callable $work );
}
