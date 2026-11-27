<?php
/**
 * DuplicateKey: an insert or update broke a UNIQUE key, e.g. a second
 * schedule row for the same customer and date (rule 5), or a second pack
 * for the same order item (a repeated payment webhook).
 *
 * The MySQL repositories and the in-memory test stores both throw this, with
 * MySQL's own message format, so services handle the two the same way.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A UNIQUE key violation.
 */
final class DuplicateKey extends StorageError {
}
