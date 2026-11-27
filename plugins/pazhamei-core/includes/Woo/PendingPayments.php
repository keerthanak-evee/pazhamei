<?php
/**
 * PendingPayments: is a plan order still waiting to become a pack?
 *
 * The dashboard shows "Confirming your payment…" and checks again every few
 * seconds while this is true. A plan order counts as waiting when it is:
 * - "on-hold" (the gateway is still confirming), placed in the last 3 hours;
 * - "pending" (payment started; Razorpay confirms by webhook), placed in
 *   the last 20 minutes, so an abandoned payment doesn't keep the message up;
 * - paid ("processing" or "completed") in the last 3 hours, but its pack
 *   hasn't been created yet.
 *
 * HPOS-safe: wc_get_orders() only.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Services\Engine;
use WC_Order;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plan orders waiting for payment confirmation.
 */
final class PendingPayments {

	/** How long to wait for each status, in seconds. */
	private const WINDOWS = array(
		'pending'    => 1200,
		'on-hold'    => 10800,
		'processing' => 10800,
		'completed'  => 10800,
	);

	/**
	 * Is one of this customer's plan orders still waiting?
	 *
	 * @param int $customer_id Customer.
	 */
	public static function confirming( int $customer_id ): bool {
		if ( $customer_id <= 0 || ! function_exists( 'wc_get_orders' ) ) {
			return false;
		}
		$orders = wc_get_orders(
			array(
				'customer_id'  => $customer_id,
				'status'       => array_keys( self::WINDOWS ),
				'date_created' => '>' . ( time() - max( self::WINDOWS ) ),
				'limit'        => 10,
			)
		);
		$stores = Engine::live()->stores;

		foreach ( $orders as $order ) {
			if ( ! $order instanceof WC_Order || ! $order->get_date_created() ) {
				continue;
			}
			$age = time() - $order->get_date_created()->getTimestamp();
			if ( $age > self::WINDOWS[ $order->get_status() ] ) {
				continue;
			}
			foreach ( $order->get_items() as $item_id => $item ) {
				$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
				if ( ! $product || '' === PlanProducts::plan_code( $product ) ) {
					continue;
				}
				if ( null === $stores->subscriptions->find_by_order_item( (int) $item_id ) ) {
					return true; // A plan line with no pack yet.
				}
			}
		}
		return false;
	}
}
