<?php
/**
 * Plans: the three packs Pazhamei sells and the three meal types (business rule 2).
 *
 * One place for plan names, meal counts and what each pack includes, so the
 * plan cards, the enquiry form and (later) the subscription engine all agree.
 * No prices here: prices live in WooCommerce and are shown to customers only.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plan and meal type catalogue.
 */
final class Plans {

	/** Value used in forms and URLs (?plan=trial). */
	public const TRIAL = 'trial';
	public const D6    = '6-day';
	public const D26   = '26-day';

	/** Extra enquiry choice for visitors who haven't decided. */
	public const NOT_SURE = 'not-sure';

	/** Meal types, fixed at purchase. Stored in upper case, as in the data model. */
	public const MEAL_TYPES = array(
		'VEG'    => 'Vegetarian',
		'NONVEG' => 'Non-vegetarian',
		'MIXED'  => 'Mixed',
	);

	/**
	 * The packs, in display order. Keyed by the URL value.
	 *
	 * @return array<string, array{code: string, meals: int, name: string, label: string, summary: string, includes: string[], image: string}>
	 */
	public static function all(): array {
		return array(
			self::TRIAL => array(
				'code'     => 'TRIAL',
				'meals'    => 1,
				'name'     => 'The Tasting Experience',
				'label'    => 'Trial · 1 lunch',
				'summary'  => 'Experience Pazhamei for a day before settling into a subscription.',
				'includes' => array(
					'One lunch on a service day you choose',
					'Vegetarian, non-vegetarian or mixed',
					'Delivered between noon and 1 pm',
					'One trial per customer',
				),
				'image'    => 'plan-tasting',
			),
			self::D6    => array(
				'code'     => 'D6',
				'meals'    => 6,
				'name'     => 'The Weekly Table',
				'label'    => '6-day plan · 6 lunches',
				'summary'  => 'Six thoughtfully planned lunches on our Monday to Saturday schedule.',
				'includes' => array(
					'Six lunches on service days (Monday to Saturday)',
					'Skip or pause until 8 pm the day before',
					'Skipped and paused days move to the end of your plan',
					'Calories and macros for each lunch',
				),
				'image'    => 'plan-weekly',
			),
			self::D26   => array(
				'code'     => 'D26',
				'meals'    => 26,
				'name'     => 'The Monthly Tradition',
				'label'    => '26-day plan · 26 lunches',
				'summary'  => 'Your everyday lunch routine, taken care of, even when your schedule changes.',
				'includes' => array(
					'Twenty-six lunches on service days (Monday to Saturday)',
					'Skip or pause until 8 pm the day before',
					'Skipped and paused days move to the end of your plan',
					'Renew early and your next pack starts right after this one',
				),
				'image'    => 'plan-monthly',
			),
		);
	}

	/**
	 * Short name for drop-downs, e.g. "26-day plan (26 lunches)" or "Trial (1 lunch)".
	 *
	 * @param string $slug Plan value, e.g. '26-day'.
	 */
	public static function short_label( string $slug ): string {
		$plan = self::all()[ $slug ] ?? null;
		if ( null === $plan ) {
			return '';
		}

		$lunches = 1 === $plan['meals'] ? '1 lunch' : $plan['meals'] . ' lunches';
		$name    = self::TRIAL === $slug ? 'Trial' : $slug . ' plan';

		return "{$name} ({$lunches})";
	}

	/**
	 * Is this a plan value the enquiry form accepts (including "not sure")?
	 *
	 * @param string $value Submitted value.
	 */
	public static function is_enquiry_choice( string $value ): bool {
		return self::NOT_SURE === $value || array_key_exists( $value, self::all() );
	}

	/**
	 * Meals in a pack, by plan code (TRIAL 1, D6 6, D26 26); 0 if unknown.
	 *
	 * @param string $code Plan code as stored on the pack.
	 */
	public static function meals_for_code( string $code ): int {
		foreach ( self::all() as $plan ) {
			if ( $plan['code'] === $code ) {
				return $plan['meals'];
			}
		}
		return 0;
	}

	/**
	 * URL value for a plan code, e.g. 'D6' => '6-day'; '' if unknown.
	 *
	 * @param string $code Plan code.
	 */
	public static function slug_for_code( string $code ): string {
		foreach ( self::all() as $slug => $plan ) {
			if ( $plan['code'] === $code ) {
				return $slug;
			}
		}
		return '';
	}

	/**
	 * Is this a known meal type code (VEG, NONVEG, MIXED)?
	 *
	 * @param string $value Submitted value.
	 */
	public static function is_meal_type( string $value ): bool {
		return array_key_exists( $value, self::MEAL_TYPES );
	}
}
