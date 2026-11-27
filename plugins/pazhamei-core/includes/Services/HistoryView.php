<?php
/**
 * HistoryView: every pack the customer has bought, newest first, with the
 * days that were delivered, not delivered (failed), skipped or cancelled by
 * a holiday. Shown on /account/history (pz/order-history), next to the
 * payments, which come from WooCommerce.
 *
 * Plain PHP on top of the Engine, so it is unit-tested.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\DayStatus;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The customer's pack history.
 */
final class HistoryView {

	/**
	 * Build the view.
	 *
	 * @param Engine $engine      Engine.
	 * @param int    $customer_id Customer.
	 * @return array<int, array<string, mixed>> Packs, newest first.
	 */
	public static function packs( Engine $engine, int $customer_id ): array {
		$by_pack = array();
		foreach ( $engine->stores->schedule->for_customer( $customer_id ) as $day ) {
			$by_pack[ $day->subscription_id ][] = $day;
		}

		$out = array();
		foreach ( array_reverse( $engine->stores->subscriptions->for_customer( $customer_id ) ) as $pack ) {
			$days      = $by_pack[ $pack->id ] ?? array();
			$delivered = array();
			$failed    = array();
			$skipped   = 0;
			foreach ( $days as $day ) {
				$label = Dates::weekday_name( $day->service_date ) . ' ' . Dates::display( $day->service_date );
				if ( DayStatus::DELIVERED === $day->status ) {
					$delivered[] = $label;
				} elseif ( DayStatus::FAILED === $day->status ) {
					$failed[] = $label;
				} elseif ( DayStatus::SKIPPED === $day->status ) {
					++$skipped;
				}
			}

			$out[] = DashboardView::pack( $pack ) + array(
				'orderId'   => $pack->order_id,
				'delivered' => $delivered,
				'failed'    => $failed,
				'skipped'   => $skipped,
			);
		}
		return $out;
	}
}
