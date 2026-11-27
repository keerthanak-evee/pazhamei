<?php
/**
 * CheckoutController: data for the checkout's start-date picker.
 *
 *   GET /wp-json/pz/v1/checkout/start-dates
 *
 * Every date from today to the last allowed start (rule 4), each with a
 * status (ok, locked, sunday, holiday) and a display label. The picker
 * greys out every date that isn't "ok"; the checkout still checks the
 * chosen date again on the server (CheckoutCheck).
 *
 * Needs pz_customer and an X-WP-Nonce header ("wp_rest"), like every route.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Domain\Dates;
use Pazhamei\Domain\StartDate;
use Pazhamei\Services\Engine;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Checkout helper routes.
 */
final class CheckoutController {

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
		register_rest_route(
			Guard::NAMESPACE,
			'/checkout/start-dates',
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( self::class, 'start_dates' ),
				'permission_callback' => Guard::needs( 'pz_customer' ),
			)
		);
	}

	/**
	 * GET /checkout/start-dates.
	 */
	public static function start_dates(): WP_REST_Response {
		return Guard::reply(
			static function () {
				$engine = Engine::live();
				$days   = array();
				$first  = '';

				foreach ( StartDate::options( $engine->now(), $engine->calendar(), $engine->cutoff() ) as $day ) {
					$day['display'] = Dates::weekday_name( $day['date'] ) . ' ' . Dates::display( $day['date'] );
					$days[]         = $day;
					if ( '' === $first && 'ok' === $day['status'] ) {
						$first = $day['date'];
					}
				}
				return array(
					'today'    => $engine->today(),
					'earliest' => $first,
					'days'     => $days,
				);
			}
		);
	}
}
