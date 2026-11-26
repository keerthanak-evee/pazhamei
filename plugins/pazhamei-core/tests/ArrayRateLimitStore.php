<?php
/**
 * ArrayRateLimitStore: an in-memory RateLimitStore for tests (no database).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\RateLimitStore;

/**
 * Keeps attempt times in a PHP array and remembers the last expiry it was given.
 */
final class ArrayRateLimitStore implements RateLimitStore {

	/** @var array<string, int[]> */
	public array $data = array();

	/** @var array<string, int> */
	public array $ttl = array();

	public function get( string $key ): array {
		return $this->data[ $key ] ?? array();
	}

	public function set( string $key, array $times, int $seconds ): void {
		$this->data[ $key ] = $times;
		$this->ttl[ $key ]  = $seconds;
	}
}
