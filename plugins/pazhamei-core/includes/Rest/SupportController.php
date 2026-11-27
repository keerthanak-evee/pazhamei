<?php
/**
 * SupportController: the customer's support tickets.
 *
 *   GET  /wp-json/pz/v1/support/tickets   my tickets, newest first, with their messages
 *   POST /wp-json/pz/v1/support/tickets   { topic, message, date? } → new ticket, admin email
 *
 * Both need pz_customer and an X-WP-Nonce header ("wp_rest"). A customer
 * can raise at most 10 tickets a day (a simple flood guard).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Rest;

use Pazhamei\Clock;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Domain\TicketInput;
use Pazhamei\Emails\SupportAdminEmail;
use Pazhamei\Repositories\ProfileRepository;
use Pazhamei\Repositories\SupportRepository;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Support ticket routes.
 */
final class SupportController {

	/** Most tickets one customer may raise in 24 hours. */
	private const DAILY_LIMIT = 10;

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
			'/support/tickets',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( self::class, 'list' ),
					'permission_callback' => $permission,
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( self::class, 'create' ),
					'permission_callback' => $permission,
				),
			)
		);
	}

	/**
	 * The customer's tickets, shaped for the page.
	 *
	 * @param int $customer_id Customer.
	 * @return array<int, array<string, mixed>>
	 */
	public static function tickets( int $customer_id ): array {
		$out = array();
		foreach ( SupportRepository::for_customer( $customer_id ) as $ticket ) {
			$messages = array();
			foreach ( $ticket['messages'] as $message ) {
				$messages[] = array(
					'id'   => (int) $message['id'],
					'who'  => 'customer' === $message['author_role'] ? __( 'You', 'pazhamei-core' ) : __( 'Pazhamei kitchen', 'pazhamei-core' ),
					'mine' => 'customer' === $message['author_role'],
					'body' => (string) $message['body'],
					'when' => Dates::display( substr( (string) $message['created_at'], 0, 10 ) ) . ', ' . substr( (string) $message['created_at'], 11, 5 ),
				);
			}
			$out[] = array(
				'id'          => (int) $ticket['id'],
				'topic'       => TicketInput::TOPICS[ $ticket['topic'] ] ?? (string) $ticket['topic'],
				'about'       => null !== $ticket['about_date'] ? Dates::weekday_name( (string) $ticket['about_date'] ) . ' ' . Dates::display( (string) $ticket['about_date'] ) : '',
				'status'      => (string) $ticket['status'],
				'statusLabel' => TicketInput::STATUSES[ $ticket['status'] ] ?? (string) $ticket['status'],
				'created'     => Dates::display( substr( (string) $ticket['created_at'], 0, 10 ) ),
				'messages'    => $messages,
			);
		}
		return $out;
	}

	/**
	 * GET /support/tickets.
	 */
	public static function list(): WP_REST_Response {
		return Guard::reply( static fn() => array( 'tickets' => self::tickets( get_current_user_id() ) ) );
	}

	/**
	 * POST /support/tickets.
	 *
	 * @param WP_REST_Request $request Request (JSON body).
	 */
	public static function create( WP_REST_Request $request ): WP_REST_Response {
		$body    = (array) $request->get_json_params();
		$checked = TicketInput::check(
			array(
				'topic'   => sanitize_key( (string) ( $body['topic'] ?? '' ) ),
				'message' => sanitize_textarea_field( (string) ( $body['message'] ?? '' ) ),
				'date'    => sanitize_text_field( (string) ( $body['date'] ?? '' ) ),
			)
		);
		if ( $checked['errors'] ) {
			$response = new WP_REST_Response(
				array(
					'ok'      => false,
					'code'    => 'pz_invalid',
					'message' => __( 'Please fix the fields marked below.', 'pazhamei-core' ),
					'errors'  => $checked['errors'],
				),
				422
			);
			$response->header( 'Cache-Control', 'no-store, private' );
			return $response;
		}

		return Guard::reply(
			static function () use ( $checked ) {
				$customer_id = get_current_user_id();
				$now         = Clock::now();
				if ( SupportRepository::count_since( $customer_id, $now->modify( '-1 day' )->format( 'Y-m-d H:i:s' ) ) >= self::DAILY_LIMIT ) {
					throw new RuleViolation( 'too_many', __( 'You have raised a lot of tickets today. Please call the kitchen instead.', 'pazhamei-core' ) );
				}

				$ticket_id = SupportRepository::create( $customer_id, $checked['values']['topic'], $checked['values']['date'], $checked['values']['message'], $now->format( 'Y-m-d H:i:s' ) );
				$profile   = ( new ProfileRepository() )->get( $customer_id );
				SupportAdminEmail::send( $ticket_id, wp_get_current_user(), $checked['values'], (string) ( $profile['mobile'] ?? '' ) );

				return array(
					'id'      => $ticket_id,
					'message' => sprintf( /* translators: %d: ticket number. */ __( 'Thank you. Ticket #%d is with the kitchen team; we’ll get back to you soon.', 'pazhamei-core' ), $ticket_id ),
					'tickets' => self::tickets( $customer_id ),
				);
			},
			201
		);
	}
}
