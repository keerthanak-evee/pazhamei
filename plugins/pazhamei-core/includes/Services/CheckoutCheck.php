<?php
/**
 * CheckoutCheck: the business rules a plan order must pass before payment.
 *
 *  - Only signed-in customers can buy (guest checkout is off).
 *  - One plan per order.
 *  - A valid start date (rule 4).
 *  - A delivery pincode we serve (the delivery_pincodes setting; all while empty).
 *  - Trial: switch on and the customer is eligible (rule 10).
 *
 * Plain PHP on top of the Engine, so it is unit-tested; Woo\CheckoutFields
 * calls it from both the block checkout and the classic checkout.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Pincodes;
use Pazhamei\Domain\StartDate;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout rules.
 */
final class CheckoutCheck {

	/** Shown when the customer's current plan is on an open-ended pause (decision 10, Phase 4 UI). */
	public const PAUSED_NOTICE = 'Your plan is paused. Resume it to start your new pack.';

	/**
	 * Every problem with this checkout, as customer-facing messages.
	 *
	 * @param Engine   $engine      Engine.
	 * @param int      $customer_id Signed-in user (0 for guests).
	 * @param string[] $plan_codes  Plan code of each plan line in the cart.
	 * @param string   $start_date  Submitted start date.
	 * @param string   $mobile      Billing phone.
	 * @param string   $pincode     Delivery pincode.
	 * @param string   $address     Delivery address line 1.
	 * @return string[]
	 */
	public static function errors( Engine $engine, int $customer_id, array $plan_codes, string $start_date, string $mobile, string $pincode, string $address ): array {
		if ( ! $plan_codes ) {
			return array(); // Not a plan order.
		}
		if ( $customer_id <= 0 ) {
			return array( 'Please sign in or create an account to buy a plan.' );
		}

		$errors = array();
		if ( count( $plan_codes ) > 1 ) {
			$errors[] = 'Please buy one plan at a time.';
		}

		$start_error = StartDate::error( $start_date, $engine->now(), $engine->calendar(), $engine->cutoff() );
		if ( null !== $start_error ) {
			$errors[] = $start_error;
		}

		$pincode_error = Pincodes::refusal( $pincode, $engine->config->delivery_pincodes );
		if ( null !== $pincode_error ) {
			$errors[] = $pincode_error;
		}

		if ( in_array( 'TRIAL', $plan_codes, true ) ) {
			$refusal = $engine->trials()->refusal( $customer_id, $mobile, $pincode, $address );
			if ( null !== $refusal ) {
				$errors[] = $refusal;
			}
		}
		return $errors;
	}

	/**
	 * The "your plan is paused" notice, or null. For the Phase 4 checkout.
	 *
	 * @param Engine $engine      Engine.
	 * @param int    $customer_id Customer.
	 */
	public static function paused_notice( Engine $engine, int $customer_id ): ?string {
		$paused = $engine->stores->subscriptions->for_customer( $customer_id, array( PackStatus::PAUSED ) );
		return $paused ? self::PAUSED_NOTICE : null;
	}
}
