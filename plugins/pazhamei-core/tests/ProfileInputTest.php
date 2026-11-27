<?php
/**
 * Tests for ProfileInput: the customer Profile page rules.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\ProfileInput;
use PHPUnit\Framework\TestCase;

/**
 * Name, mobile, address with a served pincode, dietary preference, allergens with severity, instructions.
 */
final class ProfileInputTest extends TestCase {

	/**
	 * @return array<string, mixed>
	 */
	private static function form(): array {
		return array(
			'name'         => 'Meena Raghavan',
			'mobile'       => '+91 91234 56780',
			'address_1'    => 'Flat 4B, Lake View Apartments',
			'address_2'    => 'Velachery Main Road',
			'city'         => 'Chennai',
			'pincode'      => '600042',
			'dietary'      => 'VEG',
			'allergens'    => array( 1 => 'severe', 3 => '', 7 => 'mild' ),
			'instructions' => 'Please ring the bell twice.',
		);
	}

	private static function check( array $form, array $served = array(), bool $taken = false ): array {
		return ProfileInput::check( $form, array( 1, 2, 3, 7 ), $served, static fn() => $taken );
	}

	public function test_valid_profile(): void {
		$result = self::check( self::form() );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( '9123456780', $result['values']['mobile'] );
		$this->assertSame( array( 1 => 'severe', 7 => 'mild' ), $result['values']['allergens'], 'Blank severity = not allergic' );
	}

	public function test_each_field_is_checked(): void {
		$result = self::check(
			array(
				'name'         => 'M',
				'mobile'       => '123',
				'address_1'    => '',
				'city'         => '',
				'pincode'      => '12',
				'dietary'      => 'VEGAN',
				'allergens'    => array( 99 => 'severe' ),
				'instructions' => str_repeat( 'x', 501 ),
			)
		);
		$this->assertSame( array( 'name', 'mobile', 'address_1', 'city', 'pincode', 'dietary', 'allergens', 'instructions' ), array_keys( $result['errors'] ) );
	}

	public function test_unknown_severity_is_refused(): void {
		$form              = self::form();
		$form['allergens'] = array( 1 => 'deadly' );
		$this->assertArrayHasKey( 'allergens', self::check( $form )['errors'] );
	}

	public function test_mobile_of_another_account_is_refused(): void {
		$this->assertSame( 'This phone number is already linked to another account.', self::check( self::form(), array(), true )['errors']['mobile'] );
	}

	public function test_pincode_must_be_served_when_the_list_is_set(): void {
		$this->assertArrayHasKey( 'pincode', self::check( self::form(), array( '600032' ) )['errors'] );
		$this->assertSame( array(), self::check( self::form(), array( '600042' ) )['errors'] );
	}

	public function test_no_dietary_preference_is_fine(): void {
		$form            = self::form();
		$form['dietary'] = '';
		$this->assertSame( array(), self::check( $form )['errors'] );
	}

	public function test_password_change_rules(): void {
		$this->assertNull( ProfileInput::password_error( 'Banana-Leaf-2027', 'Banana-Leaf-2027' ) );
		$this->assertNotNull( ProfileInput::password_error( 'short', 'short' ) );
		$this->assertSame( 'The two new passwords don’t match.', ProfileInput::password_error( 'Banana-Leaf-2027', 'Banana-Leaf-2028' ) );
	}
}
