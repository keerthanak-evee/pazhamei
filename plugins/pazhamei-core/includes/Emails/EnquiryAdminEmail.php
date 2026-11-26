<?php
/**
 * EnquiryAdminEmail: tells the site admin that a new enquiry arrived.
 *
 * Plain-text email to the address in Settings > General (admin_email), sent
 * through wp_mail(), so FluentSMTP delivers and logs it. Reply-To is set to
 * the visitor's email when they gave one, so the admin can just press Reply.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Emails;

use Pazhamei\Domain\Plans;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * New-enquiry notification.
 */
final class EnquiryAdminEmail {

	/**
	 * Send the email.
	 *
	 * @param int                        $id   Enquiry ID.
	 * @param array<string, string|null> $data Cleaned enquiry values.
	 * @return bool Whether wp_mail() accepted the email.
	 */
	public static function send( int $id, array $data ): bool {
		$plans     = Plans::all();
		$plan      = $plans[ $data['plan_interest'] ]['name'] ?? 'Not sure yet';
		$meal_type = Plans::MEAL_TYPES[ $data['meal_type'] ] ?? 'Not sure yet';
		$start     = $data['preferred_start'] ? wp_date( 'j M Y', strtotime( $data['preferred_start'] . ' 12:00:00' ) ) : 'Not given';

		$subject = sprintf( '[Pazhamei] New enquiry #%d from %s', $id, $data['name'] );

		$lines = array(
			'A new enquiry came in from the website.',
			'',
			'Name:            ' . $data['name'],
			'Mobile:          ' . $data['mobile'],
			'Email:           ' . ( '' !== $data['email'] ? $data['email'] : 'Not given' ),
			'Area:            ' . ( '' !== $data['area'] ? $data['area'] : 'Not given' ),
			'Pincode:         ' . $data['pincode'],
			'Plan:            ' . $plan,
			'Meal type:       ' . $meal_type,
			'Preferred start: ' . $start,
			'',
			'Message:',
			'' !== $data['message'] ? $data['message'] : '(none)',
			'',
			'Enquiry #' . $id . ' is saved in the wp_pz_enquiries table.',
		);

		$headers = array( 'Content-Type: text/plain; charset=UTF-8' );
		if ( '' !== $data['email'] && is_email( $data['email'] ) ) {
			$headers[] = 'Reply-To: ' . str_replace( array( "\r", "\n", '<', '>', '"' ), '', $data['name'] ) . ' <' . $data['email'] . '>';
		}

		return wp_mail( (string) get_option( 'admin_email' ), $subject, implode( "\n", $lines ), $headers );
	}
}
