<?php
/**
 * Tests for DashboardView: what the customer dashboard shows.
 *
 * Clock: Friday 27 Nov 2026, 10:00 IST. Pack: 6-day Veg from Monday 30 Nov.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\DashboardView;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * The server decides every Skip/Undo/Pause/Resume button and every label.
 */
final class DashboardViewTest extends TestCase {

	private TestKit $kit;

	protected function setUp(): void {
		$this->kit = new TestKit( '2026-11-27 10:00:00' );
		$this->kit->buy( 7, 'D6' );
	}

	/**
	 * @return array<string, array<string, mixed>> Days keyed by date.
	 */
	private function days(): array {
		$view = DashboardView::build( $this->kit->engine, 7 );
		return array_column( $view['days'], null, 'date' );
	}

	public function test_summary_of_the_current_pack(): void {
		$view = DashboardView::build( $this->kit->engine, 7 );

		$this->assertSame( 'The Weekly Table', $view['current']['name'] );
		$this->assertSame( 'Vegetarian', $view['current']['mealType'] );
		$this->assertSame( 6, $view['current']['left'] );
		$this->assertSame( 'Mon 30 Nov 2026', $view['current']['start'] );
		$this->assertSame( 'Sat 5 Dec 2026', $view['current']['end'] );
		$this->assertTrue( $view['canPause'] );
		$this->assertFalse( $view['canResume'] );
		$this->assertSame( array(), $view['queued'] );
	}

	public function test_sundays_are_off_and_service_days_can_be_skipped(): void {
		$days = $this->days();

		$this->assertSame( '2026-11-27', array_key_first( $days ), 'Starts today' );
		$this->assertSame( 'off', $days['2026-11-29']['kind'] );
		$this->assertSame( 'Sunday · no delivery', $days['2026-11-29']['label'] );
		$this->assertSame( 'scheduled', $days['2026-11-30']['kind'] );
		$this->assertTrue( $days['2026-11-30']['canSkip'] );
		$this->assertFalse( $days['2026-11-30']['canUndo'] );
		$this->assertSame( 'none', $days['2026-11-28']['kind'], 'Saturday before the pack starts' );
	}

	public function test_skipped_day_offers_undo_and_holiday_is_labelled(): void {
		$this->kit->engine->skips()->skip( 7, '2026-12-02', $this->kit->customer( 7 ) );
		$this->kit->engine->holidays()->add( '2026-12-03', 'Festival', $this->kit->admin() );
		$days = $this->days();

		$this->assertSame( 'skipped', $days['2026-12-02']['kind'] );
		$this->assertTrue( $days['2026-12-02']['canUndo'] );
		$this->assertFalse( $days['2026-12-02']['canSkip'] );
		$this->assertSame( 'Kitchen holiday', $days['2026-12-03']['label'] );
		$this->assertSame( 'off', $days['2026-12-03']['kind'] );
	}

	public function test_locked_day_shows_the_reason_and_no_buttons(): void {
		$this->kit->at( '2026-11-29 20:01:00' ); // Sunday after the cutoff: Monday is locked.
		$days = $this->days();

		$this->assertTrue( $days['2026-11-30']['locked'] );
		$this->assertFalse( $days['2026-11-30']['canSkip'] );
		$this->assertStringContainsString( 'closed at 20:00 on 29 Nov 2026', $days['2026-11-30']['lockReason'] );
		$this->assertTrue( $days['2026-12-01']['canSkip'], 'Tuesday is still open' );
	}

	public function test_pause_offers_resume(): void {
		$this->kit->engine->pauses()->pause( 7, '2026-12-02', null, $this->kit->customer( 7 ) );
		$view = DashboardView::build( $this->kit->engine, 7 );

		$this->assertFalse( $view['canPause'] );
		$this->assertTrue( $view['canResume'] );
		$this->assertTrue( $view['pause']['openEnded'] );
		$this->assertStringContainsString( 'until you resume', $view['pause']['text'] );
		$this->assertSame( 'paused', $view['current']['status'] );
	}

	public function test_cutoff_countdown(): void {
		$view = DashboardView::build( $this->kit->engine, 7 );
		$this->assertSame( 'Changes for tomorrow close at 8:00 PM', $view['cutoff']['text'] );
		$this->assertSame( 10 * 3600, $view['cutoff']['secondsLeft'] );

		$this->kit->at( '2026-11-28 21:00:00' ); // Saturday evening: next changeable day is Monday.
		$view = DashboardView::build( $this->kit->engine, 7 );
		$this->assertSame( '2026-11-30', $view['cutoff']['nextDate'] );
		$this->assertSame( 'Changes for Mon 30 Nov 2026 close at 8:00 PM on Sun 29 Nov 2026', $view['cutoff']['text'] );
	}

	public function test_customer_without_packs(): void {
		$view = DashboardView::build( $this->kit->engine, 99 );
		$this->assertNull( $view['current'] );
		$this->assertFalse( $view['hasPacks'] );
		$this->assertFalse( $view['canPause'] );
	}
}
