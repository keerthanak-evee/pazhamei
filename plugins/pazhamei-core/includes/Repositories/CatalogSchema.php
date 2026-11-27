<?php
/**
 * CatalogSchema: table definitions for meals, ingredients, allergens and
 * menus. Created now (Phase 3) so the schema is complete; the screens and
 * queries that use them arrive in Phases 7 and 8.
 *
 * Meals themselves are a custom post type (pz_meal, later), so meal_id
 * columns hold post IDs. See docs/DATA-MODEL.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CREATE TABLE statements for the catalogue tables.
 */
final class CatalogSchema {

	/**
	 * Every catalogue table, for dbDelta.
	 *
	 * @return string[]
	 */
	public static function schemas(): array {
		$o = Db::table_options();
		$t = array(
			'allergens'            => Db::table( 'allergens' ),
			'customer_allergens'   => Db::table( 'customer_allergens' ),
			'ingredients'          => Db::table( 'ingredients' ),
			'ingredient_allergens' => Db::table( 'ingredient_allergens' ),
			'meal_ingredients'     => Db::table( 'meal_ingredients' ),
			'menu_templates'       => Db::table( 'menu_templates' ),
			'menus'                => Db::table( 'menus' ),
			'menu_items'           => Db::table( 'menu_items' ),
			'substitutions'        => Db::table( 'substitutions' ),
		);

		return array(
			"CREATE TABLE {$t['allergens']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(60) NOT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) {$o};",
			"CREATE TABLE {$t['customer_allergens']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  customer_id bigint(20) unsigned NOT NULL,
  allergen_id bigint(20) unsigned NOT NULL,
  severity varchar(10) NOT NULL DEFAULT 'avoid',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY customer_allergen (customer_id,allergen_id),
  KEY allergen_id (allergen_id)
) {$o};",
			"CREATE TABLE {$t['ingredients']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  name varchar(100) NOT NULL,
  unit varchar(10) NOT NULL DEFAULT 'g',
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY name (name)
) {$o};",
			"CREATE TABLE {$t['ingredient_allergens']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  ingredient_id bigint(20) unsigned NOT NULL,
  allergen_id bigint(20) unsigned NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY ingredient_allergen (ingredient_id,allergen_id),
  KEY allergen_id (allergen_id)
) {$o};",
			"CREATE TABLE {$t['meal_ingredients']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  meal_id bigint(20) unsigned NOT NULL,
  ingredient_id bigint(20) unsigned NOT NULL,
  grams decimal(8,2) NOT NULL DEFAULT 0.00,
  PRIMARY KEY  (id),
  UNIQUE KEY meal_ingredient (meal_id,ingredient_id),
  KEY ingredient_id (ingredient_id)
) {$o};",
			"CREATE TABLE {$t['menu_templates']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  weekday tinyint(1) unsigned NOT NULL,
  meal_type varchar(10) NOT NULL,
  meal_id bigint(20) unsigned NOT NULL,
  sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY weekday_type_meal (weekday,meal_type,meal_id)
) {$o};",
			"CREATE TABLE {$t['menus']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  menu_date date NOT NULL,
  meal_type varchar(10) NOT NULL,
  status varchar(20) NOT NULL DEFAULT 'draft',
  version int(10) unsigned NOT NULL DEFAULT 1,
  published_by bigint(20) unsigned DEFAULT NULL,
  published_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  updated_at datetime NOT NULL,
  PRIMARY KEY  (id),
  UNIQUE KEY date_type (menu_date,meal_type)
) {$o};",
			"CREATE TABLE {$t['menu_items']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  menu_id bigint(20) unsigned NOT NULL,
  meal_id bigint(20) unsigned NOT NULL,
  sort_order smallint(5) unsigned NOT NULL DEFAULT 0,
  PRIMARY KEY  (id),
  UNIQUE KEY menu_meal (menu_id,meal_id)
) {$o};",
			"CREATE TABLE {$t['substitutions']} (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  menu_id bigint(20) unsigned NOT NULL,
  original_meal_id bigint(20) unsigned NOT NULL,
  replacement_meal_id bigint(20) unsigned NOT NULL,
  reason varchar(255) NOT NULL DEFAULT '',
  status varchar(20) NOT NULL DEFAULT 'pending',
  requested_by bigint(20) unsigned NOT NULL DEFAULT 0,
  decided_by bigint(20) unsigned DEFAULT NULL,
  decided_at datetime DEFAULT NULL,
  created_at datetime NOT NULL,
  PRIMARY KEY  (id),
  KEY menu_id (menu_id),
  KEY status (status)
) {$o};",
		);
	}
}
