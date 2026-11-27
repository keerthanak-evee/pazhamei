<?php
/**
 * MenuRepository: published menus (wp_pz_menus, wp_pz_menu_items) and meal
 * allergens (wp_pz_meal_ingredients → wp_pz_ingredient_allergens → wp_pz_allergens).
 *
 * Reading is for the customer Meals page (Phase 4). Writing here is only
 * for the local sample data in `wp pz seed`; the menu screens come in Phase 8.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Menus in MySQL.
 */
final class MenuRepository {

	/**
	 * Published menus between two dates: date => meal type => meal IDs (in order).
	 *
	 * @param string $from 'Y-m-d'.
	 * @param string $to   'Y-m-d'.
	 * @return array<string, array<string, int[]>>
	 */
	public static function published( string $from, string $to ): array {
		$rows = Db::results(
			Db::wpdb()->prepare(
				"SELECT m.menu_date, m.meal_type, i.meal_id FROM %i m JOIN %i i ON i.menu_id = m.id
				WHERE m.status = 'published' AND m.menu_date BETWEEN %s AND %s
				ORDER BY m.menu_date, m.meal_type, i.sort_order, i.id",
				Db::table( 'menus' ),
				Db::table( 'menu_items' ),
				$from,
				$to
			)
		);
		$out = array();
		foreach ( $rows as $row ) {
			$out[ (string) $row['menu_date'] ][ (string) $row['meal_type'] ][] = (int) $row['meal_id'];
		}
		return $out;
	}

	/**
	 * Allergen names per meal, from the meal's ingredients.
	 *
	 * @param int[] $meal_ids Meal post IDs.
	 * @return array<int, string[]> meal_id => allergen names.
	 */
	public static function allergens_for( array $meal_ids ): array {
		if ( ! $meal_ids ) {
			return array();
		}
		$rows = Db::results(
			Db::wpdb()->prepare(
				'SELECT DISTINCT mi.meal_id, a.name FROM %i mi
				JOIN %i ia ON ia.ingredient_id = mi.ingredient_id
				JOIN %i a ON a.id = ia.allergen_id
				WHERE mi.meal_id IN (' . Db::placeholders( $meal_ids, '%d' ) . ') ORDER BY a.id', // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders -- Placeholders built by Db::placeholders().
				array_merge( array( Db::table( 'meal_ingredients' ), Db::table( 'ingredient_allergens' ), Db::table( 'allergens' ) ), array_map( 'intval', $meal_ids ) )
			)
		);
		$out = array();
		foreach ( $rows as $row ) {
			$out[ (int) $row['meal_id'] ][] = (string) $row['name'];
		}
		return $out;
	}

	/* ---------- Local sample data (wp pz seed) ---------- */

	/**
	 * Create an ingredient if missing and link it to allergens. Returns its ID.
	 *
	 * @param string   $name      Ingredient name.
	 * @param string[] $allergens Allergen names (must exist).
	 * @param string   $now       'Y-m-d H:i:s'.
	 */
	public static function sample_ingredient( string $name, array $allergens, string $now ): int {
		Db::query( Db::wpdb()->prepare( "INSERT IGNORE INTO %i (name, unit, created_at) VALUES (%s, 'g', %s)", Db::table( 'ingredients' ), $name, $now ) );
		$id = (int) Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE name = %s', Db::table( 'ingredients' ), $name ) );
		foreach ( $allergens as $allergen ) {
			Db::query(
				Db::wpdb()->prepare(
					'INSERT IGNORE INTO %i (ingredient_id, allergen_id) SELECT %d, id FROM %i WHERE name = %s',
					Db::table( 'ingredient_allergens' ),
					$id,
					Db::table( 'allergens' ),
					$allergen
				)
			);
		}
		return $id;
	}

	/**
	 * Link a meal to an ingredient (no-op if linked already).
	 *
	 * @param int $meal_id       Meal post ID.
	 * @param int $ingredient_id Ingredient ID.
	 */
	public static function sample_link( int $meal_id, int $ingredient_id ): void {
		Db::query( Db::wpdb()->prepare( 'INSERT IGNORE INTO %i (meal_id, ingredient_id, grams) VALUES (%d, %d, 0)', Db::table( 'meal_ingredients' ), $meal_id, $ingredient_id ) );
	}

	/**
	 * Publish a menu for a date and meal type with these meals (replaces its items).
	 *
	 * @param string $date      'Y-m-d'.
	 * @param string $meal_type VEG, NONVEG or MIXED.
	 * @param int[]  $meal_ids  Meal post IDs in order.
	 * @param string $now       'Y-m-d H:i:s'.
	 */
	public static function sample_menu( string $date, string $meal_type, array $meal_ids, string $now ): void {
		$table = Db::table( 'menus' );
		Db::query(
			Db::wpdb()->prepare(
				"INSERT INTO %i (menu_date, meal_type, status, version, published_at, created_at, updated_at)
				VALUES (%s, %s, 'published', 1, %s, %s, %s)
				ON DUPLICATE KEY UPDATE status = 'published', updated_at = VALUES(updated_at)",
				$table,
				$date,
				$meal_type,
				$now,
				$now,
				$now
			)
		);
		$menu_id = (int) Db::value( Db::wpdb()->prepare( 'SELECT id FROM %i WHERE menu_date = %s AND meal_type = %s', $table, $date, $meal_type ) );
		Db::query( Db::wpdb()->prepare( 'DELETE FROM %i WHERE menu_id = %d', Db::table( 'menu_items' ), $menu_id ) );
		foreach ( array_values( $meal_ids ) as $i => $meal_id ) {
			Db::insert(
				Db::table( 'menu_items' ),
				array(
					'menu_id'    => $menu_id,
					'meal_id'    => $meal_id,
					'sort_order' => $i,
				)
			);
		}
	}
}
