<?php
/**
 * RateLimitStore: where the rate limiter keeps its counts.
 *
 * On the site this is WordPress transients (TransientStore). Tests use a
 * simple in-memory version, so the limiter can be tested without a database.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage used by RateLimiter.
 */
interface RateLimitStore {

	/**
	 * Read the saved list of attempt times for a key.
	 *
	 * @param string $key Storage key.
	 * @return int[] Unix timestamps, or an empty array.
	 */
	public function get( string $key ): array;

	/**
	 * Save the list of attempt times for a key.
	 *
	 * @param string $key     Storage key.
	 * @param int[]  $times   Unix timestamps.
	 * @param int    $seconds How long to keep it.
	 */
	public function set( string $key, array $times, int $seconds ): void;
}
