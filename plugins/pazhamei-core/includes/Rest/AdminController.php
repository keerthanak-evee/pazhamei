<?php
/**
 * AdminController: admin REST routes for the engine (Phases 5 and 8).
 *
 *   GET    /wp-json/pz/v1/admin/holidays                     pz_manage_settings
 *   POST   /wp-json/pz/v1/admin/holidays     { date, label } pz_manage_settings
 *   DELETE /wp-json/pz/v1/admin/holidays/{date}              pz_manage_settings
 *   POST   /wp-json/pz/v1/admin/subscriptions/{id}/credit    { reason }     pz_manage_subscriptions
 *   POST   /wp-json/pz/v1/admin/subscriptions/{id}/cancel    { reason }     pz_manage_subscriptions
 *   POST   /wp-json/pz/v1/admin/subscriptions/{id}/meal-type { meal_type }  pz_manage_subscriptions
 *
 * Every route checks the capability and an X-WP-Nonce ("wp_rest") header.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Domain\PackStatus;
use Pazhamei\Services\Engine;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin engine routes.
 */
final class AdminController {

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
		$settings = Guard::needs( 'pz_manage_settings' );
		$packs    = Guard::needs( 'pz_manage_subscriptions' );
		$reason   = array(
			'reason' => array(
				'type'              => 'string',
				'required'          => false,
				'sanitize_callback' => 'sanitize_text_field',
			),
		);

		register_rest_route(
			Guard::NAMESPACE,
			'/admin/holidays',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'holidays' ),
					'permission_callback' => $settings,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'add_holiday' ),
					'permission_callback' => $settings,
					'args'                => array(
						'date'  => Guard::date_arg( true ),
						'label' => array(
							'type'              => 'string',
							'required'          => false,
							'sanitize_callback' => 'sanitize_text_field',
						),
					),
				),
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/admin/holidays/(?P<date>\d{4}-\d{2}-\d{2})',
			array(
				'methods'             => WP_REST_Server::DELETABLE,
				'callback'            => array( self::class, 'remove_holiday' ),
				'permission_callback' => $settings,
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/admin/subscriptions/(?P<id>\d+)/credit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'credit' ),
				'permission_callback' => $packs,
				'args'                => $reason,
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/admin/subscriptions/(?P<id>\d+)/cancel',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'cancel' ),
				'permission_callback' => $packs,
				'args'                => $reason,
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/admin/subscriptions/(?P<id>\d+)/meal-type',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'meal_type' ),
				'permission_callback' => $packs,
				'args'                => array(
					'meal_type' => array(
						'type'     => 'string',
						'required' => true,
						'enum'     => array( 'VEG', 'NONVEG', 'MIXED' ),
					),
				),
			)
		);
	}

	/**
	 * GET /admin/holidays.
	 */
	public static function holidays(): WP_REST_Response {
		return Guard::reply(
			static function () {
				$engine = Engine::live();
				return array( 'holidays' => $engine->stores->holidays->list_from( $engine->today() ) );
			}
		);
	}

	/**
	 * POST /admin/holidays.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function add_holiday( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				$customers = Engine::live()->holidays()->add( (string) $request->get_param( 'date' ), (string) $request->get_param( 'label' ), Guard::actor() );
				return array(
					'date'               => (string) $request->get_param( 'date' ),
					'customers_affected' => count( $customers ),
				);
			},
			201
		);
	}

	/**
	 * DELETE /admin/holidays/{date}.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function remove_holiday( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				Engine::live()->holidays()->remove( (string) $request['date'], Guard::actor() );
				return array( 'date' => (string) $request['date'] );
			}
		);
	}

	/**
	 * POST /admin/subscriptions/{id}/credit.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function credit( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply( static fn() => Shape::pack( Engine::live()->packs()->credit_meal( (int) $request['id'], Guard::actor(), (string) $request->get_param( 'reason' ) ) ) );
	}

	/**
	 * POST /admin/subscriptions/{id}/cancel.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function cancel( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				$engine = Engine::live();
				$engine->packs()->cancel( (int) $request['id'], PackStatus::CANCELLED, Guard::actor(), (string) $request->get_param( 'reason' ) );
				return Shape::pack( $engine->stores->subscriptions->find( (int) $request['id'] ) );
			}
		);
	}

	/**
	 * POST /admin/subscriptions/{id}/meal-type.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function meal_type( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply( static fn() => Shape::pack( Engine::live()->packs()->change_meal_type( (int) $request['id'], (string) $request->get_param( 'meal_type' ), Guard::actor() ) ) );
	}
}
