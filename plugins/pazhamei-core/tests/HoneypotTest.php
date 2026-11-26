<?php
/**
 * Tests for Honeypot: the hidden "company_url" field.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\Honeypot;
use PHPUnit\Framework\TestCase;

/**
 * Honeypot check.
 */
final class HoneypotTest extends TestCase {

	public function test_field_is_named_company_url(): void {
		$this->assertSame( 'company_url', Honeypot::FIELD );
	}

	public function test_people_leave_it_empty(): void {
		$this->assertFalse( Honeypot::is_tripped( array( 'name' => 'Test' ) ) );
		$this->assertFalse( Honeypot::is_tripped( array( 'company_url' => '' ) ) );
		$this->assertFalse( Honeypot::is_tripped( array( 'company_url' => '   ' ) ) );
	}

	public function test_bots_fill_it_in(): void {
		$this->assertTrue( Honeypot::is_tripped( array( 'company_url' => 'https://spam.example' ) ) );
		$this->assertTrue( Honeypot::is_tripped( array( 'company_url' => '0' ) ) );
	}

	public function test_non_text_values_count_as_bots(): void {
		$this->assertTrue( Honeypot::is_tripped( array( 'company_url' => array( 'x' ) ) ) );
	}
}
