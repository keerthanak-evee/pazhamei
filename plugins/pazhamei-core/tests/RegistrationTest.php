<?php
/**
 * Tests for Registration: the Register fields, phone uniqueness and phone sign-in.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Registration;
use Pazhamei\Tests\Fakes\MemoryDb;
use Pazhamei\Tests\Fakes\MemoryProfiles;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Register: full name, email (WooCommerce), phone (unique, 10 digits), pincode, password ×2.
 */
final class RegistrationTest extends TestCase {

	/**
	 * A valid form.
	 *
	 * @return array<string, string>
	 */
	private static function form(): array {
		return array(
			'pz_full_name' => 'Arun Kumar',
			'email'        => 'arun.k@example.test',
			'pz_mobile'    => '91234 56789',
			'pz_pincode'   => '600032',
			'password'     => 'Banana-Leaf-2026',
			'pz_password2' => 'Banana-Leaf-2026',
		);
	}

	private static function nobody_has_it(): callable {
		return static fn( string $mobile ) => false;
	}

	public function test_valid_form_has_no_errors(): void {
		$this->assertSame( array(), Registration::errors( self::form(), self::nobody_has_it() ) );
	}

	public function test_each_field_is_checked(): void {
		$errors = Registration::errors(
			array(
				'pz_full_name' => ' A ',
				'pz_mobile'    => '12345',
				'pz_pincode'   => '012345',
				'password'     => 'short',
				'pz_password2' => 'short',
			),
			self::nobody_has_it()
		);

		$this->assertSame( array( 'pz_full_name', 'pz_mobile', 'pz_pincode', 'password' ), array_keys( $errors ) );
	}

	public function test_passwords_must_match(): void {
		$form                 = self::form();
		$form['pz_password2'] = 'Banana-Leaf-2027';
		$this->assertSame( array( 'pz_password2' ), array_keys( Registration::errors( $form, self::nobody_has_it() ) ) );
	}

	public function test_mobile_already_used_is_refused(): void {
		$asked  = '';
		$errors = Registration::errors(
			array( 'pz_mobile' => '+91 91234-56789' ) + self::form(),
			static function ( string $mobile ) use ( &$asked ) {
				$asked = $mobile;
				return true;
			}
		);

		$this->assertSame( '9123456789', $asked, 'Uniqueness is checked on the 10 digits.' );
		$this->assertSame( Registration::MOBILE_TAKEN, $errors['pz_mobile'] );
	}

	/**
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function mobiles(): array {
		return array(
			'plain'         => array( '9123456789', '9123456789' ),
			'spaces'        => array( '91234 56789', '9123456789' ),
			'+91'           => array( '+91 91234 56789', '9123456789' ),
			'91 no plus'    => array( '919123456789', '9123456789' ),
			'leading 0'     => array( '09123456789', '9123456789' ),
			'dashes'        => array( '912-345-6789', '9123456789' ),
			'starts with 5' => array( '5123456789', '' ),
			'too short'     => array( '912345678', '' ),
			'too long'      => array( '91234567890', '' ),
			'letters'       => array( '91234abc89', '' ),
			'email'         => array( 'arun@example.test', '' ),
			'empty'         => array( '', '' ),
		);
	}

	#[DataProvider( 'mobiles' )]
	public function test_mobile_is_stored_as_ten_digits( string $typed, string $expected ): void {
		$this->assertSame( $expected, Registration::mobile( $typed ) );
		$this->assertSame( '' !== $expected, Registration::is_mobile_login( $typed ) );
	}

	public function test_pincode(): void {
		$this->assertTrue( Registration::is_pincode( '600032' ) );
		$this->assertTrue( Registration::is_pincode( ' 600032 ' ) );
		$this->assertFalse( Registration::is_pincode( '060032' ) );
		$this->assertFalse( Registration::is_pincode( '60003' ) );
		$this->assertFalse( Registration::is_pincode( '6000321' ) );
		$this->assertFalse( Registration::is_pincode( '60OO32' ) );
	}

	public function test_full_name_is_split_at_the_first_space(): void {
		$this->assertSame( array( 'Arun', 'Kumar Raj' ), Registration::split_name( '  Arun   Kumar Raj ' ) );
		$this->assertSame( array( 'Meena', '' ), Registration::split_name( 'Meena' ) );
	}

	/**
	 * Phone sign-in finds the account through the profile's 10-digit mobile,
	 * whichever way the number is typed.
	 */
	public function test_phone_sign_in_finds_the_account_by_mobile(): void {
		$profiles = new MemoryProfiles( new MemoryDb() );
		$profiles->ensure( 42, '2026-11-27 10:00:00' );
		$profiles->update( 42, array( 'mobile' => '9123456789' ) );
		$profiles->ensure( 43, '2026-11-27 10:00:00' );

		$this->assertSame( 42, $profiles->customer_by_mobile( Registration::mobile( '+91 91234 56789' ) ) );
		$this->assertSame( 42, $profiles->customer_by_mobile( Registration::mobile( '09123456789' ) ) );
		$this->assertSame( 0, $profiles->customer_by_mobile( Registration::mobile( '9000000000' ) ) );
		$this->assertSame( 0, $profiles->customer_by_mobile( '' ), 'Profiles without a mobile never match.' );
	}
}
