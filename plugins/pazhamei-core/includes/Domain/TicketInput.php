<?php
/**
 * TicketInput: the rules for raising a support ticket on /account/support.
 *
 * - Topic: one from TOPICS.
 * - Message: 5 to 2000 characters.
 * - Date (optional): the lunch date the ticket is about, as 'Y-m-d'.
 *
 * Plain PHP, unit-tested.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support ticket rules.
 */
final class TicketInput {

	/** Topics: value => label. */
	public const TOPICS = array(
		'delivery' => 'Delivery',
		'meal'     => 'Meal or food',
		'schedule' => 'Skip, pause or dates',
		'payment'  => 'Payment or refund',
		'account'  => 'My account',
		'other'    => 'Something else',
	);

	/** Ticket statuses: value => label. Admin replies come in Phase 8. */
	public const STATUSES = array(
		'open'     => 'Open',
		'answered' => 'Answered',
		'closed'   => 'Closed',
	);

	/** Message length limits. */
	public const MIN_MESSAGE = 5;
	public const MAX_MESSAGE = 2000;

	/**
	 * Check a new ticket.
	 *
	 * @param array<string, mixed> $input topic, message, date.
	 * @return array{values: array{topic: string, message: string, date: ?string}, errors: array<string, string>}
	 */
	public static function check( array $input ): array {
		$text    = static fn( string $key ) => is_scalar( $input[ $key ] ?? '' ) ? trim( (string) ( $input[ $key ] ?? '' ) ) : '';
		$errors  = array();
		$topic   = $text( 'topic' );
		$message = $text( 'message' );
		$date    = $text( 'date' );

		if ( ! isset( self::TOPICS[ $topic ] ) ) {
			$errors['topic'] = 'Please choose a topic.';
		}
		if ( mb_strlen( $message ) < self::MIN_MESSAGE ) {
			$errors['message'] = 'Please tell us a little more.';
		} elseif ( mb_strlen( $message ) > self::MAX_MESSAGE ) {
			$errors['message'] = 'Please keep your message under ' . self::MAX_MESSAGE . ' characters.';
		}
		if ( '' !== $date && ! Dates::is_valid( $date ) ) {
			$errors['date'] = 'Please choose a valid date, or leave it empty.';
		}

		return array(
			'values' => array(
				'topic'   => $topic,
				'message' => $message,
				'date'    => '' === $date ? null : $date,
			),
			'errors' => $errors,
		);
	}
}
