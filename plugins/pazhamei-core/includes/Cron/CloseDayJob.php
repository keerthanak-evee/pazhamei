<?php
/**
 * CloseDayJob: the 23:30 close-day job (business rule 15, open decision 9).
 *
 * With the setting auto_mark_delivered on (default), every day still
 * `locked` up to the last closed date counts as delivered (failed days were
 * already marked). With it off, nothing changes: days stay locked until
 * someone marks them.
 *
 * "Last closed date" is today once it is 23:30 IST, otherwise yesterday, so
 * a run that fires a few minutes late (after midnight) never closes the new
 * day before lunch.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Cron;

use DateTimeImmutable;
use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\DayStatus;
use Pazhamei\Services\Engine;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Close the day.
 */
final class CloseDayJob {

	/** IST time the day closes. */
	public const CLOSE_TIME = '23:30';

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Run the job.
	 *
	 * @param Actor $actor Usually Actor::system().
	 * @return array{through: string, auto_mark_delivered: bool, delivered: int}
	 */
	public function run( Actor $actor ): array {
		$through = $this->last_closed_date();
		$summary = array(
			'through'             => $through,
			'auto_mark_delivered' => $this->engine->config->auto_mark_delivered,
			'delivered'           => 0,
		);

		if ( $this->engine->config->auto_mark_delivered ) {
			foreach ( $this->engine->stores->schedule->in_range( '2000-01-01', $through, array( DayStatus::LOCKED ) ) as $day ) {
				$this->engine->deliveries()->mark_delivered( $day->id, $actor );
				++$summary['delivered'];
			}
		}

		$this->engine->log( 'job', 0, 'close_day', null, $summary, $actor );
		return $summary;
	}

	/**
	 * Today if it is past 23:30 IST, else yesterday.
	 */
	public function last_closed_date(): string {
		$now   = $this->engine->now();
		$today = Dates::date_of( $now );
		$close = new DateTimeImmutable( $today . ' ' . self::CLOSE_TIME . ':00', $this->engine->config->timezone );

		return $now >= $close ? $today : Dates::add_days( $today, -1 );
	}
}
