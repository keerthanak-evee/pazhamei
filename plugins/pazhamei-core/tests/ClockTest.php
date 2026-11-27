<?php
/**
 * Tests for Clock: PZ_FAKE_NOW works on local sites only, and always in IST.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use DateTimeImmutable;
use DateTimeZone;
use InvalidArgumentException;
use Pazhamei\Clock;
use PHPUnit\Framework\TestCase;

/**
 * The fake clock override.
 */
final class ClockTest extends TestCase {

	private DateTimeZone $ist;
	private DateTimeImmutable $real;

	protected function setUp(): void {
		$this->ist  = new DateTimeZone( 'Asia/Kolkata' );
		$this->real = new DateTimeImmutable( '2026-11-26 10:00:00', $this->ist );
	}

	public function test_fake_time_is_used_on_local(): void {
		$now = Clock::resolve( '2026-11-30 19:59:00', 'local', $this->real, $this->ist );

		$this->assertSame( '2026-11-30 19:59:00', $now->format( 'Y-m-d H:i:s' ) );
		$this->assertSame( 'Asia/Kolkata', $now->getTimezone()->getName() );
	}

	public function test_fake_time_is_ignored_on_staging_and_production(): void {
		foreach ( array( 'staging', 'production', 'development' ) as $type ) {
			$now = Clock::resolve( '2026-11-30 19:59:00', $type, $this->real, $this->ist );
			$this->assertSame( '2026-11-26 10:00:00', $now->format( 'Y-m-d H:i:s' ), "Fake time must be ignored on {$type}" );
		}
	}

	public function test_real_time_when_not_set(): void {
		$this->assertEquals( $this->real, Clock::resolve( null, 'local', $this->real, $this->ist ) );
		$this->assertEquals( $this->real, Clock::resolve( '', 'local', $this->real, $this->ist ) );
	}

	public function test_real_time_is_returned_in_ist(): void {
		$utc = new DateTimeImmutable( '2026-11-26 14:30:00', new DateTimeZone( 'UTC' ) );
		$now = Clock::resolve( null, 'production', $utc, $this->ist );

		$this->assertSame( '2026-11-26 20:00:00', $now->format( 'Y-m-d H:i:s' ) );
	}

	public function test_badly_formatted_fake_time_fails_loudly_on_local(): void {
		$this->expectException( InvalidArgumentException::class );
		Clock::resolve( '30/11/2026 8pm', 'local', $this->real, $this->ist );
	}

	public function test_constant_overrides_the_option(): void {
		$this->assertSame( '2026-12-01 10:00:00', Clock::pick_fake( '2026-12-01 10:00:00', '2026-11-29 20:01:00' ) );
		$this->assertSame( '2026-11-29 20:01:00', Clock::pick_fake( null, '2026-11-29 20:01:00' ) );
		$this->assertSame( '2026-11-29 20:01:00', Clock::pick_fake( '', '2026-11-29 20:01:00' ) );
		$this->assertNull( Clock::pick_fake( null, '' ) );
	}

	public function test_fake_time_is_ignored_with_a_warning_when_not_local(): void {
		$this->assertTrue( Clock::is_ignored( '2026-11-29 20:01:00', 'staging' ) );
		$this->assertTrue( Clock::is_ignored( '2026-11-29 20:01:00', 'production' ) );
		$this->assertFalse( Clock::is_ignored( '2026-11-29 20:01:00', 'local' ) );
		$this->assertFalse( Clock::is_ignored( null, 'production' ), 'Nothing set, nothing to warn about' );

		$now = Clock::resolve( Clock::pick_fake( null, '2026-11-29 20:01:00' ), 'staging', $this->real, $this->ist );
		$this->assertSame( '2026-11-26 10:00:00', $now->format( 'Y-m-d H:i:s' ), 'The option is ignored on staging' );
	}
}
