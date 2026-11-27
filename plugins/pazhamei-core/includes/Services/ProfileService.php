<?php
/**
 * ProfileService: reads and saves the customer's Profile page.
 *
 * Where each value lives:
 * - Name: the WordPress user (first/last name) and the WooCommerce billing name.
 * - Mobile, pincode, dietary preference, special instructions: wp_pz_customer_profiles
 *   (mobile and pincode are also the WooCommerce billing phone and postcode).
 * - Delivery address: the WooCommerce billing address on the account. The
 *   20:00 lock job copies it into the next day's order (Woo\OrderAddress), so
 *   a change applies from the next unlocked day.
 * - Allergens: wp_pz_customer_allergens (AllergenRepository).
 *
 * The rules are in Domain\ProfileInput; this class only reads and writes.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Clock;
use Pazhamei\Domain\ProfileInput;
use Pazhamei\Domain\Registration;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Repositories\AllergenRepository;
use Pazhamei\Repositories\ProfileRepository;
use Pazhamei\Settings;
use WC_Customer;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile read and save.
 */
final class ProfileService {

	/**
	 * The profile as the form shows it.
	 *
	 * @param int $customer_id Customer.
	 * @return array<string, mixed>
	 */
	public static function get( int $customer_id ): array {
		$user     = get_userdata( $customer_id );
		$customer = new WC_Customer( $customer_id );
		$profile  = ( new ProfileRepository() )->get( $customer_id ) ?? array();
		$mine     = AllergenRepository::for_customer( $customer_id );

		$allergens = array();
		foreach ( AllergenRepository::all() as $id => $name ) {
			$allergens[] = array(
				'id'       => $id,
				'name'     => $name,
				'severity' => $mine[ $id ] ?? '',
			);
		}

		return array(
			'name'         => $user ? trim( $user->first_name . ' ' . $user->last_name ) : '',
			'email'        => $user ? $user->user_email : '',
			'mobile'       => '' !== (string) ( $profile['mobile'] ?? '' ) ? (string) $profile['mobile'] : Registration::mobile( $customer->get_billing_phone() ),
			'address_1'    => $customer->get_billing_address_1(),
			'address_2'    => $customer->get_billing_address_2(),
			'city'         => '' !== $customer->get_billing_city() ? $customer->get_billing_city() : 'Chennai',
			'pincode'      => '' !== $customer->get_billing_postcode() ? $customer->get_billing_postcode() : (string) ( $profile['pincode'] ?? '' ),
			'dietary'      => (string) ( $profile['dietary_preference'] ?? '' ),
			'instructions' => (string) ( $profile['instructions'] ?? '' ),
			'allergens'    => $allergens,
		);
	}

	/**
	 * Check and save the profile.
	 *
	 * @param int                  $customer_id Customer.
	 * @param array<string, mixed> $input       Submitted fields.
	 * @return array<string, string> Errors by field (empty when saved).
	 */
	public static function save( int $customer_id, array $input ): array {
		$repo    = new ProfileRepository();
		$checked = ProfileInput::check(
			$input,
			array_keys( AllergenRepository::all() ),
			(array) Settings::engine_config()->delivery_pincodes,
			static function ( string $mobile ) use ( $repo, $customer_id ) {
				$owner = $repo->customer_by_mobile( $mobile );
				return $owner > 0 && $owner !== $customer_id;
			}
		);
		if ( $checked['errors'] ) {
			return $checked['errors'];
		}

		$v                    = $checked['values'];
		$now                  = Clock::now()->format( 'Y-m-d H:i:s' );
		list( $first, $last ) = Registration::split_name( $v['name'] );

		wp_update_user(
			array(
				'ID'           => $customer_id,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => $v['name'],
			)
		);

		$customer = new WC_Customer( $customer_id );
		$customer->set_first_name( $first );
		$customer->set_last_name( $last );
		$customer->set_billing_first_name( $first );
		$customer->set_billing_last_name( $last );
		$customer->set_billing_phone( $v['mobile'] );
		$customer->set_billing_address_1( $v['address_1'] );
		$customer->set_billing_address_2( $v['address_2'] );
		$customer->set_billing_city( $v['city'] );
		$customer->set_billing_postcode( $v['pincode'] );
		$customer->set_billing_country( 'IN' );
		if ( '' === $customer->get_billing_state() ) {
			$customer->set_billing_state( 'TN' );
		}
		$customer->save();

		$repo->ensure( $customer_id, $now );
		$repo->update(
			$customer_id,
			array(
				'mobile'             => $v['mobile'],
				'pincode'            => $v['pincode'],
				'dietary_preference' => $v['dietary'],
				'instructions'       => $v['instructions'],
				'updated_at'         => $now,
			)
		);
		AllergenRepository::replace_for_customer( $customer_id, $v['allergens'], $now );

		return array();
	}

	/**
	 * Change the password after checking the current one.
	 *
	 * @param int    $customer_id Customer.
	 * @param string $current     Current password.
	 * @param string $new         New password.
	 * @param string $confirm     New password again.
	 * @throws RuleViolation When a check fails.
	 */
	public static function change_password( int $customer_id, string $current, string $new, string $confirm ): void {
		$user = get_userdata( $customer_id );
		if ( ! $user || ! wp_check_password( $current, $user->user_pass, $user->ID ) ) {
			throw new RuleViolation( 'wrong_password', 'Your current password is not right.' );
		}
		$error = ProfileInput::password_error( $new, $confirm );
		if ( null !== $error ) {
			throw new RuleViolation( 'bad_password', $error );
		}
		// wp_update_user() signs the customer in again with the new password.
		wp_update_user(
			array(
				'ID'        => $customer_id,
				'user_pass' => $new,
			)
		);
	}
}
