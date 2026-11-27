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
use Pazhamei\Admin\PlansPricesPage;
use Pazhamei\Access\LoginRedirect;
use Pazhamei\Access\MovedPages;
use Pazhamei\Access\PortalGuard;
use Pazhamei\Access\PortalMeta;
use Pazhamei\Cron\Scheduler;
use Pazhamei\Install\Installer;
use Pazhamei\Rest\AdminController;
use Pazhamei\Rest\EnquiryController;
use Pazhamei\Rest\ScheduleController;
use Pazhamei\Woo\AccountForms;
use Pazhamei\Woo\BuyLink;
use Pazhamei\Woo\CheckoutFields;
use Pazhamei\Woo\HidePrices;
use Pazhamei\Woo\OrderHooks;
use Pazhamei\Woo\OrderReceived;
use Pazhamei\Woo\PlanProducts;
use Pazhamei\Woo\ShopRedirects;
use Pazhamei\Woo\TestPay;

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
		AccountForms::register();  // Register fields, phone sign-in, sign-in page views.
		AdminLockdown::register(); // Only administrators get wp-admin and the admin bar.
		ShopRedirects::register(); // /my-account, shop, product and cart pages go to our pages.
		MovedPages::register();    // /about-contact and /enquire go to /about-us (301).
		OrderReceived::register(); // "Go to my dashboard" button after payment.
		HidePrices::register();    // No prices for visitors who are not signed in.
		EnquiryController::register(); // REST routes for the storefront enquiry form.
		EnquiriesPage::register();     // wp-admin: Pazhamei > Enquiries (administrators only).
		PlansPricesPage::register();   // wp-admin: Pazhamei > Plans and Prices (administrators only).

		// Subscription engine (Phase 3).
		PlanProducts::register();       // The trial switch on the plan products.
		CheckoutFields::register();     // Start date field and plan rules at checkout.
		OrderHooks::register();         // Paid order → pack; cancelled/refunded order → pack cancelled.
		BuyLink::register();            // Plan card Buy button: one plan in the cart, then checkout.
		TestPay::register();            // Local only: "Pazhamei Test Pay" marks orders paid, for testing.
		Scheduler::register();          // 20:00 lock, 23:30 close day, 00:10 pack status.
		ScheduleController::register(); // Customer REST routes: schedule, skip, pause, resume.
		AdminController::register();    // Admin REST routes: holidays, credit, cancel, meal type.

		add_action( 'init', array( self::class, 'register_blocks' ) );

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'pz', Cli\Commands::class );
			Cli\EngineCommands::register();
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
