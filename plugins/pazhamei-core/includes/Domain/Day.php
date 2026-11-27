<?php
/**
 * Day: one schedule row (one customer, one date) in wp_pz_schedule.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A customer's plan for one date.
 */
final class Day {

	public int $id = 0;
	public int $customer_id = 0;

	/** 'Y-m-d'. Unique per customer (rule 5). */
	public string $service_date = '';

	public int $subscription_id = 0;
	public string $meal_type = '';

	/** One of DayStatus. */
	public string $status = DayStatus::SCHEDULED;

	/** initial, reflow or admin. */
	public string $source = 'reflow';

	public string $created_at = '';
	public string $updated_at = '';

	/**
	 * Values for the change log and the REST API.
	 *
	 * @return array<string, mixed>
	 */
	public function to_array(): array {
		return get_object_vars( $this );
	}
}
