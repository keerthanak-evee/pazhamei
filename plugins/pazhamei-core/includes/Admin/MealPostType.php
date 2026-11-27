<?php
/**
 * MealPostType: the "pz_meal" post type, one post per lunch on the menu.
 *
 * Menus (wp_pz_menus / wp_pz_menu_items) point at these posts. A meal has a
 * title, a photo (featured image), a short description (excerpt), and two
 * numbers in post meta: _pz_kcal and _pz_protein_g. Its allergens come from
 * its ingredients (wp_pz_meal_ingredients → wp_pz_ingredient_allergens).
 *
 * Not public: meals only appear inside the portal blocks. Only
 * administrators see it in wp-admin (Pazhamei → Meals) until the /manage
 * menu screens arrive (Phase 8).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Meal post type.
 */
final class MealPostType {

	/** Post type name. */
	public const TYPE = 'pz_meal';

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'init', array( self::class, 'register_type' ) );
	}

	/**
	 * Register the post type and its two numbers.
	 */
	public static function register_type(): void {
		register_post_type(
			self::TYPE,
			array(
				'labels'          => array(
					'name'          => __( 'Meals', 'pazhamei-core' ),
					'singular_name' => __( 'Meal', 'pazhamei-core' ),
					'add_new_item'  => __( 'Add meal', 'pazhamei-core' ),
					'edit_item'     => __( 'Edit meal', 'pazhamei-core' ),
				),
				'public'          => false,
				'show_ui'         => true,
				'show_in_menu'    => EnquiriesPage::SLUG,
				'show_in_rest'    => true,
				'supports'        => array( 'title', 'excerpt', 'thumbnail', 'custom-fields' ),
				'capability_type' => 'post',
				'map_meta_cap'    => true,
				'capabilities'    => array( 'create_posts' => 'manage_options' ),
			)
		);

		foreach ( array( '_pz_kcal' => 'Calories (kcal)', '_pz_protein_g' => 'Protein (g)' ) as $key => $label ) {
			register_post_meta(
				self::TYPE,
				$key,
				array(
					'type'              => 'integer',
					'description'       => $label,
					'single'            => true,
					'show_in_rest'      => true,
					'sanitize_callback' => 'absint',
					'auth_callback'     => static fn() => current_user_can( 'manage_options' ),
				)
			);
		}
	}
}
