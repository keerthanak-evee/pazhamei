<?php
/**
 * Rows: turns database rows (arrays) into Pack, Day and Pause objects and back.
 *
 * Shared by the MySQL repositories and the in-memory test stores, so both
 * read and write exactly the same columns.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Domain\Day;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\Pause;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Row mapping.
 */
final class Rows {

	/**
	 * @param array<string, mixed> $row wp_pz_subscriptions row.
	 */
	public static function pack( array $row ): Pack {
		$pack                  = new Pack();
		$pack->id              = (int) $row['id'];
		$pack->customer_id     = (int) $row['customer_id'];
		$pack->order_id        = (int) $row['order_id'];
		$pack->order_item_id   = (int) $row['order_item_id'];
		$pack->product_id      = (int) $row['product_id'];
		$pack->plan_code       = (string) $row['plan_code'];
		$pack->meal_type       = (string) $row['meal_type'];
		$pack->quantity        = (int) ( $row['quantity'] ?? 1 );
		$pack->total_meals     = (int) $row['total_meals'];
		$pack->consumed_meals  = (int) $row['consumed_meals'];
		$pack->requested_start = self::date_or_null( $row['requested_start'] ?? null );
		$pack->start_date      = self::date_or_null( $row['start_date'] ?? null );
		$pack->end_date        = self::date_or_null( $row['end_date'] ?? null );
		$pack->status          = (string) $row['status'];
		$pack->renewed_from_id = isset( $row['renewed_from_id'] ) ? (int) $row['renewed_from_id'] : null;
		$pack->created_at      = (string) $row['created_at'];
		$pack->updated_at      = (string) $row['updated_at'];
		return $pack;
	}

	/**
	 * Columns for inserting a pack (no id).
	 *
	 * @param Pack $pack Pack.
	 * @return array<string, mixed>
	 */
	public static function from_pack( Pack $pack ): array {
		$data = $pack->to_array();
		unset( $data['id'] );
		return $data;
	}

	/**
	 * @param array<string, mixed> $row wp_pz_schedule row.
	 */
	public static function day( array $row ): Day {
		$day                  = new Day();
		$day->id              = (int) $row['id'];
		$day->customer_id     = (int) $row['customer_id'];
		$day->service_date    = (string) $row['service_date'];
		$day->subscription_id = (int) $row['subscription_id'];
		$day->meal_type       = (string) $row['meal_type'];
		$day->status          = (string) $row['status'];
		$day->source          = (string) $row['source'];
		$day->created_at      = (string) $row['created_at'];
		$day->updated_at      = (string) $row['updated_at'];
		return $day;
	}

	/**
	 * Columns for inserting a schedule row (no id).
	 *
	 * @param Day $day Row.
	 * @return array<string, mixed>
	 */
	public static function from_day( Day $day ): array {
		$data = $day->to_array();
		unset( $data['id'] );
		return $data;
	}

	/**
	 * @param array<string, mixed> $row wp_pz_pauses row.
	 */
	public static function pause( array $row ): Pause {
		$pause                  = new Pause();
		$pause->id              = (int) $row['id'];
		$pause->subscription_id = (int) $row['subscription_id'];
		$pause->customer_id     = (int) $row['customer_id'];
		$pause->start_date      = (string) $row['start_date'];
		$pause->end_date        = self::date_or_null( $row['end_date'] ?? null );
		$pause->status          = (string) $row['status'];
		$pause->created_by      = (int) $row['created_by'];
		$pause->created_at      = (string) $row['created_at'];
		return $pause;
	}

	/**
	 * Columns for inserting a pause (no id).
	 *
	 * @param Pause $pause Pause.
	 * @return array<string, mixed>
	 */
	public static function from_pause( Pause $pause ): array {
		$data = $pause->to_array();
		unset( $data['id'] );
		return $data;
	}

	/**
	 * A 'Y-m-d' date, or null for NULL / empty / zero dates.
	 *
	 * @param mixed $value Column value.
	 */
	private static function date_or_null( $value ): ?string {
		if ( null === $value || '' === $value || '0000-00-00' === $value ) {
			return null;
		}
		return (string) $value;
	}
}
