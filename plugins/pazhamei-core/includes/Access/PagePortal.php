<?php
/**
 * PagePortal: works out which portal a page belongs to.
 *
 * A page's portal is stored in the `_pz_portal` post meta. If a page has no
 * value, it inherits the value from its nearest parent page, so a new page
 * added under /kitchen is protected even if someone forgets the setting.
 * Pages with no value anywhere up the tree are Storefront pages.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Looks up the portal for a page.
 */
final class PagePortal {

	/** Post meta key that stores a page's portal. */
	public const META_KEY = '_pz_portal';

	/**
	 * The portal of a page, following parents if the page has no setting.
	 *
	 * @param int $page_id Page ID.
	 * @return string One of the Portals::* constants.
	 */
	public static function for_page( int $page_id ): string {
		if ( $page_id <= 0 || 'page' !== get_post_type( $page_id ) ) {
			return Portals::STOREFRONT;
		}

		// The page itself first, then its parent, grandparent, and so on.
		$ids = array_merge( array( $page_id ), get_post_ancestors( $page_id ) );

		foreach ( $ids as $id ) {
			$portal = (string) get_post_meta( $id, self::META_KEY, true );
			if ( Portals::is_valid( $portal ) ) {
				return $portal;
			}
		}

		return Portals::STOREFRONT;
	}

	/**
	 * The portal of the page being viewed right now (storefront if not a page).
	 */
	public static function current(): string {
		if ( ! is_page() ) {
			return Portals::STOREFRONT;
		}
		return self::for_page( (int) get_queried_object_id() );
	}
}
