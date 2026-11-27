<?php
/**
 * Engine: the subscription engine's front door.
 *
 * It holds what every service needs (storage, settings, the clock, the
 * change log, event hooks) and hands out the services:
 *
 *     $engine = Engine::live();
 *     $engine->skips()->skip( $customer_id, '2026-12-02', $actor );
 *
 * Tests build an Engine with in-memory stores and a fixed clock instead.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Closure;
use DateTimeImmutable;
use Pazhamei\Clock;
use Pazhamei\Cron\CloseDayJob;
use Pazhamei\Cron\LockJob;
use Pazhamei\Cron\PackStatusJob;
use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Calendar;
use Pazhamei\Domain\Cutoff;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\EngineConfig;
use Pazhamei\Repositories\Stores;
use Pazhamei\Settings;
use Pazhamei\Woo\OrderAddress;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shared context and service factory.
 */
final class Engine {

	public Stores $stores;
	public EngineConfig $config;
	public CustomerInfo $customers;
	public Events $events;

	/** Returns the current time. */
	private Closure $clock;

	/**
	 * @param Stores       $stores    Storage.
	 * @param EngineConfig $config    Settings.
	 * @param callable     $clock     Returns the current time (DateTimeImmutable).
	 * @param CustomerInfo $customers Delivery address lookups.
	 * @param Events       $events    Event hooks for other modules (emails in Phase 6).
	 */
	public function __construct( Stores $stores, EngineConfig $config, callable $clock, CustomerInfo $customers, Events $events ) {
		$this->stores    = $stores;
		$this->config    = $config;
		$this->clock     = Closure::fromCallable( $clock );
		$this->customers = $customers;
		$this->events    = $events;
	}

	/**
	 * The engine for the live site: MySQL, pz_settings, Clock::now(), WooCommerce.
	 */
	public static function live(): self {
		return new self( Stores::wpdb(), Settings::engine_config(), array( Clock::class, 'now' ), new OrderAddress(), new WpEvents() );
	}

	/* ---------- Time ---------- */

	/**
	 * Current time in IST. Read fresh on every call, never cached.
	 */
	public function now(): DateTimeImmutable {
		return ( $this->clock )()->setTimezone( $this->config->timezone );
	}

	/**
	 * Current time as 'Y-m-d H:i:s' (IST), for created_at / updated_at.
	 */
	public function stamp(): string {
		return $this->now()->format( 'Y-m-d H:i:s' );
	}

	/**
	 * Today's date in IST.
	 */
	public function today(): string {
		return Dates::date_of( $this->now() );
	}

	/**
	 * The cutoff rule (rule 3).
	 */
	public function cutoff(): Cutoff {
		return new Cutoff( $this->config->cutoff_time, $this->config->timezone );
	}

	/**
	 * Service days with the current holidays (rule 1).
	 */
	public function calendar(): Calendar {
		$holidays = $this->stores->holidays->list_from( Dates::add_days( $this->today(), -60 ) );
		return new Calendar( array_column( $holidays, 'holiday_date' ) );
	}

	/**
	 * The first date that can still be changed right now.
	 */
	public function first_unlocked(): string {
		return $this->cutoff()->first_unlocked_date( $this->now() );
	}

	/**
	 * Is this date locked right now?
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function is_locked( string $date ): bool {
		return $this->cutoff()->is_locked( $date, $this->now() );
	}

	/**
	 * Customer-facing "too late" message, e.g. "Changes for 2 Dec 2026 closed at 20:00 on 1 Dec 2026."
	 *
	 * @param string $date 'Y-m-d'.
	 */
	public function locked_message( string $date ): string {
		return sprintf(
			'Changes for %s closed at %s on %s.',
			Dates::display( $date ),
			$this->config->cutoff_time,
			Dates::display( Dates::add_days( $date, -1 ) )
		);
	}

	/* ---------- Transactions, log, events ---------- */

	/**
	 * Run work for one customer inside a transaction, holding that customer's
	 * lock (their profile row, SELECT ... FOR UPDATE) until it commits.
	 *
	 * @param int      $customer_id Customer.
	 * @param callable $work        Work to do.
	 * @return mixed What the work returns.
	 */
	public function customer_tx( int $customer_id, callable $work ) {
		return $this->stores->transaction->run(
			function () use ( $customer_id, $work ) {
				$this->stores->profiles->ensure( $customer_id, $this->stamp() );
				$this->stores->profiles->lock( $customer_id );
				return $work();
			}
		);
	}

	/**
	 * Write one change log entry.
	 *
	 * @param string $entity_type subscription, schedule, pause, holiday, customer or job.
	 * @param int    $entity_id   Its ID.
	 * @param string $action      What happened.
	 * @param mixed  $old         Values before (null if none).
	 * @param mixed  $new         Values after (null if none).
	 * @param Actor  $actor       Who did it.
	 * @param string $reason      Why.
	 */
	public function log( string $entity_type, int $entity_id, string $action, $old, $new, Actor $actor, string $reason = '' ): void {
		$this->stores->change_log->insert(
			array(
				'entity_type' => $entity_type,
				'entity_id'   => $entity_id,
				'action'      => $action,
				'old_json'    => null === $old ? null : self::json( $old ),
				'new_json'    => null === $new ? null : self::json( $new ),
				'actor_id'    => $actor->id,
				'actor_role'  => $actor->role,
				'reason'      => substr( $reason, 0, 255 ),
				'ip'          => $actor->ip,
				'created_at'  => $this->stamp(),
			)
		);
	}

	/**
	 * Tell other modules something happened (do_action( 'pz_' . $name ) on the live site).
	 *
	 * @param string            $name Event name, e.g. 'pack_activated'.
	 * @param array<int, mixed> $args Arguments.
	 */
	public function emit( string $name, array $args = array() ): void {
		$this->events->emit( $name, $args );
	}

	/* ---------- Services ---------- */

	/** Lays out a customer's meals. */
	public function reflow(): ReflowService {
		return new ReflowService( $this );
	}

	/** Paid order → pack. */
	public function activation(): ActivationService {
		return new ActivationService( $this );
	}

	/** Skip and undo skip. */
	public function skips(): SkipService {
		return new SkipService( $this );
	}

	/** Pause and resume. */
	public function pauses(): PauseService {
		return new PauseService( $this );
	}

	/** Holidays. */
	public function holidays(): HolidayService {
		return new HolidayService( $this );
	}

	/** Admin pack actions: cancel, credit, change meal type. */
	public function packs(): PackAdminService {
		return new PackAdminService( $this );
	}

	/** Mark delivered or failed. */
	public function deliveries(): DeliveryService {
		return new DeliveryService( $this );
	}

	/** One trial per customer (rule 10). */
	public function trials(): TrialEligibility {
		return new TrialEligibility( $this );
	}

	/** 20:00 lock job. */
	public function lock_job(): LockJob {
		return new LockJob( $this );
	}

	/** 23:30 close-day job. */
	public function close_day_job(): CloseDayJob {
		return new CloseDayJob( $this );
	}

	/** 00:10 pack-status job. */
	public function pack_status_job(): PackStatusJob {
		return new PackStatusJob( $this );
	}

	/**
	 * JSON for the change log.
	 *
	 * @param mixed $value Value.
	 */
	private static function json( $value ): string {
		return (string) json_encode( $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES );
	}
}
