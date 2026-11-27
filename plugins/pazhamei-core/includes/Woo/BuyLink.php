<?php
/**
 * BuyLink: the plan cards' Buy button. One plan per order.
 *
 * Buy links look like /checkout/?pz_buy=<variation ID>&_pznonce=<nonce>.
 * When one is opened by a signed-in customer:
 *   1. the nonce is checked (so a link on another site can't fill a cart),
 *   2. the variation must be a plan the customer may buy (the Trial only
 *      when trials are on and the customer is eligible, rule 10),
 *   3. the cart is emptied (one plan per order) and the plan is added,
 *   4. the customer lands on the checkout without the query string.
 * Problems go back to /account/plans with a message.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Services\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Buy button links and their handler.
 */
final class BuyLink {

	/** Query argument with the variation ID. */
	public const ARG = 'pz_buy';

	/** Query argument with the nonce. */
	private const NONCE_ARG = '_pznonce';

	/**
	 * Handle Buy links once WooCommerce's cart is ready.
	 */
	public static function register(): void {
		add_action( 'wp_loaded', array( self::class, 'handle' ), 25 );
	}

	/**
	 * The Buy link for a plan variation.
	 *
	 * @param int $variation_id Variation ID.
	 */
	public static function url( int $variation_id ): string {
		return add_query_arg(
			array(
				self::ARG       => $variation_id,
				self::NONCE_ARG => wp_create_nonce( self::ARG . '_' . $variation_id ),
			),
			wc_get_checkout_url()
		);
	}

	/**
	 * Why this customer can't have the trial right now, or null.
	 * Uses the mobile and address we already know for them.
	 *
	 * @param int $customer_id Customer.
	 */
	public static function trial_refusal( int $customer_id ): ?string {
		$customer = new \WC_Customer( $customer_id );
		$profile  = Engine::live()->stores->profiles->get( $customer_id );
		$mobile   = '' !== (string) ( $profile['mobile'] ?? '' ) ? (string) $profile['mobile'] : $customer->get_billing_phone();
		$pincode  = '' !== $customer->get_shipping_postcode() ? $customer->get_shipping_postcode() : $customer->get_billing_postcode();
		$address  = '' !== $customer->get_shipping_address_1() ? $customer->get_shipping_address_1() : $customer->get_billing_address_1();

		return Engine::live()->trials()->refusal( $customer_id, $mobile, '' !== $pincode ? $pincode : (string) ( $profile['pincode'] ?? '' ), $address );
	}

	/**
	 * Open a Buy link.
	 */
	public static function handle(): void {
		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- The nonce is checked below.
		if ( ! isset( $_GET[ self::ARG ] ) || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$variation_id = absint( $_GET[ self::ARG ] );
		$nonce        = isset( $_GET[ self::NONCE_ARG ] ) ? sanitize_text_field( wp_unslash( $_GET[ self::NONCE_ARG ] ) ) : '';
		// phpcs:enable

		if ( ! is_user_logged_in() ) {
			wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( home_url( '/account/plans/' ) ), home_url( '/sign-in/' ) ) );
			exit;
		}

		$problem = self::problem( $variation_id, $nonce );
		if ( null !== $problem ) {
			wc_add_notice( $problem, 'error' );
			wp_safe_redirect( home_url( '/account/plans/' ) );
			exit;
		}

		$variation = wc_get_product( $variation_id );
		WC()->cart->empty_cart();
		WC()->cart->add_to_cart( $variation->get_parent_id(), 1, $variation_id, $variation->get_variation_attributes() );
		wp_safe_redirect( wc_get_checkout_url() );
		exit;
	}

	/**
	 * What is wrong with this Buy link, or null.
	 *
	 * @param int    $variation_id Variation ID.
	 * @param string $nonce        Nonce from the link.
	 */
	private static function problem( int $variation_id, string $nonce ): ?string {
		if ( ! wp_verify_nonce( $nonce, self::ARG . '_' . $variation_id ) ) {
			return __( 'That link has expired. Please choose your plan again.', 'pazhamei-core' );
		}
		$variation = wc_get_product( $variation_id );
		if ( ! $variation || ! $variation->is_type( 'variation' ) || '' === PlanProducts::plan_code( $variation ) ) {
			return __( 'Please choose a plan below.', 'pazhamei-core' );
		}
		if ( 'TRIAL' === PlanProducts::plan_code( $variation ) ) {
			$refusal = self::trial_refusal( get_current_user_id() );
			if ( null !== $refusal ) {
				return $refusal;
			}
		}
		if ( ! $variation->is_purchasable() ) {
			return __( 'This plan can’t be bought right now.', 'pazhamei-core' );
		}
		return null;
	}
}
