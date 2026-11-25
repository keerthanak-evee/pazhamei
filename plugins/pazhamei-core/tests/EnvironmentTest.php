<?php
/**
 * Tests for Environment: developer tools run on local sites only.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Environment;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * Developer tools (wp pz seed, PZ_FAKE_NOW) are allowed only on "local".
 */
final class EnvironmentTest extends TestCase {

	/**
	 * @return array<string, array{0: string, 1: bool}>
	 */
	public static function cases(): array {
		return array(
			'local'       => array( 'local', true ),
			'development' => array( 'development', false ),
			'staging'     => array( 'staging', false ),
			'production'  => array( 'production', false ),
			'empty'       => array( '', false ),
		);
	}

	#[DataProvider( 'cases' )]
	public function test_dev_tools_only_on_local( string $type, bool $expected ): void {
		$this->assertSame( $expected, Environment::allows_dev_tools( $type ) );
	}
}
