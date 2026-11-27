<?php
/**
 * Tests for Pincodes: the delivery pincode check at checkout.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Pincodes;
use Pazhamei\Services\CheckoutCheck;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Served pincodes come from the delivery_pincodes setting; an empty list accepts every valid pincode.
 */
final class PincodesTest extends TestCase {

	public function test_empty_list_accepts_any_valid_pincode(): void {
		$this->assertNull( Pincodes::refusal( '600032', array() ) );
		$this->assertNull( Pincodes::refusal( ' 110001 ', array() ) );
	}

	public function test_invalid_pincode_is_refused_even_when_the_list_is_empty(): void {
		$this->assertSame( 'Please enter a valid 6-digit delivery pincode.', Pincodes::refusal( '6000', array() ) );
		$this->assertNotNull( Pincodes::refusal( '', array() ) );
		$this->assertNotNull( Pincodes::refusal( '060032', array() ) );
	}

	public function test_only_served_pincodes_pass_when_the_list_is_set(): void {
		$served = array( '600032', 600097, ' 600113 ', 'junk' );

		$this->assertNull( Pincodes::refusal( '600032', $served ) );
		$this->assertNull( Pincodes::refusal( '600097', $served ), 'Numbers in the setting work too.' );
		$this->assertNull( Pincodes::refusal( '600113', $served ) );
		$this->assertStringContainsString( 'don’t deliver to 600001', (string) Pincodes::refusal( '600001', $served ) );
		$this->assertSame( array( '600032', '600097', '600113' ), Pincodes::clean( $served ) );
	}

	public function test_checkout_refuses_an_unserved_pincode(): void {
		$kit                                     = new TestKit();
		$kit->engine->config->delivery_pincodes = array( '600032' );

		$errors = CheckoutCheck::errors( $kit->engine, 7, array( 'D6' ), '2026-11-30', '9876543210', '600001', '12 Main Road' );
		$this->assertCount( 1, $errors );
		$this->assertStringContainsString( '600001', $errors[0] );

		$this->assertSame( array(), CheckoutCheck::errors( $kit->engine, 7, array( 'D6' ), '2026-11-30', '9876543210', '600032', '12 Main Road' ) );
	}
}
