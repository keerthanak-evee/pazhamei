<?php
/**
 * Tests for HistoryView: the customer's past packs and delivered days.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\HistoryView;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Packs newest first, with delivered, failed and skipped days.
 */
final class HistoryViewTest extends TestCase {

	public function test_delivered_failed_and_skipped_days(): void {
		$kit  = new TestKit( '2026-11-27 10:00:00' );
		$pack = $kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
		$kit->engine->skips()->skip( 7, '2026-12-02', $kit->customer( 7 ) );

		// Monday: locked at Sunday 20:00, then the day closes as delivered.
		$kit->at( '2026-11-29 20:01:00' );
		$kit->engine->lock_job()->run( $kit->admin() );
		$kit->at( '2026-11-30 23:31:00' );
		$kit->engine->close_day_job()->run( $kit->admin() );

		// Tuesday: locked, then marked not delivered.
		$kit->at( '2026-11-30 20:01:00' );
		$kit->engine->lock_job()->run( $kit->admin() );
		$tuesday = $kit->engine->stores->schedule->find_by_date( 7, '2026-12-01' );
		$kit->engine->deliveries()->mark_failed( $tuesday->id, $kit->admin(), 'Rider could not find the house' );

		$renewal = $kit->buy( 7, 'D26', 'NONVEG', '2026-12-07' );
		$history = HistoryView::packs( $kit->engine, 7 );

		$this->assertSame( array( $renewal->id, $pack->id ), array_column( $history, 'id' ), 'Newest first' );
		$this->assertSame( array( 'Mon 30 Nov 2026' ), $history[1]['delivered'] );
		$this->assertSame( array( 'Tue 1 Dec 2026' ), $history[1]['failed'] );
		$this->assertSame( 1, $history[1]['skipped'] );
		$this->assertSame( 'Non-vegetarian', $history[0]['mealType'] );
	}

	public function test_customer_without_packs(): void {
		$kit = new TestKit();
		$this->assertSame( array(), HistoryView::packs( $kit->engine, 7 ) );
	}
}
