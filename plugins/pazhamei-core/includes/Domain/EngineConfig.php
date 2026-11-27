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
	public float $trial_price;
	public DateTimeZone $timezone;

	/**
	 * @param string       $cutoff_time         'HH:MM' in IST (rule 3).
	 * @param bool         $auto_mark_delivered Close locked days as delivered at 23:30 (rule 15).
	 * @param bool         $trial_enabled       Trial switch (rule 10).
	 * @param float        $trial_price         Trial price in rupees.
	 * @param DateTimeZone $timezone            Site timezone.
	 */
	public function __construct( string $cutoff_time, bool $auto_mark_delivered, bool $trial_enabled, float $trial_price, DateTimeZone $timezone ) {
		$this->cutoff_time         = $cutoff_time;
		$this->auto_mark_delivered = $auto_mark_delivered;
		$this->trial_enabled       = $trial_enabled;
		$this->trial_price         = $trial_price;
		$this->timezone            = $timezone;
	}

	/**
	 * The defaults, in IST. Handy for tests.
	 */
	public static function defaults(): self {
		return new self( '20:00', true, false, 0.0, new DateTimeZone( 'Asia/Kolkata' ) );
	}
}
