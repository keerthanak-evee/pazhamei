<?php
/**
 * PortalState: the first data the customer portal blocks show on page load.
 *
 * It is exactly what the matching REST route returns (so the page and later
 * refreshes agree), plus the REST address and a fresh "wp_rest" nonce for
 * the block's own requests. Built once per page, however many blocks use it.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Services\DashboardView;
use Pazhamei\Services\Engine;
use Pazhamei\Woo\PendingPayments;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Initial Interactivity API state for the portal blocks.
 */
final class PortalState {

	/** Dashboard state for this request, once built. */
	private static ?array $dashboard = null;

	/**
	 * Is the visitor a signed-in customer (or staff) who can use the portal blocks?
	 */
	public static function is_customer(): bool {
		return is_user_logged_in() && current_user_can( 'pz_customer' );
	}

	/**
	 * Address and nonce for the blocks' REST calls.
	 *
	 * @return array{restUrl: string, nonce: string}
	 */
	public static function api(): array {
		return array(
			'restUrl' => esc_url_raw( rest_url( Guard::NAMESPACE . '/' ) ),
			'nonce'   => wp_create_nonce( 'wp_rest' ),
		);
	}

	/**
	 * The dashboard data (GET /me/dashboard) for the signed-in customer.
	 *
	 * @return array<string, mixed>
	 */
	public static function dashboard(): array {
		if ( null === self::$dashboard ) {
			$customer_id     = get_current_user_id();
			self::$dashboard = DashboardView::build( Engine::live(), $customer_id, PendingPayments::confirming( $customer_id ) ) + self::api();
		}
		return self::$dashboard;
	}
}
