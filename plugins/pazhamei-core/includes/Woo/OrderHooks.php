<?php
/**
 * OrderHooks: WooCommerce order events → the subscription engine.
 *
 *   Payment complete, Processing, Completed  →  activate each plan line
 *   Cancelled                                →  cancel its packs (status cancelled)
 *   Refunded (full)                          →  cancel its packs (status refunded)
 *
 * Activation is idempotent (one pack per order line), so the same order
 * passing through several of these statuses is fine. Partial refunds don't
 * cancel anything: refunds are handled offline (owner decision 6).
 * HPOS-safe: only wc_get_order() and order methods.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Services\Engine;
use Pazhamei\Services\PackRequest;
use Throwable;
use WC_Order;
use WC_Order_Item_Product;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Order status hooks.
 */
final class OrderHooks {

	/**
	 * Hook into order status changes.
	 */
	public static function register(): void {
		add_action( 'woocommerce_payment_complete', array( self::class, 'activate' ) );
		add_action( 'woocommerce_order_status_processing', array( self::class, 'activate' ) );
		add_action( 'woocommerce_order_status_completed', array( self::class, 'activate' ) );
		add_action( 'woocommerce_order_status_cancelled', array( self::class, 'cancelled' ) );
		add_action( 'woocommerce_order_status_refunded', array( self::class, 'refunded' ) );
	}

	/**
	 * Create a pack for every plan line of a paid order.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function activate( $order_id ): void {
		$order = wc_get_order( $order_id );
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$engine = Engine::live();

		foreach ( self::requests( $order ) as $request ) {
			try {
				$result = $engine->activation()->activate( $request, Actor::system() );
				if ( $result['created'] ) {
					$pack = $result['pack'];
					$order->add_order_note(
						sprintf(
							'Pazhamei pack #%d activated (%s, %s): %s to %s, status %s.',
							$pack->id,
							$pack->plan_code,
							$pack->meal_type,
							Dates::display( $pack->start_date ),
							Dates::display( $pack->end_date ),
							$pack->status
						)
					);
				}
			} catch ( Throwable $error ) {
				$order->add_order_note( 'Pazhamei: could not activate the pack for this order line: ' . $error->getMessage() );
				error_log( 'Pazhamei activation failed for order ' . $order->get_id() . ': ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
		}
	}

	/**
	 * Order cancelled: cancel its packs.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function cancelled( $order_id ): void {
		self::cancel( (int) $order_id, PackStatus::CANCELLED );
	}

	/**
	 * Order fully refunded: cancel its packs.
	 *
	 * @param int $order_id Order ID.
	 */
	public static function refunded( $order_id ): void {
		self::cancel( (int) $order_id, PackStatus::REFUNDED );
	}

	/**
	 * Cancel every pack bought with an order.
	 *
	 * @param int    $order_id Order ID.
	 * @param string $status   cancelled or refunded.
	 */
	private static function cancel( int $order_id, string $status ): void {
		$order  = wc_get_order( $order_id );
		$engine = Engine::live();

		foreach ( $engine->stores->subscriptions->for_order( $order_id ) as $pack ) {
			try {
				if ( $engine->packs()->cancel( $pack->id, $status, Actor::system(), 'WooCommerce order #' . $order_id . ' ' . $status ) && $order instanceof WC_Order ) {
					$order->add_order_note( sprintf( 'Pazhamei pack #%d %s: unlocked days removed. Refund (if any) is handled offline.', $pack->id, $status ) );
				}
			} catch ( Throwable $error ) {
				if ( $order instanceof WC_Order ) {
					$order->add_order_note( 'Pazhamei: could not cancel pack #' . $pack->id . ': ' . $error->getMessage() );
				}
			}
		}
	}

	/**
	 * Read the plan lines of an order.
	 *
	 * @param WC_Order $order Order.
	 * @return PackRequest[]
	 */
	public static function requests( WC_Order $order ): array {
		$requests = array();
		$shipping = '' !== $order->get_shipping_address_1();

		foreach ( $order->get_items() as $item_id => $item ) {
			if ( ! $item instanceof WC_Order_Item_Product ) {
				continue;
			}
			$product = $item->get_product();
			$code    = $product ? PlanProducts::plan_code( $product ) : '';
			if ( ! $product || '' === $code ) {
				continue; // Not a plan.
			}

			$start = CheckoutFields::start_date( $order );

			$request                  = new PackRequest();
			$request->customer_id     = (int) $order->get_customer_id();
			$request->order_id        = (int) $order->get_id();
			$request->order_item_id   = (int) $item_id;
			$request->product_id      = (int) $product->get_id();
			$request->plan_code       = $code;
			$request->meal_type       = PlanProducts::meal_type( $product );
			$request->requested_start = Dates::is_valid( $start ) ? $start : null;
			$request->mobile          = $order->get_billing_phone();
			$request->pincode         = $shipping ? $order->get_shipping_postcode() : $order->get_billing_postcode();
			$request->address_line    = $shipping ? $order->get_shipping_address_1() : $order->get_billing_address_1();
			$requests[]               = $request;
		}
		return $requests;
	}
}
