<?php
/**
 * Roles: creates the Pazhamei roles and capabilities, and removes them on uninstall.
 *
 * Roles:
 * - customer   (made by WooCommerce) gets pz_customer.
 * - pz_kitchen (ours) can view the kitchen portal and mark deliveries.
 * - pz_admin   (ours) gets every pz_* capability.
 * - administrator also gets every pz_* capability.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Role and capability setup.
 */
final class Roles {

	/** Capabilities for customers. */
	public const CUSTOMER_CAPS = array( 'pz_customer' );

	/** Capabilities for kitchen staff. */
	public const KITCHEN_CAPS = array( 'pz_view_kitchen', 'pz_mark_delivery' );

	/** Capabilities only admins have. */
	public const ADMIN_ONLY_CAPS = array(
		'pz_manage_menu',
		'pz_manage_customers',
		'pz_manage_subscriptions',
		'pz_view_reports',
		'pz_manage_users',
		'pz_manage_settings',
	);

	/**
	 * Every pz_* capability.
	 *
	 * @return string[]
	 */
	public static function all_caps(): array {
		return array_merge( self::CUSTOMER_CAPS, self::KITCHEN_CAPS, self::ADMIN_ONLY_CAPS );
	}

	/**
	 * Create or update the roles. Safe to run many times.
	 */
	public static function install(): void {
		// Kitchen staff: can sign in (read) and use the kitchen portal.
		self::ensure_role( 'pz_kitchen', 'Kitchen staff', self::KITCHEN_CAPS );

		// Pazhamei admin: every pz_* capability, but no wp-admin.
		self::ensure_role( 'pz_admin', 'Pazhamei admin', self::all_caps() );

		// WooCommerce makes the `customer` role. If WooCommerce has not run yet, create it the same way it does.
		self::ensure_role( 'customer', 'Customer', self::CUSTOMER_CAPS );

		// Site administrators get everything.
		self::add_caps( 'administrator', self::all_caps() );
	}

	/**
	 * Remove our roles and capabilities. Called from uninstall.php.
	 */
	public static function uninstall(): void {
		remove_role( 'pz_kitchen' );
		remove_role( 'pz_admin' );

		foreach ( array( 'customer', 'administrator' ) as $role_name ) {
			$role = get_role( $role_name );
			if ( ! $role ) {
				continue;
			}
			foreach ( self::all_caps() as $cap ) {
				$role->remove_cap( $cap );
			}
		}
	}

	/**
	 * Create a role if missing, then make sure it has the given capabilities.
	 *
	 * @param string   $name  Role key, e.g. 'pz_kitchen'.
	 * @param string   $label Name shown in the Users screen.
	 * @param string[] $caps  Capabilities to add.
	 */
	private static function ensure_role( string $name, string $label, array $caps ): void {
		if ( ! get_role( $name ) ) {
			add_role( $name, $label, array( 'read' => true ) );
		}
		self::add_caps( $name, $caps );
	}

	/**
	 * Add capabilities to an existing role.
	 *
	 * @param string   $name Role key.
	 * @param string[] $caps Capabilities to add.
	 */
	private static function add_caps( string $name, array $caps ): void {
		$role = get_role( $name );
		if ( ! $role ) {
			return;
		}
		foreach ( $caps as $cap ) {
			$role->add_cap( $cap );
		}
	}
}
