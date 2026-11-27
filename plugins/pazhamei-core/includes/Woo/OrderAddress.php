<?php
/**
 * OrderAddress: a pack's delivery address, for the daily order snapshot
 * made by the 20:00 lock job.
 *
 * The customer's saved delivery address comes first: the WooCommerce
 * billing address on their account, which the Profile page edits and every
 * checkout updates. Because the lock job copies it at 20:00 for the next
 * day, a change applies from the next unlocked day (owner rule, Phase 4).
 * If the account has no address yet, the pack's order is used (shipping
 * address when it has one, otherwise billing).
 *
 * HPOS-safe: only wc_get_order(), order getters and WC_Customer.
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
 * Delivery address from the customer's account, else from the order.
 */
final class OrderAddress implements CustomerInfo {

	/** {@inheritDoc} */
	public function delivery_address( Pack $pack ): array {
		$customer = $pack->customer_id > 0 ? new \WC_Customer( $pack->customer_id ) : null;
		if ( $customer && '' !== $customer->get_billing_address_1() ) {
			return array(
				'name'      => trim( $customer->get_billing_first_name() . ' ' . $customer->get_billing_last_name() ),
				'phone'     => $customer->get_billing_phone(),
				'address_1' => $customer->get_billing_address_1(),
				'address_2' => $customer->get_billing_address_2(),
				'city'      => $customer->get_billing_city(),
				'postcode'  => $customer->get_billing_postcode(),
			);
		}

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
