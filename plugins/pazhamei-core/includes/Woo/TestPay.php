<?php
/**
 * TestPay: switches on "Pazhamei Test Pay", a fake payment method for local testing.
 *
 * It marks an order as paid (processing) straight away, so the whole buy
 * flow (checkout → pack activated → dashboard) can be tested in a browser
 * without Razorpay keys.
 *
 * It exists ONLY when wp_get_environment_type() is "local". On staging and
 * production register() does nothing, so the gateway is never loaded,
 * listed or usable there.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Environment;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local-only test gateway wiring.
 */
final class TestPay {

	/** Gateway ID used by WooCommerce. */
	public const ID = 'pz_test_pay';

	/**
	 * May Test Pay exist in this environment? Plain PHP, unit-tested.
	 *
	 * @param string $environment_type Value of wp_get_environment_type().
	 */
	public static function allowed( string $environment_type ): bool {
		return Environment::allows_dev_tools( $environment_type );
	}

	/**
	 * Add the gateway to both checkouts, on local only.
	 */
	public static function register(): void {
		if ( ! self::allowed( wp_get_environment_type() ) ) {
			return;
		}
		add_filter( 'woocommerce_payment_gateways', array( self::class, 'add_gateway' ) );
		add_action( 'woocommerce_blocks_payment_method_type_registration', array( self::class, 'add_block_support' ) );
	}

	/**
	 * Classic checkout and order screens.
	 *
	 * @param array<int, mixed> $gateways Gateway classes.
	 * @return array<int, mixed>
	 */
	public static function add_gateway( $gateways ) {
		$gateways[] = TestPayGateway::class;
		return $gateways;
	}

	/**
	 * Block checkout.
	 *
	 * @param object $registry WooCommerce's payment method registry.
	 */
	public static function add_block_support( $registry ): void {
		$registry->register( new TestPayBlocks() );
	}
}
