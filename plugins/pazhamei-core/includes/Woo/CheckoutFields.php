<?php
/**
 * CheckoutFields: the start-date field at checkout and the plan rules
 * (CheckoutCheck) before payment.
 *
 * Block checkout (what /checkout uses now): an additional checkout field
 * "pazhamei/start-date" in the order section. WooCommerce stores it as the
 * order meta "_wc_other/pazhamei/start-date". Rules run on
 * woocommerce_store_api_checkout_update_order_from_request and stop the
 * checkout with a message.
 *
 * Classic checkout (fallback, if Razorpay misbehaves with blocks): the same
 * field via woocommerce_checkout_fields, saved as "_pz_start_date".
 *
 * Phase 4 additions:
 * - A date picker on the start-date field (assets/js/start-date-picker.js)
 *   that greys out Sundays, holidays and locked days, from
 *   GET /pz/v1/checkout/start-dates. The server still checks the date.
 * - Two required checkboxes: allergens confirmed, and terms accepted.
 * - The "Your plan is paused" notice above the checkout.
 *
 * HPOS-safe.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Automattic\WooCommerce\StoreApi\Exceptions\RouteException;
use Pazhamei\Domain\Dates;
use Pazhamei\Services\CheckoutCheck;
use Pazhamei\Services\Engine;
use WC_Order;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Start date and checkout rules.
 */
final class CheckoutFields {

	/** Block checkout field ID. */
	public const BLOCK_FIELD = 'pazhamei/start-date';

	/** Order meta written by the block checkout. */
	public const BLOCK_META = '_wc_other/pazhamei/start-date';

	/** Classic checkout field name and our own order meta key. */
	public const CLASSIC_FIELD = 'pz_start_date';
	public const META          = '_pz_start_date';

	/** Required checkboxes (block checkout field IDs). */
	public const ALLERGENS_FIELD = 'pazhamei/allergens-ok';
	public const TERMS_FIELD     = 'pazhamei/terms';

	/** Checkbox labels (plain text: the checkout can't show links here). */
	public const ALLERGENS_LABEL = 'I have added my food allergies to my profile, or I have none.';
	public const TERMS_LABEL     = 'I accept the Terms & Conditions and the Refund & Cancellation Policy.';

	/**
	 * Hook into both checkouts.
	 */
	public static function register(): void {
		add_action( 'woocommerce_init', array( self::class, 'register_block_field' ) );
		add_action( 'wp_enqueue_scripts', array( self::class, 'enqueue_picker' ) );
		add_filter( 'render_block_woocommerce/checkout', array( self::class, 'paused_notice' ) );
		add_action( 'woocommerce_validate_additional_field', array( self::class, 'validate_block_field' ), 10, 3 );
		add_action( 'woocommerce_store_api_checkout_update_order_from_request', array( self::class, 'check_block_order' ), 10, 1 );

		add_filter( 'woocommerce_checkout_fields', array( self::class, 'classic_field' ) );
		add_action( 'woocommerce_after_checkout_validation', array( self::class, 'check_classic' ), 10, 2 );
		add_action( 'woocommerce_checkout_create_order', array( self::class, 'save_classic' ), 10, 2 );
	}

	/**
	 * The start date saved on an order ('' if none), whichever checkout was used.
	 *
	 * @param WC_Order $order Order.
	 */
	public static function start_date( WC_Order $order ): string {
		$date = (string) $order->get_meta( self::META );
		return '' !== $date ? $date : trim( (string) $order->get_meta( self::BLOCK_META ) );
	}

	/* ---------- Block checkout ---------- */

	/**
	 * Add the field to the block checkout.
	 */
	public static function register_block_field(): void {
		if ( ! function_exists( 'woocommerce_register_additional_checkout_field' ) ) {
			return;
		}
		woocommerce_register_additional_checkout_field(
			array(
				'id'         => self::BLOCK_FIELD,
				'label'      => __( 'Start date', 'pazhamei-core' ),
				'location'   => 'order',
				'type'       => 'text',
				'required'   => true,
				'attributes' => array(
					'autocomplete' => 'off',
					'pattern'      => '[0-9]{4}-[0-9]{2}-[0-9]{2}',
					'title'        => __( 'A date like 2026-11-30', 'pazhamei-core' ),
				),
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::ALLERGENS_FIELD,
				'label'    => self::ALLERGENS_LABEL,
				'location' => 'order',
				'type'     => 'checkbox',
				'required' => true,
			)
		);
		woocommerce_register_additional_checkout_field(
			array(
				'id'       => self::TERMS_FIELD,
				'label'    => self::TERMS_LABEL,
				'location' => 'order',
				'type'     => 'checkbox',
				'required' => true,
			)
		);
	}

