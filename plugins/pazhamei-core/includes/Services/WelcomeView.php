<?php
/**
 * WelcomeView: what the /account landing page says.
 *
 * - The current pack in one line.
 * - Tomorrow: a lunch (and whether it can still be changed), skipped,
 *   paused, a Sunday or holiday, or nothing; plus the next lunch if
 *   tomorrow has none.
 * - For a customer with no pack: whether to offer the trial (switch on and
 *   eligible, decided by the caller) or "Choose a plan".
 *
 * Built on DashboardView, so both pages always agree. Plain PHP, unit-tested.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Dates;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The customer landing page's data.
 */
final class WelcomeView {

	/**
	 * Build the view.
	 *
	 * @param Engine $engine      Engine.
	 * @param int    $customer_id Customer.
	 * @param bool   $trial_ok    The trial is on and this customer may have it.
	 * @return array{packLine: string, tomorrow: string, next: string, hasPack: bool, offerTrial: bool}
	 */
	public static function build( Engine $engine, int $customer_id, bool $trial_ok ): array {
		$view = DashboardView::build( $engine, $customer_id );
		$pack = $view['current'];

		$pack_line = '';
		if ( $pack ) {
			$pack_line = sprintf( '%s · %s · %d of %d lunches left · ends %s', $pack['name'], $pack['mealType'], $pack['left'], $pack['total'], $pack['end'] );
			if ( 'paused' === $pack['status'] ) {
				$pack_line .= ' · paused';
			}
		}

		$tomorrow_date = Dates::add_days( $view['today'], 1 );
		$tomorrow      = null;
		$next          = null;
		foreach ( $view['days'] as $day ) {
			if ( $day['date'] === $tomorrow_date ) {
				$tomorrow = $day;
			}
			if ( null === $next && $day['date'] >= $tomorrow_date && in_array( $day['kind'], array( 'scheduled', 'locked' ), true ) ) {
				$next = $day;
			}
		}

		$next_line = '';
		if ( $next && ( null === $tomorrow || $next['date'] !== $tomorrow['date'] ) ) {
			$next_line = 'Your next lunch: ' . $next['display'] . ( '' !== $next['mealType'] ? ' (' . $next['mealType'] . ')' : '' ) . '.';
		}

		return array(
			'packLine'   => $pack_line,
			'tomorrow'   => self::tomorrow_line( $tomorrow, $view['cutoff']['time'] ),
			'next'       => $next_line,
			'hasPack'    => null !== $pack || (bool) $view['queued'],
			'offerTrial' => null === $pack && ! $view['queued'] && $trial_ok,
		);
	}

	/**
	 * One sentence about tomorrow.
	 *
	 * @param array<string, mixed>|null $day  Tomorrow from DashboardView (null if outside the calendar).
	 * @param string                    $time Cutoff time, e.g. "8:00 PM".
	 */
	private static function tomorrow_line( ?array $day, string $time ): string {
		if ( null === $day ) {
			return 'No lunch tomorrow.';
		}
		$when = 'Tomorrow, ' . $day['display'];

		switch ( $day['kind'] ) {
			case 'scheduled':
			case 'locked':
				$type = '' !== $day['mealType'] ? $day['mealType'] . ' lunch' : 'Lunch';
				return $day['locked']
					? $when . ': ' . $type . '. Locked: it can no longer be changed.'
					: $when . ': ' . $type . '. You can still skip it until ' . $time . ' today.';
			case 'skipped':
				return $when . ': skipped' . ( $day['canUndo'] ? ' (you can undo this until ' . $time . ' today).' : '.' );
			case 'paused':
				return $when . ': paused.';
			case 'off':
				return $when . ': ' . $day['label'] . '.';
			default:
				return 'No lunch tomorrow.';
		}
	}
}
