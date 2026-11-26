<?php
/**
 * Tests for EnquiryStatus: statuses and who may manage enquiries in wp-admin.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\EnquiryStatus;
use PHPUnit\Framework\TestCase;

/**
 * Enquiry statuses and the Enquiries screen permission.
 */
final class EnquiryStatusTest extends TestCase {

	public function test_only_administrators_can_manage_enquiries(): void {
		$this->assertTrue( EnquiryStatus::can_manage( array( 'administrator' ) ) );
		$this->assertTrue( EnquiryStatus::can_manage( array( 'customer', 'administrator' ) ) );
	}

	public function test_staff_customers_and_visitors_cannot(): void {
		foreach ( array( array( 'pz_admin' ), array( 'pz_kitchen' ), array( 'customer' ), array( 'shop_manager' ), array( 'editor' ), array() ) as $roles ) {
			$this->assertFalse( EnquiryStatus::can_manage( $roles ), implode( ',', $roles ) ?: 'no roles' );
		}
	}

	public function test_role_names_must_match_exactly(): void {
		$this->assertFalse( EnquiryStatus::can_manage( array( 'Administrator' ) ) );
		$this->assertFalse( EnquiryStatus::can_manage( array( 'administrator ' ) ) );
	}

	public function test_statuses_in_workflow_order(): void {
		$this->assertSame( array( 'new', 'contacted', 'converted', 'closed' ), array_keys( EnquiryStatus::ALL ) );
		$this->assertTrue( EnquiryStatus::is_valid( 'contacted' ) );
		$this->assertFalse( EnquiryStatus::is_valid( 'deleted' ) );
		$this->assertFalse( EnquiryStatus::is_valid( '' ) );
	}
}
