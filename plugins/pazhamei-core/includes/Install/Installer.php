<?php
/**
 * Installer: sets up the plugin's data when it is activated or upgraded.
 *
 * It creates the roles, the `pz_settings` option and the wp_pz_* tables (with
 * dbDelta, which adds missing tables and columns without touching data).
 * Every schema change must bump PZ_VERSION: the check below then upgrades
 * any site on its next page load, without deactivating the plugin.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Clock;
use Pazhamei\Cron\Scheduler;
use Pazhamei\Repositories\AllergenRepository;
use Pazhamei\Repositories\EnquiryRepository;
use Pazhamei\Repositories\SchemaRepository;
use Pazhamei\Repositories\Stores;
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
	 * Run the upgrade check. Called from Plugin::boot(), which runs on plugins_loaded,
	 * so new tables exist before any other module needs them.
	 */
	public static function register(): void {
		self::maybe_upgrade();
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
		Scheduler::unschedule_all(); // Re-created on the next page load after activation.
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
		self::install_tables();

		// add_option() does nothing if the option already exists, so saved settings are never overwritten.
		add_option( Settings::OPTION, Settings::defaults() );
		Settings::upgrade();

		// Starter allergen list (Phase 4); existing rows are never changed.
		AllergenRepository::seed( Clock::now()->format( 'Y-m-d H:i:s' ) );

		update_option( self::VERSION_OPTION, PZ_VERSION );
	}

	/**
	 * Create or upgrade every wp_pz_* table. dbDelta compares each CREATE TABLE
	 * statement with the real table and only adds what is missing.
	 * Every table must be InnoDB (transactions and row locks need it).
	 */
	private static function install_tables(): void {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		dbDelta( EnquiryRepository::schema() );
		dbDelta( Stores::schemas() );

		SchemaRepository::convert_to_innodb();
	}
}
