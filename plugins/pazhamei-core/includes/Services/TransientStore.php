<?php
/**
 * TransientStore: keeps rate-limit counts in WordPress transients.
 *
 * Transients expire on their own, so old counts clean themselves up.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * RateLimitStore backed by transients.
 */
final class TransientStore implements RateLimitStore {

	/**
	 * Read the saved attempt times.
	 *
	 * @param string $key Storage key.
	 * @return int[]
	 */
	public function get( string $key ): array {
		$value = get_transient( $key );
		return is_array( $value ) ? array_map( 'intval', $value ) : array();
	}

	/**
	 * Save the attempt times.
	 *
	 * @param string $key     Storage key.
	 * @param int[]  $times   Unix timestamps.
	 * @param int    $seconds How long to keep it.
	 */
	public function set( string $key, array $times, int $seconds ): void {
		set_transient( $key, array_values( $times ), $seconds );
	}
}
