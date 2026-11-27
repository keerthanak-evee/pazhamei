<?php
/**
 * Tests for Money: rupees with Indian digit grouping.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\Money;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * ₹1,23,456 style formatting.
 */
final class MoneyTest extends TestCase {

	/**
	 * @return array<string, array{0: float, 1: string}>
	 */
	public static function amounts(): array {
		return array(
			'zero'        => array( 0.0, '₹0' ),
			'hundreds'    => array( 900.0, '₹900' ),
			'thousands'   => array( 4420.0, '₹4,420' ),
			'lakh'        => array( 123456.0, '₹1,23,456' ),
			'ten lakh'    => array( 1234567.0, '₹12,34,567' ),
			'crore'       => array( 12345678.0, '₹1,23,45,678' ),
			'paise'       => array( 99.5, '₹99.50' ),
			'paise round' => array( 1080.256, '₹1,080.26' ),
		);
	}

	#[DataProvider( 'amounts' )]
	public function test_format( float $amount, string $expected ): void {
		$this->assertSame( $expected, Money::rupees( $amount ) );
	}
}
