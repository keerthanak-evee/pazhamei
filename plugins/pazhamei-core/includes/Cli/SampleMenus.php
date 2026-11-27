<?php
/**
 * SampleMenus: local-only sample meals and menus for `wp pz seed`.
 *
 * Creates (once) six sample lunches per meal type as pz_meal posts marked
 * _pz_sample = 1, gives them ingredients linked to the starter allergens,
 * and publishes Veg / Non-Veg / Mixed menus for every service day of this
 * week and next week. Rerunning reuses the same meals and republishes.
 * Photos come from the storefront images already in the Media Library.
 *
 * Every name, number and photo here is sample data for testing the Meals
 * page; the real meals and menus are entered in Phase 8.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cli;

use Pazhamei\Admin\MealPostType;
use Pazhamei\Clock;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\MenuWeek;
use Pazhamei\Install\MediaImporter;
use Pazhamei\Repositories\AllergenRepository;
use Pazhamei\Repositories\MenuRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sample meals and menus.
 */
final class SampleMenus {

	/** Ingredient => allergens it contains. */
	private const INGREDIENTS = array(
		'Curd'          => array( 'Milk' ),
		'Ghee'          => array( 'Milk' ),
		'Wheat chapati' => array( 'Gluten / wheat' ),
		'Fish'          => array( 'Fish' ),
		'Prawns'        => array( 'Shellfish' ),
		'Egg'           => array( 'Egg' ),
		'Groundnut'     => array( 'Peanut' ),
		'Cashew'        => array( 'Tree nuts' ),
		'Sesame oil'    => array( 'Sesame' ),
		'Mustard seeds' => array( 'Mustard' ),
		'Rice'          => array(),
		'Toor dal'      => array(),
		'Chicken'       => array(),
	);

	/**
	 * Meals per type, Monday to Saturday: name, description, kcal, protein (g), ingredients, photo key.
	 */
	private const MEALS = array(
		'VEG'    => array(
			array( 'Ponni rice, drumstick sambar, beans poriyal', 'With curd and a papad.', 620, 18, array( 'Rice', 'Toor dal', 'Curd', 'Mustard seeds' ), 'cooking-ponni-rice' ),
			array( 'Millet rice, keerai kootu, carrot poriyal', 'Little millet with greens and moong dal.', 560, 19, array( 'Toor dal', 'Sesame oil', 'Mustard seeds' ), 'cooking-millet-rice' ),
			array( 'Chapati, paneer kurma, cabbage poriyal', 'Two wheat chapatis.', 650, 24, array( 'Wheat chapati', 'Curd', 'Cashew' ), 'cooking-chapati' ),
			array( 'Lemon rice, vadai, mor kuzhambu', 'With groundnut and curry leaves.', 600, 15, array( 'Rice', 'Groundnut', 'Curd' ), 'cooking-flavoured-rice' ),
			array( 'Red rice, vatha kuzhambu, beetroot poriyal', 'Matta red rice with a tangy kuzhambu.', 590, 14, array( 'Rice', 'Sesame oil', 'Mustard seeds' ), 'cooking-red-rice' ),
			array( 'Veg biryani, raita, onion salad', 'Seeraga samba rice.', 680, 16, array( 'Rice', 'Curd', 'Ghee', 'Cashew' ), 'cooking-biryani' ),
		),
		'NONVEG' => array(
			array( 'Ponni rice, chicken chettinad, beans poriyal', 'With rasam.', 720, 34, array( 'Rice', 'Chicken', 'Sesame oil' ), 'cooking-ponni-rice' ),
			array( 'Red rice, meen kuzhambu, cabbage poriyal', 'Seer fish in a tamarind gravy.', 680, 32, array( 'Rice', 'Fish', 'Mustard seeds' ), 'cooking-red-rice' ),
			array( 'Chapati, egg curry, keerai poriyal', 'Two wheat chapatis.', 690, 28, array( 'Wheat chapati', 'Egg' ), 'cooking-chapati' ),
			array( 'Millet rice, prawn thokku, carrot poriyal', 'Spicy prawns.', 660, 30, array( 'Toor dal', 'Prawns', 'Sesame oil' ), 'cooking-millet-rice' ),
			array( 'Tomato rice, pepper chicken fry, raita', 'With curd raita.', 710, 33, array( 'Rice', 'Chicken', 'Curd' ), 'cooking-flavoured-rice' ),
			array( 'Chicken biryani, egg, raita', 'Seeraga samba rice.', 780, 36, array( 'Rice', 'Chicken', 'Egg', 'Curd', 'Ghee' ), 'cooking-biryani' ),
		),
		'MIXED'  => array(
			array( 'Ponni rice, sambar, egg poriyal', 'Veg kuzhambu with an egg side.', 640, 22, array( 'Rice', 'Toor dal', 'Egg' ), 'cooking-ponni-rice' ),
			array( 'Millet rice, keerai kootu, fish fry', 'A veg base with a fish side.', 650, 27, array( 'Toor dal', 'Fish', 'Sesame oil' ), 'cooking-millet-rice' ),
			array( 'Chapati, paneer kurma, chicken sukka', 'Two wheat chapatis.', 700, 31, array( 'Wheat chapati', 'Chicken', 'Curd', 'Cashew' ), 'cooking-chapati' ),
			array( 'Lemon rice, vadai, chicken 65', 'With groundnut.', 690, 26, array( 'Rice', 'Groundnut', 'Chicken' ), 'cooking-flavoured-rice' ),
			array( 'Red rice, vatha kuzhambu, prawn fry', 'A tangy kuzhambu with prawns.', 660, 25, array( 'Rice', 'Prawns', 'Mustard seeds' ), 'cooking-red-rice' ),
			array( 'Veg biryani, egg roast, raita', 'Seeraga samba rice.', 720, 22, array( 'Rice', 'Egg', 'Curd', 'Ghee' ), 'cooking-biryani' ),
		),
	);

