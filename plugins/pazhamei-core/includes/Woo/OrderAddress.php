<?php
/**
 * OrderAddress: reads a pack's delivery address from its WooCommerce order,
 * for the daily order snapshot made by the 20:00 lock job.
 *
 * Uses the shipping address when the order has one, otherwise the billing
 * address (plans are virtual products, so checkout normally only asks for
 * billing). HPOS-safe: only wc_get_order() and order getters.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Domain\Pack;
use Pazhamei\Services\CustomerInfo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Delivery address from the order.
 */
final class OrderAddress implements CustomerInfo {

	/** {@inheritDoc} */
	public function delivery_address( Pack $pack ): array {
		$order = wc_get_order( $pack->order_id );
		if ( ! $order instanceof \WC_Order ) {
			return array();
		}

		$shipping = '' !== $order->get_shipping_address_1();

		return array(
			'name'      => $shipping ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name(),
			'phone'     => $order->get_billing_phone(),
			'address_1' => $shipping ? $order->get_shipping_address_1() : $order->get_billing_address_1(),
			'address_2' => $shipping ? $order->get_shipping_address_2() : $order->get_billing_address_2(),
			'city'      => $shipping ? $order->get_shipping_city() : $order->get_billing_city(),
			'postcode'  => $shipping ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
		);
	}
}
