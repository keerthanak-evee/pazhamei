<?php
/**
 * EngineConfig: the settings the subscription engine needs, in one object.
 *
 * Built from pz_settings on the live site (Settings::engine_config()) and
 * by hand in tests, so tests never need WordPress options.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

use DateTimeZone;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Engine settings.
 */
final class EngineConfig {

	public string $cutoff_time;
	public bool $auto_mark_delivered;
	public bool $trial_enabled;
	public DateTimeZone $timezone;

	/** @var string[] Pincodes we deliver to; empty = every pincode ([EDIT] until the owner adds them). */
	public array $delivery_pincodes;

	/**
	 * @param string       $cutoff_time         'HH:MM' in IST (rule 3).
	 * @param bool         $auto_mark_delivered Close locked days as delivered at 23:30 (rule 15).
	 * @param bool         $trial_enabled       Trial switch (rule 10).
	 * @param DateTimeZone $timezone            Site timezone.
	 * @param string[]     $delivery_pincodes   Served pincodes (empty = all).
	 */
	public function __construct( string $cutoff_time, bool $auto_mark_delivered, bool $trial_enabled, DateTimeZone $timezone, array $delivery_pincodes = array() ) {
		$this->cutoff_time         = $cutoff_time;
		$this->auto_mark_delivered = $auto_mark_delivered;
		$this->trial_enabled       = $trial_enabled;
		$this->timezone            = $timezone;
		$this->delivery_pincodes   = $delivery_pincodes;
	}

	/**
	 * The defaults, in IST. Handy for tests.
	 */
	public static function defaults(): self {
		return new self( '20:00', true, false, new DateTimeZone( 'Asia/Kolkata' ) );
	}
}
