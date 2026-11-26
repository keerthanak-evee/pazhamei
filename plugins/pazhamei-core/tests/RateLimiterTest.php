<?php
/**
 * Tests for RateLimiter: 5 enquiries per 15 minutes per visitor.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\RateLimiter;
use PHPUnit\Framework\TestCase;

/**
 * Rolling-window rate limit.
 */
final class RateLimiterTest extends TestCase {

	private const KEY   = 'pz_rl_enquiry_abc';
	private const START = 1_800_000_000;

	private ArrayRateLimitStore $store;
	private RateLimiter $limiter;

	protected function setUp(): void {
		$this->store   = new ArrayRateLimitStore();
		$this->limiter = new RateLimiter( $this->store );
	}

	public function test_enquiry_limits_are_five_per_fifteen_minutes(): void {
		$this->assertSame( 5, RateLimiter::ENQUIRY_LIMIT );
		$this->assertSame( 900, RateLimiter::ENQUIRY_WINDOW );
	}

	public function test_sixth_attempt_within_fifteen_minutes_is_refused(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->assertTrue( $this->limiter->attempt( self::KEY, self::START + $i * 60 ), 'Attempt ' . ( $i + 1 ) );
		}

		$this->assertFalse( $this->limiter->attempt( self::KEY, self::START + 14 * 60 ) );
	}

	public function test_refused_attempts_are_not_counted(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->limiter->attempt( self::KEY, self::START );
		}
		$this->limiter->attempt( self::KEY, self::START + 60 );
		$this->limiter->attempt( self::KEY, self::START + 120 );

		$this->assertCount( 5, $this->store->data[ self::KEY ] );
	}

	public function test_window_rolls_so_old_attempts_expire(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->limiter->attempt( self::KEY, self::START + $i * 60 ); // Minutes 0 to 4.
		}

		// At minute 15 the first attempt (minute 0) is exactly 15 minutes old and drops out.
		$this->assertTrue( $this->limiter->attempt( self::KEY, self::START + 15 * 60 ) );
		// The other four are still inside the window, so we're full again.
		$this->assertFalse( $this->limiter->attempt( self::KEY, self::START + 15 * 60 + 1 ) );
	}

	public function test_visitors_are_counted_separately(): void {
		for ( $i = 0; $i < 5; $i++ ) {
			$this->limiter->attempt( self::KEY, self::START );
		}

		$this->assertFalse( $this->limiter->attempt( self::KEY, self::START ) );
		$this->assertTrue( $this->limiter->attempt( 'pz_rl_enquiry_other', self::START ) );
	}

	public function test_counts_are_stored_for_the_window_length(): void {
		$this->limiter->attempt( self::KEY, self::START );

		$this->assertSame( 900, $this->store->ttl[ self::KEY ] );
	}

	public function test_custom_limit_and_window(): void {
		$limiter = new RateLimiter( $this->store, 2, 60 );

		$this->assertTrue( $limiter->attempt( 'k', self::START ) );
		$this->assertTrue( $limiter->attempt( 'k', self::START + 1 ) );
		$this->assertFalse( $limiter->attempt( 'k', self::START + 2 ) );
		$this->assertTrue( $limiter->attempt( 'k', self::START + 61 ) );
	}
}
