<?php
/**
 * HidePrices: visitors who are not signed in never see a price.
 *
 * Shop and product pages already redirect to /plans (ShopRedirects), but prices
 * can still leak through WooCommerce's price HTML (blocks, widgets, feeds) and
 * the product structured data that search engines read. This removes both for
 * visitors. Signed-in customers see prices (Phase 4).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Price hiding for visitors.
 */
final class HidePrices {

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_get_price_html', array( self::class, 'price_html' ), 100 );
		add_filter( 'woocommerce_structured_data_product', array( self::class, 'structured_data' ), 100 );
	}

	/**
	 * No price HTML for visitors.
	 *
	 * @param string $html Price HTML.
	 */
	public static function price_html( $html ) {
		return is_user_logged_in() ? $html : '';
	}

	/**
	 * Remove the price ("offers") from product structured data for visitors.
	 *
	 * @param array<string, mixed> $markup Structured data.
	 * @return array<string, mixed>
	 */
	public static function structured_data( $markup ) {
		if ( ! is_user_logged_in() && is_array( $markup ) ) {
			unset( $markup['offers'] );
		}
		return $markup;
	}
}
