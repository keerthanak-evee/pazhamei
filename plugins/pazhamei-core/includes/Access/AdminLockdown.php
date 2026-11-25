<?php
/**
 * AdminLockdown: keeps everyone except administrators out of wp-admin and hides their admin bar.
 *
 * Kitchen staff, Pazhamei admins and customers work only in the front-end
 * portals. admin-ajax.php and admin-post.php keep working for everyone,
 * because front-end features (and WooCommerce) rely on them.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * wp-admin and admin bar restrictions.
 */
final class AdminLockdown {

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_filter( 'show_admin_bar', array( self::class, 'filter_admin_bar' ) );
		add_action( 'admin_init', array( self::class, 'block_wp_admin' ), 1 );

		// Some screens (e.g. wp-admin/edit.php) refuse access before admin_init runs; redirect those too.
		add_action( 'admin_page_access_denied', array( self::class, 'block_wp_admin' ) );
	}

	/**
	 * Is the current user a site administrator?
	 */
	public static function is_administrator(): bool {
		return in_array( 'administrator', (array) wp_get_current_user()->roles, true );
	}

	/**
	 * Show the admin bar only to administrators.
	 *
	 * @param bool $show Whether WordPress would show it.
	 */
	public static function filter_admin_bar( $show ): bool {
		return self::is_administrator() ? (bool) $show : false;
	}

	/**
	 * Send non-administrators who open wp-admin to their landing page.
	 */
	public static function block_wp_admin(): void {
		if ( wp_doing_ajax() || wp_doing_cron() ) {
			return; // admin-ajax.php and cron must keep working.
		}

		global $pagenow;
		if ( 'admin-post.php' === $pagenow ) {
			return; // Front-end forms post here.
		}

		if ( ! is_user_logged_in() || self::is_administrator() ) {
			return; // Logged-out users are already sent to the login screen by WordPress.
		}

		wp_safe_redirect( home_url( Portals::landing_path( (array) wp_get_current_user()->roles ) ) );
		exit;
	}
}
