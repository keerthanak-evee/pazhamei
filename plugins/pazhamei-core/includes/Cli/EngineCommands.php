<?php
/**
 * EngineCommands: WP-CLI commands for running and inspecting the engine.
 * These are safe on every environment (they create nothing that already
 * exists, and the jobs are safe to rerun):
 *
 *   wp pz schedule <user>        Packs, pauses and the day-by-day calendar.
 *   wp pz run-lock               Run the 20:00 lock job now.
 *   wp pz run-close              Run the 23:30 close-day job now.
 *   wp pz run-status             Run the 00:10 pack-status job now.
 *   wp pz tables                 Every wp_pz_* table and its engine (must be InnoDB).
 *   wp pz jobs [--reschedule]    Next run of each daily job.
 *   wp pz install-products       Create the three plan products if missing.
 *
 * Local-only test helpers live in DevCommands.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cli;

use DateTimeImmutable;
use DateTimeZone;
use Pazhamei\Clock;
use Pazhamei\Cron\Scheduler;
use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Repositories\SchemaRepository;
use Pazhamei\Services\Engine;
use Pazhamei\Woo\PlanProducts;
use WP_CLI;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine commands.
 */
final class EngineCommands {

	/**
	 * Register the subcommands under `wp pz`.
	 */
	public static function register(): void {
		$commands = new self();
		WP_CLI::add_command( 'pz schedule', array( $commands, 'schedule' ) );
		WP_CLI::add_command( 'pz run-lock', array( $commands, 'run_lock' ) );
		WP_CLI::add_command( 'pz run-close', array( $commands, 'run_close' ) );
		WP_CLI::add_command( 'pz run-status', array( $commands, 'run_status' ) );
		WP_CLI::add_command( 'pz tables', array( $commands, 'tables' ) );
		WP_CLI::add_command( 'pz jobs', array( $commands, 'jobs' ) );
		WP_CLI::add_command( 'pz install-products', array( $commands, 'install_products' ) );
		DevCommands::register();
	}

	/**
	 * Print a customer's packs, pauses and calendar with end dates.
	 *
	 * ## OPTIONS
	 *
	 * <user>
	 * : Customer login, email or ID.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz schedule demo_customer
	 *
	 * @param array<int, string> $args Positional arguments.
	 */
	public function schedule( array $args ): void {
		$user   = self::user( $args[0] );
		$engine = Engine::live();
		self::print_schedule( $engine, $user );
	}

	/**
	 * Run the 20:00 lock job now (locks every day past its cutoff and creates daily orders). Safe to rerun.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz run-lock
	 *
	 * @subcommand run-lock
	 */
	public function run_lock(): void {
		$engine = Engine::live();
		self::print_now( $engine );
		$result = $engine->lock_job()->run( Actor::cli( get_current_user_id() ) );
		WP_CLI::success( sprintf( 'Lock job: %s to %s, %d day(s) locked, %d daily order(s) created.', Dates::display( $result['from'] ), Dates::display( $result['to'] ), $result['locked'], $result['orders_created'] ) );
	}

	/**
	 * Run the 23:30 close-day job now. Respects auto_mark_delivered.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz run-close
	 *
	 * @subcommand run-close
	 */
	public function run_close(): void {
		$engine = Engine::live();
		self::print_now( $engine );
		$result = $engine->close_day_job()->run( Actor::cli( get_current_user_id() ) );
		WP_CLI::success( sprintf( 'Close day through %s: auto_mark_delivered %s, %d day(s) marked delivered.', Dates::display( $result['through'] ), $result['auto_mark_delivered'] ? 'on' : 'off', $result['delivered'] ) );
	}

	/**
	 * Run the 00:10 pack-status job now.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz run-status
	 *
	 * @subcommand run-status
	 */
	public function run_status(): void {
		$engine = Engine::live();
		self::print_now( $engine );
		$result = $engine->pack_status_job()->run( Actor::cli( get_current_user_id() ) );
		WP_CLI::success( sprintf( 'Pack status: %d pause(s) ended, %d open-ended pause(s), %d pack(s) completed, %d customer(s) checked.', $result['pauses_ended'], $result['pauses_ongoing'], $result['completed'], $result['customers'] ) );
	}

