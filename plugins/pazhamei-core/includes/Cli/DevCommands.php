<?php
/**
 * DevCommands: local-only WP-CLI helpers for testing the engine without a UI.
 * Every command here refuses to run unless the environment type is "local".
 *
 *   wp pz time [<datetime>] [--clear]    Show, set or clear the fake clock (option pz_fake_now).
 *   wp pz simulate-order <user> <plan> <meal-type> <start-date>
 *                                        Create a paid test order (activates a pack).
 *   wp pz skip <user> <date>             Skip a day.
 *   wp pz undo-skip <user> <date>        Undo a skip.
 *   wp pz pause <user> <start> [<end>]   Pause (no end = open-ended).
 *   wp pz resume <user>                  Resume.
 *   wp pz holiday <add|remove> <date> [--label=<label>]
 *   wp pz credit <pack> [--reason=<reason>]
 *   wp pz cancel <pack> [--reason=<reason>]
 *   wp pz meal-type <pack> <type>
 *   wp pz mark-failed <user> <date>
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cli;

use Pazhamei\Clock;
use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Plans;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Environment;
use Pazhamei\Services\CheckoutCheck;
use Pazhamei\Services\Engine;
use Pazhamei\Woo\CheckoutFields;
use Pazhamei\Woo\PlanProducts;
use WP_CLI;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Local-only test helpers.
 */
final class DevCommands {

	/**
	 * Register the subcommands under `wp pz`.
	 */
	public static function register(): void {
		$commands = new self();
		WP_CLI::add_command( 'pz time', array( $commands, 'time' ) );
		WP_CLI::add_command( 'pz simulate-order', array( $commands, 'simulate_order' ) );
		WP_CLI::add_command( 'pz skip', array( $commands, 'skip' ) );
		WP_CLI::add_command( 'pz undo-skip', array( $commands, 'undo_skip' ) );
		WP_CLI::add_command( 'pz pause', array( $commands, 'pause' ) );
		WP_CLI::add_command( 'pz resume', array( $commands, 'resume' ) );
		WP_CLI::add_command( 'pz holiday', array( $commands, 'holiday' ) );
		WP_CLI::add_command( 'pz credit', array( $commands, 'credit' ) );
		WP_CLI::add_command( 'pz cancel', array( $commands, 'cancel' ) );
		WP_CLI::add_command( 'pz meal-type', array( $commands, 'meal_type' ) );
		WP_CLI::add_command( 'pz mark-failed', array( $commands, 'mark_failed' ) );
	}

