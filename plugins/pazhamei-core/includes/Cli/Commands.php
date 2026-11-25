<?php
/**
 * Commands: the `wp pz ...` WP-CLI commands.
 *
 *   wp pz install-pages   Create missing pages and menus (never overwrites).
 *   wp pz seed            Create the three test users and print their passwords.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cli;

use Pazhamei\Environment;
use Pazhamei\Install\PageInstaller;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Pazhamei setup commands.
 */
final class Commands {

	/** Test users created by `wp pz seed`: login => role. */
	private const TEST_USERS = array(
		'test_customer' => 'customer',
		'test_kitchen'  => 'pz_kitchen',
		'test_admin'    => 'pz_admin',
	);

	/**
	 * Create the site's pages and Navigation menus if they are missing.
	 *
	 * Existing pages and menus are never changed. Also sets Home as the
	 * front page and /sign-in as WooCommerce's My Account page.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz install-pages
	 *
	 * @subcommand install-pages
	 */
	public function install_pages(): void {
		$report = ( new PageInstaller() )->run();

		$counts = array();
		foreach ( $report as $line ) {
			$counts[ $line['type'] ] = ( $counts[ $line['type'] ] ?? 0 ) + 1;

			if ( 'warning' === $line['type'] ) {
				WP_CLI::warning( $line['text'] );
			} else {
				WP_CLI::log( str_pad( $line['type'], 8 ) . ' ' . $line['text'] );
			}
		}

		WP_CLI::success(
			sprintf(
				'%d created, %d already existed, %d settings updated, %d warnings.',
				$counts['created'] ?? 0,
				$counts['exists'] ?? 0,
				$counts['updated'] ?? 0,
				$counts['warning'] ?? 0
			)
		);
	}

	/**
	 * Create the test users test_customer, test_kitchen and test_admin.
	 *
	 * Each gets a new random password, printed here only. Running it again
	 * resets the passwords. Runs only on local sites.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz seed
	 */
	public function seed(): void {
		if ( ! Environment::allows_dev_tools( wp_get_environment_type() ) ) {
			WP_CLI::error( 'wp pz seed runs only on local sites (environment type "local"), never on staging or production.' );
		}

		$rows = array();

		foreach ( self::TEST_USERS as $login => $role ) {
			$password = wp_generate_password( 20, false ); // Letters and digits only: easy to copy.
			$user     = get_user_by( 'login', $login );

			if ( $user ) {
				wp_set_password( $password, $user->ID );
				$user->set_role( $role );
				$action = 'password reset';
			} else {
				$user_id = wp_insert_user(
					array(
						'user_login'   => $login,
						'user_pass'    => $password,
						'user_email'   => $login . '@pazhamei.test',
						'display_name' => ucwords( str_replace( '_', ' ', $login ) ),
						'role'         => $role,
					)
				);
				if ( is_wp_error( $user_id ) ) {
					WP_CLI::warning( "Could not create {$login}: " . $user_id->get_error_message() );
					continue;
				}
				$action = 'created';
			}

			$rows[] = array(
				'user'     => $login,
				'role'     => $role,
				'action'   => $action,
				'password' => $password,
			);
		}

		// Printed to the terminal only; never written to a file.
		WP_CLI\Utils\format_items( 'table', $rows, array( 'user', 'role', 'action', 'password' ) );
		WP_CLI::success( 'Test users ready. Copy the passwords now: they are not stored anywhere else.' );
	}
}