	/**
	 * The start-date picker, on the checkout for signed-in customers.
	 */
	public static function enqueue_picker(): void {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || ! is_user_logged_in() || is_wc_endpoint_url() ) {
			return;
		}
		wp_enqueue_style( 'pz-start-date-picker', PZ_CORE_URL . 'assets/css/start-date-picker.css', array(), PZ_VERSION );
		wp_enqueue_script( 'pz-start-date-picker', PZ_CORE_URL . 'assets/js/start-date-picker.js', array(), PZ_VERSION, true );
		wp_localize_script(
			'pz-start-date-picker',
			'pzStartDates',
			array(
				'url'     => rest_url( 'pz/v1/checkout/start-dates' ),
				'nonce'   => wp_create_nonce( 'wp_rest' ),
				'inputId' => 'order-' . str_replace( '/', '-', self::BLOCK_FIELD ),
			)
		);
	}

	/**
	 * "Your plan is paused" above the block checkout, when it applies (decision 10).
	 *
	 * @param string $html Checkout block HTML.
	 */
	public static function paused_notice( $html ): string {
		if ( ! is_user_logged_in() ) {
			return (string) $html;
		}
		$notice = CheckoutCheck::paused_notice( Engine::live(), get_current_user_id() );
		if ( null === $notice ) {
			return (string) $html;
		}
		return '<div class="pz-checkout-notice" role="status"><p>' . esc_html( $notice ) . ' <a href="' . esc_url( home_url( '/account/dashboard/' ) ) . '">' . esc_html__( 'Go to my dashboard', 'pazhamei-core' ) . '</a></p></div>' . $html;
	}

	/**
	 * Basic format check while the customer types (the full rules run at order time).
	 *
	 * @param WP_Error $errors Errors to add to.
	 * @param string   $key    Field ID.
	 * @param mixed    $value  Value.
	 */
	public static function validate_block_field( WP_Error $errors, $key, $value ): void {
		if ( self::BLOCK_FIELD === $key && ! Dates::is_valid( trim( (string) $value ) ) ) {
			$errors->add( 'pz_start_date', __( 'Please enter the start date as YYYY-MM-DD, e.g. 2026-11-30.', 'pazhamei-core' ) );
		}
	}

	/**
	 * Full plan rules on the draft order, before payment.
	 *
	 * @param WC_Order $order Draft order with the customer's details.
	 * @throws RouteException When a rule fails; the customer sees the message.
	 */
	public static function check_block_order( $order ): void {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$codes = array();
		foreach ( $order->get_items() as $item ) {
			$product = $item instanceof \WC_Order_Item_Product ? $item->get_product() : null;
			$code    = $product ? PlanProducts::plan_code( $product ) : '';
			if ( '' !== $code ) {
				$codes[] = $code;
			}
		}

		$errors = CheckoutCheck::errors(
			Engine::live(),
			(int) $order->get_customer_id(),
			$codes,
			self::start_date( $order ),
			$order->get_billing_phone(),
			'' !== $order->get_shipping_postcode() ? $order->get_shipping_postcode() : $order->get_billing_postcode(),
			'' !== $order->get_shipping_address_1() ? $order->get_shipping_address_1() : $order->get_billing_address_1()
		);
		if ( $errors ) {
			throw new RouteException( 'pz_checkout_rules', esc_html( implode( ' ', $errors ) ), 400 );
		}
	}

	/* ---------- Classic checkout ---------- */

	/**
	 * Add the field to the classic checkout's order section.
	 *
	 * @param array<string, array<string, mixed>> $fields Checkout fields.
	 * @return array<string, array<string, mixed>>
	 */
	public static function classic_field( $fields ) {
		$fields['order'][ self::CLASSIC_FIELD ] = array(
			'type'     => 'date',
			'label'    => __( 'Start date', 'pazhamei-core' ),
			'required' => true,
			'priority' => 5,
		);
		// Classic checkout has its own terms checkbox (the terms page is set), so only allergens here.
		$fields['order']['pz_allergens_ok'] = array(
			'type'     => 'checkbox',
			'label'    => self::ALLERGENS_LABEL,
			'required' => true,
			'priority' => 6,
		);
		return $fields;
	}

	/**
	 * Plan rules for the classic checkout.
	 *
	 * @param array<string, mixed> $data   Posted checkout data.
	 * @param WP_Error             $errors Errors to add to.
	 */
	public static function check_classic( $data, $errors ): void {
		if ( ! $errors instanceof WP_Error || ! function_exists( 'WC' ) || ! WC()->cart ) {
			return;
		}
		$codes = array();
		foreach ( WC()->cart->get_cart() as $line ) {
			$code = isset( $line['data'] ) && $line['data'] instanceof \WC_Product ? PlanProducts::plan_code( $line['data'] ) : '';
			if ( '' !== $code ) {
				$codes[] = $code;
			}
		}

		$messages = CheckoutCheck::errors(
			Engine::live(),
			get_current_user_id(),
			$codes,
			sanitize_text_field( (string) ( $data[ self::CLASSIC_FIELD ] ?? '' ) ),
			(string) ( $data['billing_phone'] ?? '' ),
			(string) ( ! empty( $data['shipping_postcode'] ) ? $data['shipping_postcode'] : ( $data['billing_postcode'] ?? '' ) ),
			(string) ( ! empty( $data['shipping_address_1'] ) ? $data['shipping_address_1'] : ( $data['billing_address_1'] ?? '' ) )
		);
		foreach ( $messages as $message ) {
			$errors->add( 'pz_checkout_rules', esc_html( $message ) );
		}
	}

	/**
	 * Save the classic checkout's start date on the order.
	 *
	 * @param WC_Order             $order Order being created.
	 * @param array<string, mixed> $data  Posted checkout data.
	 */
	public static function save_classic( $order, $data ): void {
		$date = sanitize_text_field( (string) ( $data[ self::CLASSIC_FIELD ] ?? '' ) );
		if ( $order instanceof WC_Order && Dates::is_valid( $date ) ) {
			$order->update_meta_data( self::META, $date );
		}
	}
}
