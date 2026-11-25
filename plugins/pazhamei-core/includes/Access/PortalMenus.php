<?php
/**
 * PortalMenus: the four Navigation menus (Storefront, Customer, Kitchen, Admin).
 *
 * Menus are normal `wp_navigation` posts, editable in Appearance -> Editor ->
 * Navigation. The page installer stores their IDs in the `pz_nav_menus`
 * option; if an ID is missing, we look the menu up by its title instead.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Finds the Navigation menu for a portal.
 */
final class PortalMenus {

	/** Option holding array( portal => wp_navigation post ID ). */
	public const OPTION = 'pz_nav_menus';

	/** Menu title for each portal. */
	public const TITLES = array(
		Portals::STOREFRONT => 'Storefront',
		Portals::CUSTOMER   => 'Customer',
		Portals::KITCHEN    => 'Kitchen',
		Portals::ADMIN      => 'Admin',
	);

	/**
	 * The menu ID for a portal, falling back to the Storefront menu. 0 if no menu exists.
	 *
	 * @param string $portal Portal name.
	 */
	public static function id_for( string $portal ): int {
		$id = self::find( $portal );

		if ( 0 === $id && Portals::STOREFRONT !== $portal ) {
			$id = self::find( Portals::STOREFRONT );
		}

		return $id;
	}

	/**
	 * Find one portal's menu: saved ID first, then by title.
	 *
	 * @param string $portal Portal name.
	 */
	public static function find( string $portal ): int {
		$saved = get_option( self::OPTION, array() );
		$id    = is_array( $saved ) ? (int) ( $saved[ $portal ] ?? 0 ) : 0;

		if ( $id > 0 && self::is_published_menu( $id ) ) {
			return $id;
		}

		if ( ! isset( self::TITLES[ $portal ] ) ) {
			return 0;
		}

		$found = get_posts(
			array(
				'post_type'      => 'wp_navigation',
				'post_status'    => 'publish',
				'title'          => self::TITLES[ $portal ],
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'orderby'        => 'ID',
				'order'          => 'ASC',
			)
		);

		return $found ? (int) $found[0] : 0;
	}

	/**
	 * Remember a portal's menu ID.
	 *
	 * @param string $portal Portal name.
	 * @param int    $id     wp_navigation post ID.
	 */
	public static function save( string $portal, int $id ): void {
		$saved            = get_option( self::OPTION, array() );
		$saved            = is_array( $saved ) ? $saved : array();
		$saved[ $portal ] = $id;
		update_option( self::OPTION, $saved, false );
	}

	/**
	 * Is this ID a published Navigation menu?
	 *
	 * @param int $id Post ID.
	 */
	private static function is_published_menu( int $id ): bool {
		return 'wp_navigation' === get_post_type( $id ) && 'publish' === get_post_status( $id );
	}
}
