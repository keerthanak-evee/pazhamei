<?php
/**
 * PortalGuard: checks every page request and sends people away from portals they may not open.
 *
 * - Logged out on a customer/kitchen/admin page -> /sign-in/?redirect_to=<that page>
 * - Logged in but wrong role                    -> their own landing page
 *
 * This protects pages only. REST routes (Phase 2+) must still check a
 * capability and a nonce themselves.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The page guard.
 */
final class PortalGuard {

	/**
	 * Hook into template_redirect, which runs once WordPress knows which page is being viewed.
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'check' ), 1 );
	}

	/**
	 * Run the check for the current request.
	 */
	public static function check(): void {
		$portal = PagePortal::current();

		if ( ! Portals::requires_login( $portal ) ) {
			return; // Storefront: everyone is welcome.
		}

		// Portal pages show personal data: never let a browser or proxy cache them.
		nocache_headers();

		if ( ! is_user_logged_in() ) {
			$page_url = get_permalink( get_queried_object_id() );
			wp_safe_redirect( add_query_arg( 'redirect_to', rawurlencode( $page_url ), home_url( '/sign-in/' ) ) );
			exit;
		}

		$roles = (array) wp_get_current_user()->roles;

		if ( Portals::can_open( $portal, $roles ) ) {
			return;
		}

		wp_safe_redirect( home_url( Portals::landing_path( $roles ) ) );
		exit;
	}
}
