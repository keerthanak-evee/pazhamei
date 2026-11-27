<?php
/**
 * Tests for MenuWeek: this week's menu on the customer Meals page.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\MenuWeek;
use PHPUnit\Framework\TestCase;

/**
 * Monday to Saturday; "Menu coming soon" when nothing is published.
 */
final class MenuWeekTest extends TestCase {

	public function test_week_is_monday_to_saturday(): void {
		$friday = MenuWeek::dates( '2026-11-27' );
		$this->assertSame( array( '2026-11-23', '2026-11-24', '2026-11-25', '2026-11-26', '2026-11-27', '2026-11-28' ), $friday );
		$this->assertSame( $friday, MenuWeek::dates( '2026-11-23' ), 'Monday' );
	}

	public function test_on_sunday_it_shows_the_coming_week(): void {
		$this->assertSame( '2026-11-30', MenuWeek::dates( '2026-11-29' )[0] );
		$this->assertSame( '2026-12-05', MenuWeek::dates( '2026-11-29' )[5] );
	}

	public function test_published_meals_and_coming_soon(): void {
		$menus = array( '2026-11-30' => array( 'VEG' => array( 11, 12 ), 'NONVEG' => array( 99 ) ) );
		$meals = array(
			11 => array( 'name' => 'Red rice and sambar' ),
			12 => array( 'name' => 'Beans poriyal' ),
		);
		$week  = MenuWeek::build( '2026-11-29', $menus, $meals, 'VEG' );
		$types = array_column( $week[0]['types'], null, 'code' );

		$this->assertSame( array( 'Red rice and sambar', 'Beans poriyal' ), array_column( $types['VEG']['meals'], 'name' ) );
		$this->assertTrue( $types['VEG']['isMine'] );
		$this->assertFalse( $types['VEG']['comingSoon'] );
		$this->assertTrue( $types['NONVEG']['comingSoon'], 'A meal that no longer exists is left out' );
		$this->assertTrue( $types['MIXED']['comingSoon'] );
		$this->assertTrue( $week[1]['types'][0]['comingSoon'], 'Tuesday has no menu yet' );
		$this->assertSame( 'Mon 30 Nov 2026', $week[0]['display'] );
	}
}
