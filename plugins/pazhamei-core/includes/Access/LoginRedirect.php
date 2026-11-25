<?php
/**
 * LoginRedirect: decides where people go after signing in, and keeps signed-in users off /sign-in.
 *
 * After sign-in:
 * - If the sign-in page was opened with ?redirect_to=<a page on this site>, go there.
 *   (The PortalGuard still checks that page, so this can't be used to get into a portal.)
 * - Otherwise go to the role's landing page (see Portals::landing_path()).
 *
 * Works for both the WooCommerce form on /sign-in and wp-login.php.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sign-in redirects.
 */
final class LoginRedirect {

	/** Slug of the sign-in page (also WooCommerce's My Account page). */
	public const SIGN_IN_SLUG = 'sign-in';

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'woocommerce_login_form_end', array( self::class, 'print_redirect_field' ) );
		add_filter( 'woocommerce_login_redirect', array( self::class, 'after_woocommerce_login' ), 10, 2 );
		add_filter( 'login_redirect', array( self::class, 'after_wp_login' ), 10, 3 );
		add_action( 'template_redirect', array( self::class, 'leave_sign_in_page' ), 2 );
	}

	/**
	 * Carry ?redirect_to=... from the URL into the WooCommerce sign-in form.
	 */
	public static function print_redirect_field(): void {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only: only copied into a hidden field.
		$requested = isset( $_GET['redirect_to'] ) ? self::safe_local_url( wp_unslash( $_GET['redirect_to'] ) ) : '';

		if ( '' !== $requested ) {
			printf( '<input type="hidden" name="redirect_to" value="%s" />', esc_attr( $requested ) );
		}
	}

	/**
	 * Where to go after signing in with the WooCommerce form.
	 *
	 * @param string   $redirect Where WooCommerce wanted to go.
	 * @param \WP_User $user     The user who just signed in.
	 */
	public static function after_woocommerce_login( $redirect, $user ): string {
		// WooCommerce has already checked its own login nonce before this filter runs.
		// phpcs:ignore WordPress.Security.NonceVerification.Missing
		$requested = isset( $_POST['redirect_to'] ) ? wp_unslash( $_POST['redirect_to'] ) : '';

		return self::destination( $user, (string) $requested );
	}

	/**
	 * Where to go after signing in on wp-login.php.
	 *
	 * @param string             $redirect_to Final URL WordPress picked.
	 * @param string             $requested   The URL that was asked for (may be empty).
	 * @param \WP_User|\WP_Error $user        The user, or an error if sign-in failed.
	 */
	public static function after_wp_login( $redirect_to, $requested, $user ): string {
		if ( ! $user instanceof \WP_User ) {
			return (string) $redirect_to; // Sign-in failed; leave WordPress alone.
		}
		return self::destination( $user, (string) $requested );
	}

	/**
	 * A signed-in user has no reason to see the sign-in form: send them to their landing page.
	 * The WooCommerce sign-out link (/sign-in/customer-logout/) must keep working.
	 */
	public static function leave_sign_in_page(): void {
		if ( ! is_user_logged_in() || ! is_page( self::SIGN_IN_SLUG ) ) {
			return;
		}
		if ( function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url() ) {
			return; // e.g. customer-logout, lost-password.
		}

		wp_safe_redirect( home_url( Portals::landing_path( (array) wp_get_current_user()->roles ) ) );
		exit;
	}

	/**
	 * Pick the destination: the requested page if it is safe, otherwise the landing page.
	 *
	 * @param \WP_User $user      The user who signed in.
	 * @param string   $requested Requested URL, possibly empty.
	 */
	private static function destination( $user, string $requested ): string {
		$landing   = home_url( Portals::landing_path( (array) $user->roles ) );
		$requested = self::safe_local_url( $requested );

		if ( '' === $requested ) {
			return $landing;
		}

		// Only administrators may be sent into wp-admin.
		$is_admin_url = 0 === strpos( $requested, admin_url() );
		if ( $is_admin_url && ! in_array( 'administrator', (array) $user->roles, true ) ) {
			return $landing;
		}

		return $requested;
	}

	/**
	 * Return the URL if it points to this site (and isn't the sign-in page), otherwise ''.
	 *
	 * @param string $url Untrusted URL from the request.
	 */
	private static function safe_local_url( string $url ): string {
		$url = wp_validate_redirect( esc_url_raw( $url ), '' );

		if ( '' === $url ) {
			return '';
		}

		$path = (string) wp_parse_url( $url, PHP_URL_PATH );
		if ( '/' . self::SIGN_IN_SLUG === untrailingslashit( $path ) ) {
			return ''; // Going "back" to the sign-in page would be a loop.
		}

		return $url;
	}
}
