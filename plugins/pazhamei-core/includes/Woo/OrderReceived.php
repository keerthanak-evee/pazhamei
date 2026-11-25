<?php
/**
 * OrderReceived: adds a "Go to my dashboard" button to WooCommerce's thank-you page.
 *
 * We keep WooCommerce's own order-received page (it shows the order summary)
 * and do not redirect automatically. The button is added through the
 * `woocommerce_thankyou_order_received_text` filter, which both the block
 * checkout and the classic checkout use.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Thank-you page button.
 */
final class OrderReceived {

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		// Priority 99: Razorpay replaces this message at priority 20, so we add the button after it.
		add_filter( 'woocommerce_thankyou_order_received_text', array( self::class, 'add_dashboard_button' ), 99, 2 );
	}

	/**
	 * Append the button to the thank-you message, except for failed orders.
	 *
	 * @param string         $text  Message WooCommerce will show.
	 * @param \WC_Order|null $order The order, when WooCommerce passes it.
	 */
	public static function add_dashboard_button( $text, $order = null ): string {
		if ( $order instanceof \WC_Order && $order->has_status( 'failed' ) ) {
			return (string) $text;
		}

		$button = sprintf(
			' <span class="wp-block-button pz-dashboard-link" style="display:inline-block;margin-top:0.75rem"><a class="wp-block-button__link wp-element-button" href="%s">%s</a></span>',
			esc_url( home_url( '/account/dashboard/' ) ),
			esc_html__( 'Go to my dashboard', 'pazhamei-core' )
		);

		return (string) $text . $button;
	}
}
