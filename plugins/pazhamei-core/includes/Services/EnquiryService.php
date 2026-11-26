<?php
/**
 * EnquiryService: handles one enquiry from the storefront form.
 *
 * Steps, in order:
 * 1. Rate limit: at most 5 submissions per 15 minutes per visitor (hashed IP).
 * 2. Honeypot: if the hidden field is filled, pretend it worked and stop.
 * 3. Validate the fields.
 * 4. Save to wp_pz_enquiries.
 * 5. Email the site admin.
 *
 * The nonce is checked before this, in the REST route's permission check.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Clock;
use Pazhamei\Domain\EnquiryValidator;
use Pazhamei\Emails\EnquiryAdminEmail;
use Pazhamei\Repositories\EnquiryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enquiry submission.
 */
final class EnquiryService {

	/** Outcomes of submit(). */
	public const OK      = 'ok';
	public const INVALID = 'invalid';
	public const LIMITED = 'limited';
	public const FAILED  = 'failed';

	/**
	 * Process one submission.
	 *
	 * @param array<string, mixed> $input       Submitted form values.
	 * @param string               $source_page URL of the page the form was on.
	 * @return array{result: string, errors: array<string, string>}
	 */
	public static function submit( array $input, string $source_page ): array {
		$now = Clock::now();

		$limiter = new RateLimiter( new TransientStore() );
		if ( ! $limiter->attempt( 'pz_rl_enquiry_' . ClientIp::current_hash(), $now->getTimestamp() ) ) {
			return self::outcome( self::LIMITED );
		}

		// A bot filled the hidden field: answer as if all went well, but save and send nothing.
		if ( Honeypot::is_tripped( $input ) ) {
			return self::outcome( self::OK );
		}

		$checked = EnquiryValidator::validate( $input, $now );
		if ( $checked['errors'] ) {
			return self::outcome( self::INVALID, $checked['errors'] );
		}

		$id = EnquiryRepository::insert( $checked['data'], $source_page, $now->format( 'Y-m-d H:i:s' ) );
		if ( $id < 1 ) {
			return self::outcome( self::FAILED );
		}

		// The enquiry is saved even if the email fails; FluentSMTP's log shows any failure.
		EnquiryAdminEmail::send( $id, $checked['data'] );

		return self::outcome( self::OK );
	}

	/**
	 * Build the result array.
	 *
	 * @param string                $result One of the constants above.
	 * @param array<string, string> $errors Field errors, for INVALID.
	 * @return array{result: string, errors: array<string, string>}
	 */
	private static function outcome( string $result, array $errors = array() ): array {
		return array(
			'result' => $result,
			'errors' => $errors,
		);
	}
}
