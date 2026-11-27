<?php
/**
 * ScheduleController: customer REST routes for the Phase 4–5 portal.
 *
 *   GET    /wp-json/pz/v1/me/subscription            packs, pauses, cutoff info
 *   GET    /wp-json/pz/v1/me/schedule?from=&to=      calendar rows
 *   POST   /wp-json/pz/v1/schedule/{date}/skip       skip a day
 *   DELETE /wp-json/pz/v1/schedule/{date}/skip       undo the skip
 *   POST   /wp-json/pz/v1/me/pause                   { start_date, end_date? }
 *   POST   /wp-json/pz/v1/me/resume
 *
 * Every route needs the pz_customer capability and an X-WP-Nonce header
 * ("wp_rest"). Staff with pz_manage_subscriptions may add customer_id to
 * act for a customer (logged with the staff member's name).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Services\CheckoutCheck;
use Pazhamei\Services\Engine;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Customer schedule routes.
 */
final class ScheduleController {

	/** Capability for every route here. */
	private const CAP = 'pz_customer';

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
		$permission = Guard::needs( self::CAP );
		$customer   = array(
			'customer_id' => array(
				'type'              => 'integer',
				'required'          => false,
				'sanitize_callback' => 'absint',
			),
		);
		$date_path  = '/schedule/(?P<date>\d{4}-\d{2}-\d{2})/skip';

		register_rest_route(
			Guard::NAMESPACE,
			'/me/subscription',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'subscription' ),
				'permission_callback' => $permission,
				'args'                => $customer,
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/me/schedule',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'schedule' ),
				'permission_callback' => $permission,
				'args'                => $customer + array(
					'from' => Guard::date_arg( false ),
					'to'   => Guard::date_arg( false ),
				),
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			$date_path,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'skip' ),
					'permission_callback' => $permission,
					'args'                => $customer,
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( self::class, 'undo_skip' ),
					'permission_callback' => $permission,
					'args'                => $customer,
				),
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/me/pause',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'pause' ),
				'permission_callback' => $permission,
				'args'                => $customer + array(
					'start_date' => Guard::date_arg( true ),
					'end_date'   => Guard::date_arg( false ),
				),
			)
		);
		register_rest_route(
			Guard::NAMESPACE,
			'/me/resume',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( self::class, 'resume' ),
				'permission_callback' => $permission,
				'args'                => $customer,
			)
		);
	}

	/**
	 * GET /me/subscription.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function subscription( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				$engine      = Engine::live();
				$customer_id = Guard::customer_id( $request );
				$from        = $engine->first_unlocked();

				return array(
					'packs'          => array_map( array( Shape::class, 'pack' ), $engine->stores->subscriptions->for_customer( $customer_id ) ),
					'open_packs'     => count( $engine->stores->subscriptions->for_customer( $customer_id, PackStatus::OPEN ) ),
					'pauses'         => array_map( array( Shape::class, 'pause' ), $engine->stores->pauses->for_customer( $customer_id ) ),
					'first_unlocked' => $from,
					'cutoff_time'    => $engine->config->cutoff_time,
					'next_cutoff'    => $engine->cutoff()->locks_at( $from )->format( DATE_ATOM ),
					'paused_notice'  => CheckoutCheck::paused_notice( $engine, $customer_id ),
				);
			}
		);
	}

	/**
	 * GET /me/schedule.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function schedule( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				$engine = Engine::live();
				$from   = (string) $request->get_param( 'from' );
				$to     = (string) $request->get_param( 'to' );
				$days   = array();

				foreach ( $engine->stores->schedule->for_customer( Guard::customer_id( $request ) ) as $day ) {
					if ( ( Dates::is_valid( $from ) && $day->service_date < $from ) || ( Dates::is_valid( $to ) && $day->service_date > $to ) ) {
						continue;
					}
					$days[] = Shape::day( $day, $engine->is_locked( $day->service_date ) );
				}
				return array( 'days' => $days );
			}
		);
	}

	/**
	 * POST /schedule/{date}/skip.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function skip( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static fn() => Shape::day(
				Engine::live()->skips()->skip( Guard::customer_id( $request ), (string) $request['date'], Guard::actor() ),
				false
			)
		);
	}

	/**
	 * DELETE /schedule/{date}/skip.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function undo_skip( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				Engine::live()->skips()->undo( Guard::customer_id( $request ), (string) $request['date'], Guard::actor() );
				return array( 'date' => (string) $request['date'] );
			}
		);
	}

	/**
	 * POST /me/pause.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function pause( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply(
			static function () use ( $request ) {
				$end = (string) $request->get_param( 'end_date' );
				return Shape::pause(
					Engine::live()->pauses()->pause( Guard::customer_id( $request ), (string) $request->get_param( 'start_date' ), '' === $end ? null : $end, Guard::actor() )
				);
			},
			201
		);
	}

	/**
	 * POST /me/resume.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function resume( WP_REST_Request $request ): WP_REST_Response {
		return Guard::reply( static fn() => Shape::pause( Engine::live()->pauses()->resume( Guard::customer_id( $request ), Guard::actor() ) ) );
	}
}
