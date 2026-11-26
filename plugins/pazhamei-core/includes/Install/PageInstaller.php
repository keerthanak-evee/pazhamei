<?php
/**
 * PageInstaller: creates the site's pages and Navigation menus.
 *
 * Run through `wp pz install-pages`. It is safe to run many times:
 * - A page that already exists (same path) is never edited, with two narrow exceptions
 *   (see tidy_existing_page()): a missing author is set, and placeholder text that is
 *   still exactly as an older installer wrote it is refreshed.
 * - New pages are owned by the first administrator.
 * - A menu that already exists is never changed.
 * - Site settings (front page, WooCommerce My Account page) are set every run.
 *
 * Every step adds a line to the report, which the WP-CLI command prints.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Access\PagePortal;
use Pazhamei\Access\PortalMenus;
use Pazhamei\Access\Portals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Creates pages, menus and the related site settings.
 */
final class PageInstaller {

	/** Template used by customer, kitchen and admin pages. */
	private const PORTAL_TEMPLATE = 'page-portal';

	/**
	 * Lines describing what happened: array( 'type' => 'created|exists|updated|warning', 'text' => '...' ).
	 *
	 * @var array<int, array{type: string, text: string}>
	 */
	private array $report = array();

	/**
	 * Run every step and return the report.
	 *
	 * @return array<int, array{type: string, text: string}>
	 */
	public function run(): array {
		$this->ensure_pretty_permalinks();
		$this->report = array_merge( $this->report, MediaImporter::run() ); // Photos first: the Home content uses them.
		$this->prepare_privacy_page();
		$this->install_pages();
		$this->link_privacy_page();
		$this->install_menus();
		$this->set_front_page();
		$this->set_woocommerce_account_page();
		$this->set_site_logo();

		return $this->report;
	}

	/**
	 * Create every missing page from PageList::pages().
	 */
	private function install_pages(): void {
		foreach ( PageList::pages() as $page ) {
			$path     = self::path( $page );
			$existing = get_page_by_path( $path, OBJECT, 'page' );

			if ( $existing ) {
				$this->note( 'exists', "Page /{$path}/ (ID {$existing->ID})" );
				$this->tidy_existing_page( $existing, $page, $path );
				if ( 'publish' !== $existing->post_status ) {
					$this->note( 'warning', "Page /{$path}/ is '{$existing->post_status}', not published. Publish it in the editor when ready." );
				}
				continue;
			}

			$parent_id = 0;
			if ( '' !== $page['parent'] ) {
				$parent    = get_page_by_path( $page['parent'], OBJECT, 'page' );
				$parent_id = $parent ? (int) $parent->ID : 0;
			}

			$is_portal = Portals::requires_login( $page['portal'] );

			$page_id = wp_insert_post(
				array(
					'post_type'     => 'page',
					'post_status'   => 'publish',
					'post_author'   => self::first_admin_id(),
					'post_title'    => $page['title'],
					'post_name'     => $page['slug'],
					'post_parent'   => $parent_id,
					'post_content'  => self::starter_content( $page ),
					'page_template' => $is_portal ? self::PORTAL_TEMPLATE : '',
					'meta_input'    => array( PagePortal::META_KEY => $page['portal'] ),
				),
				true
			);

			if ( is_wp_error( $page_id ) ) {
				$this->note( 'warning', "Could not create /{$path}/: " . $page_id->get_error_message() );
				continue;
			}

			$this->note( 'created', "Page /{$path}/ (ID {$page_id}, portal: {$page['portal']})" );
		}
	}

	/**
	 * Two small fixes on a page that already exists. Edited pages are never touched:
	 * - No author (made by an older version of this command): set the first administrator.
	 * - Content still exactly as an older version of this installer wrote it: replace it
	 *   with the current starter content.
	 *
	 * @param \WP_Post                                                                          $existing The page.
	 * @param array{slug: string, title: string, parent: string, portal: string, phase: string} $page     Page row.
	 * @param string                                                                            $path     Page path, for the report.
	 */
	private function tidy_existing_page( \WP_Post $existing, array $page, string $path ): void {
		$changes = array();

		if ( 0 === (int) $existing->post_author && self::first_admin_id() > 0 ) {
			$changes['post_author'] = self::first_admin_id();
		}

		$current = self::normalise( $existing->post_content );
		$fresh   = self::starter_content( $page );
		foreach ( self::old_starter_contents( $page ) as $old ) {
			if ( self::normalise( $old ) === $current && self::normalise( $fresh ) !== $current ) {
				$changes['post_content'] = $fresh;
				break;
			}
		}

		if ( ! $changes ) {
			return;
		}

		$result = wp_update_post( wp_slash( array( 'ID' => $existing->ID ) + $changes ), true );

		if ( is_wp_error( $result ) ) {
			$this->note( 'warning', "Could not update /{$path}/: " . $result->get_error_message() );
			return;
		}

		$what = array();
		if ( isset( $changes['post_author'] ) ) {
			$what[] = 'author set to the first administrator';
		}
		if ( isset( $changes['post_content'] ) ) {
			$what[] = 'placeholder text refreshed';
		}
		$this->note( 'updated', "Page /{$path}/: " . implode( ', ', $what ) );
	}

