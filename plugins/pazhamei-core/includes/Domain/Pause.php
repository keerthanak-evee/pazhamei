<?php
/**
 * Pause: one pause range (a row in wp_pz_pauses).
 *
 * A dated pause covers start_date to end_date, both included. An open-ended
 * pause has no end_date and lasts until someone resumes (rule 7). The pause
 * blocks deliveries for the customer; subscription_id records which pack was
 * current when it was made.
 *
 * Status: active (still in force or upcoming), ended (resumed or past its
 * end date), cancelled (withdrawn before it began).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A pause range.
 */
final class Pause {

	public const ACTIVE    = 'active';
	public const ENDED     = 'ended';
	public const CANCELLED = 'cancelled';

	public int $id = 0;
	public int $subscription_id = 0;
	public int $customer_id = 0;
	public string $start_date = '';

	/** Last paused day ('Y-m-d'), or null for an open-ended pause. */
	public ?string $end_date = null;

	public string $status = self::ACTIVE;
	public int $created_by = 0;
	public string $created_at = '';

	/**
	 * Is this pause open-ended and still in force?
	 */
	public function is_open_ended(): bool {
		return null === $this->end_date && self::ACTIVE === $this->status;
	}

	/**
	 * Does this pause cover a date?
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function covers( string $date ): bool {
		if ( self::CANCELLED === $this->status || $date < $this->start_date ) {
			return false;
		}
		return null === $this->end_date ? self::ACTIVE === $this->status : $date <= $this->end_date;
	}

	/**
	 * Values for the change log and the REST API.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return get_object_vars( $this );
	}
}
