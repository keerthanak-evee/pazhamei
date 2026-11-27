<?php
/**
 * AllergenRepository: the allergen list (wp_pz_allergens) and each
 * customer's allergens with severity (wp_pz_customer_allergens).
 *
 * The starter list is seeded by the installer (owner request, Phase 4).
 * Reading a customer's allergens is in ProfileRepository::allergens().
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Allergens in MySQL.
 */
final class AllergenRepository {

	/** Starter allergen list. */
	public const STARTER = array( 'Peanut', 'Tree nuts', 'Milk', 'Egg', 'Gluten / wheat', 'Soy', 'Fish', 'Shellfish', 'Sesame', 'Mustard' );

	/**
	 * Add any missing starter allergens. Existing rows are never changed.
	 *
	 * @param string $now 'Y-m-d H:i:s'.
	 * @return int How many were added.
	 */
	public static function seed( string $now ): int {
		$added = 0;
		foreach ( self::STARTER as $name ) {
			$added += (int) Db::query( Db::wpdb()->prepare( 'INSERT IGNORE INTO %i (name, created_at) VALUES (%s, %s)', Db::table( 'allergens' ), $name, $now ) );
		}
		return $added;
	}

	/**
	 * Every allergen: id => name, in list order.
	 *
	 * @return array<int, string>
	 */
	public static function all(): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT id, name FROM %i ORDER BY id', Db::table( 'allergens' ) ) );
		$out  = array();
		foreach ( $rows as $row ) {
			$out[ (int) $row['id'] ] = (string) $row['name'];
		}
		return $out;
	}

	/**
	 * The customer's allergens: allergen id => severity.
	 *
	 * @param int $customer_id Customer.
	 * @return array<int, string>
	 */
	public static function for_customer( int $customer_id ): array {
		$rows = Db::results( Db::wpdb()->prepare( 'SELECT allergen_id, severity FROM %i WHERE customer_id = %d', Db::table( 'customer_allergens' ), $customer_id ) );
		$out  = array();
		foreach ( $rows as $row ) {
			$out[ (int) $row['allergen_id'] ] = (string) $row['severity'];
		}
		return $out;
	}

	/**
	 * Replace the customer's allergens.
	 *
	 * @param int                $customer_id Customer.
	 * @param array<int, string> $allergens   Allergen id => severity.
	 * @param string             $now         'Y-m-d H:i:s'.
	 */
	public static function replace_for_customer( int $customer_id, array $allergens, string $now ): void {
		Db::query( Db::wpdb()->prepare( 'DELETE FROM %i WHERE customer_id = %d', Db::table( 'customer_allergens' ), $customer_id ) );
		foreach ( $allergens as $allergen_id => $severity ) {
			Db::query(
				Db::wpdb()->prepare(
					'INSERT INTO %i (customer_id, allergen_id, severity, created_at) VALUES (%d, %d, %s, %s)',
					Db::table( 'customer_allergens' ),
					$customer_id,
					(int) $allergen_id,
					$severity,
					$now
				)
			);
		}
	}
}