	/**
	 * ID of the first administrator (lowest user ID), or 0 if there is none.
	 */
	private static function first_admin_id(): int {
		$ids = get_users(
			array(
				'role'    => 'administrator',
				'orderby' => 'ID',
				'order'   => 'ASC',
				'number'  => 1,
				'fields'  => 'ID',
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Make two pieces of content comparable: same line endings, no outer whitespace.
	 *
	 * @param string $content Post content.
	 */
	private static function normalise( string $content ): string {
		return trim( str_replace( "\r\n", "\n", $content ) );
	}

	/**
	 * Create every missing Navigation menu from PageList::menus().
	 */
	private function install_menus(): void {
		foreach ( PageList::menus() as $portal => $items ) {
			$title    = PortalMenus::TITLES[ $portal ];
			$existing = PortalMenus::find( $portal );

			if ( $existing > 0 ) {
				PortalMenus::save( $portal, $existing ); // Remember it, in case it was found by title.
				$this->note( 'exists', "Menu '{$title}' (ID {$existing})" );
				continue;
			}

			$menu_id = wp_insert_post(
				array(
					'post_type'    => 'wp_navigation',
					'post_status'  => 'publish',
					'post_title'   => $title,
					// wp_insert_post() strips backslashes, which would break escapes like & (&) in block attributes.
					'post_content' => wp_slash( self::menu_content( $items ) ),
				),
				true
			);

			if ( is_wp_error( $menu_id ) ) {
				$this->note( 'warning', "Could not create menu '{$title}': " . $menu_id->get_error_message() );
				continue;
			}

			PortalMenus::save( $portal, (int) $menu_id );
			$this->note( 'created', "Menu '{$title}' (ID {$menu_id}) with " . count( $items ) . ' links' );
		}
	}

	/**
	 * Make the Home page the site's front page.
	 */
	private function set_front_page(): void {
		$home = get_page_by_path( 'home', OBJECT, 'page' );
		if ( ! $home ) {
			$this->note( 'warning', 'No /home/ page, so the front page was not set.' );
			return;
		}

		update_option( 'show_on_front', 'page' );
		update_option( 'page_on_front', (int) $home->ID );
		$this->note( 'updated', "Front page set to Home (ID {$home->ID})" );
	}

	/**
	 * Point WooCommerce's My Account page at /sign-in, so its sign-in form lives there.
	 */
	private function set_woocommerce_account_page(): void {
		$sign_in = get_page_by_path( 'sign-in', OBJECT, 'page' );
		if ( ! $sign_in ) {
			$this->note( 'warning', 'No /sign-in/ page, so the WooCommerce My Account page was not set.' );
			return;
		}

		update_option( 'woocommerce_myaccount_page_id', (int) $sign_in->ID );
		$this->note( 'updated', "WooCommerce My Account page set to Sign in (ID {$sign_in->ID})" );

		$this->retire_old_account_page();

		// WooCommerce endpoints (/sign-in/orders/, /checkout/order-received/...) are rewrite
		// rules built from its page settings, so rebuild them now that the pages exist.
		flush_rewrite_rules();
	}

	/**
	 * WooCommerce creates its own /my-account/ page. We use /sign-in/ instead, so move the
	 * old page to the Trash (recoverable), but only if nobody has edited it. /my-account/
	 * links are redirected to /sign-in/ by Woo\ShopRedirects.
	 */
	private function retire_old_account_page(): void {
		$old = get_page_by_path( 'my-account', OBJECT, 'page' );
		if ( ! $old || 'trash' === $old->post_status ) {
			return;
		}

		// WooCommerce's untouched page holds only its My Account shortcode or block.
		$content      = trim( wp_strip_all_tags( preg_replace( '/<!--.*?-->/s', '', $old->post_content ) ) );
		$is_untouched = in_array( $content, array( '', '[woocommerce_my_account]' ), true );

		if ( ! $is_untouched ) {
			$this->note( 'warning', "Page /my-account/ (ID {$old->ID}) has been edited, so it was left alone. Trash it by hand; its URL already redirects to /sign-in/." );
			return;
		}

		wp_trash_post( $old->ID );
		$this->note( 'updated', "Old WooCommerce page /my-account/ (ID {$old->ID}) moved to Trash; its URL redirects to /sign-in/" );
	}

	/**
	 * Portal URLs like /account/dashboard need "pretty" permalinks.
	 */
	private function ensure_pretty_permalinks(): void {
		if ( '' !== (string) get_option( 'permalink_structure' ) ) {
			return;
		}

		global $wp_rewrite;
		$wp_rewrite->set_permalink_structure( '/%postname%/' );
		flush_rewrite_rules();
		$this->note( 'updated', 'Permalinks set to /%postname%/' );
	}

	/**
	 * Use the theme's logo as the site logo, if no logo is set yet.
	 */
	private function set_site_logo(): void {
		if ( (int) get_option( 'site_logo' ) > 0 ) {
			return;
		}

		$source = get_theme_file_path( 'assets/images/logo.jpg' );
		if ( ! file_exists( $source ) ) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		// media_handle_sideload() moves the file, so give it a temporary copy.
		$temp = wp_tempnam( 'pazhamei-logo.jpg' );
		copy( $source, $temp );

		$logo_id = media_handle_sideload(
			array(
				'name'     => 'pazhamei-logo.jpg',
				'tmp_name' => $temp,
			),
			0,
			'Pazhamei logo'
		);

		if ( is_wp_error( $logo_id ) ) {
			wp_delete_file( $temp );
			$this->note( 'warning', 'Could not import the logo: ' . $logo_id->get_error_message() );
			return;
		}

		update_option( 'site_logo', (int) $logo_id );
		$this->note( 'created', "Site logo imported from the theme (attachment ID {$logo_id})" );
	}

	/**
	 * WordPress makes its own draft Privacy Policy page (the one in Settings > Privacy).
	 * Use that page rather than making a second one: give it the slug `privacy-policy`,
	 * the storefront portal, our draft text (only if it still has WordPress's sample
	 * text), and publish it.
	 */
	private function prepare_privacy_page(): void {
		$page = get_post( (int) get_option( 'wp_page_for_privacy_policy' ) );
		if ( ! $page || 'page' !== $page->post_type || 'trash' === $page->post_status ) {
			return; // No WordPress privacy page: install_pages() creates /privacy-policy/ and link_privacy_page() links it.
		}

		$changes = array();

		if ( 'privacy-policy' !== $page->post_name || 0 !== (int) $page->post_parent ) {
			$taken = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
			if ( $taken && (int) $taken->ID !== (int) $page->ID ) {
				$this->note( 'warning', "The WordPress privacy page (ID {$page->ID}) can't take /privacy-policy/: page ID {$taken->ID} already uses it. Choose one in Settings > Privacy and delete the other." );
				return;
			}
			$changes['post_name']   = 'privacy-policy';
			$changes['post_parent'] = 0;
		}

		// WordPress's sample text always contains "Suggested text:". Replace only that.
		if ( str_contains( $page->post_content, 'Suggested text:' ) ) {
			$changes['post_content'] = (string) PolicyContent::for_path( 'privacy-policy' );
		}

		if ( 'publish' !== $page->post_status ) {
			$changes['post_status'] = 'publish';
		}
		if ( 0 === (int) $page->post_author && self::first_admin_id() > 0 ) {
			$changes['post_author'] = self::first_admin_id();
		}

		update_post_meta( $page->ID, PagePortal::META_KEY, Portals::STOREFRONT );

		if ( $changes ) {
			$result = wp_update_post( wp_slash( array( 'ID' => $page->ID ) + $changes ), true );
			if ( is_wp_error( $result ) ) {
				$this->note( 'warning', 'Could not update the privacy page: ' . $result->get_error_message() );
				return;
			}
			$this->note( 'updated', "Privacy page (ID {$page->ID}): " . implode( ', ', array_keys( $changes ) ) . ' updated' );
		}
	}

	/**
	 * Make sure Settings > Privacy points at /privacy-policy/, and warn about duplicates.
	 */
	private function link_privacy_page(): void {
		$page = get_page_by_path( 'privacy-policy', OBJECT, 'page' );
		if ( $page && (int) get_option( 'wp_page_for_privacy_policy' ) !== (int) $page->ID ) {
			update_option( 'wp_page_for_privacy_policy', (int) $page->ID );
			$this->note( 'updated', "Settings > Privacy now points at /privacy-policy/ (ID {$page->ID})" );
		}

		foreach ( array( 'privacy-policy-2', 'privacy-policy-3' ) as $duplicate ) {
			$extra = get_page_by_path( $duplicate, OBJECT, 'page' );
			if ( $extra && 'trash' !== $extra->post_status ) {
				$this->note( 'warning', "Duplicate privacy page /{$duplicate}/ (ID {$extra->ID}). Delete it in the editor." );
			}
		}
	}

	/**
	 * Starter block content for a new page. Storefront pages get real content
	 * (StorefrontContent); the others get one paragraph saying which phase fills them.
	 * No heading: the page templates already show the page title.
	 *
	 * @param array{slug: string, title: string, parent: string, portal: string, phase: string} $page Page row.
	 */
	private static function starter_content( array $page ): string {
		return StorefrontContent::for_path( self::path( $page ) ) ?? self::placeholder( $page, $page['phase'], false );
	}

	/**
	 * Starter content written by older versions of this installer. A page whose content
	 * still matches one of these exactly has never been edited, so it may be refreshed.
	 *
	 * @param array{slug: string, title: string, parent: string, portal: string, phase: string} $page Page row.
	 * @return string[]
	 */
	private static function old_starter_contents( array $page ): array {
		// Version 0.1.0: a heading with the title, and the phase numbers of the old PDF plan.
		$old_phases = array(
			Portals::STOREFRONT => 'Phase 1 (Storefront)',
			Portals::CUSTOMER   => 'Phase 3 (Customer portal)',
			Portals::KITCHEN    => 'Phase 6 (Kitchen portal)',
			Portals::ADMIN      => 'Phase 7 (Admin portal)',
		);

		return array(
			self::placeholder( $page, $old_phases[ $page['portal'] ], true ),
			// Version 0.1.x (Phase 0): one placeholder paragraph, replaced by real content in Phase 1.
			self::placeholder( $page, $page['phase'], false ),
		);
	}

	/**
	 * Build placeholder block content.
	 *
	 * @param array{slug: string, title: string} $page         Page row.
	 * @param string                             $phase        Phase name, e.g. 'Phase 4 (Customer portal)'.
	 * @param bool                               $with_heading Add a heading with the title (old versions only).
	 */
	private static function placeholder( array $page, string $phase, bool $with_heading ): string {
		$content = '';

		if ( $with_heading ) {
			$content .= "<!-- wp:heading {\"level\":2} -->\n";
			$content .= '<h2 class="wp-block-heading">' . esc_html( $page['title'] ) . "</h2>\n";
			$content .= "<!-- /wp:heading -->\n\n";
		}

		$content .= "<!-- wp:paragraph -->\n";
		$content .= '<p>' . esc_html( "This page is a placeholder. Its content is built in {$phase}." ) . "</p>\n";
		$content .= "<!-- /wp:paragraph -->\n";

		// The sign-in page needs a working form now, for testing the portals.
		if ( 'sign-in' === $page['slug'] ) {
			$content .= "\n<!-- wp:shortcode -->\n[woocommerce_my_account]\n<!-- /wp:shortcode -->\n";
		}

		return $content;
	}

	/**
	 * Block content for a Navigation menu: one navigation-link block per page.
	 *
	 * @param array<string, string> $items Label => page path.
	 */
	private static function menu_content( array $items ): string {
		$blocks = array();

		foreach ( $items as $label => $path ) {
			$page = get_page_by_path( $path, OBJECT, 'page' );
			if ( ! $page ) {
				continue;
			}

			$blocks[] = serialize_block(
				array(
					'blockName'    => 'core/navigation-link',
					'attrs'        => array(
						'label' => $label,
						'type'  => 'page',
						'id'    => (int) $page->ID,
						'url'   => get_permalink( $page ),
						'kind'  => 'post-type',
					),
					'innerBlocks'  => array(),
					'innerHTML'    => '',
					'innerContent' => array(),
				)
			);
		}

		return implode( "\n", $blocks );
	}

	/**
	 * Full path of a page row, e.g. 'account/dashboard'.
	 *
	 * @param array{slug: string, parent: string} $page Page row.
	 */
	private static function path( array $page ): string {
		return '' === $page['parent'] ? $page['slug'] : $page['parent'] . '/' . $page['slug'];
	}

	/**
	 * Add a line to the report.
	 *
	 * @param string $type created | exists | updated | warning.
	 * @param string $text Message.
	 */
	private function note( string $type, string $text ): void {
		$this->report[] = array(
			'type' => $type,
			'text' => $text,
		);
	}
}
