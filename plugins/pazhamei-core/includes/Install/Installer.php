<?php
/**
 * Installer: sets up the plugin's data when it is activated or upgraded.
 *
 * Phase 0 creates the roles and the `pz_settings` option. Later phases will
 * add the wp_pz_* tables here (using dbDelta), which is why every change
 * must bump PZ_VERSION.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Activation, deactivation and version upgrades.
 */
final class Installer {

	/** Option that stores the version that was last installed. */
	public const VERSION_OPTION = 'pz_version';

	/**
	 * Hook the upgrade check. Called from Plugin::boot().
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'maybe_upgrade' ), 5 );
	}

	/**
	 * Runs when the plugin is activated.
	 */
	public static function activate(): void {
		self::install();
	}

	/**
	 * Runs when the plugin is deactivated. Nothing is deleted here:
	 * roles and settings are only removed on uninstall (see uninstall.php).
	 */
	public static function deactivate(): void {
		flush_rewrite_rules();
	}

	/**
	 * Re-run the install steps if the code is newer than what was installed.
	 */
	public static function maybe_upgrade(): void {
		$installed = (string) get_option( self::VERSION_OPTION, '0' );

		if ( version_compare( $installed, PZ_VERSION, '<' ) ) {
			self::install();
		}
	}

	/**
	 * The install steps. Each step is safe to run again.
	 */
	private static function install(): void {
		Roles::install();

		// add_option() does nothing if the option already exists, so saved settings are never overwritten.
		add_option( Settings::OPTION, Settings::defaults() );

		update_option( self::VERSION_OPTION, PZ_VERSION );
	}
}
