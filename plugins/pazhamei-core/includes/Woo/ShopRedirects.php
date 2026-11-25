<?php
/**
 * ShopRedirects: keeps customers on Pazhamei's own pages instead of WooCommerce's defaults.
 *
 * - /my-account/...           -> /sign-in/... (the sign-in page is WooCommerce's My Account page)
 * - Shop, product, category   -> /plans/ (plans are sold from our own pages, not the shop)
 * - Cart                      -> /checkout/ (or /plans/ when the cart is empty, because
 *                                WooCommerce sends an empty checkout back to the cart)
 *
 * The order-received page after payment is left alone (see OrderReceived).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WooCommerce page redirects.
 */
final class ShopRedirects {

	/** Old WooCommerce account slug that now redirects to /sign-in/. */
	private const OLD_ACCOUNT_SLUG = 'my-account';

	/**
	 * Add the hooks. Priority 1 runs before WordPress's own canonical redirects.
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'redirect' ), 1 );
	}

	/**
	 * Send the request elsewhere if it is one of the pages above.
	 */
	public static function redirect(): void {
		// Old account links moved for good: 301 (permanent).
		$old_account = self::old_account_target();
		if ( '' !== $old_account ) {
			wp_safe_redirect( $old_account, 301 );
			exit;
		}

		if ( ! function_exists( 'is_woocommerce' ) ) {
			return;
		}

		// The rest depend on the cart, so use 302 (temporary) and let browsers ask again next time.
		$target = '';
		if ( is_shop() || is_product() || is_product_taxonomy() ) {
			$target = home_url( '/plans/' );
		} elseif ( is_cart() ) {
			$target = ( WC()->cart && ! WC()->cart->is_empty() ) ? wc_get_checkout_url() : home_url( '/plans/' );
		}

		if ( '' !== $target ) {
			wp_safe_redirect( $target );
			exit;
		}
	}

	/**
	 * If the URL starts with /my-account/, the same path under /sign-in/ (keeps endpoints like /orders/).
	 */
	private static function old_account_target(): string {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$path    = trim( (string) wp_parse_url( $request, PHP_URL_PATH ), '/' );
		$parts   = explode( '/', $path );

		if ( self::OLD_ACCOUNT_SLUG !== $parts[0] ) {
			return '';
		}

		$rest  = implode( '/', array_slice( $parts, 1 ) );
		$query = (string) wp_parse_url( $request, PHP_URL_QUERY );
		$url   = home_url( '/sign-in/' . ( '' !== $rest ? $rest . '/' : '' ) );

		return '' !== $query ? $url . '?' . $query : $url;
	}
}
