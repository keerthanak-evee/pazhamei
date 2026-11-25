<?php
/**
 * Pazhamei theme setup.
 *
 * Design only: this file loads the theme stylesheet. Business logic
 * belongs in the pazhamei-core plugin, never here.
 *
 * @package pazhamei-theme
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Load style.css on the front end and inside the block editor.
 */
function pz_theme_enqueue_styles() {
	$version = wp_get_theme()->get( 'Version' );
	wp_enqueue_style( 'pazhamei-theme', get_stylesheet_uri(), array(), $version );
}
add_action( 'wp_enqueue_scripts', 'pz_theme_enqueue_styles' );

/**
 * Let the block editor use the same small extras as the front end.
 */
function pz_theme_editor_styles() {
	add_editor_style( 'style.css' );
}
add_action( 'after_setup_theme', 'pz_theme_editor_styles' );