	/**
	 * List every wp_pz_* table with its storage engine. All must be InnoDB.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz tables
	 */
	public function tables(): void {
		$tables = SchemaRepository::tables();
		WP_CLI\Utils\format_items( 'table', $tables, array( 'table', 'engine', 'rows' ) );

		$other = array_filter( $tables, static fn( $t ) => 'innodb' !== strtolower( $t['engine'] ) );
		if ( $other ) {
			WP_CLI::error( count( $other ) . ' table(s) are not InnoDB. Deactivate and reactivate the plugin, or run the installer.' );
		}
		WP_CLI::success( count( $tables ) . ' tables, all InnoDB.' );
	}

	/**
	 * Show the next run of each daily job (Action Scheduler).
	 *
	 * ## OPTIONS
	 *
	 * [--reschedule]
	 * : Remove and recreate the three jobs.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz jobs
	 *     wp pz jobs --reschedule
	 *
	 * @param array<int, string>   $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Flags.
	 */
	public function jobs( array $args, array $assoc_args ): void {
		if ( ! function_exists( 'as_next_scheduled_action' ) ) {
			WP_CLI::error( 'Action Scheduler is not available (is WooCommerce active?).' );
		}
		if ( ! empty( $assoc_args['reschedule'] ) ) {
			Scheduler::schedule_all( true );
			update_option( Scheduler::SIGNATURE_OPTION, PZ_VERSION . '|' . implode( ',', Scheduler::jobs() ), false );
		} else {
			Scheduler::schedule_all( false );
		}

		$rows = array();
		foreach ( Scheduler::jobs() as $hook => $time ) {
			$next   = as_next_scheduled_action( $hook, array(), Scheduler::GROUP );
			$rows[] = array(
				'job'          => $hook,
				'daily at IST' => $time,
				'next run IST' => is_int( $next ) ? wp_date( 'D j M Y H:i', $next ) : '—',
				'next run UTC' => is_int( $next ) ? gmdate( 'Y-m-d H:i', $next ) : '—',
			);
		}
		WP_CLI\Utils\format_items( 'table', $rows, array( 'job', 'daily at IST', 'next run IST', 'next run UTC' ) );
	}

	/**
	 * Create the Trial, 6-day and 26-day plan products if they don't exist.
	 *
	 * Variable products with Veg / Non-Veg / Mixed variations, published but
	 * hidden from the shop and search. Placeholder prices are marked [EDIT].
	 * Existing products are never changed.
	 *
	 * ## EXAMPLES
	 *
	 *     wp pz install-products
	 *
	 * @subcommand install-products
	 */
	public function install_products(): void {
		$report = PlanProducts::install();
		WP_CLI\Utils\format_items( 'table', $report, array( 'plan', 'action', 'id' ) );
		WP_CLI::success( 'Plan products ready. Prices are placeholders: replace every [EDIT] price before selling.' );
	}

	/* ---------- Shared helpers (also used by DevCommands) ---------- */

	/**
	 * Find a user by login, email or ID, or stop with an error.
	 *
	 * @param string $who Login, email or ID.
	 */
	public static function user( string $who ): WP_User {
		$user = ctype_digit( $who ) ? get_user_by( 'id', (int) $who ) : ( get_user_by( 'login', $who ) ?: get_user_by( 'email', $who ) );
		if ( ! $user instanceof WP_User ) {
			WP_CLI::error( "No user '{$who}'." );
		}
		return $user;
	}

	/**
	 * Print the clock the engine is using.
	 *
	 * @param Engine $engine Engine.
	 */
	public static function print_now( Engine $engine ): void {
		$fake = Clock::configured_fake();
		$note = null !== $fake && ! Clock::is_ignored( $fake, wp_get_environment_type() ) ? 'FAKE' : 'real';
		WP_CLI::log( sprintf( 'Now: %s IST (%s time). First unlocked date: %s.', $engine->now()->format( 'D j M Y H:i' ), $note, Dates::display( $engine->first_unlocked() ) ) );
	}

