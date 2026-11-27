<?php
/**
 * Tests for TicketInput: raising a support ticket.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Tests;

use Pazhamei\Domain\TicketInput;
use PHPUnit\Framework\TestCase;

/**
 * Topic from the list, a real message, an optional date.
 */
final class TicketInputTest extends TestCase {

	public function test_valid_ticket(): void {
		$result = TicketInput::check( array( 'topic' => 'delivery', 'message' => '  Lunch came late today.  ', 'date' => '2026-11-30' ) );
		$this->assertSame( array(), $result['errors'] );
		$this->assertSame( 'Lunch came late today.', $result['values']['message'] );
		$this->assertSame( '2026-11-30', $result['values']['date'] );
	}

	public function test_date_is_optional(): void {
		$result = TicketInput::check( array( 'topic' => 'other', 'message' => 'A question about menus.' ) );
		$this->assertSame( array(), $result['errors'] );
		$this->assertNull( $result['values']['date'] );
	}

	public function test_required_fields(): void {
		$errors = TicketInput::check( array( 'topic' => 'complaint', 'message' => 'Hi', 'date' => '30/11/2026' ) )['errors'];
		$this->assertSame( array( 'topic', 'message', 'date' ), array_keys( $errors ) );
	}

	public function test_long_message_is_refused(): void {
		$errors = TicketInput::check( array( 'topic' => 'meal', 'message' => str_repeat( 'a', 2001 ) ) )['errors'];
		$this->assertArrayHasKey( 'message', $errors );
	}
}
