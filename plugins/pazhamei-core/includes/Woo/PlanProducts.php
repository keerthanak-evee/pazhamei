<?php
/**
 * PlanProducts: the three plan products (Trial, 6-day, 26-day) in WooCommerce.
 *
 * Each is a variable product with Veg / Non-Veg / Mixed variations.
 *   Product meta:   _pz_plan_code (TRIAL, D6, D26), _pz_meals (1, 6, 26)
 *   Variation meta: _pz_meal_type (VEG, NONVEG, MIXED)
 *
 * Products are published with catalogue visibility "hidden" (never
 * private), so customers can buy them from /account/plans (Phase 4) while
 * they never appear in the shop or in search. Sold individually and
 * virtual (the delivery address comes from the billing address).
 *
 * Prices live only on the variations (regular price) and are edited in
 * wp-admin → Pazhamei → Plans & Prices (Admin\PlansPricesPage), including
 * the Trial row. The Trial can't be bought while trial_enabled is off
 * (rule 10).
 *
 * `wp pz install-products` creates missing products and never changes
 * existing ones.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Domain\Plans;
use Pazhamei\Settings;
use WC_Product;
use WC_Product_Attribute;
use WC_Product_Variable;
use WC_Product_Variation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plan products and their price rules.
 */
final class PlanProducts {

	/** Product attribute used for the meal type. */
	public const ATTRIBUTE = 'Meal type';

	/** Meal type code => attribute option shown to customers. */
	public const OPTIONS = array(
		'VEG'    => 'Veg',
		'NONVEG' => 'Non-Veg',
		'MIXED'  => 'Mixed',
	);

	/** [EDIT] Starting prices in rupees per pack, used only when the products are created. Edit them in Plans & Prices. */
	public const PLACEHOLDER_PRICES = array(
		'TRIAL' => array( 'VEG' => 0, 'NONVEG' => 0, 'MIXED' => 0 ),
		'D6'    => array( 'VEG' => 900, 'NONVEG' => 1080, 'MIXED' => 990 ),
		'D26'   => array( 'VEG' => 3640, 'NONVEG' => 4420, 'MIXED' => 4030 ),
	);

	/** Plan code per parent product ID, cached for this request. */
	private static array $codes = array();

	/**
	 * The trial switch.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_variation_is_purchasable', array( self::class, 'purchasable' ), 10, 2 );
		add_filter( 'woocommerce_is_purchasable', array( self::class, 'purchasable' ), 10, 2 );
	}

	/**
	 * The plan code of a product or variation ('' if it is not a plan).
	 *
	 * @param WC_Product $product Product or variation.
	 */
	public static function plan_code( WC_Product $product ): string {
		$parent_id = $product->get_parent_id() ? $product->get_parent_id() : $product->get_id();
		if ( ! isset( self::$codes[ $parent_id ] ) ) {
			$parent                    = $parent_id === $product->get_id() ? $product : wc_get_product( $parent_id );
			self::$codes[ $parent_id ] = $parent ? (string) $parent->get_meta( '_pz_plan_code' ) : '';
		}
		return self::$codes[ $parent_id ];
	}

	/**
	 * The meal type code of a variation ('' if unknown).
	 *
	 * @param WC_Product $product Variation.
	 */
	public static function meal_type( WC_Product $product ): string {
		$code = (string) $product->get_meta( '_pz_meal_type' );
		if ( '' !== $code ) {
			return $code;
		}
		$option = (string) $product->get_attribute( sanitize_title( self::ATTRIBUTE ) );
		$found  = array_search( $option, self::OPTIONS, true );
		return false === $found ? '' : (string) $found;
	}

	/**
	 * The Trial can't be bought while the trial switch is off.
	 *
	 * @param bool       $purchasable What WooCommerce decided.
	 * @param WC_Product $product     Product or variation.
	 */
	public static function purchasable( $purchasable, $product ): bool {
		if ( $product instanceof WC_Product && 'TRIAL' === self::plan_code( $product ) && ! Settings::get( 'trial_enabled' ) ) {
			return false;
		}
		return (bool) $purchasable;
	}

	/**
	 * The product ID for a plan code, or 0.
	 *
	 * @param string $code TRIAL, D6 or D26.
	 */
	public static function find( string $code ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'product',
				'post_status'    => 'any',
				'meta_key'       => '_pz_plan_code', // phpcs:ignore WordPress.DB.SlowDBQuery -- Three products, run by an install command.
				'meta_value'     => $code, // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * The variation of a plan for a meal type, or 0.
	 *
	 * @param string $code      Plan code.
	 * @param string $meal_type VEG, NONVEG or MIXED.
	 */
	public static function find_variation( string $code, string $meal_type ): int {
		$product = wc_get_product( self::find( $code ) );
		if ( ! $product instanceof WC_Product_Variable ) {
			return 0;
		}
		foreach ( $product->get_children() as $child_id ) {
			$child = wc_get_product( $child_id );
			if ( $child && self::meal_type( $child ) === $meal_type ) {
				return (int) $child_id;
			}
		}
		return 0;
	}

	/**
	 * Create any missing plan product. Existing products are never changed.
	 *
	 * @return array<int, array{plan: string, action: string, id: int}>
	 */
	public static function install(): array {
		$report = array();

		foreach ( Plans::all() as $slug => $plan ) {
			$existing = self::find( $plan['code'] );
			if ( $existing ) {
				$report[] = array(
					'plan'   => $plan['code'],
					'action' => 'exists',
					'id'     => $existing,
				);
				continue;
			}

			$report[] = array(
				'plan'   => $plan['code'],
				'action' => 'created',
				'id'     => self::create( $slug, $plan ),
			);
		}
		return $report;
	}

	/**
	 * Create one variable product with its three variations.
	 *
	 * @param string               $slug Plan URL value, e.g. '6-day'.
	 * @param array<string, mixed> $plan Plan from Plans::all().
	 * @return int Product ID.
	 */
	private static function create( string $slug, array $plan ): int {
		$attribute = new WC_Product_Attribute();
		$attribute->set_name( self::ATTRIBUTE );
		$attribute->set_options( array_values( self::OPTIONS ) );
		$attribute->set_visible( true );
		$attribute->set_variation( true );

		$product = new WC_Product_Variable();
		$product->set_name( $plan['name'] . ' – ' . Plans::short_label( $slug ) );
		$product->set_slug( 'plan-' . $slug );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'hidden' );
		$product->set_sold_individually( true );
		$product->set_virtual( true );
		$product->set_short_description( '[EDIT] Placeholder prices. ' . $plan['summary'] );
		$product->set_attributes( array( $attribute ) );
		$product->update_meta_data( '_pz_plan_code', $plan['code'] );
		$product->update_meta_data( '_pz_meals', $plan['meals'] );
		$product_id = $product->save();

		foreach ( self::OPTIONS as $code => $label ) {
			$variation = new WC_Product_Variation();
			$variation->set_parent_id( $product_id );
			$variation->set_attributes( array( sanitize_title( self::ATTRIBUTE ) => $label ) );
			$variation->set_regular_price( (string) self::PLACEHOLDER_PRICES[ $plan['code'] ][ $code ] );
			$variation->set_virtual( true );
			$variation->set_status( 'publish' );
			$variation->update_meta_data( '_pz_meal_type', $code );
			$variation->save();
		}

		WC_Product_Variable::sync( $product_id );
		return $product_id;
	}
}