	/**
	 * Show, set or clear the fake clock. Local sites only.
	 *
	 * The fake time is stored in the option pz_fake_now, so it lasts across
	 * separate commands and page loads. A PZ_FAKE_NOW constant in
	 * wp-config.php overrides it.
	 *
	 * ## OPTIONS
	 *
	 * [<date>]
	 * : IST date, YYYY-MM-DD (or the whole time as "2026-11-29 20:01:00" or 2026-11-29T20:01:00).
	 *
	 * [<time>]
	 * : IST time, HH:MM or HH:MM:SS.
	 *
	 * [--clear]
	 * : Go back to the real time.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz time 2026-11-29 20:01
	 *     wp pz time 2026-11-29T20:01:00
	 *     wp pz time
	 *     wp pz time --clear
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function time( array $args, array $assoc_args ): void {
		self::local_only( 'wp pz time' );

		if ( ! empty( $assoc_args['clear'] ) ) {
			delete_option( Clock::OPTION );
			WP_CLI::success( 'Fake time cleared. Now: ' . Clock::now()->format( 'D j M Y H:i:s' ) . ' IST (real).' );
			return;
		}
		if ( isset( $args[0] ) ) {
			// Accept "date time", "dateTtime", or date and time as two arguments (no quoting needed).
			$text = str_replace( 'T', ' ', trim( $args[0] . ' ' . ( $args[1] ?? '' ) ) );
			if ( preg_match( '/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $text ) ) {
				$text .= ':00';
			}
			$time = EngineCommands::parse_time( $text );
			update_option( Clock::OPTION, $time->format( Clock::FAKE_FORMAT ) );
		}
		if ( defined( 'PZ_FAKE_NOW' ) ) {
			WP_CLI::warning( 'PZ_FAKE_NOW is defined in wp-config.php and overrides the stored fake time.' );
		}
		$fake = Clock::configured_fake();
		WP_CLI::success( sprintf( 'Now: %s IST (%s).', Clock::now()->format( 'D j M Y H:i:s' ), null === $fake ? 'real time' : 'fake time' ) );
	}

	/**
	 * Create a paid test order for a plan, which activates a pack. Local sites only.
	 *
	 * Uses the same checks as checkout (start date, trial rules). Billing
	 * details come from the user's saved billing fields, or test values.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer login, email or ID.
	 *
	 * <plan>
	 * : trial, 6-day or 26-day.
	 *
	 * <meal-type>
	 * : veg, nonveg or mixed.
	 *
	 * <start-date>
	 * : Requested start, YYYY-MM-DD.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz simulate-order demo_customer 6-day veg 2026-11-30
	 *
	 * @subcommand simulate-order
	 * @param array<int, string> $args Positional arguments.
	 */
	public function simulate_order( array $args ): void {
		self::local_only( 'wp pz simulate-order' );
		list( $who, $slug, $meal, $start ) = $args;

		$user = EngineCommands::user( $who );
		$plan = Plans::all()[ $slug ] ?? null;
		if ( null === $plan ) {
			WP_CLI::error( 'Plan must be trial, 6-day or 26-day.' );
		}
		$meal_type = strtoupper( str_replace( '-', '', $meal ) );
		if ( ! Plans::is_meal_type( $meal_type ) ) {
			WP_CLI::error( 'Meal type must be veg, nonveg or mixed.' );
		}
		$variation_id = PlanProducts::find_variation( $plan['code'], $meal_type );
		if ( ! $variation_id ) {
			WP_CLI::error( 'Plan products are missing. Run `wp pz install-products` first.' );
		}

		$billing = self::billing( $user );
		$engine  = Engine::live();
		$errors  = CheckoutCheck::errors( $engine, $user->ID, array( $plan['code'] ), $start, $billing['phone'], $billing['postcode'], $billing['address_1'] );
		if ( $errors ) {
			WP_CLI::error( 'Checkout would refuse this order: ' . implode( ' ', $errors ) );
		}

		$order = wc_create_order( array( 'customer_id' => $user->ID ) );
		if ( is_wp_error( $order ) ) {
			WP_CLI::error( $order->get_error_message() );
		}
		$order->add_product( wc_get_product( $variation_id ), 1 );
		$order->set_address( $billing, 'billing' );
		$order->set_payment_method( 'pz_simulated' );
		$order->set_payment_method_title( 'Simulated payment (wp pz simulate-order)' );
		$order->update_meta_data( CheckoutFields::META, $start );
		$order->calculate_totals();
		$order->save();
		$order->payment_complete( 'SIM-' . $order->get_id() ); // Fires the same hooks as a real payment.

		WP_CLI::log( sprintf( 'Order #%d paid (%s).', $order->get_id(), html_entity_decode( wp_strip_all_tags( wc_price( (float) $order->get_total() ) ), ENT_QUOTES, 'UTF-8' ) ) );
		foreach ( $engine->stores->subscriptions->for_order( $order->get_id() ) as $pack ) {
			WP_CLI::success( sprintf( 'Pack #%d %s %s: %s, %s to %s.', $pack->id, $pack->plan_code, $pack->meal_type, $pack->status, null === $pack->start_date ? 'no dates yet' : Dates::display( $pack->start_date ), null === $pack->end_date ? 'no dates yet' : Dates::display( $pack->end_date ) ) );
		}
	}

