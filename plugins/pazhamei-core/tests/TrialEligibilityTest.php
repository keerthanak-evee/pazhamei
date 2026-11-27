<?php
/**
 * Tests for the one-trial rule (rule 10, owner decision 4).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\EngineConfig;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Trial eligibility.
 */
final class TrialEligibilityTest extends TestCase {

	private TestKit $kit;

	protected function setUp(): void {
		$config                = EngineConfig::defaults();
		$config->trial_enabled = true;
		$this->kit             = new TestKit( '2026-11-27 10:00:00', $config );

		// Customer 7 had a trial at Flat 4-B, 600001, mobile +91 98765 43210.
		$this->kit->buy( 7, 'TRIAL', 'VEG', '2026-11-30', null, '+91 98765 43210', '600001', 'Flat No. 4-B, Lotus Apartments' );
	}

	private function refusal( int $customer, string $mobile, string $pincode, string $address ): ?string {
		return $this->kit->engine->trials()->refusal( $customer, $mobile, $pincode, $address );
	}

	public function test_a_new_customer_at_a_new_address_may_buy(): void {
		$this->assertNull( $this->refusal( 8, '9123456789', '600002', '22 Temple Road' ) );
	}

	public function test_same_account_is_refused(): void {
		$this->assertSame( 'You have already had a trial.', $this->refusal( 7, '9123456789', '600002', '22 Temple Road' ) );
	}

	public function test_second_attempt_with_a_reformatted_mobile_is_refused(): void {
		foreach ( array( '9876543210', '09876543210', '+919876543210', '98765-43210' ) as $mobile ) {
			$this->assertStringContainsString( 'mobile', (string) $this->refusal( 8, $mobile, '600002', '22 Temple Road' ), $mobile );
		}
	}

	public function test_second_attempt_with_a_reformatted_address_is_refused(): void {
		foreach ( array( '4B Lotus Apts', 'flat 4b, lotus apartments', 'Door No. 4-B Lotus' ) as $address ) {
			$this->assertStringContainsString( 'address', (string) $this->refusal( 8, '9123456789', '600 001', $address ), $address );
		}
	}

	public function test_same_flat_number_at_another_pincode_is_fine(): void {
		$this->assertNull( $this->refusal( 8, '9123456789', '600020', 'Flat 4B' ) );
	}

	public function test_trial_switch_off_refuses_everyone(): void {
		$this->kit->engine->config->trial_enabled = false;
		$this->assertSame( 'The trial is not available right now.', $this->refusal( 8, '9123456789', '600002', '22 Temple Road' ) );
	}
}
