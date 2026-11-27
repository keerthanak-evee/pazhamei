<?php
/**
 * PackRequest: what ActivationService needs to create a pack, read from
 * one line of a paid WooCommerce order (see Woo\OrderPacks).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Input for activation.
 */
final class PackRequest {

	public int $customer_id = 0;
	public int $order_id = 0;
	public int $order_item_id = 0;
	public int $product_id = 0;

	/** TRIAL, D6 or D26. */
	public string $plan_code = '';

	/** VEG, NONVEG or MIXED. */
	public string $meal_type = '';

	/** Start date chosen at checkout ('Y-m-d'), or null. */
	public ?string $requested_start = null;

	/** Billing phone, any format. */
	public string $mobile = '';

	/** Delivery pincode. */
	public string $pincode = '';

	/** First line of the delivery address (house or flat number). */
	public string $address_line = '';
}
