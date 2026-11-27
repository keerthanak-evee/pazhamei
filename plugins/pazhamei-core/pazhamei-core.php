<?php
/**
 * Plugin Name:       Pazhamei Core
 * Description:       Business logic for the Pazhamei cloud kitchen: portals, roles, access control and the subscription engine.
 * Version:           0.4.1
 * Requires at least: 6.9
 * Requires PHP:      8.1
 * Requires Plugins:  woocommerce
 * Author:            Pazhamei
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       pazhamei-core
 *
 * @package Pazhamei
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plugin version. Bump this whenever the database schema, roles or settings change:
// the installer compares it with the stored `pz_version` option and upgrades.
define( 'PZ_VERSION', '0.4.1' );

// Handy paths, used by the rest of the plugin.
define( 'PZ_CORE_FILE', __FILE__ );
define( 'PZ_CORE_DIR', plugin_dir_path( __FILE__ ) );
define( 'PZ_CORE_URL', plugin_dir_url( __FILE__ ) );

// Load classes from includes/ automatically (Pazhamei\Access\PortalGuard -> includes/Access/PortalGuard.php).
require_once PZ_CORE_DIR . 'includes/Autoloader.php';
Pazhamei\Autoloader::register( PZ_CORE_DIR . 'includes/' );

// Runs once when the plugin is activated / deactivated in Plugins screen or with `wp plugin activate`.
register_activation_hook( __FILE__, array( Pazhamei\Install\Installer::class, 'activate' ) );
register_deactivation_hook( __FILE__, array( Pazhamei\Install\Installer::class, 'deactivate' ) );

// Tell WooCommerce we work with High-Performance Order Storage (HPOS). We never read
// orders from the posts table directly, only through WooCommerce's order functions.
add_action(
	'before_woocommerce_init',
	static function () {
		if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
			\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', __FILE__, true );
		}
	}
);

// Start every module once all plugins (including WooCommerce) are loaded.
add_action( 'plugins_loaded', array( Pazhamei\Plugin::class, 'boot' ) );
