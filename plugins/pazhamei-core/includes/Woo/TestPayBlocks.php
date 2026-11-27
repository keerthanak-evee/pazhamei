<?php
/**
 * TestPayBlocks: lets the block checkout show "Pazhamei Test Pay" (local only).
 *
 * The block checkout needs a small script that registers the payment
 * method on the page (assets/js/test-pay-blocks.js). Loaded only through
 * TestPay::register(), which checks the environment.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Automattic\WooCommerce\Blocks\Payments\Integrations\AbstractPaymentMethodType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Block checkout integration for Test Pay.
 */
final class TestPayBlocks extends AbstractPaymentMethodType {

	/** Same name as the gateway ID. */
	protected $name = TestPay::ID;

	/**
	 * Nothing to load: the gateway has no settings.
	 */
	public function initialize(): void {}

	/**
	 * Active on local only.
	 */
	public function is_active(): bool {
		return TestPay::allowed( wp_get_environment_type() );
	}

	/**
	 * The script that registers the method in the block checkout.
	 *
	 * @return string[]
	 */
	public function get_payment_method_script_handles(): array {
		wp_register_script(
			'pz-test-pay-blocks',
			PZ_CORE_URL . 'assets/js/test-pay-blocks.js',
			array( 'wc-blocks-registry', 'wp-element' ),
			PZ_VERSION,
			true
		);
		return array( 'pz-test-pay-blocks' );
	}

	/**
	 * Data the script reads.
	 *
	 * @return array<string, mixed>
	 */
	public function get_payment_method_data(): array {
		return array(
			'title'       => 'Pazhamei Test Pay (local testing)',
			'description' => 'No money is taken. The order is marked as paid.',
		);
	}
}
