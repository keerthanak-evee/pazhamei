<?php
/**
 * Portals: the rules for who may open which portal, and where each role lands.
 *
 * This class is plain PHP with no WordPress calls, so PHPUnit can test it
 * directly (see tests/PortalsTest.php). It mirrors the table in CLAUDE.md:
 *
 *   Storefront  everyone
 *   Customer    customer                              -> /account/dashboard
 *   Kitchen     pz_kitchen, pz_admin, administrator   -> /kitchen
 *   Admin       pz_admin, administrator               -> /manage
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Portal access rules.
 */
final class Portals {

	public const STOREFRONT = 'storefront';
	public const CUSTOMER   = 'customer';
	public const KITCHEN    = 'kitchen';
	public const ADMIN      = 'admin';

	/** Every valid value for the `_pz_portal` page setting. */
	public const ALL = array( self::STOREFRONT, self::CUSTOMER, self::KITCHEN, self::ADMIN );

	/** Roles allowed into each signed-in portal. The storefront is open to everyone. */
	private const ALLOWED_ROLES = array(
		self::CUSTOMER => array( 'customer' ),
		self::KITCHEN  => array( 'pz_kitchen', 'pz_admin', 'administrator' ),
		self::ADMIN    => array( 'pz_admin', 'administrator' ),
	);

	/**
	 * Landing page for each role, most powerful role first.
	 * A user with several roles lands on the first match.
	 */
	private const LANDING = array(
		'administrator' => '/manage/',
		'pz_admin'      => '/manage/',
		'pz_kitchen'    => '/kitchen/',
		'customer'      => '/account/dashboard/',
	);

	/** Where users with none of our roles land. */
	private const DEFAULT_LANDING = '/';

	/**
	 * Is this a valid portal name?
	 *
	 * @param string $portal Portal name.
	 */
	public static function is_valid( string $portal ): bool {
		return in_array( $portal, self::ALL, true );
	}

	/**
	 * Does this portal need a signed-in user?
	 *
	 * @param string $portal Portal name.
	 */
	public static function requires_login( string $portal ): bool {
		return self::STOREFRONT !== $portal;
	}

	/**
	 * May a user with these roles open this portal?
	 *
	 * @param string   $portal Portal name.
	 * @param string[] $roles  The user's roles (empty for a logged-out visitor).
	 */
	public static function can_open( string $portal, array $roles ): bool {
		if ( self::STOREFRONT === $portal ) {
			return true;
		}

		if ( ! isset( self::ALLOWED_ROLES[ $portal ] ) ) {
			return false; // Unknown portal: deny, to be safe.
		}

		return count( array_intersect( $roles, self::ALLOWED_ROLES[ $portal ] ) ) > 0;
	}

	/**
	 * The path a user with these roles should land on after signing in.
	 *
	 * @param string[] $roles The user's roles.
	 * @return string Path relative to the site root, e.g. '/kitchen/'.
	 */
	public static function landing_path( array $roles ): string {
		foreach ( self::LANDING as $role => $path ) {
			if ( in_array( $role, $roles, true ) ) {
				return $path;
			}
		}
		return self::DEFAULT_LANDING;
	}

	/**
	 * Account links shown in the header for a signed-in user.
	 *
	 * @param string[] $roles The user's roles.
	 * @return array<int, array{label: string, path: string}>
	 */
	public static function account_links( array $roles ): array {
		$links = array();

		if ( self::can_open( self::CUSTOMER, $roles ) ) {
			$links[] = array( 'label' => 'My account', 'path' => '/account/dashboard/' );
		}
		if ( self::can_open( self::KITCHEN, $roles ) ) {
			$links[] = array( 'label' => 'Kitchen', 'path' => '/kitchen/' );
		}
		if ( self::can_open( self::ADMIN, $roles ) ) {
			$links[] = array( 'label' => 'Manage', 'path' => '/manage/' );
		}

		return $links;
	}
}
