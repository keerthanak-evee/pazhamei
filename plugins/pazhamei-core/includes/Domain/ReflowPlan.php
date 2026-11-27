<?php
/**
 * ReflowPlan: the result of Reflow::plan(), before anything is saved.
 *
 * ReflowService applies it inside a transaction: delete, then update, then
 * insert, then save each pack's dates and status.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Changes produced by one reflow.
 */
final class ReflowPlan {

	/**
	 * Schedule row IDs to delete.
	 *
	 * @var int[]
	 */
	public array $delete_ids = array();

	/**
	 * Existing rows to change: row ID => new values.
	 *
	 * @var array<int, array{subscription_id: int, meal_type: string, status: string, source: string}>
	 */
	public array $updates = array();

	/**
	 * New rows to insert (id is 0).
	 *
	 * @var Day[]
	 */
	public array $inserts = array();

	/**
	 * New dates and status per pack: pack ID => values.
	 *
	 * @var array<int, array{start_date: ?string, end_date: ?string, status: string}>
	 */
	public array $packs = array();

	/** Date where an open-ended pause stopped the walk, or null. */
	public ?string $stopped_at = null;

	/**
	 * Does the plan change any schedule row?
	 */
	public function changes_rows(): bool {
		return $this->delete_ids || $this->updates || $this->inserts;
	}
}
