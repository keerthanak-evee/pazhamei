<?php
/**
 * Guard: shared permission checks and replies for the engine's REST routes.
 *
 * Every route checks BOTH a capability and a nonce (CLAUDE.md): the
 * X-WP-Nonce header must hold a valid "wp_rest" nonce, and the signed-in
 * user must have the route's capability. The page guard alone is never
 * enough.
 *
 * Rule violations become 422 replies with a customer-facing message.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Services\ClientIp;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Permission and reply helpers.
 */
final class Guard {

	/** REST namespace for all Pazhamei routes. */
	public const NAMESPACE = 'pz/v1';

	/** Capability that lets staff act on behalf of a customer. */
	public const STAFF_CAP = 'pz_manage_subscriptions';

	/**
	 * A permission_callback requiring a valid wp_rest nonce and a capability.
	 *
	 * @param string $capability Capability, e.g. 'pz_customer'.
	 */
	public static function needs( string $capability ): callable {
		return static function ( WP_REST_Request $request ) use ( $capability ) {
			$nonce = (string) $request->get_header( 'x_wp_nonce' );
			if ( '' === $nonce || ! wp_verify_nonce( $nonce, 'wp_rest' ) ) {
				return new WP_Error( 'pz_bad_nonce', __( 'Your session has expired. Please reload the page.', 'pazhamei-core' ), array( 'status' => 403 ) );
			}
			if ( ! is_user_logged_in() || ! current_user_can( $capability ) ) {
				return new WP_Error( 'pz_forbidden', __( 'You are not allowed to do that.', 'pazhamei-core' ), array( 'status' => 403 ) );
			}
			return true;
		};
	}

	/**
	 * The signed-in user as an Actor for the change log.
	 */
	public static function actor(): Actor {
		$user_id = get_current_user_id();
		if ( current_user_can( self::STAFF_CAP ) ) {
			$role = 'admin';
		} elseif ( current_user_can( 'pz_view_kitchen' ) ) {
			$role = 'kitchen';
		} else {
			$role = 'customer';
		}
		return new Actor( $user_id, $role, ClientIp::from_server( $_SERVER ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Validated as an IP inside from_server().
	}

	/**
	 * Whose schedule a customer route works on: the signed-in customer, or,
	 * for staff only, the customer named in the customer_id parameter.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function customer_id( WP_REST_Request $request ): int {
		$requested = absint( $request->get_param( 'customer_id' ) );
		if ( $requested > 0 && current_user_can( self::STAFF_CAP ) ) {
			return $requested;
		}
		return get_current_user_id();
	}

	/**
	 * Run an action and turn the outcome into a JSON reply.
	 *
	 * @param callable $action Returns the data to send back.
	 * @param int      $status HTTP status on success.
	 */
	public static function reply( callable $action, int $status = 200 ): WP_REST_Response {
		try {
			$response = new WP_REST_Response(
				array(
					'ok'   => true,
					'data' => $action(),
				),
				$status
			);
		} catch ( RuleViolation $refused ) {
			$response = new WP_REST_Response(
				array(
					'ok'      => false,
					'code'    => $refused->rule(),
					'message' => $refused->getMessage(),
				),
				422
			);
		} catch ( Throwable $error ) {
			error_log( 'Pazhamei REST error: ' . $error->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			$response = new WP_REST_Response(
				array(
					'ok'      => false,
					'code'    => 'pz_error',
					'message' => __( 'Something went wrong. Please try again.', 'pazhamei-core' ),
				),
				500
			);
		}
		$response->header( 'Cache-Control', 'no-store, private' );
		return $response;
	}

	/**
	 * Argument definition for a 'Y-m-d' date.
	 *
	 * @param bool $required Required?
	 * @return array<string, mixed>
	 */
	public static function date_arg( bool $required = true ): array {
		return array(
			'type'              => 'string',
			'required'          => $required,
			'pattern'           => '^\d{4}-\d{2}-\d{2}$',
			'sanitize_callback' => 'sanitize_text_field',
		);
	}
}
