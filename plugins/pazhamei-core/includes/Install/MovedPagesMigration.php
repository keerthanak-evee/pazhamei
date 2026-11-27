<?php
/**
 * MovedPagesMigration: the one-off changes for pages that moved (design update, Nov 2026).
 *
 * - /about-contact/ is renamed to /about-us/ ("About Us"); the page keeps its ID.
 * - /enquire/ is retired: moved to the Trash if nobody edited it. The enquiry
 *   form now lives on /about-us/#contact; pz/enquiry-form and its REST route stay.
 * - The Storefront menu gets one "About Us" link in place of the About/Contact
 *   link (whatever its label), and loses its Enquire link. Other items stay.
 * - Old links in saved content (the Site Editor copies of the header and footer,
 *   menus, and pages someone edited) are pointed at the new addresses.
 *
 * Every step checks first, so running `wp pz install-pages` again changes nothing.
 * Access\MovedPages redirects the old addresses (301).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Access\MovedPages;
use Pazhamei\Access\PortalMenus;
use Pazhamei\Access\Portals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Renames, retires and relinks moved storefront pages.
 */
final class MovedPagesMigration {

	/** Old footer label for the About page, from the theme's first footer. */
	private const OLD_FOOTER_LABEL = '>About &amp; contact</a>';

	/**
	 * Before the pages are installed: rename moved pages, so they keep their ID and content.
	 *
	 * @return array<int, array{type: string, text: string}> Report lines.
	 */
	public static function rename_pages(): array {
		$report = array();

		foreach ( PageList::RENAMED as $old_slug => list( $new_slug, $title ) ) {
			$old = get_page_by_path( $old_slug, OBJECT, 'page' );
			if ( ! $old ) {
				continue;
			}
			if ( get_page_by_path( $new_slug, OBJECT, 'page' ) ) {
				$report[] = self::line( 'warning', "Both /{$old_slug}/ and /{$new_slug}/ exist, so /{$old_slug}/ (ID {$old->ID}) was left alone. Its URL redirects to /{$new_slug}/." );
				continue;
			}

			$result = wp_update_post(
				array(
					'ID'         => $old->ID,
					'post_name'  => $new_slug,
					'post_title' => $title,
				),
				true
			);
			$report[] = is_wp_error( $result )
				? self::line( 'warning', "Could not rename /{$old_slug}/: " . $result->get_error_message() )
				: self::line( 'updated', "Page /{$old_slug}/ (ID {$old->ID}) renamed to /{$new_slug}/ \"{$title}\"; the old URL redirects (301)" );
		}

		return $report;
	}

	/**
	 * After the pages and menus are installed: retire pages, fix the Storefront menu, relink old URLs.
	 *
	 * @param callable $is_untouched fn( \WP_Post $page ): bool, true if nobody edited the page's starter content.
	 * @return array<int, array{type: string, text: string}> Report lines.
	 */
	public static function tidy_up( callable $is_untouched ): array {
		return array_merge(
			self::retire_pages( $is_untouched ),
			self::update_storefront_menu(),
			self::relink_saved_content( $is_untouched )
		);
	}

	/**
	 * Move retired pages to the Trash (recoverable), unless someone edited them.
	 *
	 * @param callable $is_untouched See tidy_up().
	 * @return array<int, array{type: string, text: string}>
	 */
	private static function retire_pages( callable $is_untouched ): array {
		$report = array();

		foreach ( PageList::RETIRED as $slug ) {
			$page = get_page_by_path( $slug, OBJECT, 'page' );
			if ( ! $page || 'trash' === $page->post_status ) {
				continue;
			}
			if ( ! $is_untouched( $page ) ) {
				$report[] = self::line( 'warning', "Page /{$slug}/ (ID {$page->ID}) has been edited, so it was left alone. Trash it by hand; its URL already redirects." );
				continue;
			}

			wp_trash_post( $page->ID );
			$report[] = self::line( 'updated', "Page /{$slug}/ (ID {$page->ID}) moved to Trash; its URL redirects (301)" );
		}

		return $report;
	}

