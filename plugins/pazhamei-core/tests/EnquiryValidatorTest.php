<?php
/**
 * Tests for EnquiryValidator: the storefront enquiry form rules.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use Pazhamei\Domain\EnquiryValidator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Enquiry validation.
 */
final class EnquiryValidatorTest extends TestCase {

	/** Thursday 26 Nov 2026, 10:00 IST. */
	private DateTimeImmutable $today;

	protected function setUp(): void {
		$this->today = new DateTimeImmutable( '2026-11-26 10:00:00', new DateTimeZone( 'Asia/Kolkata' ) );
	}

	/**
	 * A complete, valid submission; tests change one field at a time.
	 *
	 * @param array<string, string> $changes Fields to override.
	 * @return array<string, string>
	 */
	private function input( array $changes = array() ): array {
		return array_merge(
			array(
				'name'            => 'Test Visitor',
				'mobile'          => '98765 43210',
				'email'           => 'visitor@example.test',
				'area'            => 'Guindy',
				'pincode'         => '600032',
				'plan_interest'   => '26-day',
				'meal_type'       => 'VEG',
				'preferred_start' => '2026-11-30',
				'message'         => 'Do you deliver to offices?',
			),
			$changes
		);
	}

	public function test_valid_enquiry_has_no_errors_and_clean_data(): void {
		$result = EnquiryValidator::validate( $this->input(), $this->today );

		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( '9876543210', $result['data']['mobile'] );
		$this->assertSame( '26-day', $result['data']['plan_interest'] );
		$this->assertSame( '2026-11-30', $result['data']['preferred_start'] );
	}

	public function test_only_name_mobile_pincode_and_plan_are_required(): void {
		$result = EnquiryValidator::validate(
			array(
				'name'          => 'Test Visitor',
				'mobile'        => '9876543210',
				'pincode'       => '600032',
				'plan_interest' => 'not-sure',
			),
			$this->today
		);

		$this->assertSame( array(), $result['errors'] );
		$this->assertNull( $result['data']['preferred_start'] );
		$this->assertSame( '', $result['data']['email'] );
	}

	public function test_missing_required_fields_are_reported(): void {
		$result = EnquiryValidator::validate( array(), $this->today );

		$this->assertSame( array( 'name', 'mobile', 'pincode', 'plan_interest' ), array_keys( $result['errors'] ) );
	}

	/**
	 * Mobile formats that must be accepted, and the 10 digits they become.
	 *
	 * @return array<string, array{0: string, 1: string}>
	 */
	public static function good_mobiles(): array {
		return array(
			'plain'              => array( '9876543210', '9876543210' ),
			'with +91'           => array( '+91 98765 43210', '9876543210' ),
			'with 91'            => array( '919876543210', '9876543210' ),
			'with leading 0'     => array( '09876543210', '9876543210' ),
			'dashes and spaces'  => array( '+91-98765-43210', '9876543210' ),
			'starts with 6'      => array( '6123456789', '6123456789' ),
		);
	}

	#[DataProvider( 'good_mobiles' )]
	public function test_mobile_formats_are_normalised_to_ten_digits( string $raw, string $expected ): void {
		$this->assertSame( $expected, EnquiryValidator::mobile( $raw ) );
	}

