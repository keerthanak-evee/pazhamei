<?php
/**
 * StorageError: a database statement failed.
 *
 * Repositories throw it instead of returning false, so a failed write inside
 * a transaction always rolls the whole change back.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A failed database statement.
 */
class StorageError extends RuntimeException {
}