	/**
	 * Create the sample data. Local only (the seed command checks).
	 *
	 * @return array{meals: int, menus: int, from: string, to: string}
	 */
	public static function run(): array {
		$now = Clock::now()->format( 'Y-m-d H:i:s' );
		AllergenRepository::seed( $now );

		$ingredients = array();
		foreach ( self::INGREDIENTS as $name => $allergens ) {
			$ingredients[ $name ] = MenuRepository::sample_ingredient( $name, $allergens, $now );
		}

		$meal_ids = array();
		foreach ( self::MEALS as $type => $meals ) {
			foreach ( $meals as $i => $meal ) {
				$id = self::meal( $type . '-' . $i, $meal );
				foreach ( $meal[4] as $ingredient ) {
					MenuRepository::sample_link( $id, $ingredients[ $ingredient ] );
				}
				$meal_ids[ $type ][ $i ] = $id;
			}
		}

		$today = Dates::date_of( Clock::now() );
		$dates = array_merge( MenuWeek::dates( $today ), MenuWeek::dates( Dates::add_days( MenuWeek::dates( $today )[0], 7 ) ) );
		$menus = 0;
		foreach ( $dates as $i => $date ) {
			foreach ( $meal_ids as $type => $ids ) {
				MenuRepository::sample_menu( $date, $type, array( $ids[ $i % 6 ] ), $now );
				++$menus;
			}
		}

		return array(
			'meals' => count( $meal_ids, COUNT_RECURSIVE ) - count( $meal_ids ),
			'menus' => $menus,
			'from'  => $dates[0],
			'to'    => end( $dates ),
		);
	}

	/**
	 * Find or create one sample meal post.
	 *
	 * @param string            $key  Stable key, e.g. 'VEG-0'.
	 * @param array<int, mixed> $meal Name, description, kcal, protein, ingredients, photo key.
	 */
	private static function meal( string $key, array $meal ): int {
		$found = get_posts(
			array(
				'post_type'      => MealPostType::TYPE,
				'post_status'    => 'any',
				'meta_key'       => '_pz_sample', // phpcs:ignore WordPress.DB.SlowDBQuery -- Local sample data only.
				'meta_value'     => $key, // phpcs:ignore WordPress.DB.SlowDBQuery
				'fields'         => 'ids',
				'posts_per_page' => 1,
			)
		);
		$id = $found ? (int) $found[0] : (int) wp_insert_post(
			array(
				'post_type'    => MealPostType::TYPE,
				'post_status'  => 'publish',
				'post_title'   => $meal[0],
				'post_excerpt' => '[Sample] ' . $meal[1],
				'post_author'  => 0,
			)
		);
		update_post_meta( $id, '_pz_sample', $key );
		update_post_meta( $id, '_pz_kcal', (int) $meal[2] );
		update_post_meta( $id, '_pz_protein_g', (int) $meal[3] );
		$photo = MediaImporter::find( (string) $meal[5] );
		if ( $photo > 0 ) {
			set_post_thumbnail( $id, $photo );
		}
		return $id;
	}
}
