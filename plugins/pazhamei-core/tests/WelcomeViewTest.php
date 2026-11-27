<?php
/**
 * Tests for WelcomeView: the /account landing page.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\WelcomeView;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Pack line, tomorrow's lunch and the trial offer.
 */
final class WelcomeViewTest extends TestCase {

	public function test_new_customer_gets_the_trial_only_when_allowed(): void {
		$kit = new TestKit( '2026-11-27 10:00:00' );

		$view = WelcomeView::build( $kit->engine, 7, true );
		$this->assertFalse( $view['hasPack'] );
		$this->assertTrue( $view['offerTrial'] );
		$this->assertSame( 'No lunch tomorrow.', $view['tomorrow'] );

		$this->assertFalse( WelcomeView::build( $kit->engine, 7, false )['offerTrial'] );
	}

	public function test_tomorrow_can_still_be_skipped(): void {
		$kit = new TestKit( '2026-11-29 10:00:00' ); // Sunday morning.
		$kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
		$view = WelcomeView::build( $kit->engine, 7, true );

		$this->assertSame( 'The Weekly Table · Vegetarian · 6 of 6 lunches left · ends Sat 5 Dec 2026', $view['packLine'] );
		$this->assertSame( 'Tomorrow, Mon 30 Nov 2026: Vegetarian lunch. You can still skip it until 8:00 PM today.', $view['tomorrow'] );
		$this->assertSame( '', $view['next'] );
		$this->assertFalse( $view['offerTrial'], 'Customers with a pack are not offered the trial' );
	}

	public function test_tomorrow_locked_after_the_cutoff(): void {
		$kit = new TestKit( '2026-11-29 10:00:00' );
		$kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
		$kit->at( '2026-11-29 20:01:00' );

		$this->assertSame( 'Tomorrow, Mon 30 Nov 2026: Vegetarian lunch. Locked: it can no longer be changed.', WelcomeView::build( $kit->engine, 7, false )['tomorrow'] );
	}

	public function test_sunday_tomorrow_shows_the_next_lunch(): void {
		$kit = new TestKit( '2026-11-28 10:00:00' ); // Saturday.
		$kit->buy( 7, 'D6', 'VEG', '2026-11-30' );
		$view = WelcomeView::build( $kit->engine, 7, false );

		$this->assertSame( 'Tomorrow, Sun 29 Nov 2026: Sunday · no delivery.', $view['tomorrow'] );
		$this->assertSame( 'Your next lunch: Mon 30 Nov 2026 (Vegetarian).', $view['next'] );
	}
}
