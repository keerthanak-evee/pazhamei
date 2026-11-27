<?php
/**
 * TestPayGateway: the "Pazhamei Test Pay" payment method itself (local only).
 *
 * Loaded only through TestPay::register(), which checks the environment.
 * process_payment() marks the order paid, the same as a successful
 * Razorpay payment, so OrderHooks activates the pack.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use WC_Payment_Gateway;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Fake gateway for local testing.
 */
final class TestPayGateway extends WC_Payment_Gateway {

	/**
	 * Set up the gateway. Enabled by default on local.
	 */
	public function __construct() {
		$this->id                 = TestPay::ID;
		$this->method_title       = 'Pazhamei Test Pay';
		$this->method_description = 'Local testing only: marks the order as paid straight away. Never available on staging or production.';
		$this->title              = 'Pazhamei Test Pay (local testing)';
		$this->description        = 'No money is taken. The order is marked as paid.';
		$this->has_fields         = false;
		$this->supports           = array( 'products' );
		$this->enabled            = 'yes';
	}

	/**
	 * Always available on local.
	 */
	public function is_available(): bool {
		return TestPay::allowed( wp_get_environment_type() );
	}

	/**
	 * Mark the order paid and go to the order-received page.
	 *
	 * @param int $order_id Order ID.
	 * @return array<string, string>
	 */
	public function process_payment( $order_id ): array {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! TestPay::allowed( wp_get_environment_type() ) ) {
			wc_add_notice( 'Test Pay is not available here.', 'error' );
			return array( 'result' => 'failure' );
		}

		$order->payment_complete( 'pztest-' . $order_id );
		$order->add_order_note( 'Paid with Pazhamei Test Pay (local testing; no money taken).' );

		if ( WC()->cart ) {
			WC()->cart->empty_cart();
		}

		return array(
			'result'   => 'success',
			'redirect' => $this->get_return_url( $order ),
		);
	}
}
