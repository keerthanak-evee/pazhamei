<?php
/**
 * Uninstall: runs when the plugin is deleted from the Plugins screen.
 *
 * Removes our roles, capabilities and options. Pages and Navigation menus
 * are content, so they are left alone.
 *
 * @package Pazhamei
 */

// WordPress defines this constant only during a real uninstall.
if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/Autoloader.php';
Pazhamei\Autoloader::register( __DIR__ . '/includes/' );

Pazhamei\Install\Roles::uninstall();

delete_option( 'pz_settings' );
delete_option( 'pz_version' );
delete_option( 'pz_nav_menus' );
