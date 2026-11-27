<?php
/**
 * MemoryDb: an in-memory stand-in for the engine's MySQL tables, for PHPUnit.
 *
 * It behaves like the real tables where it matters:
 *  - The same UNIQUE keys as the CREATE TABLE statements, checked on insert
 *    and update. A violation throws DuplicateKey with MySQL's own message
 *    ("Duplicate entry '7-2026-11-30' for key 'wp_pz_schedule.customer_date'")
 *    and changes nothing, like a failed MySQL statement.
 *  - NOT NULL columns throw StorageError ("Column 'x' cannot be null").
 *  - Transactions: run() rolls every table back if the work throws. Like
 *    InnoDB, auto-increment counters are not rolled back.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Contracts\Transaction;
use Pazhamei\Repositories\DuplicateKey;
use Pazhamei\Repositories\StorageError;
use Throwable;

/**
 * In-memory tables with MySQL-like constraints.
 */
final class MemoryDb implements Transaction {

	/** UNIQUE keys per table, as in the repositories' schema(): key name => columns. */
	private const UNIQUE = array(
		'subscriptions'     => array( 'order_item_id' => array( 'order_item_id' ) ),
		'schedule'          => array( 'customer_date' => array( 'customer_id', 'service_date' ) ),
		'daily_orders'      => array( 'schedule_id' => array( 'schedule_id' ) ),
		'holidays'          => array( 'holiday_date' => array( 'holiday_date' ) ),
		'customer_profiles' => array( 'customer_id' => array( 'customer_id' ) ),
	);

	/** NOT NULL columns without a default, as in the repositories' schema(). */
	private const NOT_NULL = array(
		'subscriptions'     => array( 'customer_id', 'order_item_id', 'plan_code', 'meal_type', 'total_meals', 'created_at', 'updated_at' ),
		'schedule'          => array( 'customer_id', 'service_date', 'subscription_id', 'meal_type', 'created_at', 'updated_at' ),
		'pauses'            => array( 'subscription_id', 'customer_id', 'start_date', 'created_at' ),
		'daily_orders'      => array( 'schedule_id', 'subscription_id', 'customer_id', 'service_date', 'meal_type', 'address_json', 'allergens_json', 'instructions', 'generated_at', 'updated_at' ),
		'holidays'          => array( 'holiday_date', 'created_at' ),
		'change_log'        => array( 'entity_type', 'action', 'created_at' ),
		'customer_profiles' => array( 'customer_id', 'created_at', 'updated_at' ),
		'trial_claims'      => array( 'customer_id', 'created_at' ),
	);

	/**
	 * Rows per table, keyed by ID.
	 *
	 * @var array<string, array<int, array<string, mixed>>>
	 */
	private array $tables = array();

	/** @var array<string, int> */
	private array $auto_increment = array();

	private int $depth = 0;

	/** Transactions committed / rolled back, for tests. */
	public int $commits = 0;
	public int $rollbacks = 0;

	/**
	 * Insert a row; returns its new ID.
	 *
	 * @param string               $table Table name without prefix.
	 * @param array<string, mixed> $row   Column => value.
	 * @throws DuplicateKey On a UNIQUE violation.
	 * @throws StorageError On a NULL in a NOT NULL column.
	 */
	public function insert( string $table, array $row ): int {
		unset( $row['id'] );
		$this->check_not_null( $table, $row );

		$id        = ( $this->auto_increment[ $table ] ?? 0 ) + 1;
		$row['id'] = $id;
		$this->check_unique( $table, $row, null );

		$this->auto_increment[ $table ] = $id; // Used even if a later statement fails, like InnoDB.
		$this->tables[ $table ][ $id ]  = $row;
		return $id;
	}

	/**
	 * Change columns of one row; returns rows changed (0 or 1).
	 *
	 * @param string               $table  Table.
	 * @param int                  $id     Row ID.
	 * @param array<string, mixed> $fields Column => value.
	 * @throws DuplicateKey On a UNIQUE violation.
	 */
	public function update( string $table, int $id, array $fields ): int {
		if ( ! isset( $this->tables[ $table ][ $id ] ) ) {
			return 0;
		}
		$row = array_merge( $this->tables[ $table ][ $id ], $fields );
		$this->check_not_null( $table, $row );
		$this->check_unique( $table, $row, $id );
		$this->tables[ $table ][ $id ] = $row;
		return 1;
	}

	/**
	 * Delete one row.
	 *
	 * @param string $table Table.
	 * @param int    $id    Row ID.
	 */
	public function delete( string $table, int $id ): bool {
		$existed = isset( $this->tables[ $table ][ $id ] );
		unset( $this->tables[ $table ][ $id ] );
		return $existed;
	}

	/**
	 * One row, or null.
	 *
	 * @param string $table Table.
	 * @param int    $id    Row ID.
	 * @return array<string, mixed>|null
	 */
	public function find( string $table, int $id ): ?array {
		return $this->tables[ $table ][ $id ] ?? null;
	}

	/**
	 * Rows matching a filter, in ID order.
	 *
	 * @param string        $table  Table.
	 * @param callable|null $filter fn( array $row ): bool.
	 * @return array<int, array<string, mixed>>
	 */
	public function rows( string $table, ?callable $filter = null ): array {
		$rows = array_values( $this->tables[ $table ] ?? array() );
		return null === $filter ? $rows : array_values( array_filter( $rows, $filter ) );
	}

	/** {@inheritDoc} */
	public function run( callable $work ) {
		if ( $this->depth > 0 ) {
			++$this->depth;
			try {
				return $work();
			} finally {
				--$this->depth;
			}
		}

		$snapshot    = $this->tables;
		$this->depth = 1;
		try {
			$result = $work();
			++$this->commits;
			return $result;
		} catch ( Throwable $error ) {
			$this->tables = $snapshot;
			++$this->rollbacks;
			throw $error;
		} finally {
			$this->depth = 0;
		}
	}

	/**
	 * Is a transaction open?
	 */
	public function in_transaction(): bool {
		return $this->depth > 0;
	}

	/**
	 * @param string               $table Table.
	 * @param array<string, mixed> $row   Row.
	 * @param int|null             $self  ID of the row being updated.
	 * @throws DuplicateKey On a clash.
	 */
	private function check_unique( string $table, array $row, ?int $self ): void {
		foreach ( self::UNIQUE[ $table ] ?? array() as $key => $columns ) {
			$values = array_map( static fn( $c ) => (string) ( $row[ $c ] ?? '' ), $columns );
			foreach ( $this->tables[ $table ] ?? array() as $id => $other ) {
				if ( $id === $self ) {
					continue;
				}
				$other_values = array_map( static fn( $c ) => (string) ( $other[ $c ] ?? '' ), $columns );
				if ( $other_values === $values ) {
					throw new DuplicateKey( sprintf( "Duplicate entry '%s' for key 'wp_pz_%s.%s'", implode( '-', $values ), $table, $key ) );
				}
			}
		}
	}

	/**
	 * @param string               $table Table.
	 * @param array<string, mixed> $row   Row.
	 * @throws StorageError On a NULL.
	 */
	private function check_not_null( string $table, array $row ): void {
		foreach ( self::NOT_NULL[ $table ] ?? array() as $column ) {
			// Like MySQL under WordPress's SQL mode: an explicit NULL fails; a missing column does not.
			if ( array_key_exists( $column, $row ) && null === $row[ $column ] ) {
				throw new StorageError( "Column '{$column}' cannot be null" );
			}
		}
	}
}
