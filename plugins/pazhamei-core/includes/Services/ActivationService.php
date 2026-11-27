<?php
/**
 * ActivationService: turns one paid order line into a pack.
 *
 * Idempotent on order_item_id: a repeated payment webhook, or the order
 * moving from processing to completed, finds the existing pack and does
 * nothing. The UNIQUE key on order_item_id backs this up when two requests
 * arrive at the same moment.
 *
 * If the customer already has an open pack, the new one is queued behind
 * it (rule 9); otherwise it is active. Then reflow() gives it dates.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

use Pazhamei\Domain\Actor;
use Pazhamei\Domain\Dates;
use Pazhamei\Domain\Pack;
use Pazhamei\Domain\PackStatus;
use Pazhamei\Domain\Plans;
use Pazhamei\Domain\Reflow;
use Pazhamei\Domain\RuleViolation;
use Pazhamei\Domain\TrialIdentity;
use Pazhamei\Repositories\DuplicateKey;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Paid order line → pack.
 */
final class ActivationService {

	private Engine $engine;

	/**
	 * @param Engine $engine Engine.
	 */
	public function __construct( Engine $engine ) {
		$this->engine = $engine;
	}

	/**
	 * Create the pack for an order line, or return the one that already exists.
	 *
	 * @param PackRequest $request Order line details.
	 * @param Actor       $actor   Usually Actor::system().
	 * @return array{pack: Pack, created: bool}
	 * @throws RuleViolation When the order line is not a valid plan.
	 */
	public function activate( PackRequest $request, Actor $actor ): array {
		$this->validate( $request );

		$existing = $this->engine->stores->subscriptions->find_by_order_item( $request->order_item_id );
		if ( $existing ) {
			return array(
				'pack'    => $existing,
				'created' => false,
			);
		}

		$result = $this->engine->customer_tx(
			$request->customer_id,
			fn() => $this->create( $request, $actor )
		);

		if ( $result['created'] ) {
			$this->engine->emit( 'pack_activated', array( $result['pack']->id ) );
		}
		return $result;
	}

	/**
	 * Insert the pack and lay out its meals. Runs inside the customer's transaction.
	 *
	 * @param PackRequest $request Order line details.
	 * @param Actor       $actor   Who.
	 * @return array{pack: Pack, created: bool}
	 */
	private function create( PackRequest $request, Actor $actor ): array {
		$stores = $this->engine->stores;

		// Checked again under the customer lock: another request may have won the race.
		$existing = $stores->subscriptions->find_by_order_item( $request->order_item_id );
		if ( $existing ) {
			return array(
				'pack'    => $existing,
				'created' => false,
			);
		}

		$open = Reflow::in_walk_order( $stores->subscriptions->for_customer( $request->customer_id, PackStatus::OPEN ) );
		$now  = $this->engine->stamp();

		$pack                  = new Pack();
		$pack->customer_id     = $request->customer_id;
		$pack->order_id        = $request->order_id;
		$pack->order_item_id   = $request->order_item_id;
		$pack->product_id      = $request->product_id;
		$pack->plan_code       = $request->plan_code;
		$pack->meal_type       = $request->meal_type;
		$pack->total_meals     = Plans::meals_for_code( $request->plan_code );
		$pack->requested_start = $request->requested_start;
		$pack->status          = $open ? PackStatus::QUEUED : PackStatus::ACTIVE; // Rule 9.
		$pack->renewed_from_id = $open ? end( $open )->id : null;
		$pack->created_at      = $now;
		$pack->updated_at      = $now;

		try {
			$pack->id = $stores->subscriptions->insert( $pack );
		} catch ( DuplicateKey $duplicate ) {
			return array(
				'pack'    => $stores->subscriptions->find_by_order_item( $request->order_item_id ),
				'created' => false,
			);
		}

		if ( 'TRIAL' === $pack->plan_code ) {
			$stores->trial_claims->insert(
				$pack->customer_id,
				$pack->id,
				TrialIdentity::mobile_key( $request->mobile ),
				TrialIdentity::address_key( $request->pincode, $request->address_line ),
				$now
			);
		}
		$this->remember_contact( $request );

		$this->engine->log( 'subscription', $pack->id, 'activated', null, $pack->to_array(), $actor, 'Order #' . $request->order_id );
		$this->engine->reflow()->run( $pack->customer_id, $actor, 'New pack #' . $pack->id, 'initial' );

		return array(
			'pack'    => $stores->subscriptions->find( $pack->id ),
			'created' => true,
		);
	}

	/**
	 * Refuse order lines that are not a valid plan.
	 *
	 * @param PackRequest $request Order line details.
	 * @throws RuleViolation When something is missing or unknown.
	 */
	private function validate( PackRequest $request ): void {
		if ( $request->customer_id <= 0 ) {
			throw new RuleViolation( 'no_customer', 'Plans can only be bought by a signed-in customer.' );
		}
		if ( $request->order_item_id <= 0 ) {
			throw new RuleViolation( 'no_order_item', 'The order line is missing.' );
		}
		if ( Plans::meals_for_code( $request->plan_code ) <= 0 ) {
			throw new RuleViolation( 'unknown_plan', "Unknown plan code '{$request->plan_code}'." );
		}
		if ( ! Plans::is_meal_type( $request->meal_type ) ) {
			throw new RuleViolation( 'unknown_meal_type', "Unknown meal type '{$request->meal_type}'." );
		}
		if ( null !== $request->requested_start && ! Dates::is_valid( $request->requested_start ) ) {
			throw new RuleViolation( 'bad_start', 'The start date is not a valid date.' );
		}
	}

	/**
	 * Save the mobile and pincode on the profile if it has none yet.
	 *
	 * @param PackRequest $request Order line details.
	 */
	private function remember_contact( PackRequest $request ): void {
		$profile = $this->engine->stores->profiles->get( $request->customer_id );
		$fields  = array();

		$mobile = TrialIdentity::mobile_key( $request->mobile );
		if ( '' !== $mobile && '' === (string) ( $profile['mobile'] ?? '' ) ) {
			$fields['mobile'] = $mobile;
		}
		$pincode = substr( (string) preg_replace( '/\D+/', '', $request->pincode ), 0, 6 );
		if ( '' !== $pincode && '' === (string) ( $profile['pincode'] ?? '' ) ) {
			$fields['pincode'] = $pincode;
		}

		if ( $fields ) {
			$fields['updated_at'] = $this->engine->stamp();
			$this->engine->stores->profiles->update( $request->customer_id, $fields );
		}
	}
}
