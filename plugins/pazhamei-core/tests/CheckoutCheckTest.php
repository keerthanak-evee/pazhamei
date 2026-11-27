<?php
/**
 * Tests for CheckoutCheck: sign-in, one plan per order, start date (rule 4),
 * trial rules (rule 10) and the paused notice (decision 10).
 *
 * Clock: Friday 27 Nov 2026, 10:00 IST.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\EngineConfig;
use Pazhamei\Services\CheckoutCheck;
use Pazhamei\Tests\Fakes\TestKit;
use PHPUnit\Framework\TestCase;

/**
 * Checkout rules.
 */
final class CheckoutCheckTest extends TestCase {

	private function errors( TestKit $kit, int $customer, array $codes, string $start ): array {
		return CheckoutCheck::errors( $kit->engine, $customer, $codes, $start, '9123456789', '600002', '22 Temple Road' );
	}

	public function test_valid_plan_order_passes(): void {
		$this->assertSame( array(), $this->errors( new TestKit(), 7, array( 'D6' ), '2026-11-30' ) );
	}

	public function test_non_plan_orders_are_ignored(): void {
		$this->assertSame( array(), $this->errors( new TestKit(), 0, array(), '' ) );
	}

	public function test_guests_must_sign_in(): void {
		$this->assertStringContainsString( 'sign in', $this->errors( new TestKit(), 0, array( 'D6' ), '2026-11-30' )[0] );
	}

	public function test_one_plan_per_order(): void {
		$this->assertContains( 'Please buy one plan at a time.', $this->errors( new TestKit(), 7, array( 'D6', 'D26' ), '2026-11-30' ) );
	}

	public function test_start_date_must_be_valid(): void {
		$this->assertNotEmpty( $this->errors( new TestKit(), 7, array( 'D6' ), '2026-11-29' ), 'Sunday' );
		$this->assertNotEmpty( $this->errors( new TestKit(), 7, array( 'D6' ), '' ), 'Missing' );
	}

	public function test_trial_needs_the_switch_and_eligibility(): void {
		$kit = new TestKit();
		$this->assertContains( 'The trial is not available right now.', $this->errors( $kit, 7, array( 'TRIAL' ), '2026-11-30' ) );

		$kit->engine->config->trial_enabled = true;
		$this->assertSame( array(), $this->errors( $kit, 7, array( 'TRIAL' ), '2026-11-30' ) );

		$kit->buy( 7, 'TRIAL', 'VEG', '2026-11-30', null, '9123456789', '600002', '22 Temple Road' );
		$this->assertContains( 'You have already had a trial.', $this->errors( $kit, 7, array( 'TRIAL' ), '2026-12-01' ) );
		$this->assertNotEmpty( $this->errors( $kit, 8, array( 'TRIAL' ), '2026-12-01' ), 'Same mobile and address, other account' );
	}

	public function test_paused_notice(): void {
		$kit = new TestKit();
		$kit->buy( 7, 'D6' );
		$this->assertNull( CheckoutCheck::paused_notice( $kit->engine, 7 ) );

		$kit->engine->pauses()->pause( 7, '2026-12-01', null, $kit->customer( 7 ) );
		$this->assertSame( 'Your plan is paused. Resume it to start your new pack.', CheckoutCheck::paused_notice( $kit->engine, 7 ) );
	}
}
