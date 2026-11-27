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

use Pazhamei\Domain\EngineConfig;
use Pazhamei\Domain\Plans;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Default values and a simple getter for `pz_settings`.
 */
final class Settings {

	/** Name of the option in wp_options. */
	public const OPTION = 'pz_settings';

	/** Keys that older versions stored and are no longer used. */
	private const RETIRED = array(
		'max_pause_days', // Owner decision (Phase 3): pauses have no time limit.
		'trial_price',    // Owner decision (Phase 4): the Trial variation prices are the only trial price.
	);

	/**
	 * Default settings, created on activation.
	 *
	 * @return array<string, mixed>
	 */
	public static function defaults(): array {
		return array(
			'cutoff_time'         => '20:00', // IST, on the calendar day before the meal (business rule 3).
			'trial_enabled'       => false,   // Trial pack is sold only when this is on (rule 10).
			'auto_mark_delivered' => true,    // At 23:30, locked days not marked failed count as delivered (rule 15).
			'service_days'        => array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat' ), // Sunday is always off (rule 1).
			'delivery_pincodes'   => array(), // [EDIT] Pincodes we deliver to. Empty = accept any pincode.
			'plan_includes'       => array(), // Plan code => "what's included" lines, edited in Plans & Prices. Empty = the text in Domain\Plans.
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

	/**
	 * Add new keys with their defaults and drop retired ones. Saved values
	 * are never changed. Called by the installer on upgrade.
	 */
	public static function upgrade(): void {
		$saved = get_option( self::OPTION, array() );
		$saved = is_array( $saved ) ? $saved : array();

		$merged = array_merge( self::defaults(), $saved );
		foreach ( self::RETIRED as $key ) {
			unset( $merged[ $key ] );
		}

		if ( $merged !== $saved ) {
			update_option( self::OPTION, $merged );
		}
	}

	/**
	 * The "what's included" lines for a plan: the owner's text from Plans & Prices,
	 * or the starting text in Domain\Plans.
	 *
	 * @param string $slug Plan URL value, e.g. '6-day'.
	 * @return string[]
	 */
	public static function plan_includes( string $slug ): array {
		$plan  = Plans::all()[ $slug ] ?? null;
		$saved = (array) self::get( 'plan_includes' );
		if ( null === $plan ) {
			return array();
		}
		$lines = $saved[ $plan['code'] ] ?? null;
		return is_array( $lines ) && $lines ? array_map( 'strval', $lines ) : $plan['includes'];
	}

	/**
	 * Save one setting (used by the wp-admin screens).
	 *
	 * @param string $key   Setting name.
	 * @param mixed  $value New value.
	 */
	public static function set( string $key, $value ): void {
		$saved         = get_option( self::OPTION, array() );
		$saved         = is_array( $saved ) ? $saved : array();
		$saved[ $key ] = $value;
		update_option( self::OPTION, $saved );
	}

	/**
	 * The settings the subscription engine needs.
	 */
	public static function engine_config(): EngineConfig {
		return new EngineConfig(
			(string) self::get( 'cutoff_time' ),
			(bool) self::get( 'auto_mark_delivered' ),
			(bool) self::get( 'trial_enabled' ),
			wp_timezone()
		);
	}
}
