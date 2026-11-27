<?php
/**
 * ChangeLogStore: the audit trail (wp_pz_change_log).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories\Contracts;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Storage for change log entries.
 */
interface ChangeLogStore {

	/**
	 * @param array<string, mixed> $entry Column => value.
	 * @return int New ID.
	 */
	public function insert( array $entry ): int;

	/**
	 * Entries for one thing, oldest first.
	 *
	 * @param string $entity_type subscription, schedule, pause, holiday, customer or job.
	 * @param int    $entity_id   Its ID.
	 * @return array<int, array<string, mixed>>
	 */
	public function for_entity( string $entity_type, int $entity_id ): array;

	/**
	 * The newest entries, newest first.
	 *
	 * @param int $limit How many.
	 * @return array<int, array<string, mixed>>
	 */
	public function latest( int $limit ): array;
}
