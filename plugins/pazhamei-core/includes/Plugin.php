<?php
/**
 * Plugin: the starting point that switches on every module.
 *
 * Each module has a static register() method that adds its WordPress hooks.
 * Adding a new module later means adding one line to boot().
 *
 * @package Pazhamei
 */

namespace Pazhamei;

use Pazhamei\Access\AdminLockdown;
use Pazhamei\Admin\EnquiriesPage;
use Pazhamei\Access\LoginRedirect;
use Pazhamei\Access\PortalGuard;
use Pazhamei\Access\PortalMeta;
use Pazhamei\Install\Installer;
use Pazhamei\Rest\EnquiryController;
use Pazhamei\Woo\HidePrices;
use Pazhamei\Woo\OrderReceived;
use Pazhamei\Woo\ShopRedirects;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Boots all modules. Called on `plugins_loaded`.
 */
final class Plugin {

	/**
	 * Register hooks for every module.
	 */
	public static function boot(): void {
		Installer::register();     // Upgrades roles, settings and tables when PZ_VERSION changes.
		PortalMeta::register();    // The `_pz_portal` page setting and its sidebar dropdown.
		PortalGuard::register();   // Blocks portal pages for the wrong users.
		LoginRedirect::register(); // Sends each role to its landing page after sign-in.
		AdminLockdown::register(); // Only administrators get wp-admin and the admin bar.
		ShopRedirects::register(); // /my-account, shop, product and cart pages go to our pages.
		OrderReceived::register(); // "Go to my dashboard" button after payment.
		HidePrices::register();    // No prices for visitors who are not signed in.
		EnquiryController::register(); // REST routes for the storefront enquiry form.
		EnquiriesPage::register();     // wp-admin: Pazhamei > Enquiries (administrators only).

		add_action( 'init', array( self::class, 'register_blocks' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pz', Cli\Commands::class );
		}
	}

	/**
	 * Register every block that has a block.json inside blocks/.
	 */
	public static function register_blocks(): void {
		$block_folders = glob( PZ_CORE_DIR . 'blocks/*', GLOB_ONLYDIR );

		foreach ( (array) $block_folders as $folder ) {
			if ( file_exists( $folder . '/block.json' ) ) {
				register_block_type( $folder );
			}
		}
	}
}
