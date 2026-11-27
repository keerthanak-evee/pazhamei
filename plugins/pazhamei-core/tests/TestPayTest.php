<?php
/**
 * Tests for TestPay: the fake payment method exists on local sites only.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Woo\TestPay;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * "Pazhamei Test Pay" must never be available on staging or production.
 */
final class TestPayTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function environments(): array {
		return array(
			'local'       => array( 'local', true ),
			'development' => array( 'development', false ),
			'staging'     => array( 'staging', false ),
			'production'  => array( 'production', false ),
			'empty'       => array( '', false ),
			'Local caps'  => array( 'Local', false ),
		);
	}

	#[DataProvider( 'environments' )]
	public function test_only_on_local( string $type, bool $expected ): void {
		$this->assertSame( $expected, TestPay::allowed( $type ) );
	}
}