	/**
	 * Skip a day. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer.
	 *
	 * <date>
	 * : YYYY-MM-DD.
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function skip( array $args ): void {
		self::local_only( 'wp pz skip' );
		$user = EngineCommands::user( $args[0] );
		self::attempt( fn( Engine $e ) => $e->skips()->skip( $user->ID, $args[1], self::actor() ), 'Skipped ' . Dates::display( $args[1] ) . '.' );
	}

	/**
	 * Undo a skip. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer.
	 *
	 * <date>
	 * : YYYY-MM-DD.
	 *
	 * @subcommand undo-skip
	 * @param array<int, string> $args Positional arguments.
	 */
	public function undo_skip( array $args ): void {
		self::local_only( 'wp pz undo-skip' );
		$user = EngineCommands::user( $args[0] );
		self::attempt( fn( Engine $e ) => $e->skips()->undo( $user->ID, $args[1], self::actor() ), 'Skip on ' . Dates::display( $args[1] ) . ' undone.' );
	}

	/**
	 * Pause deliveries. Without an end date the pause is open-ended. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer.
	 *
	 * <start>
	 * : First paused day, YYYY-MM-DD.
	 *
	 * [<end>]
	 * : Last paused day, YYYY-MM-DD.
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function pause( array $args ): void {
		self::local_only( 'wp pz pause' );
		$user = EngineCommands::user( $args[0] );
		$end  = $args[2] ?? null;
		self::attempt(
			fn( Engine $e ) => $e->pauses()->pause( $user->ID, $args[1], $end, self::actor() ),
			'Paused from ' . Dates::display( $args[1] ) . ( null === $end ? ' until resumed.' : ' to ' . Dates::display( $end ) . '.' )
		);
	}

	/**
	 * Resume deliveries. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer.
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function resume( array $args ): void {
		self::local_only( 'wp pz resume' );
		$user = EngineCommands::user( $args[0] );
		self::attempt( fn( Engine $e ) => $e->pauses()->resume( $user->ID, self::actor() ), 'Resumed.' );
	}

	/**
	 * Add or remove a holiday. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <action>
	 * : add or remove.
	 *
	 * <date>
	 * : YYYY-MM-DD.
	 *
	 * [--label=<label>]
	 * : Name of the holiday.
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function holiday( array $args, array $assoc_args ): void {
		self::local_only( 'wp pz holiday' );
		list( $action, $date ) = $args;
		if ( 'add' === $action ) {
			self::attempt( fn( Engine $e ) => $e->holidays()->add( $date, (string) ( $assoc_args['label'] ?? '' ), self::actor() ), 'Holiday added on ' . Dates::display( $date ) . '.' );
		} elseif ( 'remove' === $action ) {
			self::attempt( fn( Engine $e ) => $e->holidays()->remove( $date, self::actor() ), 'Holiday removed on ' . Dates::display( $date ) . '.' );
		} else {
			WP_CLI::error( 'Use add or remove.' );
		}
	}

	/**
	 * Credit one meal to a pack. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <pack>
	 * : Pack ID.
	 *
	 * [--reason=<reason>]
	 * : Why.
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function credit( array $args, array $assoc_args ): void {
		self::local_only( 'wp pz credit' );
		self::attempt( fn( Engine $e ) => $e->packs()->credit_meal( (int) $args[0], self::actor(), (string) ( $assoc_args['reason'] ?? '' ) ), 'One meal credited to pack #' . (int) $args[0] . '.' );
	}

	/**
	 * Cancel a pack (removes its unlocked days). Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <pack>
	 * : Pack ID.
	 *
	 * [--reason=<reason>]
	 * : Why.
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function cancel( array $args, array $assoc_args ): void {
		self::local_only( 'wp pz cancel' );
		self::attempt( fn( Engine $e ) => $e->packs()->cancel( (int) $args[0], PackStatus::CANCELLED, self::actor(), (string) ( $assoc_args['reason'] ?? '' ) ), 'Pack #' . (int) $args[0] . ' cancelled.' );
	}

	/**
	 * Change a pack's meal type for its unlocked days. Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <pack>
	 * : Pack ID.
	 *
	 * <type>
	 * : veg, nonveg or mixed.
	 *
	 * @subcommand meal-type
	 * @param array<int, string> $args Positional arguments.
	 */
	public function meal_type( array $args ): void {
		self::local_only( 'wp pz meal-type' );
		$type = strtoupper( str_replace( '-', '', $args[1] ) );
		self::attempt( fn( Engine $e ) => $e->packs()->change_meal_type( (int) $args[0], $type, self::actor() ), 'Pack #' . (int) $args[0] . ' is now ' . $type . '.' );
	}