	/**
	 * Print packs, pauses and the calendar for one customer.
	 *
	 * @param Engine  $engine Engine.
	 * @param WP_User $user   Customer.
	 */
	public static function print_schedule( Engine $engine, WP_User $user ): void {
		$stores = $engine->stores;
		self::print_now( $engine );
		WP_CLI::log( sprintf( 'Customer: %s (#%d)', $user->user_login, $user->ID ) );

		$packs = array();
		foreach ( $stores->subscriptions->for_customer( $user->ID ) as $pack ) {
			$packs[] = array(
				'pack'  => '#' . $pack->id,
				'plan'  => $pack->plan_code,
				'meal'  => $pack->meal_type,
				'status' => $pack->status,
				'meals' => $pack->consumed_meals . ' eaten / ' . $pack->total_meals,
				'start' => null === $pack->start_date ? 'no dates yet' : Dates::display( $pack->start_date ),
				'end'   => null === $pack->end_date ? 'no dates yet' : Dates::display( $pack->end_date ),
			);
		}
		if ( ! $packs ) {
			WP_CLI::log( 'No packs.' );
			return;
		}
		WP_CLI::log( '' );
		WP_CLI\Utils\format_items( 'table', $packs, array( 'pack', 'plan', 'meal', 'status', 'meals', 'start', 'end' ) );

		$pauses = array();
		foreach ( $stores->pauses->for_customer( $user->ID ) as $pause ) {
			$pauses[] = array(
				'pause'  => '#' . $pause->id,
				'from'   => Dates::display( $pause->start_date ),
				'to'     => null === $pause->end_date ? 'until resumed' : Dates::display( $pause->end_date ),
				'status' => $pause->status,
			);
		}
		if ( $pauses ) {
			WP_CLI::log( 'Pauses:' );
			WP_CLI\Utils\format_items( 'table', $pauses, array( 'pause', 'from', 'to', 'status' ) );
		}

		$days = $stores->schedule->for_customer( $user->ID );
		if ( ! $days ) {
			return;
		}
		$by_date  = array();
		foreach ( $days as $day ) {
			$by_date[ $day->service_date ] = $day;
		}
		$holidays = array_column( $stores->holidays->list_from( $days[0]->service_date ), 'label', 'holiday_date' );
		$calendar = $engine->calendar();
		$rows     = array();
		$last     = end( $days )->service_date;

		for ( $date = $days[0]->service_date; $date <= $last; $date = Dates::add_days( $date, 1 ) ) {
			$day = $by_date[ $date ] ?? null;
			if ( $day ) {
				$status = $day->status;
				$pack   = '#' . $day->subscription_id;
				$meal   = DayStatus::uses_meal( $day->status ) ? $day->meal_type : '';
			} else {
				$status = $calendar->is_sunday( $date ) ? '(Sunday, off)' : ( isset( $holidays[ $date ] ) ? '(holiday: ' . $holidays[ $date ] . ')' : '' );
				$pack   = '';
				$meal   = '';
			}
			$rows[] = array(
				'date'   => Dates::display( $date ),
				'day'    => Dates::weekday_name( $date ),
				'status' => $status,
				'pack'   => $pack,
				'meal'   => $meal,
				'locked' => $engine->is_locked( $date ) ? 'yes' : '',
			);
		}
		WP_CLI::log( 'Calendar:' );
		WP_CLI\Utils\format_items( 'table', $rows, array( 'date', 'day', 'status', 'pack', 'meal', 'locked' ) );
	}

	/**
	 * Parse a 'Y-m-d H:i:s' time in IST, or stop with an error.
	 *
	 * @param string $value Text.
	 */
	public static function parse_time( string $value ): DateTimeImmutable {
		$parsed = Clock::parse( $value, new DateTimeZone( wp_timezone_string() ) );
		if ( null === $parsed ) {
			WP_CLI::error( "Use the format \"2026-11-29 20:01:00\", got '{$value}'." );
		}
		return $parsed;
	}
}
