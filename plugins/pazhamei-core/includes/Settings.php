<?php
/**
 * Settings: reads the single `pz_settings` option that holds all business settings.
 *
 * The admin portal's Settings page (Phase 8) will edit these values.
 * Code should always read settings through Settings::get() so that missing
 * keys fall back to the defaults below.
 *
 * @package Pazhamei
 */

namespace Pazhamei;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values and a simple getter for `pz_settings`.
 */
final class Settings {

	/** Name of the option in wp_options. */
	public const OPTION = 'pz_settings';

	/**
	 * Default settings, created on activation.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'cutoff_time'       => '20:00', // IST, on the calendar day before the meal (business rule 3).
			'trial_enabled'     => false,   // Trial pack is sold only when this is on (rule 10).
			'trial_price'       => 0,       // Rupees.
			'max_pause_days'    => 30,
			'service_days'      => array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ), // Sunday is always off (rule 1).
			'delivery_pincodes' => array(), // Empty until the admin adds areas.
		);
	}

	/**
	 * Read one setting, falling back to its default.
	 *
	 * @param string $key Setting name, e.g. 'cutoff_time'.
	 * @return mixed
	 */
	public static function get( string $key ) {
		$saved    = get_option( self::OPTION, array() );
		$settings = array_merge( self::defaults(), is_array( $saved ) ? $saved : array() );

		return $settings[ $key ] ?? null;
	}
}
