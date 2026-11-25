<?php
/**
 * PortalMeta: the `_pz_portal` page setting and its "Portal" dropdown in the block editor sidebar.
 *
 * The setting is saved through the REST API like any other page field, so it
 * is registered with show_in_rest. Because it controls who can open the
 * page, only users who can manage Pazhamei settings may change it.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Access;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the meta field and loads the sidebar panel script.
 */
final class PortalMeta {

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_meta' ) );
		add_action( 'enqueue_block_editor_assets', array( self::class, 'enqueue_panel' ) );
	}

	/**
	 * Tell WordPress about the `_pz_portal` field on pages.
	 */
	public static function register_meta(): void {
		register_post_meta(
			'page',
			PagePortal::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => true,
				'sanitize_callback' => array( self::class, 'sanitize' ),
				'auth_callback'     => array( self::class, 'can_edit' ),
			)
		);
	}

	/**
	 * Accept only known portal names; anything else is saved as '' (inherit from parent).
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function sanitize( $value ): string {
		$value = sanitize_key( (string) $value );
		return Portals::is_valid( $value ) ? $value : '';
	}

	/**
	 * Who may change a page's portal.
	 *
	 * @param bool   $allowed  Unused default.
	 * @param string $meta_key Meta key.
	 * @param int    $post_id  Page ID.
	 */
	public static function can_edit( $allowed, $meta_key, $post_id ): bool {
		return current_user_can( 'edit_post', $post_id ) && current_user_can( 'pz_manage_settings' );
	}

	/**
	 * Load the sidebar panel script, only when editing a page.
	 */
	public static function enqueue_panel(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'page' !== $screen->post_type ) {
			return;
		}

		wp_enqueue_script(
			'pz-portal-panel',
			PZ_CORE_URL . 'assets/js/portal-panel.js',
			array( 'wp-plugins', 'wp-editor', 'wp-components', 'wp-data', 'wp-element', 'wp-core-data' ),
			PZ_VERSION,
			true
		);
	}
}
