<?php
/**
 * Tests for ClientIp: REMOTE_ADDR only, and salted hashing.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Services\ClientIp;
use PHPUnit\Framework\TestCase;

/**
 * Visitor IP lookup and hashing.
 */
final class ClientIpTest extends TestCase {

	public function test_reads_remote_addr(): void {
		$this->assertSame( '203.0.113.7', ClientIp::from_server( array( 'REMOTE_ADDR' => '203.0.113.7' ) ) );
		$this->assertSame( '2001:db8::1', ClientIp::from_server( array( 'REMOTE_ADDR' => '2001:db8::1' ) ) );
	}

	public function test_ignores_forwarded_headers_until_phase_2(): void {
		$server = array(
			'REMOTE_ADDR'          => '203.0.113.7',
			'HTTP_X_FORWARDED_FOR' => '198.51.100.1',
			'HTTP_CLIENT_IP'       => '198.51.100.2',
		);

		$this->assertSame( '203.0.113.7', ClientIp::from_server( $server ) );
	}

	public function test_missing_or_invalid_ip_gives_empty_string(): void {
		$this->assertSame( '', ClientIp::from_server( array() ) );
		$this->assertSame( '', ClientIp::from_server( array( 'REMOTE_ADDR' => 'not-an-ip' ) ) );
		$this->assertSame( '', ClientIp::from_server( array( 'REMOTE_ADDR' => array( '1.2.3.4' ) ) ) );
	}

	public function test_hash_never_contains_the_ip(): void {
		$hash = ClientIp::hash( '203.0.113.7', 'secret-salt' );

		$this->assertMatchesRegularExpression( '/^[0-9a-f]{64}$/', $hash );
		$this->assertStringNotContainsString( '203.0.113.7', $hash );
	}

	public function test_same_ip_and_salt_give_the_same_hash(): void {
		$this->assertSame( ClientIp::hash( '203.0.113.7', 'salt' ), ClientIp::hash( '203.0.113.7', 'salt' ) );
	}

	public function test_hash_depends_on_ip_and_salt(): void {
		$base = ClientIp::hash( '203.0.113.7', 'salt' );

		$this->assertNotSame( $base, ClientIp::hash( '203.0.113.8', 'salt' ), 'Different IP' );
		$this->assertNotSame( $base, ClientIp::hash( '203.0.113.7', 'other-salt' ), 'Different salt' );
	}

	public function test_hash_is_salted_not_a_plain_sha256(): void {
		$this->assertNotSame( hash( 'sha256', '203.0.113.7' ), ClientIp::hash( '203.0.113.7', 'salt' ) );
	}
}
