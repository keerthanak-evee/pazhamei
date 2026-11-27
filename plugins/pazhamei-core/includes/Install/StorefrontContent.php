<?php
/**
 * StorefrontContent: starter content for the storefront pages.
 *
 * Used by the page installer for new pages, and to refresh pages nobody has
 * edited. After that the owner edits the pages in the block editor; the
 * installer never overwrites edited pages. Home, Plans, About Us and FAQ are
 * built from the theme's patterns (themes/pazhamei-theme/patterns).
 *
 * Text marked [EDIT] is a placeholder the owner must replace before launch.
 * Policy pages are in PolicyContent.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Install\BlockMarkup as B;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content for Home, Plans, About Us, FAQ and Sign in.
 */
final class StorefrontContent {

	/** Home sections, in order: theme pattern slugs. */
	public const HOME_PATTERNS = array(
		'pazhamei/home-hero',
		'pazhamei/home-heritage-strip',
		'pazhamei/home-vision',
		'pazhamei/home-meal-types',
		'pazhamei/home-plans-teaser',
		'pazhamei/home-cooking-week',
		'pazhamei/home-balanced',
		'pazhamei/home-how-it-works',
		'pazhamei/home-testimonials',
		'pazhamei/home-faq-teaser',
		'pazhamei/home-cta',
	);

	/** Plans sections (design/prototype-v2.html). */
	public const PLANS_PATTERNS = array(
		'pazhamei/page-plans-hero',
		'pazhamei/page-plans-durations',
		'pazhamei/page-plans-menu',
		'pazhamei/page-plans-health',
	);

	/** About Us sections: the About part first, then Contact with the enquiry form (#contact). */
	public const ABOUT_PATTERNS = array(
		'pazhamei/page-about-hero',
		'pazhamei/page-about-philosophy',
		'pazhamei/page-about-route',
		'pazhamei/page-about-cta',
		'pazhamei/page-about-contact',
	);

	/** FAQ: one pattern with the questions and the 8 pm rule. */
	public const FAQ_PATTERNS = array(
		'pazhamei/page-faq',
	);

	/** Sign in: the Sign in, Register and Forgot password views (Login&RegisterPAgeReference.jpg). */
	public const SIGN_IN_PATTERNS = array(
		'pazhamei/page-sign-in',
	);

	/**
	 * Content for a page path, or null if this class has none for it
	 * (or the theme's patterns are not available because another theme is active).
	 *
	 * @param string $path Page path, e.g. 'faq'.
	 */
	public static function for_path( string $path ): ?string {
		switch ( $path ) {
			case 'home':
				return self::from_patterns( self::HOME_PATTERNS );
			case 'plans':
				return self::from_patterns( self::PLANS_PATTERNS );
			case 'about-us':
				return self::from_patterns( self::ABOUT_PATTERNS );
			case 'faq':
				return self::from_patterns( self::FAQ_PATTERNS );
			case 'sign-in':
				return self::from_patterns( self::SIGN_IN_PATTERNS );
			default:
				return PolicyContent::for_path( $path );
		}
	}

	/**
	 * True for pages built from theme patterns (their content needs pazhamei-theme).
	 *
	 * @param string $path Page path.
	 */
	public static function uses_patterns( string $path ): bool {
		return in_array( $path, array( 'home', 'plans', 'about-us', 'faq' ), true );
	}

	/**
	 * The theme's patterns, copied in as real blocks so every part can be edited.
	 * Returns null if any pattern is missing (another theme is active).
	 *
	 * @param string[] $slugs Pattern slugs, in page order.
	 */
	private static function from_patterns( array $slugs ): ?string {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$parts    = array();

		foreach ( $slugs as $slug ) {
			$pattern = $registry->get_registered( $slug );
			if ( ! $pattern ) {
				return null;
			}
			$parts[] = trim( $pattern['content'] );
		}

		return B::join( $parts );
	}
}