	/**
	 * Mobile values that must be refused.
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function bad_mobiles(): array {
		return array(
			'too short'        => array( '98765' ),
			'too long'         => array( '98765432101' ),
			'starts with 5'    => array( '5876543210' ),
			'landline-like'    => array( '044 2345 6789' ),
			'letters'          => array( '98765abcde' ),
			'empty'            => array( '' ),
		);
	}

	#[DataProvider( 'bad_mobiles' )]
	public function test_bad_mobiles_are_refused( string $raw ): void {
		$this->assertNull( EnquiryValidator::mobile( $raw ) );
		$this->assertArrayHasKey( 'mobile', EnquiryValidator::validate( $this->input( array( 'mobile' => $raw ) ), $this->today )['errors'] );
	}

	public function test_bad_email_is_refused_but_empty_email_is_fine(): void {
		$this->assertArrayHasKey( 'email', EnquiryValidator::validate( $this->input( array( 'email' => 'not-an-email' ) ), $this->today )['errors'] );
		$this->assertArrayNotHasKey( 'email', EnquiryValidator::validate( $this->input( array( 'email' => '' ) ), $this->today )['errors'] );
	}

	public function test_pincode_must_be_six_digits_not_starting_with_zero(): void {
		foreach ( array( '60003', '6000321', '060032', 'abcdef' ) as $bad ) {
			$this->assertArrayHasKey( 'pincode', EnquiryValidator::validate( $this->input( array( 'pincode' => $bad ) ), $this->today )['errors'], $bad );
		}
		$this->assertArrayNotHasKey( 'pincode', EnquiryValidator::validate( $this->input( array( 'pincode' => '600 032' ) ), $this->today )['errors'] );
	}

	public function test_unknown_plan_and_meal_type_are_refused(): void {
		$errors = EnquiryValidator::validate( $this->input( array( 'plan_interest' => '12-day', 'meal_type' => 'VEGAN' ) ), $this->today )['errors'];

		$this->assertArrayHasKey( 'plan_interest', $errors );
		$this->assertArrayHasKey( 'meal_type', $errors );
	}

	public function test_meal_type_is_case_insensitive_and_optional(): void {
		$this->assertSame( 'NONVEG', EnquiryValidator::validate( $this->input( array( 'meal_type' => 'nonveg' ) ), $this->today )['data']['meal_type'] );
		$this->assertArrayNotHasKey( 'meal_type', EnquiryValidator::validate( $this->input( array( 'meal_type' => '' ) ), $this->today )['errors'] );
	}

	/**
	 * Start dates that must be refused (today is Thursday 26 Nov 2026).
	 *
	 * @return array<string, array{0: string}>
	 */
	public static function bad_start_dates(): array {
		return array(
			'today'             => array( '2026-11-26' ),
			'in the past'       => array( '2026-11-20' ),
			'a Sunday'          => array( '2026-11-29' ),
			'more than 60 days' => array( '2027-01-26' ),
			'not a real date'   => array( '2026-02-30' ),
			'wrong format'      => array( '30/11/2026' ),
		);
	}

	#[DataProvider( 'bad_start_dates' )]
	public function test_bad_start_dates_are_refused( string $date ): void {
		$errors = EnquiryValidator::validate( $this->input( array( 'preferred_start' => $date ) ), $this->today )['errors'];
		$this->assertArrayHasKey( 'preferred_start', $errors );
	}

	public function test_tomorrow_and_day_sixty_are_allowed(): void {
		foreach ( array( '2026-11-27', '2027-01-25' ) as $date ) {
			$errors = EnquiryValidator::validate( $this->input( array( 'preferred_start' => $date ) ), $this->today )['errors'];
			$this->assertArrayNotHasKey( 'preferred_start', $errors, $date );
		}
	}

	public function test_html_is_stripped_from_text_fields(): void {
		$data = EnquiryValidator::validate(
			$this->input(
				array(
					'name'    => '<b>Test</b>   Visitor',
					'message' => "<script>alert(1)</script>Line one\r\nLine two",
				)
			),
			$this->today
		)['data'];

		$this->assertSame( 'Test Visitor', $data['name'] );
		$this->assertSame( "alert(1)Line one\nLine two", $data['message'] );
	}

	public function test_overlong_message_is_refused(): void {
		$errors = EnquiryValidator::validate( $this->input( array( 'message' => str_repeat( 'a', 1001 ) ) ), $this->today )['errors'];
		$this->assertArrayHasKey( 'message', $errors );
	}

	public function test_arrays_in_place_of_text_are_treated_as_empty(): void {
		$input         = $this->input();
		$input['name'] = array( 'Test' );

		$this->assertArrayHasKey( 'name', EnquiryValidator::validate( $input, $this->today )['errors'] );
	}
}
