<?php
/**
 * Stores: every storage object the subscription engine uses, in one bundle.
 *
 * The live site uses Stores::wpdb() (MySQL). PHPUnit builds the same bundle
 * from in-memory stores, so the services run unchanged in tests.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Repositories;

use Pazhamei\Repositories\Contracts\ChangeLogStore;
use Pazhamei\Repositories\Contracts\DailyOrderStore;
use Pazhamei\Repositories\Contracts\HolidayStore;
use Pazhamei\Repositories\Contracts\PauseStore;
use Pazhamei\Repositories\Contracts\ProfileStore;
use Pazhamei\Repositories\Contracts\ScheduleStore;
use Pazhamei\Repositories\Contracts\SubscriptionStore;
use Pazhamei\Repositories\Contracts\Transaction;
use Pazhamei\Repositories\Contracts\TrialClaimStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The engine's storage bundle.
 */
final class Stores {

	public SubscriptionStore $subscriptions;
	public ScheduleStore $schedule;
	public PauseStore $pauses;
	public HolidayStore $holidays;
	public DailyOrderStore $daily_orders;
	public ChangeLogStore $change_log;
	public ProfileStore $profiles;
	public TrialClaimStore $trial_claims;
	public Transaction $transaction;

	/**
	 * @param SubscriptionStore $subscriptions Packs.
	 * @param ScheduleStore     $schedule      Schedule rows.
	 * @param PauseStore        $pauses        Pauses.
	 * @param HolidayStore      $holidays      Holidays.
	 * @param DailyOrderStore   $daily_orders  Daily orders.
	 * @param ChangeLogStore    $change_log    Change log.
	 * @param ProfileStore      $profiles      Customer profiles.
	 * @param TrialClaimStore   $trial_claims  Trial claims.
	 * @param Transaction       $transaction   Transaction runner.
	 */
	public function __construct(
		SubscriptionStore $subscriptions,
		ScheduleStore $schedule,
		PauseStore $pauses,
		HolidayStore $holidays,
		DailyOrderStore $daily_orders,
		ChangeLogStore $change_log,
		ProfileStore $profiles,
		TrialClaimStore $trial_claims,
		Transaction $transaction
	) {
		$this->subscriptions = $subscriptions;
		$this->schedule      = $schedule;
		$this->pauses        = $pauses;
		$this->holidays      = $holidays;
		$this->daily_orders  = $daily_orders;
		$this->change_log    = $change_log;
		$this->profiles      = $profiles;
		$this->trial_claims  = $trial_claims;
		$this->transaction   = $transaction;
	}

	/**
	 * The MySQL stores used on the live site.
	 */
	public static function wpdb(): self {
		return new self(
			new SubscriptionRepository(),
			new ScheduleRepository(),
			new PauseRepository(),
			new HolidayRepository(),
			new DailyOrderRepository(),
			new ChangeLogRepository(),
			new ProfileRepository(),
			new TrialClaimRepository(),
			new WpdbTransaction()
		);
	}

	/**
	 * Every engine table's CREATE TABLE statement, for the installer.
	 *
	 * @return string[]
	 */
	public static function schemas(): array {
		return array_merge(
			array(
				SubscriptionRepository::schema(),
				ScheduleRepository::schema(),
				PauseRepository::schema(),
				HolidayRepository::schema(),
				DailyOrderRepository::schema(),
				ChangeLogRepository::schema(),
				ProfileRepository::schema(),
				TrialClaimRepository::schema(),
			),
			CatalogSchema::schemas()
		);
	}
}
