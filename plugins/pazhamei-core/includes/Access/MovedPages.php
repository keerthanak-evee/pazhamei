<?php
/**
 * MovedPages: storefront pages that moved, and the permanent (301) redirects for them.
 *
 * - /about-contact/  -> /about-us/            (the page was renamed "About Us")
 * - /enquire/        -> /about-us/#contact    (the enquiry form lives on About Us now)
 *
 * Any query string is kept, so /enquire/?plan=6-day lands on
 * /about-us/?plan=6-day#contact with the plan pre-selected.
 *
 * rewrite_links() updates old links inside saved content (menus, the footer,
 * edited pages); the page installer uses it. Both functions are plain string
 * work, so they are tested without WordPress.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Redirects for renamed and retired pages.
 */
final class MovedPages {

	/** Old top-level slug => array( new path, fragment ('' for none) ). */
	public const MOVED = array(
		'about-contact' => array( '/about-us/', '' ),
		'enquire'       => array( '/about-us/', 'contact' ),
	);

	/**
	 * Add the hook. Priority 1 runs before WordPress's own canonical and 404 redirects.
	 */
	public static function register(): void {
		add_action( 'template_redirect', array( self::class, 'redirect' ), 1 );
	}

	/**
	 * Send requests for a moved page to its new address.
	 */
	public static function redirect(): void {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$home    = (string) wp_parse_url( home_url( '/' ), PHP_URL_PATH );
		$target  = self::target( $request, $home );

		if ( '' !== $target ) {
			wp_safe_redirect( home_url( $target ), 301 );
			exit;
		}
	}

	/**
	 * Where a request should go, as a path relative to the site ('' if it has not moved).
	 *
	 * @param string $request_uri The request, e.g. '/enquire/?plan=6-day'.
	 * @param string $home_path   The site's own path, '/' unless WordPress lives in a folder.
	 */
	public static function target( string $request_uri, string $home_path = '/' ): string {
		$path  = (string) parse_url( $request_uri, PHP_URL_PATH ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url -- Runs in tests without WordPress.
		$query = (string) parse_url( $request_uri, PHP_URL_QUERY ); // phpcs:ignore WordPress.WP.AlternativeFunctions.parse_url_parse_url

		// A site in a folder: only paths inside that folder belong to it.
		$home_path = '/' . trim( $home_path, '/' );
		if ( '/' !== $home_path ) {
			if ( 0 !== strpos( $path, $home_path . '/' ) ) {
				return '';
			}
			$path = substr( $path, strlen( $home_path ) );
		}

		$slug = trim( $path, '/' );
		if ( ! isset( self::MOVED[ $slug ] ) ) {
			return '';
		}

		return self::build( $slug, $query );
	}

	/**
	 * Replace links to moved pages inside saved content (block markup).
	 *
	 * Finds href="..." values and block "url" attributes that point at a moved
	 * page, relative ('/enquire') or absolute ('http://example.com/enquire/'),
	 * and points them at the new address. Everything else is left as it is.
	 *
	 * @param string $content   Post content.
	 * @param string $home_url  The site address, e.g. 'http://pazhamei.local'.
	 */
	public static function rewrite_links( string $content, string $home_url ): string {
		$home  = rtrim( $home_url, '/' );
		$slugs = implode( '|', array_map( 'preg_quote', array_keys( self::MOVED ) ) );
		// href=" or a block's "url":" ; optional site address ; /slug ; optional / ; optional ?query ; then " or #.
		$pattern = '#(href="|"url":")(' . preg_quote( $home, '#' ) . ')?/(' . $slugs . ')/?(\?[^"\#]*)?(?=["\#])#';

		return (string) preg_replace_callback(
			$pattern,
			static function ( array $m ) use ( $home ): string {
				$query  = isset( $m[4] ) ? ltrim( $m[4], '?' ) : '';
				$prefix = '' !== $m[2] ? $home : '';

				return $m[1] . $prefix . self::build( $m[3], $query );
			},
			$content
		);
	}

	/**
	 * New address for a moved slug: path, then the query, then the fragment.
	 *
	 * @param string $slug  Old slug (a key of MOVED).
	 * @param string $query Query string without '?', may be ''.
	 */
	private static function build( string $slug, string $query ): string {
		list( $path, $fragment ) = self::MOVED[ $slug ];

		return $path . ( '' !== $query ? '?' . $query : '' ) . ( '' !== $fragment ? '#' . $fragment : '' );
	}
}
