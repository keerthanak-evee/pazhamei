<?php
/**
 * RateLimiter: allows at most N attempts per key in a rolling time window.
 *
 * Used by the enquiry form: 5 submissions per 15 minutes per visitor. The key
 * is a hash of the visitor's IP (see ClientIp), never the IP itself.
 *
 * "Rolling" means the window is always the last 15 minutes from now, not
 * fixed clock blocks, so a burst can't sneak through at a block boundary.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Rolling-window attempt counter.
 */
final class RateLimiter {

	/** Enquiry form limits. */
	public const ENQUIRY_LIMIT  = 5;
	public const ENQUIRY_WINDOW = 15 * 60;

	/**
	 * Set up a limiter.
	 *
	 * @param RateLimitStore $store   Where counts are kept.
	 * @param int            $limit   Attempts allowed in the window.
	 * @param int            $window  Window length in seconds.
	 */
	public function __construct(
		private RateLimitStore $store,
		private int $limit = self::ENQUIRY_LIMIT,
		private int $window = self::ENQUIRY_WINDOW
	) {}

	/**
	 * Record an attempt, if it is allowed.
	 *
	 * @param string $key Storage key for this visitor.
	 * @param int    $now Current Unix time.
	 * @return bool True if the attempt is allowed (and was counted); false if over the limit.
	 */
	public function attempt( string $key, int $now ): bool {
		$since  = $now - $this->window;
		$recent = array_values(
			array_filter(
				$this->store->get( $key ),
				static fn( int $time ): bool => $time > $since
			)
		);

		if ( count( $recent ) >= $this->limit ) {
			return false; // Refused attempts are not counted, so the window still ends on time.
		}

		$recent[] = $now;
		$this->store->set( $key, $recent, $this->window );

		return true;
	}
}
