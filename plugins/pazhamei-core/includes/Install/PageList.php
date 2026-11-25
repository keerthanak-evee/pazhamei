<?php
/**
 * PageList: every page the site needs, and the four Navigation menus.
 *
 * Used by `wp pz install-pages`. To add a page in a later phase, add a row
 * here and run the command again; existing pages are never touched.
 *
 * Each page row: slug, title, parent slug ('' for top level), portal, and the
 * phase of the build plan that fills it with real content.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Access\Portals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Static list of pages and menus.
 */
final class PageList {

	/** Phase names from docs/build-plan.md, used in the starter text. */
	private const PHASE_STOREFRONT = 'Phase 1 (Storefront)';
	private const PHASE_CUSTOMER   = 'Phase 4 (Customer portal)';
	private const PHASE_KITCHEN    = 'Phase 7 (Kitchen portal)';
	private const PHASE_ADMIN      = 'Phase 8 (Admin portal)';

	/**
	 * All pages, parents listed before their children.
	 *
	 * @return array<int, array{slug: string, title: string, parent: string, portal: string, phase: string}>
	 */
	public static function pages(): array {
		$s = Portals::STOREFRONT;
		$c = Portals::CUSTOMER;
		$k = Portals::KITCHEN;
		$a = Portals::ADMIN;

		return array(
			// Storefront: top-level pages, open to everyone.
			self::row( 'home', 'Home', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'sign-in', 'Sign in', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'plans', 'Plans', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'about-contact', 'About & Contact', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'faq', 'FAQ', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'enquire', 'Enquire', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'terms', 'Terms & Conditions', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'privacy-policy', 'Privacy Policy', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'refund-policy', 'Refund & Cancellation Policy', '', $s, self::PHASE_STOREFRONT ),
			self::row( 'delivery-policy', 'Delivery Policy', '', $s, self::PHASE_STOREFRONT ),

			// Customer portal: /account/...
			self::row( 'account', 'My account', '', $c, self::PHASE_CUSTOMER ),
			self::row( 'meals', 'Meals', 'account', $c, self::PHASE_CUSTOMER ),
			self::row( 'plans', 'Plans', 'account', $c, self::PHASE_CUSTOMER ),
			self::row( 'dashboard', 'Dashboard', 'account', $c, self::PHASE_CUSTOMER ),
			self::row( 'profile', 'Profile', 'account', $c, self::PHASE_CUSTOMER ),
			self::row( 'support', 'Support', 'account', $c, self::PHASE_CUSTOMER ),
			self::row( 'history', 'History', 'account', $c, self::PHASE_CUSTOMER ),

			// Kitchen portal: /kitchen/...
			self::row( 'kitchen', 'Kitchen dashboard', '', $k, self::PHASE_KITCHEN ),
			self::row( 'allergens', 'Allergens & Instructions', 'kitchen', $k, self::PHASE_KITCHEN ),
			self::row( 'production', 'Production sheet', 'kitchen', $k, self::PHASE_KITCHEN ),
			self::row( 'dispatch', 'Packing & Dispatch', 'kitchen', $k, self::PHASE_KITCHEN ),

			// Admin portal: /manage/...
			self::row( 'manage', 'Admin dashboard', '', $a, self::PHASE_ADMIN ),
			self::row( 'menu', 'Menu', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'meals', 'Meals', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'customers', 'Customers', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'enquiries', 'Enquiries', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'subscriptions', 'Subscriptions', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'users', 'Users & Roles', 'manage', $a, self::PHASE_ADMIN ),
			self::row( 'settings', 'Settings', 'manage', $a, self::PHASE_ADMIN ),
		);
	}

	/**
	 * Menu items for each portal's Navigation menu: label => page path.
	 * Sign in / Sign out are not menu items: the pz/portal-nav block adds them.
	 *
	 * @return array<string, array<string, string>>
	 */
	public static function menus(): array {
		return array(
			Portals::STOREFRONT => array(
				'Home'            => 'home',
				'Plans'           => 'plans',
				'About & Contact' => 'about-contact',
				'FAQ'             => 'faq',
				'Enquire'         => 'enquire',
			),
			Portals::CUSTOMER   => array(
				'Home'      => 'account',
				'Meals'     => 'account/meals',
				'Plans'     => 'account/plans',
				'Dashboard' => 'account/dashboard',
				'Support'   => 'account/support',
			),
			Portals::KITCHEN    => array(
				'Dashboard'                => 'kitchen',
				'Allergens & Instructions' => 'kitchen/allergens',
				'Production sheet'         => 'kitchen/production',
				'Dispatch'                 => 'kitchen/dispatch',
			),
			Portals::ADMIN      => array(
				'Dashboard'     => 'manage',
				'Menu & Meals'  => 'manage/menu',
				'Customers'     => 'manage/customers',
				'Enquiries'     => 'manage/enquiries',
				'Subscriptions' => 'manage/subscriptions',
				'Users & Roles' => 'manage/users',
				'Settings'      => 'manage/settings',
			),
		);
	}

	/**
	 * Build one page row.
	 *
	 * @param string $slug   Page slug.
	 * @param string $title  Page title.
	 * @param string $parent Parent page slug, '' for top level.
	 * @param string $portal Portal name.
	 * @param string $phase  Build-plan phase that fills the page.
	 * @return array{slug: string, title: string, parent: string, portal: string, phase: string}
	 */
	private static function row( string $slug, string $title, string $parent, string $portal, string $phase ): array {
		return compact( 'slug', 'title', 'parent', 'portal', 'phase' );
	}
}
