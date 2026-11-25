<?php
/**
 * Tests for Portals: who may open each portal, and where each role lands.
 *
 * These match the "Portals, URLs and roles" table in CLAUDE.md.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Access\Portals;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Portal access rules.
 */
final class PortalsTest extends TestCase {

	/**
	 * Every role (and a logged-out visitor) against every portal.
	 *
	 * @return array<string, array{0: string, 1: string[], 2: bool}>
	 */
	public static function access_cases(): array {
		return array(
			// Storefront: everyone.
			'visitor storefront'         => array( Portals::STOREFRONT, array(), true ),
			'customer storefront'        => array( Portals::STOREFRONT, array( 'customer' ), true ),

			// Logged-out visitors: no portal.
			'visitor customer'           => array( Portals::CUSTOMER, array(), false ),
			'visitor kitchen'            => array( Portals::KITCHEN, array(), false ),
			'visitor admin'              => array( Portals::ADMIN, array(), false ),

			// Customer: only the customer portal.
			'customer customer'          => array( Portals::CUSTOMER, array( 'customer' ), true ),
			'customer kitchen'           => array( Portals::KITCHEN, array( 'customer' ), false ),
			'customer admin'             => array( Portals::ADMIN, array( 'customer' ), false ),

			// Kitchen staff: only the kitchen portal.
			'kitchen customer'           => array( Portals::CUSTOMER, array( 'pz_kitchen' ), false ),
			'kitchen kitchen'            => array( Portals::KITCHEN, array( 'pz_kitchen' ), true ),
			'kitchen admin'              => array( Portals::ADMIN, array( 'pz_kitchen' ), false ),

			// Pazhamei admin: kitchen and admin, not the customer portal.
			'pz_admin customer'          => array( Portals::CUSTOMER, array( 'pz_admin' ), false ),
			'pz_admin kitchen'           => array( Portals::KITCHEN, array( 'pz_admin' ), true ),
			'pz_admin admin'             => array( Portals::ADMIN, array( 'pz_admin' ), true ),

			// Site administrator: same as pz_admin.
			'administrator customer'     => array( Portals::CUSTOMER, array( 'administrator' ), false ),
			'administrator kitchen'      => array( Portals::KITCHEN, array( 'administrator' ), true ),
			'administrator admin'        => array( Portals::ADMIN, array( 'administrator' ), true ),

			// Other WordPress roles get no portal.
			'subscriber kitchen'         => array( Portals::KITCHEN, array( 'subscriber' ), false ),
			'shop_manager admin'         => array( Portals::ADMIN, array( 'shop_manager' ), false ),

			// Unknown portal names are always denied.
			'administrator unknown'      => array( 'secret', array( 'administrator' ), false ),
		);
	}

	#[DataProvider( 'access_cases' )]
	public function test_can_open( string $portal, array $roles, bool $expected ): void {
		$this->assertSame( $expected, Portals::can_open( $portal, $roles ) );
	}

	/**
	 * @return array<string, array{0: string[], 1: string}>
	 */
	public static function landing_cases(): array {
		return array(
			'customer'               => array( array( 'customer' ), '/account/dashboard/' ),
			'kitchen'                => array( array( 'pz_kitchen' ), '/kitchen/' ),
			'pz_admin'               => array( array( 'pz_admin' ), '/manage/' ),
			'administrator'          => array( array( 'administrator' ), '/manage/' ),
			'no role'                => array( array(), '/' ),
			'subscriber'             => array( array( 'subscriber' ), '/' ),
			'kitchen beats customer' => array( array( 'customer', 'pz_kitchen' ), '/kitchen/' ),
		);
	}

	#[DataProvider( 'landing_cases' )]
	public function test_landing_path( array $roles, string $expected ): void {
		$this->assertSame( $expected, Portals::landing_path( $roles ) );
	}

	/**
	 * Every role's landing page must be a page that role may open, or users would bounce forever.
	 */
	public function test_landing_page_is_always_openable(): void {
		$landing_portal = array(
			'/account/dashboard/' => Portals::CUSTOMER,
			'/kitchen/'           => Portals::KITCHEN,
			'/manage/'            => Portals::ADMIN,
			'/'                   => Portals::STOREFRONT,
		);

		foreach ( array( 'customer', 'pz_kitchen', 'pz_admin', 'administrator', 'subscriber' ) as $role ) {
			$path = Portals::landing_path( array( $role ) );
			$this->assertTrue( Portals::can_open( $landing_portal[ $path ], array( $role ) ), "Role {$role} cannot open its landing page {$path}" );
		}
	}

	public function test_storefront_needs_no_login_but_portals_do(): void {
		$this->assertFalse( Portals::requires_login( Portals::STOREFRONT ) );
		$this->assertTrue( Portals::requires_login( Portals::CUSTOMER ) );
		$this->assertTrue( Portals::requires_login( Portals::KITCHEN ) );
		$this->assertTrue( Portals::requires_login( Portals::ADMIN ) );
	}

	public function test_account_links_match_role(): void {
		$labels = static fn( array $roles ): array => array_column( Portals::account_links( $roles ), 'label' );

		$this->assertSame( array( 'My account' ), $labels( array( 'customer' ) ) );
		$this->assertSame( array( 'Kitchen' ), $labels( array( 'pz_kitchen' ) ) );
		$this->assertSame( array( 'Kitchen', 'Manage' ), $labels( array( 'pz_admin' ) ) );
		$this->assertSame( array(), $labels( array() ) );
	}
}
