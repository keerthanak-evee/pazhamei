<?php
/**
 * Shape: turns packs, days and pauses into the JSON the portals receive.
 *
 * Dates go out both as 'Y-m-d' (for code) and as "30 Nov 2026" (for display).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\Day;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\Pause;
use Pazhamei\Domain\Plans;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * JSON shapes.
 */
final class Shape {

	/**
	 * @param Pack $pack Pack.
	 * @return array<string, mixed>
	 */
	public static function pack( Pack $pack ): array {
		return array(
			'id'              => $pack->id,
			'plan'            => Plans::slug_for_code( $pack->plan_code ),
			'plan_code'       => $pack->plan_code,
			'meal_type'       => $pack->meal_type,
			'status'          => $pack->status,
			'total_meals'     => $pack->total_meals,
			'consumed_meals'  => $pack->consumed_meals,
			'start_date'      => $pack->start_date,
			'end_date'        => $pack->end_date,
			'start_display'   => Dates::display( $pack->start_date ),
			'end_display'     => Dates::display( $pack->end_date ),
			'renewed_from_id' => $pack->renewed_from_id,
			'order_id'        => $pack->order_id,
		);
	}

	/**
	 * @param Day  $day    Schedule row.
	 * @param bool $locked Is its date past the cutoff now?
	 * @return array<string, mixed>
	 */
	public static function day( Day $day, bool $locked ): array {
		return array(
			'date'            => $day->service_date,
			'display'         => Dates::display( $day->service_date ),
			'weekday'         => Dates::weekday_name( $day->service_date ),
			'status'          => $day->status,
			'meal_type'       => $day->meal_type,
			'subscription_id' => $day->subscription_id,
			'locked'          => $locked,
		);
	}

	/**
	 * @param Pause $pause Pause.
	 * @return array<string, mixed>
	 */
	public static function pause( Pause $pause ): array {
		return array(
			'id'              => $pause->id,
			'subscription_id' => $pause->subscription_id,
			'start_date'      => $pause->start_date,
			'end_date'        => $pause->end_date,
			'open_ended'      => null === $pause->end_date,
			'status'          => $pause->status,
		);
	}
}
