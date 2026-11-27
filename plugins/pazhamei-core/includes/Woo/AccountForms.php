<?php
/**
 * AccountForms: the extra Register fields, phone sign-in, and which view /sign-in shows.
 *
 * The forms themselves are drawn by the pz/account-form block and posted to
 * WooCommerce's own handlers (same field names and nonces), so WooCommerce
 * still signs people in, creates the account, sends its emails and resets
 * passwords. This class adds:
 * - Register: full name, phone (unique), delivery pincode, confirm password
 *   (Domain\Registration), saved to the WordPress user, the WooCommerce
 *   billing details and wp_pz_customer_profiles.
 * - Sign in with a phone number: "Email or phone number" also accepts the
 *   mobile on the customer's profile (last 10 digits).
 * - The current view of /sign-in (sign-in, register, lost-password or
 *   account) as a body class, so the page shows one view at a time.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Woo;

use Pazhamei\Clock;
use Pazhamei\Domain\Registration;
use Pazhamei\Repositories\ProfileRepository;
use WP_Error;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Register fields, phone sign-in and sign-in page views.
 */
final class AccountForms {

	/** The four views of /sign-in. */
	public const VIEWS = array( 'sign-in', 'register', 'lost-password', 'account' );

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_filter( 'woocommerce_process_registration_errors', array( self::class, 'check_registration' ), 10, 1 );
		add_action( 'woocommerce_created_customer', array( self::class, 'save_registration' ), 10, 1 );
		add_filter( 'woocommerce_registration_redirect', array( self::class, 'after_registration' ) );
		add_filter( 'authenticate', array( self::class, 'phone_sign_in' ), 30, 3 ); // After WordPress's own checks (20).
		add_filter( 'body_class', array( self::class, 'body_class' ) );
	}

	/**
	 * Which view of /sign-in to show for this request.
	 */
	public static function view(): string {
		if ( is_user_logged_in() ) {
			return 'account'; // Order pages and other WooCommerce endpoints under /sign-in/.
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'lost-password' ) ) {
			return 'lost-password';
		}
		// phpcs:disable WordPress.Security.NonceVerification -- Only chooses which form to show.
		if ( isset( $_POST['register'] ) || ( isset( $_GET['view'] ) && 'register' === $_GET['view'] ) ) {
			return 'register';
		}
		// phpcs:enable
		return 'sign-in';
	}

	/**
	 * Body class pz-auth-view--<view> on the sign-in page.
	 *
	 * @param string[] $classes Body classes.
	 * @return string[]
	 */
	public static function body_class( $classes ) {
		if ( function_exists( 'is_account_page' ) && is_account_page() ) {
			$classes[] = 'pz-auth-view--' . self::view();
		}
		return $classes;
	}

	/**
	 * Check the extra Register fields. WooCommerce has checked its nonce and the email.
	 *
	 * @param WP_Error $errors Errors so far.
	 * @return WP_Error
	 */
	public static function check_registration( $errors ) {
		if ( ! $errors instanceof WP_Error ) {
			$errors = new WP_Error();
		}
		$input = self::posted();
		$repo  = new ProfileRepository();

		foreach ( Registration::errors( $input, static fn( string $mobile ) => $repo->customer_by_mobile( $mobile ) > 0 ) as $field => $message ) {
			$errors->add( 'pz_' . $field, $message );
		}
		return $errors;
	}

	/**
	 * Save the extra fields on the new customer.
	 *
	 * @param int $customer_id New user ID.
	 */
	public static function save_registration( $customer_id ): void {
		$customer_id = (int) $customer_id;
		$input       = self::posted();
		if ( ! isset( $input['pz_mobile'] ) ) {
			return; // Created somewhere else (e.g. an admin screen): nothing of ours to save.
		}

		list( $first, $last ) = Registration::split_name( (string) $input['pz_full_name'] );
		$mobile               = Registration::mobile( (string) $input['pz_mobile'] );
		$pincode              = trim( (string) $input['pz_pincode'] );

		wp_update_user(
			array(
				'ID'           => $customer_id,
				'first_name'   => $first,
				'last_name'    => $last,
				'display_name' => trim( $first . ' ' . $last ),
			)
		);

		$customer = new \WC_Customer( $customer_id );
		$customer->set_first_name( $first );
		$customer->set_last_name( $last );
		$customer->set_billing_first_name( $first );
		$customer->set_billing_last_name( $last );
		$customer->set_billing_phone( $mobile );
		$customer->set_billing_postcode( $pincode );
		$customer->set_billing_country( 'IN' );
		$customer->set_billing_state( 'TN' );
		$customer->save();

		$repo = new ProfileRepository();
		$now  = Clock::now()->format( 'Y-m-d H:i:s' );
		$repo->ensure( $customer_id, $now );
		$repo->update(
			$customer_id,
			array(
				'mobile'     => $mobile,
				'pincode'    => $pincode,
				'updated_at' => $now,
			)
		);
	}

	/**
	 * After registering, a new customer goes to their dashboard.
	 */
	public static function after_registration(): string {
		return home_url( '/account/dashboard/' );
	}

	/**
	 * Sign in with a phone number. Runs after WordPress tried the text as a
	 * username and an email. If it looks like a mobile, find the account whose
	 * profile has it and check the password. The messages never reveal the
	 * account's username.
	 *
	 * @param WP_User|WP_Error|null $user     Result so far.
	 * @param string                $login    What was typed as the username.
	 * @param string                $password Password.
	 * @return WP_User|WP_Error|null
	 */
	public static function phone_sign_in( $user, $login, $password ) {
		if ( $user instanceof WP_User || ! is_string( $login ) || ! Registration::is_mobile_login( $login ) || '' === (string) $password ) {
			return $user;
		}

		$customer_id = ( new ProfileRepository() )->customer_by_mobile( Registration::mobile( $login ) );
		$account     = $customer_id > 0 ? get_user_by( 'id', $customer_id ) : false;
		if ( ! $account ) {
			return new WP_Error( 'pz_unknown_mobile', __( 'No account uses that phone number. Check the number, or sign in with your email.', 'pazhamei-core' ) );
		}

		$result = wp_authenticate_username_password( null, $account->user_login, (string) $password );
		if ( $result instanceof WP_User ) {
			return $result;
		}
		return new WP_Error( 'pz_wrong_password', __( 'That password is not right for this phone number. Try again, or use Forgot password.', 'pazhamei-core' ) );
	}

	/**
	 * The posted Register fields, unslashed. Passwords are not sanitised (any character is allowed).
	 *
	 * @return array<string, string>
	 */
	private static function posted(): array {
		// phpcs:disable WordPress.Security.NonceVerification.Missing -- WooCommerce verified woocommerce-register-nonce before these hooks.
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$out = array();
		foreach ( array( 'pz_full_name', 'pz_mobile', 'pz_pincode' ) as $key ) {
			if ( isset( $_POST[ $key ] ) ) {
				$out[ $key ] = sanitize_text_field( wp_unslash( (string) $_POST[ $key ] ) );
			}
		}
		foreach ( array( 'password', 'pz_password2' ) as $key ) {
			$out[ $key ] = isset( $_POST[ $key ] ) ? (string) wp_unslash( $_POST[ $key ] ) : '';
		}
		// phpcs:enable
		return $out;
	}
}