	/**
	 * Mark a locked day as failed (no automatic credit). Local sites only.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer.
	 *
	 * <date>
	 * : YYYY-MM-DD.
	 *
	 * @subcommand mark-failed
	 * @param array<int, string> $args Positional arguments.
	 */
	public function mark_failed( array $args ): void {
		self::local_only( 'wp pz mark-failed' );
		$user = EngineCommands::user( $args[0] );
		$day  = Engine::live()->stores->schedule->find_by_date( $user->ID, $args[1] );
		if ( ! $day ) {
			WP_CLI::error( 'No day on ' . $args[1] . '.' );
		}
		self::attempt( fn( Engine $e ) => $e->deliveries()->mark_failed( $day->id, self::actor(), 'Marked from WP-CLI' ), Dates::display( $args[1] ) . ' marked failed.' );
	}

	/* ---------- Helpers ---------- */

	/**
	 * Stop unless this is a local site.
	 *
	 * @param string $command Command name for the message.
	 */
	private static function local_only( string $command ): void {
		if ( ! Environment::allows_dev_tools( wp_get_environment_type() ) ) {
			WP_CLI::error( "{$command} runs only on local sites (environment type \"local\"), never on staging or production." );
		}
	}

	/**
	 * Run an engine action, reporting rule violations as errors.
	 *
	 * @param callable $action  fn( Engine $engine ).
	 * @param string   $success Message on success.
	 */
	private static function attempt( callable $action, string $success ): void {
		try {
			$action( Engine::live() );
		} catch ( RuleViolation $refused ) {
			WP_CLI::error( 'Refused: ' . $refused->getMessage() );
		}
		WP_CLI::success( $success );
	}

	/**
	 * The CLI user as an actor.
	 */
	private static function actor(): Actor {
		return Actor::cli( get_current_user_id() );
	}

	/**
	 * Billing details for a test order: the user's saved fields, or test values.
	 * Test values are unique per user so trial checks don't clash.
	 *
	 * @param WP_User $user Customer.
	 * @return array<string, string>
	 */
	private static function billing( WP_User $user ): array {
		$meta = static fn( string $key, string $fallback ) => '' !== (string) get_user_meta( $user->ID, $key, true ) ? (string) get_user_meta( $user->ID, $key, true ) : $fallback;

		return array(
			'first_name' => $meta( 'billing_first_name', $user->first_name ? $user->first_name : $user->user_login ),
			'last_name'  => $meta( 'billing_last_name', $user->last_name ),
			'email'      => $meta( 'billing_email', $user->user_email ),
			'phone'      => $meta( 'billing_phone', '90000' . str_pad( (string) $user->ID, 5, '0', STR_PAD_LEFT ) ),
			'address_1'  => $meta( 'billing_address_1', 'No. ' . $user->ID . ', Test Street' ),
			'address_2'  => $meta( 'billing_address_2', '' ),
			'city'       => $meta( 'billing_city', 'Chennai' ),
			'state'      => $meta( 'billing_state', 'TN' ),
			'postcode'   => $meta( 'billing_postcode', '600001' ),
			'country'    => $meta( 'billing_country', 'IN' ),
		);
	}
}
