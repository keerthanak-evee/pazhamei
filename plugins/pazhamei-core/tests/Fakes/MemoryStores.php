<?php
/**
 * MemoryStores: in-memory versions of the smaller stores (pauses, holidays,
 * daily orders, change log, profiles, trial claims) for tests, and a helper
 * that builds a full Stores bundle on one MemoryDb.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests\Fakes;

use Pazhamei\Repositories\Stores;

/**
 * Builds the in-memory storage bundle.
 */
final class MemoryStores {

	/**
	 * A Stores bundle backed by one MemoryDb.
	 *
	 * @param MemoryDb $db Shared in-memory tables (also the transaction runner).
	 */
	public static function make( MemoryDb $db ): Stores {
		return new Stores(
			new MemorySubscriptions( $db ),
			new MemorySchedule( $db ),
			new MemoryPauses( $db ),
			new MemoryHolidays( $db ),
			new MemoryDailyOrders( $db ),
			new MemoryChangeLog( $db ),
			new MemoryProfiles( $db ),
			new MemoryTrialClaims( $db ),
			$db
		);
	}
}
