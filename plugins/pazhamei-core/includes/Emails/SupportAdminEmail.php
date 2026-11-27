<?php
/**
 * SupportAdminEmail: tells the site admin that a customer raised a support ticket.
 *
 * Plain-text email to the address in Settings > General (admin_email),
 * through wp_mail() (FluentSMTP delivers and logs it). Reply-To is the
 * customer, so the admin can answer by email until /manage replies exist
 * (Phase 8).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Emails;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\TicketInput;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * New-ticket notification.
 */
final class SupportAdminEmail {

	/**
	 * Send the email.
	 *
	 * @param int                                                 $ticket_id Ticket ID.
	 * @param \WP_User                                            $customer  Customer.
	 * @param array{topic: string, message: string, date: ?string} $ticket    Cleaned ticket values.
	 * @param string                                              $mobile    Customer's mobile ('' if unknown).
	 * @return bool Whether wp_mail() accepted the email.
	 */
	public static function send( int $ticket_id, \WP_User $customer, array $ticket, string $mobile ): bool {
		$name    = '' !== trim( $customer->first_name . ' ' . $customer->last_name ) ? trim( $customer->first_name . ' ' . $customer->last_name ) : $customer->display_name;
		$subject = sprintf( '[Pazhamei] Support ticket #%d: %s (%s)', $ticket_id, TicketInput::TOPICS[ $ticket['topic'] ] ?? $ticket['topic'], $name );

		$lines = array(
			'A customer raised a support ticket.',
			'',
			'Ticket:   #' . $ticket_id,
			'Customer: ' . $name . ' (user #' . $customer->ID . ')',
			'Email:    ' . $customer->user_email,
			'Mobile:   ' . ( '' !== $mobile ? $mobile : 'Not given' ),
			'Topic:    ' . ( TicketInput::TOPICS[ $ticket['topic'] ] ?? $ticket['topic'] ),
			'About:    ' . ( null !== $ticket['date'] ? Dates::weekday_name( $ticket['date'] ) . ' ' . Dates::display( $ticket['date'] ) : 'No date given' ),
			'',
			'Message:',
			$ticket['message'],
			'',
			'Reply to this email to answer the customer. Ticket replies on the website arrive with the admin portal (Phase 8).',
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( is_email( $customer->user_email ) ) {
			$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', '"' ), '', $name ) . ' <' . $customer->user_email . '>';
		}
		return wp_mail( (string) get_option( 'admin_email' ), $subject, implode( "\n", $lines ), $headers );
	}
}
