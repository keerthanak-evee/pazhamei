<?php
/**
 * Pack: one purchased plan (a row in wp_pz_subscriptions).
 *
 * A plain data object. Repositories fill it from the database; services
 * read it and ask repositories to save changes.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * A pack of meals.
 */
final class Pack {

	public int $id = 0;
	public int $customer_id = 0;
	public int $order_id = 0;
	public int $order_item_id = 0;
	public int $product_id = 0;

	/** TRIAL, D6 or D26. */
	public string $plan_code = '';

	/** VEG, NONVEG or MIXED. */
	public string $meal_type = '';

	/** Meals per day. Always 1 for now (open decision 5), not exposed anywhere. */
	public int $quantity = 1;

	/** Meals in the pack, plus any credited meals. */
	public int $total_meals = 0;

	/** Meals delivered or failed. */
	public int $consumed_meals = 0;

	/** Start date chosen at checkout ('Y-m-d'). */
	public ?string $requested_start = null;

	/** First meal date; null while the pack has no dates (e.g. queued behind an open-ended pause). */
	public ?string $start_date = null;

	/** Last meal date (rule 8); recalculated only by reflow(). */
	public ?string $end_date = null;

	/** One of PackStatus. */
	public string $status = PackStatus::ACTIVE;

	/** The pack this one renews, if any. */
	public ?int $renewed_from_id = null;

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
