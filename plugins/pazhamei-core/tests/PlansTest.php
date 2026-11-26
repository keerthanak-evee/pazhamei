<?php
/**
 * Tests for Plans: the three packs and meal types (business rule 2).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Plans;
use PHPUnit\Framework\TestCase;

/**
 * Plan catalogue.
 */
final class PlansTest extends TestCase {

	public function test_packs_hold_1_6_and_26_meals(): void {
		$meals = array_map( static fn( array $plan ): int => $plan['meals'], Plans::all() );

		$this->assertSame( array( 'trial' => 1, '6-day' => 6, '26-day' => 26 ), $meals );
	}

	public function test_meal_types_are_veg_nonveg_mixed(): void {
		$this->assertSame( array( 'VEG', 'NONVEG', 'MIXED' ), array_keys( Plans::MEAL_TYPES ) );
		$this->assertTrue( Plans::is_meal_type( 'MIXED' ) );
		$this->assertFalse( Plans::is_meal_type( 'mixed' ) );
	}

	public function test_enquiry_choices_include_not_sure(): void {
		foreach ( array( 'trial', '6-day', '26-day', 'not-sure' ) as $choice ) {
			$this->assertTrue( Plans::is_enquiry_choice( $choice ), $choice );
		}
		$this->assertFalse( Plans::is_enquiry_choice( '12-day' ) );
	}

	public function test_short_labels(): void {
		$this->assertSame( 'Trial (1 lunch)', Plans::short_label( 'trial' ) );
		$this->assertSame( '6-day plan (6 lunches)', Plans::short_label( '6-day' ) );
		$this->assertSame( '26-day plan (26 lunches)', Plans::short_label( '26-day' ) );
		$this->assertSame( '', Plans::short_label( 'nope' ) );
	}

	public function test_no_prices_in_the_catalogue(): void {
		$text = json_encode( Plans::all(), JSON_UNESCAPED_UNICODE );

		$this->assertStringNotContainsString( '₹', $text );
		$this->assertArrayNotHasKey( 'price', Plans::all()['26-day'] );
	}
}
