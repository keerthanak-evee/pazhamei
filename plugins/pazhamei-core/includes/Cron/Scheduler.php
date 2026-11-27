<?php
/**
 * Scheduler: registers the three daily jobs with Action Scheduler.
 *
 *   pz_lock_job         at the cutoff time (20:00 IST)  LockJob
 *   pz_close_day_job    23:30 IST                       CloseDayJob
 *   pz_pack_status_job  00:10 IST                       PackStatusJob
 *
 * Each job's first run is the next time that IST clock time comes round,
 * converted to a UTC timestamp, then repeats every 24 hours (IST has no
 * daylight saving, so it never drifts). On production a real server cron
 * triggers Action Scheduler every 5 minutes (Phase 2).
 *
 * Scheduling uses the real time, never the PZ_FAKE_NOW test clock; the jobs
 * themselves read Clock::now() when they run.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cron;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Pazhamei\Domain\Actor;
use Pazhamei\Services\Engine;
use Pazhamei\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Daily job registration.
 */
final class Scheduler {

	/** Action Scheduler group for our jobs. */
	public const GROUP = 'pazhamei';

	/** Option remembering what was scheduled, so we only re-check when it changes. */
	public const SIGNATURE_OPTION = 'pz_jobs_scheduled';

	public const LOCK_HOOK   = 'pz_lock_job';
	public const CLOSE_HOOK  = 'pz_close_day_job';
	public const STATUS_HOOK = 'pz_pack_status_job';

	/**
	 * Hook the jobs up. Called from Plugin::boot().
	 */
	public static function register(): void {
		add_action( self::LOCK_HOOK, static fn() => Engine::live()->lock_job()->run( Actor::system() ) );
		add_action( self::CLOSE_HOOK, static fn() => Engine::live()->close_day_job()->run( Actor::system() ) );
		add_action( self::STATUS_HOOK, static fn() => Engine::live()->pack_status_job()->run( Actor::system() ) );

		add_action( 'action_scheduler_init', array( self::class, 'maybe_schedule' ) );
		// A new cutoff time moves the lock job.
		add_action( 'update_option_' . Settings::OPTION, array( self::class, 'maybe_schedule' ) );
	}

	/**
	 * Daily jobs: hook => IST time.
	 *
	 * @return array<string, string>
	 */
	public static function jobs(): array {
		return array(
			self::LOCK_HOOK   => (string) Settings::get( 'cutoff_time' ),
			self::CLOSE_HOOK  => CloseDayJob::CLOSE_TIME,
			self::STATUS_HOOK => '00:10',
		);
	}

	/**
	 * Schedule the jobs if they aren't, or if their times changed.
	 */
	public static function maybe_schedule(): void {
		if ( ! function_exists( 'as_has_scheduled_action' ) ) {
			return;
		}
		$signature = PZ_VERSION . '|' . implode( ',', self::jobs() );
		if ( get_option( self::SIGNATURE_OPTION ) === $signature && as_has_scheduled_action( self::LOCK_HOOK, array(), self::GROUP ) ) {
			return;
		}
		self::schedule_all( true );
		update_option( self::SIGNATURE_OPTION, $signature, false );
	}

	/**
	 * (Re)create every daily job.
	 *
	 * @param bool $replace Remove existing schedules first.
	 */
	public static function schedule_all( bool $replace ): void {
		$now = new DateTimeImmutable( 'now', wp_timezone() ); // Real time, not the test clock.

		foreach ( self::jobs() as $hook => $time ) {
			if ( $replace ) {
				as_unschedule_all_actions( $hook, array(), self::GROUP );
			} elseif ( as_has_scheduled_action( $hook, array(), self::GROUP ) ) {
				continue;
			}
			as_schedule_recurring_action( self::first_run( $time, $now, wp_timezone() ), DAY_IN_SECONDS, $hook, array(), self::GROUP );
		}
	}

	/**
	 * Remove every job (plugin deactivation).
	 */
	public static function unschedule_all(): void {
		if ( ! function_exists( 'as_unschedule_all_actions' ) ) {
			return;
		}
		foreach ( array_keys( self::jobs() ) as $hook ) {
			as_unschedule_all_actions( $hook, array(), self::GROUP );
		}
		delete_option( self::SIGNATURE_OPTION );
	}

	/**
	 * UTC timestamp of the next time the clock shows $time in $timezone.
	 * If that time has already passed today, it is tomorrow.
	 *
	 * @param string            $time     'HH:MM'.
	 * @param DateTimeImmutable $now      Current time.
	 * @param DateTimeZone      $timezone Site timezone (Asia/Kolkata).
	 * @throws InvalidArgumentException For a bad time.
	 */
	public static function first_run( string $time, DateTimeImmutable $now, DateTimeZone $timezone ): int {
		if ( ! preg_match( '/^([01]\d|2[0-3]):([0-5]\d)$/', $time, $parts ) ) {
			throw new InvalidArgumentException( "Job time must look like 20:00, got '{$time}'." );
		}
		$local = $now->setTimezone( $timezone );
		$run   = $local->setTime( (int) $parts[1], (int) $parts[2] );
		if ( $run <= $local ) {
			$run = $run->modify( '+1 day' );
		}
		return $run->getTimestamp();
	}
}
