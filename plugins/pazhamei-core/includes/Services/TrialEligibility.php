<?php
/**
 * TrialEligibility: may this customer buy the trial? (rule 10)
 *
 * Only when the admin switch is on, and once per customer: checked by
 * account, by mobile number (last 10 digits) and by address (pincode plus
 * house or flat number, normalised by TrialIdentity). No approval step.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\TrialIdentity;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Trial checks.
 */
final class TrialEligibility {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Why the customer can't buy a trial, or null if they can.
	 *
	 * @param int    $customer_id  Customer.
	 * @param string $mobile       Phone, any format.
	 * @param string $pincode      Delivery pincode.
	 * @param string $address_line First address line.
	 */
	public function refusal( int $customer_id, string $mobile, string $pincode, string $address_line ): ?string {
		$stores = $this->engine->stores;

		if ( ! $this->engine->config->trial_enabled ) {
			return 'The trial is not available right now.';
		}
		if ( $customer_id > 0 && ( $stores->subscriptions->has_plan( $customer_id, 'TRIAL' ) || $stores->trial_claims->exists_for_customer( $customer_id ) ) ) {
			return 'You have already had a trial.';
		}
		if ( $stores->trial_claims->exists_with_mobile( TrialIdentity::mobile_key( $mobile ), $customer_id ) ) {
			return 'A trial has already been used with this mobile number.';
		}
		if ( $stores->trial_claims->exists_with_address( TrialIdentity::address_key( $pincode, $address_line ), $customer_id ) ) {
			return 'A trial has already been used at this address.';
		}
		return null;
	}
}
