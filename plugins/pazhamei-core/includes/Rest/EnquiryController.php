<?php
/**
 * EnquiryController: REST routes for the storefront enquiry form.
 *
 *   GET  /wp-json/pz/v1/enquiries/nonce  A fresh nonce for the form. Never cached, so
 *                                        full-page caching can't serve a stale one.
 *   POST /wp-json/pz/v1/enquiries        Submit an enquiry. Needs that nonce in the
 *                                        X-PZ-Nonce header.
 *
 * Capability: this is the one public form, so visitors (who have no capabilities)
 * must be able to use it. The nonce, honeypot and rate limit protect it instead.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Services\EnquiryService;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enquiry routes.
 */
final class EnquiryController {

	/** REST namespace for all Pazhamei routes. */
	public const NAMESPACE = 'pz/v1';

	/** Nonce action for the enquiry form. */
	public const NONCE_ACTION = 'pz_enquiry';

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Register both routes.
	 */
	public static function routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/enquiries/nonce',
			array(
				'methods'             => 'GET',
				'callback'            => array( self::class, 'nonce' ),
				'permission_callback' => '__return_true', // Public: it only hands out a nonce.
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/enquiries',
			array(
				'methods'             => 'POST',
				'callback'            => array( self::class, 'create' ),
				'permission_callback' => array( self::class, 'check_nonce' ),
			)
		);
	}

	/**
	 * GET: a fresh nonce.
	 */
	public static function nonce(): WP_REST_Response {
		$response = new WP_REST_Response( array( 'nonce' => wp_create_nonce( self::NONCE_ACTION ) ) );
		$response->header( 'Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0' );

		return $response;
	}

	/**
	 * POST permission: the X-PZ-Nonce header must hold a valid nonce.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return true|WP_Error
	 */
	public static function check_nonce( WP_REST_Request $request ) {
		$nonce = (string) $request->get_header( 'x_pz_nonce' );

		if ( '' !== $nonce && wp_verify_nonce( $nonce, self::NONCE_ACTION ) ) {
			return true;
		}

		return new WP_Error(
			'pz_bad_nonce',
			__( 'This form has expired. Please reload the page and try again.', 'pazhamei-core' ),
			array( 'status' => 403 )
		);
	}

	/**
	 * POST: submit an enquiry.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function create( WP_REST_Request $request ): WP_REST_Response {
		$input = $request->get_json_params();
		if ( ! is_array( $input ) ) {
			$input = $request->get_body_params();
		}

		$source  = esc_url_raw( (string) ( $input['source_page'] ?? '' ) );
		$outcome = EnquiryService::submit( $input, substr( $source, 0, 255 ) );

		switch ( $outcome['result'] ) {
			case EnquiryService::OK:
				return self::reply( 201, true, __( 'We have your enquiry and will call you within one working day.', 'pazhamei-core' ) );

			case EnquiryService::INVALID:
				return self::reply( 400, false, __( 'Please check the highlighted fields.', 'pazhamei-core' ), $outcome['errors'] );

			case EnquiryService::LIMITED:
				return self::reply( 429, false, __( 'You have sent several enquiries in a short time. Please try again in 15 minutes, or call us.', 'pazhamei-core' ) );

			default:
				return self::reply( 500, false, __( 'Sorry, we could not save your enquiry. Please try again, or call us.', 'pazhamei-core' ) );
		}
	}

	/**
	 * Build a JSON reply.
	 *
	 * @param int                   $status  HTTP status.
	 * @param bool                  $ok      Success flag.
	 * @param string                $message Message shown to the visitor.
	 * @param array<string, string> $errors  Field errors.
	 */
	private static function reply( int $status, bool $ok, string $message, array $errors = array() ): WP_REST_Response {
		$response = new WP_REST_Response(
			array(
				'ok'      => $ok,
				'message' => $message,
				'errors'  => (object) $errors, // Always a JSON object, even when empty.
			),
			$status
		);
		$response->header( 'Cache-Control', 'no-store' );

		return $response;
	}
}
