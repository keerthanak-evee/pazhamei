<?php
/**
 * MediaImporter: puts the storefront photos into the Media Library.
 *
 * Run by `wp pz install-pages`. Each photo is read from the design folder
 * (wp-content/design/Images), shrunk to web size (at most 1600 px wide, WebP),
 * and imported once. It is tagged with the `_pz_media_key` meta, which the
 * theme's patterns and the pz/plan-cards block use to find it.
 *
 * The design folder is never deployed, so on staging and production the
 * photos arrive with the content migration instead (Phase 2 / Phase 10).
 * There, this step only reports which photos are missing.
 *
 * Decorative images (textures, borders) are not here: they live in the theme.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Imports content photos.
 */
final class MediaImporter {

	/** Meta key that names each imported photo. */
	public const META_KEY = '_pz_media_key';

	/** Longest side allowed for imported photos, in pixels. */
	private const MAX_WIDTH = 1600;

	/**
	 * Photos to import: key => array( file in the design folder, alt text ). Alt '' means decorative.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function photos(): array {
		return array(
			'hero-lunch'             => array( 'lunch.jpg', 'A banana-leaf lunch with rice, fish fry and curries' ),
			'vision-kitchen'         => array( 'explore-kitchen.webp', 'Illustration of a Tamil kitchen with brass pots, baskets and a tiffin carrier' ),
			'meal-veg'               => array( 'vegetarian.jpg', 'A vegetarian lunch with greens, poriyal and chapati' ),
			'meal-nonveg'            => array( 'coastal.jpg', 'A non-vegetarian lunch with fish curry, fried fish and rice' ),
			'meal-mixed'             => array( 'mixed.jpg', 'Lunch boxes with rice, dal and curries' ),
			'plan-tasting'           => array( 'plan-tasting.webp', 'Foxtail millet, black chickpea sundal, green beans, sambar and curd' ),
			'plan-weekly'            => array( 'plan-weekly.webp', 'Red rice with pepper chicken, green gram dal and cabbage-carrot poriyal' ),
			'plan-monthly'           => array( 'plan-monthly.webp', 'Little millet and ragi chapathis with paneer, dal, greens and beetroot poriyal' ),
			'cooking-flavoured-rice' => array( 'cooking-flavoured-rice.webp', '' ),
			'cooking-biryani'        => array( 'cooking-biryani.webp', '' ),
			'cooking-red-rice'       => array( 'cooking-red-rice.webp', '' ),
			'cooking-chapati'        => array( 'cooking-chapati.webp', '' ),
			'cooking-millet-rice'    => array( 'cooking-millet-rice.webp', '' ),
			'cooking-ponni-rice'     => array( 'cooking-ponni-rice.webp', '' ),
			'balance-grains'         => array( 'rice-and-lentils.jpg', 'Millet rice in a brass bowl beside red and yellow lentils' ),
			'journey-select'         => array( 'journey-select.webp', '' ),
			'journey-kitchen'        => array( 'journey-kitchen.webp', '' ),
			'journey-delivery'       => array( 'journey-delivery.webp', '' ),
			'cta-feast'              => array( 'explore-meal.webp', 'Illustrated banana-leaf lunch with rice, curries, curd and a brass tumbler' ),
			// Plans, About Us and FAQ (prototype-v2).
			'plans-hero'             => array( 'plans-hero-tray.jpg', 'A lunch tray of red rice, pepper chicken, vegetables, dal and fruit' ),
			'about-vessels'          => array( 'story-vessels.jpg', 'Brass cooking vessels beside clay pots, cardamom and star anise' ),
			'about-spices'           => array( 'story-spice-table.jpg', 'A brass pot, a banana leaf and bowls of ground spices' ),
			'contact-spices'         => array( 'kitchen-spices.jpg', 'Spices used in Tamil cooking' ),
			'faq-clock'              => array( 'faq-clock.jpg', 'A clock showing 8:00 PM IST' ),
			// Sign in / Register (Login&RegisterPAgeReference.jpg) and the customer portal.
			'signin-thali'           => array( 'signin-thali.jpg', 'A brass thali on a banana leaf with rice, greens and a fry, beside a tiffin carrier' ),
			'member-tiffin'          => array( 'member-brass-tiffin.jpg', 'A brass tiffin carrier' ),
		);
	}

	/**
	 * Attachment ID of an imported photo, or 0.
	 *
	 * @param string $key Media key.
	 */
	public static function find( string $key ): int {
		$ids = get_posts(
			array(
				'post_type'      => 'attachment',
				'post_status'    => 'inherit',
				'posts_per_page' => 1,
				'fields'         => 'ids',
				'meta_key'       => self::META_KEY, // phpcs:ignore WordPress.DB.SlowDBQuery
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
			)
		);

		return $ids ? (int) $ids[0] : 0;
	}

	/**
	 * Import every missing photo.
	 *
	 * @return array<int, array{type: string, text: string}> Report lines.
	 */
	public static function run(): array {
		$report = array();
		$folder = trailingslashit( WP_CONTENT_DIR ) . 'design/Images/';

		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';
		require_once ABSPATH . 'wp-admin/includes/image.php';

		foreach ( self::photos() as $key => list( $file, $alt ) ) {
			$existing = self::find( $key );
			if ( $existing > 0 ) {
				$report[] = self::line( 'exists', "Photo '{$key}' (attachment {$existing})" );
				continue;
			}

			$source = $folder . $file;
			if ( ! file_exists( $source ) ) {
				$report[] = self::line( 'warning', "Photo '{$key}' is not in the Media Library and {$file} is not in design/Images. Upload it by hand." );
				continue;
			}

			$result   = self::import( $key, $source, $alt );
			$report[] = is_wp_error( $result )
				? self::line( 'warning', "Could not import '{$key}': " . $result->get_error_message() )
				: self::line( 'created', "Photo '{$key}' imported (attachment {$result})" );
		}

		return $report;
	}

	/**
	 * Shrink one photo to web size and add it to the Media Library.
	 *
	 * @param string $key    Media key.
	 * @param string $source Full path of the original.
	 * @param string $alt    Alt text.
	 * @return int|\WP_Error Attachment ID.
	 */
	private static function import( string $key, string $source, string $alt ) {
		$editor = wp_get_image_editor( $source );
		if ( is_wp_error( $editor ) ) {
			return $editor;
		}

		$size = $editor->get_size();
		if ( $size['width'] > self::MAX_WIDTH ) {
			$editor->resize( self::MAX_WIDTH, null );
		}
		$editor->set_quality( 80 );

		$temp  = wp_tempnam( "pazhamei-{$key}" );
		$saved = $editor->save( $temp . '.webp', 'image/webp' );
		wp_delete_file( $temp ); // wp_tempnam() makes an empty file; we saved next to it.
		if ( is_wp_error( $saved ) ) {
			return $saved;
		}

		// media_handle_sideload() moves the file into uploads and creates the smaller sizes.
		$id = media_handle_sideload(
			array(
				'name'     => "pazhamei-{$key}.webp",
				'tmp_name' => $saved['path'],
			),
			0,
			'' !== $alt ? $alt : ucwords( str_replace( '-', ' ', $key ) )
		);

		if ( is_wp_error( $id ) ) {
			wp_delete_file( $saved['path'] );
			return $id;
		}

		update_post_meta( $id, self::META_KEY, $key );
		update_post_meta( $id, '_wp_attachment_image_alt', $alt );

		return (int) $id;
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
