<?php
/**
 * ProfileController: the customer's Profile page.
 *
 *   GET  /wp-json/pz/v1/me/profile    the form's values and the allergen list
 *   PUT  /wp-json/pz/v1/me/profile    save; 422 with errors by field when something is wrong
 *   POST /wp-json/pz/v1/me/password   { current, new, confirm }
 *
 * Every route needs pz_customer and an X-WP-Nonce header ("wp_rest").
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Services\Engine;
use Pazhamei\Services\ProfileService;
use Pazhamei\Domain\Dates;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Profile routes.
 */
final class ProfileController {

	/**
	 * Register on rest_api_init.
	 */
	public static function register(): void {
		add_action( 'rest_api_init', array( self::class, 'routes' ) );
	}

	/**
	 * Route table.
	 */
	public static function routes(): void {
		$permission = Guard::needs( 'pz_customer' );

		register_rest_route(
			Guard::NAMESPACE,
			'/me/profile',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'get' ),
					'permission_callback' => $permission,
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( self::class, 'save' ),
					'permission_callback' => $permission,
				),
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/me/password',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'password' ),
				'permission_callback' => $permission,
			)
		);
	}

	/**
	 * GET /me/profile.
	 */
	public static function get(): WP_REST_Response {
		return Guard::reply( static fn() => ProfileService::get( get_current_user_id() ) );
	}

	/**
	 * PUT /me/profile.
	 *
	 * @param WP_REST_Request $request Request (JSON body).
	 */
	public static function save( WP_REST_Request $request ): WP_REST_Response {
		$input = (array) $request->get_json_params();
		$input = array(
			'name'         => sanitize_text_field( (string) ( $input['name'] ?? '' ) ),
			'mobile'       => sanitize_text_field( (string) ( $input['mobile'] ?? '' ) ),
			'address_1'    => sanitize_text_field( (string) ( $input['address_1'] ?? '' ) ),
			'address_2'    => sanitize_text_field( (string) ( $input['address_2'] ?? '' ) ),
			'city'         => sanitize_text_field( (string) ( $input['city'] ?? '' ) ),
			'pincode'      => sanitize_text_field( (string) ( $input['pincode'] ?? '' ) ),
			'dietary'      => sanitize_key( (string) ( $input['dietary'] ?? '' ) ),
			'allergens'    => is_array( $input['allergens'] ?? null ) ? array_map( 'sanitize_key', array_map( 'strval', $input['allergens'] ) ) : array(),
			'instructions' => sanitize_textarea_field( (string) ( $input['instructions'] ?? '' ) ),
		);
		$input['dietary'] = strtoupper( $input['dietary'] );

		$errors = ProfileService::save( get_current_user_id(), $input );
		if ( $errors ) {
			$response = new WP_REST_Response(
				array(
					'ok'      => false,
					'code'    => 'pz_invalid',
					'message' => __( 'Nothing was saved. Please fix the fields marked below.', 'pazhamei-core' ),
					'errors'  => $errors,
				),
				422
			);
			$response->header( 'Cache-Control', 'no-store, private' );
			return $response;
		}

		return Guard::reply(
			static function () {
				$engine = Engine::live();
				$from   = $engine->calendar()->first_service_day_from( $engine->first_unlocked() );
				return array(
					'profile' => ProfileService::get( get_current_user_id() ),
					'message' => sprintf(
						/* translators: %s: date like "Mon 30 Nov 2026". */
						__( 'Saved. Address and instruction changes apply to lunches from %s.', 'pazhamei-core' ),
						Dates::weekday_name( $from ) . ' ' . Dates::display( $from )
					),
				);
			}
		);
	}

	/**
	 * POST /me/password.
	 *
	 * @param WP_REST_Request $request Request (JSON body).
	 */
	public static function password( WP_REST_Request $request ): WP_REST_Response {
		$input = (array) $request->get_json_params();
		return Guard::reply(
			static function () use ( $input ) {
				ProfileService::change_password(
					get_current_user_id(),
					(string) ( $input['current'] ?? '' ),
					(string) ( $input['new'] ?? '' ),
					(string) ( $input['confirm'] ?? '' )
				);
				return array( 'message' => __( 'Your password has been changed.', 'pazhamei-core' ) );
			}
		);
	}
}
