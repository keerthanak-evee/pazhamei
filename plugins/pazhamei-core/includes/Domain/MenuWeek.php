<?php
/**
 * MenuWeek: "this week's menu" for the customer Meals page.
 *
 * The week is Monday to Saturday of the current week; on a Sunday it is the
 * coming week. For each day and each meal type (Veg, Non-Veg, Mixed) it
 * lists the published meals, or marks the day "Menu coming soon" when no
 * menu is published yet (menus are created in Phase 8).
 *
 * Plain PHP, unit-tested. The caller passes the published menus and the
 * meal details it found.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The week of menus.
 */
final class MenuWeek {

	/**
	 * The six service weekdays to show.
	 *
	 * @param string $today 'Y-m-d' (IST).
	 * @return string[] Monday to Saturday.
	 */
	public static function dates( string $today ): array {
		$weekday = (int) gmdate( 'N', (int) strtotime( $today . ' 00:00:00 UTC' ) ); // 1 = Monday … 7 = Sunday.
		$monday  = 7 === $weekday ? Dates::add_days( $today, 1 ) : Dates::add_days( $today, 1 - $weekday );

		$dates = array();
		for ( $i = 0; $i < 6; $i++ ) {
			$dates[] = Dates::add_days( $monday, $i );
		}
		return $dates;
	}

	/**
	 * Build the week.
	 *
	 * @param string                              $today Today 'Y-m-d'.
	 * @param array<string, array<string, int[]>> $menus Published menus: date => meal type => meal IDs.
	 * @param array<int, array<string, mixed>>    $meals Meal details by ID (name, description, kcal, protein, allergens, image).
	 * @param string                              $mine  The customer's meal type ('' if none).
	 * @return array<int, array<string, mixed>>
	 */
	public static function build( string $today, array $menus, array $meals, string $mine ): array {
		$days = array();
		foreach ( self::dates( $today ) as $date ) {
			$types = array();
			foreach ( Plans::MEAL_TYPES as $code => $label ) {
				$items = array();
				foreach ( $menus[ $date ][ $code ] ?? array() as $meal_id ) {
					if ( isset( $meals[ $meal_id ] ) ) {
						$items[] = $meals[ $meal_id ] + array( 'id' => $meal_id );
					}
				}
				$types[] = array(
					'code'       => $code,
					'label'      => $label,
					'isMine'     => $code === $mine,
					'meals'      => $items,
					'comingSoon' => ! $items,
				);
			}
			$days[] = array(
				'date'    => $date,
				'weekday' => Dates::weekday_name( $date ),
				'display' => Dates::weekday_name( $date ) . ' ' . Dates::display( $date ),
				'isToday' => $date === $today,
				'types'   => $types,
			);
		}
		return $days;
	}
}
