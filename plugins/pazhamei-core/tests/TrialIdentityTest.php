<?php
/**
 * Tests for TrialIdentity: mobile and address normalisation (rule 10).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\TrialIdentity;
use PHPUnit\Framework\TestCase;

/**
 * Trial comparison keys.
 */
final class TrialIdentityTest extends TestCase {

	public function test_mobile_compares_the_last_10_digits(): void {
		$expected = '9876543210';
		foreach ( array( '9876543210', '+91 98765 43210', '+919876543210', '09876543210', '098765-43210', '91 9876 543 210' ) as $mobile ) {
			$this->assertSame( $expected, TrialIdentity::mobile_key( $mobile ), $mobile );
		}
	}

	public function test_short_mobile_gives_no_key(): void {
		$this->assertSame( '', TrialIdentity::mobile_key( '12345' ) );
	}

	public function test_house_number_ignores_filler_words_and_punctuation(): void {
		$this->assertSame( '12', TrialIdentity::house_number( 'No. 12, Gandhi Street' ) );
		$this->assertSame( '12', TrialIdentity::house_number( 'Door No 12 Gandhi St' ) );
		$this->assertSame( '12', TrialIdentity::house_number( 'no.12, gandhi street' ) );
		$this->assertSame( '12', TrialIdentity::house_number( '#12 Gandhi Street' ) );
		$this->assertSame( '4b', TrialIdentity::house_number( 'Flat 4-B, Lotus Apartments, 2nd Street' ) );
		$this->assertSame( '4b', TrialIdentity::house_number( 'Flat No. 4B Lotus Apts' ) );
		$this->assertSame( '123', TrialIdentity::house_number( '12/3 Temple Road' ) );
	}

	public function test_address_key_matches_reformatted_addresses_at_the_same_pincode(): void {
		$first  = TrialIdentity::address_key( '600 001', 'Flat No. 4-B, Lotus Apartments' );
		$second = TrialIdentity::address_key( '600001', '4B Lotus Apts' );

		$this->assertSame( '600001|4b', $first );
		$this->assertSame( $first, $second );
		$this->assertNotSame( $first, TrialIdentity::address_key( '600002', '4B Lotus Apts' ), 'Different pincode' );
	}

	public function test_missing_parts_give_no_key(): void {
		$this->assertSame( '', TrialIdentity::address_key( '', '12 Gandhi Street' ) );
		$this->assertSame( '', TrialIdentity::address_key( '600001', '' ) );
	}
}
