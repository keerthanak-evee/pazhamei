<?php
/**
 * TestKit: an Engine on in-memory stores with a clock the test controls,
 * plus small helpers to buy packs and read calendars.
 *
 *     $kit = new TestKit( '2026-11-27 10:00:00' );   // Friday, IST
 *     $pack = $kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
 *     $kit->at( '2026-11-29 20:01:00' );             // move the clock
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use DateTimeImmutable;
use DateTimeZone;
use Pazhamei\Domain\Actor;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Domain\EngineConfig;
use Pazhamei\Domain\Pack;
use Pazhamei\Repositories\Stores;
use Pazhamei\Services\CustomerInfo;
use Pazhamei\Services\Engine;
use Pazhamei\Services\Events;
use Pazhamei\Services\PackRequest;

/**
 * Test harness for the engine.
 */
final class TestKit implements CustomerInfo, Events {

	public MemoryDb $db;
	public Stores $stores;
	public Engine $engine;
	public DateTimeImmutable $now;

	/**
	 * Events emitted: list of [name, args].
	 *
	 * @var array<int, array{0: string, 1: array<int, mixed>}>
	 */
	public array $events = array();

	private int $next_item = 1000;

	/**
	 * @param string            $now    IST time 'Y-m-d H:i:s'.
	 * @param EngineConfig|null $config Settings (defaults: 20:00 cutoff, auto-mark on, trial off).
	 */
	public function __construct( string $now = '2026-11-27 10:00:00', ?EngineConfig $config = null ) {
		$this->db     = new MemoryDb();
		$this->stores = MemoryStores::make( $this->db );
		$this->at( $now );
		$this->engine = new Engine( $this->stores, $config ?? EngineConfig::defaults(), fn() => $this->now, $this, $this );
	}

	/**
	 * Move the clock.
	 *
	 * @param string $now IST time 'Y-m-d H:i:s'.
	 */
	public function at( string $now ): self {
		$this->now = new DateTimeImmutable( $now, new DateTimeZone( 'Asia/Kolkata' ) );
		return $this;
	}

	/**
	 * Activate a pack as if an order line was paid.
	 *
	 * @param int         $customer_id Customer.
	 * @param string      $plan_code   TRIAL, D6, D26.
	 * @param string      $meal_type   VEG, NONVEG, MIXED.
	 * @param string|null $start       Requested start.
	 * @param int|null    $item_id     Order item ID (new one if null).
	 * @param string      $mobile      Billing phone.
	 * @param string      $pincode     Pincode.
	 * @param string      $address     Address line 1.
	 */
	public function buy( int $customer_id, string $plan_code, string $meal_type = 'VEG', ?string $start = '2026-11-30', ?int $item_id = null, string $mobile = '9876543210', string $pincode = '600001', string $address = 'No. 12, Gandhi Street' ): Pack {
		$request                  = new PackRequest();
		$request->customer_id     = $customer_id;
		$request->order_item_id   = $item_id ?? ++$this->next_item;
		$request->order_id        = $request->order_item_id + 5000;
		$request->product_id      = 55;
		$request->plan_code       = $plan_code;
		$request->meal_type       = $meal_type;
		$request->requested_start = $start;
		$request->mobile          = $mobile;
		$request->pincode         = $pincode;
		$request->address_line    = $address;

		return $this->engine->activation()->activate( $request, Actor::system() )['pack'];
	}

	/**
	 * Fresh copy of a pack.
	 *
	 * @param int $id Pack ID.
	 */
	public function pack( int $id ): Pack {
		$pack = $this->stores->subscriptions->find( $id );
		if ( ! $pack ) {
			throw new \RuntimeException( "No pack {$id}" );
		}
		return $pack;
	}

	/**
	 * Dates that use a meal (scheduled, locked, delivered, failed), optionally for one pack.
	 *
	 * @param int      $customer_id Customer.
	 * @param int|null $pack_id     Only this pack.
	 * @return string[]
	 */
	public function meals( int $customer_id, ?int $pack_id = null ): array {
		$dates = array();
		foreach ( $this->stores->schedule->for_customer( $customer_id ) as $day ) {
			if ( DayStatus::uses_meal( $day->status ) && ( null === $pack_id || $day->subscription_id === $pack_id ) ) {
				$dates[] = $day->service_date;
			}
		}
		return $dates;
	}

	/**
	 * Every row as date => status.
	 *
	 * @param int $customer_id Customer.
	 * @return array<string, string>
	 */
	public function calendar( int $customer_id ): array {
		$out = array();
		foreach ( $this->stores->schedule->for_customer( $customer_id ) as $day ) {
			$out[ $day->service_date ] = $day->status;
		}
		return $out;
	}

	/**
	 * A customer actor.
	 *
	 * @param int $customer_id Customer.
	 */
	public function customer( int $customer_id ): Actor {
		return new Actor( $customer_id, 'customer', '127.0.0.1' );
	}

	/**
	 * An admin actor.
	 */
	public function admin(): Actor {
		return new Actor( 1, 'admin', '127.0.0.1' );
	}

	/**
	 * Names of emitted events.
	 *
	 * @return string[]
	 */
	public function event_names(): array {
		return array_map( static fn( $e ) => $e[0], $this->events );
	}

	/** {@inheritDoc} CustomerInfo: a fixed test address. */
	public function delivery_address( Pack $pack ): array {
		return array(
			'name'      => 'Test Customer ' . $pack->customer_id,
			'phone'     => '9000000000',
			'address_1' => 'No. 1, Test Street',
			'address_2' => '',
			'city'      => 'Chennai',
			'postcode'  => '600001',
		);
	}

	/** {@inheritDoc} Events: record them. */
	public function emit( string $name, array $args ): void {
		$this->events[] = array( $name, $args );
	}
}
