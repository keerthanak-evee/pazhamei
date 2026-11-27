<?php
/**
 * WooAccountSettings: the WooCommerce account and checkout settings Pazhamei needs.
 *
 * Run by `wp pz install-pages` (safe to rerun):
 * - Customers can register on the My Account page (/sign-in).
 * - Customers choose their own password (no emailed password).
 * - Usernames are made from the email, so the form doesn't ask for one.
 * - Guest checkout is off: only signed-in customers can buy a plan.
 * - The terms checkbox at checkout links to /terms.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce account options.
 */
final class WooAccountSettings {

	/** WooCommerce option => the value we need. */
	public const OPTIONS = array(
		'woocommerce_enable_myaccount_registration'  => 'yes',
		'woocommerce_registration_generate_password' => 'no',
		'woocommerce_registration_generate_username' => 'yes',
		'woocommerce_enable_guest_checkout'          => 'no',
		'woocommerce_enable_checkout_login_reminder' => 'yes',
		'woocommerce_enable_signup_and_login_from_checkout' => 'no',
	);

	/**
	 * Apply the options and point the terms page at /terms.
	 *
	 * @return array<int, array{type: string, text: string}> Report lines.
	 */
	public static function apply(): array {
		$report = array();

		foreach ( self::OPTIONS as $option => $value ) {
			if ( get_option( $option ) !== $value ) {
				update_option( $option, $value );
				$report[] = array(
					'type' => 'updated',
					'text' => "WooCommerce setting {$option} = {$value}",
				);
			}
		}

		$terms = get_page_by_path( 'terms', OBJECT, 'page' );
		if ( $terms && (int) get_option( 'woocommerce_terms_page_id' ) !== (int) $terms->ID ) {
			update_option( 'woocommerce_terms_page_id', (int) $terms->ID );
			$report[] = array(
				'type' => 'updated',
				'text' => "WooCommerce terms page set to /terms/ (ID {$terms->ID})",
			);
		}

		return $report;
	}
}