	/**
	 * Storefront menu: one "About Us" link where the About/Contact link was, and no Enquire link.
	 *
	 * @return array<int, array{type: string, text: string}>
	 */
	private static function update_storefront_menu(): array {
		$menu_id = PortalMenus::find( Portals::STOREFRONT );
		$about   = get_page_by_path( 'about-us', OBJECT, 'page' );
		if ( $menu_id <= 0 || ! $about ) {
			return array();
		}

		$menu    = get_post( $menu_id );
		$blocks  = parse_blocks( (string) $menu->post_content );
		$kept    = array();
		$has_one = false;

		foreach ( $blocks as $block ) {
			if ( 'core/navigation-link' === $block['blockName'] ) {
				$path = trim( (string) wp_parse_url( (string) ( $block['attrs']['url'] ?? '' ), PHP_URL_PATH ), '/' );
				$id   = (int) ( $block['attrs']['id'] ?? 0 );

				if ( 'enquire' === $path ) {
					continue; // The enquiry form is on About Us now.
				}
				if ( in_array( $path, array( 'about-contact', 'about-us' ), true ) || $id === (int) $about->ID ) {
					if ( $has_one ) {
						continue; // Only one About Us link.
					}
					$has_one        = true;
					$block['attrs'] = array_merge(
						$block['attrs'],
						array(
							'label' => 'About Us',
							'type'  => 'page',
							'id'    => (int) $about->ID,
							'url'   => get_permalink( $about ),
							'kind'  => 'post-type',
						)
					);
				}
			} elseif ( null === $block['blockName'] && '' === trim( $block['innerHTML'] ) && $kept && null === end( $kept )['blockName'] ) {
				continue; // Don't leave two blank gaps where a link was removed.
			}
			$kept[] = $block;
		}

		$content = serialize_blocks( $kept );
		if ( $content === $menu->post_content ) {
			return array();
		}

		// wp_update_post() strips backslashes, which would break escapes in block attributes.
		wp_update_post( wp_slash( array( 'ID' => $menu_id, 'post_content' => $content ) ) );
		return array( self::line( 'updated', "Menu 'Storefront' (ID {$menu_id}): one \"About Us\" link to /about-us/, no Enquire link; other items unchanged" ) );
	}

	/**
	 * Point old links at the new addresses in saved content: Site Editor copies of
	 * template parts, Navigation menus, and pages. Only the links change.
	 *
	 * @param callable $is_untouched See tidy_up(). An untouched page keeps counting as untouched.
	 * @return array<int, array{type: string, text: string}>
	 */
	private static function relink_saved_content( callable $is_untouched ): array {
		$report = array();
		$posts  = get_posts(
			array(
				'post_type'   => array( 'wp_template_part', 'wp_template', 'wp_navigation', 'page' ),
				'post_status' => array( 'publish', 'draft', 'private', 'future' ),
				'numberposts' => -1,
			)
		);

		foreach ( $posts as $post ) {
			$content = MovedPages::rewrite_links( (string) $post->post_content, home_url() );
			if ( in_array( $post->post_type, array( 'wp_template_part', 'wp_template' ), true ) ) {
				$content = str_replace( self::OLD_FOOTER_LABEL, '>About Us</a>', $content );
			}
			if ( $content === $post->post_content ) {
				continue;
			}

			$untouched = 'page' === $post->post_type && $is_untouched( $post );
			wp_update_post( wp_slash( array( 'ID' => $post->ID, 'post_content' => $content ) ) );
			if ( $untouched ) {
				PageInstaller::remember_starter( $post->ID, $content );
			}
			$report[] = self::line( 'updated', "Links to moved pages updated in {$post->post_type} '{$post->post_name}' (ID {$post->ID}); nothing else changed" );
		}

		return $report;
	}

	/**
	 * One report line.
	 *
	 * @param string $type created | exists | updated | warning.
	 * @param string $text Message.
	 * @return array{type: string, text: string}
	 */
	private static function line( string $type, string $text ): array {
		return array(
			'type' => $type,
			'text' => $text,
		);
	}
}
