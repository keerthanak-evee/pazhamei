<?php
/**
 * Pazhamei theme setup.
 *
 * Design only: styles, the small motion script, block styles, the pattern
 * category and a helper that finds storefront photos in the Media Library.
 * Business logic belongs in the pazhamei-core plugin, never here.
 *
 * @package pazhamei-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load style.css and the motion files on the front end.
 */
function pz_theme_enqueue_assets() {
	$version = wp_get_theme()->get( 'Version' );

	wp_enqueue_style( 'pazhamei-theme', get_stylesheet_uri(), array(), $version );
	wp_enqueue_style( 'pazhamei-ornaments', get_theme_file_uri( 'assets/css/ornaments.css' ), array( 'pazhamei-theme' ), $version );
	wp_enqueue_style( 'pazhamei-pages', get_theme_file_uri( 'assets/css/pages.css' ), array( 'pazhamei-ornaments' ), $version );
	wp_enqueue_style( 'pazhamei-motion', get_theme_file_uri( 'assets/css/motion.css' ), array( 'pazhamei-theme' ), $version );
	wp_enqueue_script(
		'pazhamei-motion',
		get_theme_file_uri( 'assets/js/motion.js' ),
		array(),
		$version,
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'wp_enqueue_scripts', 'pz_theme_enqueue_assets' );

/**
 * Register the tabs script. It is loaded only on pages that have a Group with the
 * "Tabs" style (see pz_theme_tabs_script()), so other pages don't download it.
 */
function pz_theme_register_tabs() {
	wp_register_script(
		'pazhamei-tabs',
		get_theme_file_uri( 'assets/js/tabs.js' ),
		array(),
		wp_get_theme()->get( 'Version' ),
		array(
			'strategy'  => 'defer',
			'in_footer' => true,
		)
	);
}
add_action( 'init', 'pz_theme_register_tabs' );

/**
 * Load the tabs script when a Group with the "Tabs" style is on the page.
 *
 * @param string $html  Rendered block.
 * @param array  $block Block data.
 * @return string Unchanged HTML.
 */
function pz_theme_tabs_script( $html, $block ) {
	if ( false !== strpos( (string) ( $block['attrs']['className'] ?? '' ), 'is-style-pz-tabs' ) ) {
		wp_enqueue_script( 'pazhamei-tabs' );
	}
	return $html;
}
add_filter( 'render_block_core/group', 'pz_theme_tabs_script', 10, 2 );

/**
 * Switch motion on before the page paints, so revealed blocks don't flash.
 * Nothing is hidden when the visitor prefers reduced motion or the browser is too old.
 */
function pz_theme_motion_flag() {
	wp_print_inline_script_tag(
		"if(window.matchMedia&&!matchMedia('(prefers-reduced-motion: reduce)').matches&&'IntersectionObserver' in window){document.documentElement.classList.add('pz-motion');}"
	);
}
add_action( 'wp_head', 'pz_theme_motion_flag', 1 );

/**
 * Let the block editor use the same styles and ornaments as the front end (but not the motion: nothing hides in the editor).
 */
function pz_theme_editor_styles() {
	add_editor_style( array( 'style.css', 'assets/css/ornaments.css', 'assets/css/pages.css' ) );
}
add_action( 'after_setup_theme', 'pz_theme_editor_styles' );

/**
 * Pattern category for the Home page sections.
 */
function pz_theme_pattern_categories() {
	register_block_pattern_category( 'pazhamei-home', array( 'label' => __( 'Pazhamei: Home sections', 'pazhamei-theme' ) ) );
	register_block_pattern_category( 'pazhamei-pages', array( 'label' => __( 'Pazhamei: Plans, About Us and FAQ sections', 'pazhamei-theme' ) ) );
}
add_action( 'init', 'pz_theme_pattern_categories' );

/**
 * Extra looks for core blocks, picked in the editor's Styles panel. The CSS is in style.css.
 */
function pz_theme_block_styles() {
	$styles = array(
		'core/group'   => array(
			'pz-paper-card'    => __( 'Paper card', 'pazhamei-theme' ),
			'pz-banana-leaf'   => __( 'Banana leaf', 'pazhamei-theme' ),
			'pz-journey-sheet' => __( 'Framed paper sheet', 'pazhamei-theme' ),
			'pz-heritage-top'  => __( 'Heritage border on top', 'pazhamei-theme' ),
			'pz-tile'          => __( 'Tile (cream, on dark bands)', 'pazhamei-theme' ),
			'pz-faq-sheet'     => __( 'FAQ sheet (dashed top edge)', 'pazhamei-theme' ),
			'pz-cutoff-note'   => __( 'Wine panel with gold frame', 'pazhamei-theme' ),
			'pz-enquiry-sheet' => __( 'Enquiry card (oxblood top edge)', 'pazhamei-theme' ),
			'pz-tabs'          => __( 'Tabs (each inner group is a tab)', 'pazhamei-theme' ),
			'pz-auth-panel'    => __( 'Wine welcome panel (sign in)', 'pazhamei-theme' ),
			'pz-auth-card'     => __( 'White form card (sign in)', 'pazhamei-theme' ),
		),
		'core/image'   => array(
			'pz-leaf-corner' => __( 'Leaf corner', 'pazhamei-theme' ),
			'pz-seal'        => __( 'Round seal', 'pazhamei-theme' ),
			'pz-arch'        => __( 'Arch frame', 'pazhamei-theme' ),
			'pz-route-icon'  => __( 'Small round icon', 'pazhamei-theme' ),
		),
		'core/button'  => array(
			'pz-cream'       => __( 'Cream (on dark)', 'pazhamei-theme' ),
			'pz-ghost-light' => __( 'Outline (on dark)', 'pazhamei-theme' ),
		),
		'core/quote'   => array(
			'pz-testimonial' => __( 'Testimonial', 'pazhamei-theme' ),
		),
		'core/columns'   => array(
			'pz-journey' => __( 'Journey steps (dotted line)', 'pazhamei-theme' ),
			'pz-route'   => __( 'Route steps (thin line)', 'pazhamei-theme' ),
		),
		'core/table'     => array(
			'pz-menu-table' => __( 'Menu table', 'pazhamei-theme' ),
			'pz-address'    => __( 'Address rows', 'pazhamei-theme' ),
		),
		'core/separator' => array(
			'pz-leaf' => __( 'Leaf divider', 'pazhamei-theme' ),
		),
	);

	foreach ( $styles as $block => $variations ) {
		foreach ( $variations as $name => $label ) {
			register_block_style(
				$block,
				array(
					'name'  => $name,
					'label' => $label,
				)
			);
		}
	}
}
add_action( 'init', 'pz_theme_block_styles' );

/**
 * Find a storefront photo in the Media Library by its key (for example 'hero-lunch').
 *
 * The pazhamei-core installer imports the photos and tags each one with the
 * `_pz_media_key` meta. Patterns use this to fill in the image ID and URL.
 * Returns id 0 and empty strings when the photo has not been imported.
 *
 * @param string $key  Media key.
 * @param string $size Image size.
 * @return array{id: int, url: string, alt: string}
 */
function pz_theme_image( $key, $size = 'large' ) {
	$ids = get_posts(
		array(
			'post_type'      => 'attachment',
			'post_status'    => 'inherit',
			'posts_per_page' => 1,
			'fields'         => 'ids',
			'meta_key'       => '_pz_media_key', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key -- Small table, runs only when a pattern is loaded.
			'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
		)
	);

	if ( ! $ids ) {
		return array(
			'id'  => 0,
			'url' => '',
			'alt' => '',
		);
	}

	$id = (int) $ids[0];

	return array(
		'id'  => $id,
		'url' => (string) wp_get_attachment_image_url( $id, $size ),
		'alt' => (string) get_post_meta( $id, '_wp_attachment_image_alt', true ),
	);
}

/**
 * Block markup for a core Image block showing a storefront photo.
 *
 * @param string $key       Media key (see pz_theme_image()).
 * @param string $classes   Extra classes, for example 'is-style-pz-seal pz-hover-zoom'.
 * @param string $alt       Alt text override; '' keeps the Media Library alt text, '-' means decorative (empty alt).
 */
function pz_theme_image_block( $key, $classes = '', $alt = '' ) {
	$image = pz_theme_image( $key );
	if ( '-' === $alt ) {
		$alt = '';
	} elseif ( '' === $alt ) {
		$alt = $image['alt'];
	}

	$attrs = array();
	if ( $image['id'] > 0 ) {
		$attrs['id'] = $image['id'];
	}
	$attrs['sizeSlug']        = 'large';
	$attrs['linkDestination'] = 'none';
	if ( '' !== $classes ) {
		$attrs['className'] = $classes;
	}

	$img_class = $image['id'] > 0 ? ' class="wp-image-' . $image['id'] . '"' : '';

	return '<!-- wp:image ' . wp_json_encode( $attrs, JSON_UNESCAPED_SLASHES ) . " -->\n"
		. '<figure class="wp-block-image size-large' . ( '' !== $classes ? ' ' . esc_attr( $classes ) : '' ) . '">'
		. '<img src="' . esc_url( $image['url'] ) . '" alt="' . esc_attr( $alt ) . '"' . $img_class . '/></figure>'
		. "\n<!-- /wp:image -->";
}
