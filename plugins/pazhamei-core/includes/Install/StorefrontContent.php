<?php
/**
 * StorefrontContent: starter content for the storefront pages.
 *
 * Used by the page installer for new pages, and to replace untouched Phase 0
 * placeholders. After that the owner edits the pages in the block editor;
 * the installer never overwrites edited pages.
 *
 * Text marked [EDIT] is a placeholder the owner must replace before launch.
 * Policy pages are in PolicyContent.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Install\BlockMarkup as B;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Content for Home, Plans, About & Contact, FAQ and Enquire.
 */
final class StorefrontContent {

	/** Home sections, in order: theme pattern slugs. */
	public const HOME_PATTERNS = array(
		'pazhamei/home-hero',
		'pazhamei/home-heritage-strip',
		'pazhamei/home-vision',
		'pazhamei/home-meal-types',
		'pazhamei/home-plans-teaser',
		'pazhamei/home-cooking-week',
		'pazhamei/home-balanced',
		'pazhamei/home-how-it-works',
		'pazhamei/home-testimonials',
		'pazhamei/home-faq-teaser',
		'pazhamei/home-cta',
	);

	/**
	 * Content for a page path, or null if this class has none for it.
	 *
	 * @param string $path Page path, e.g. 'faq'.
	 */
	public static function for_path( string $path ): ?string {
		switch ( $path ) {
			case 'home':
				return self::home();
			case 'plans':
				return self::plans();
			case 'about-contact':
				return self::about_contact();
			case 'faq':
				return self::faq();
			case 'enquire':
				return self::enquire();
			default:
				return PolicyContent::for_path( $path );
		}
	}

	/**
	 * Home: the theme's Home patterns, copied in as real blocks so every part can be edited.
	 * Returns null if the theme's patterns are not available (another theme is active).
	 */
	private static function home(): ?string {
		$registry = \WP_Block_Patterns_Registry::get_instance();
		$parts    = array();

		foreach ( self::HOME_PATTERNS as $slug ) {
			$pattern = $registry->get_registered( $slug );
			if ( ! $pattern ) {
				return null;
			}
			$parts[] = trim( $pattern['content'] );
		}

		return B::join( $parts );
	}

	/**
	 * Plans page.
	 */
	private static function plans(): string {
		return B::join(
			array(
				B::paragraph( 'Every plan is a pack of Tamil lunches, delivered Monday to Saturday between noon and 1 pm. Choose vegetarian, non-vegetarian or mixed when you buy; your meal type stays the same for that pack.' ),
				B::dynamic( 'pz/plan-cards', '{"align":"wide"}' ),
				B::heading( 'Good to know' ),
				B::list(
					array(
						'You pick your start date: any service day in the next 30 days.',
						'Change your plans until 8 pm the day before a lunch. After that, that day is locked.',
						'Skipped and paused days move to the end of your plan, so you never lose a meal.',
						'Buy a new pack before this one ends and it starts on the next service day after.',
					)
				),
				B::paragraph( 'Prices are shown to signed-in customers. <a href="/enquire">Send an enquiry</a> and we will call you, or <a href="/sign-in">sign in</a>.' ),
			)
		);
	}

	/**
	 * About & Contact page.
	 */
	private static function about_contact(): string {
		return B::join(
			array(
				B::heading( 'Our story' ),
				B::paragraph( '[EDIT: the Pazhamei story, in your own words.] Pazhamei began in a home kitchen in Guindy, cooking the lunches we grew up with: rice to catch the gravy, poriyal for crunch, and curd to finish. Today we cook every lunch fresh each morning and deliver it across Chennai.' ),
				B::paragraph( '[EDIT: who cooks, how the menu is planned, and what makes Pazhamei different.]' ),
				B::heading( 'Contact us' ),
				B::list(
					array(
						'<strong>Kitchen address:</strong> [EDIT: door number, street, Guindy, Chennai 6000XX]',
						'<strong>Phone:</strong> [EDIT] <a href="tel:+919840972110">+91 98409 72110</a>',
						'<strong>Email:</strong> [EDIT] <a href="mailto:care@pazhameifuelmeals.in">care@pazhameifuelmeals.in</a>',
						'<strong>WhatsApp:</strong> [EDIT] <a href="https://wa.me/919840972110">Message us on WhatsApp</a>',
						'<strong>Map:</strong> [EDIT: link to the kitchen on Google Maps]',
					)
				),
				B::heading( 'Delivery areas' ),
				B::paragraph( '[EDIT: list the areas and pincodes you deliver to.] We deliver across Chennai, including Guindy and OMR. Not sure if we reach you? <a href="/enquire">Send an enquiry</a> with your pincode.' ),
				B::heading( 'Hours' ),
				B::list(
					array(
						'<strong>Lunch delivery:</strong> Monday to Saturday, noon to 1 pm [EDIT if this changes]',
						'<strong>Changes for tomorrow:</strong> until 8 pm today',
						'<strong>Phone and WhatsApp:</strong> [EDIT: e.g. Monday to Saturday, 9 am to 8 pm]',
						'<strong>Sundays and kitchen holidays:</strong> closed',
					)
				),
			)
		);
	}

	/**
	 * FAQ page: one Details block per question, answers drafted from the business rules.
	 */
	private static function faq(): string {
		return B::join(
			array(
				B::draft_note( 'DRAFT answers – review before launch' ),
				B::details( 'How do the plans work?', 'You buy a pack of lunches: a Trial (1 lunch), 6 lunches or 26 lunches. We deliver one lunch on each service day, Monday to Saturday, from the start date you choose, until the pack is used up.' ),
				B::details( 'Can I skip a day?', 'Yes. Skip any upcoming lunch from your account until 8 pm the day before. The lunch is added to the end of your plan, so you never lose it. You can undo a skip until the same 8 pm cutoff.' ),
				B::details( 'Can I pause my plan?', 'Yes. Pause from any day you can still change, either until a date you choose or until you press Resume. Paused days move to the end of your plan.' ),
				B::details( 'What is the cutoff for changes?', 'Changes for a day close at 8 pm the evening before. For example, to skip Wednesday’s lunch, do it by 8 pm on Tuesday. After that, the kitchen has started planning and the day is locked. Monday’s lunch locks at 8 pm on Sunday.' ),
				B::details( 'Do you deliver on Sundays?', 'No. We cook and deliver Monday to Saturday. Sundays and kitchen holidays are never counted in your plan: your plan simply runs on to the next service day.' ),
				B::details( 'Is there a trial?', 'When trials are open, you can try one lunch before you subscribe. Each customer can have one trial; we check by account, mobile number and delivery address.' ),
				B::details( 'Which areas do you deliver to?', '[EDIT: list your areas and pincodes.] We deliver across Chennai, including Guindy and OMR. Send an enquiry with your pincode and we will confirm.' ),
				B::details( 'How do I pay?', 'You pay online when you buy a pack, through Razorpay: UPI, cards, net banking and wallets. Prices are shown once you sign in.' ),
				B::details( 'Can I change between Veg, Non-Veg and Mixed?', 'Your meal type is fixed for the pack you bought. You can choose a different meal type when you buy your next pack.' ),
				B::details( 'Do you handle allergies?', 'Tell us about any allergies in your account and we will show them to the kitchen with every order. [EDIT: our kitchen handles nuts, dairy, gluten and other common allergens, so we cannot promise any meal is completely free of them.]' ),
				B::paragraph( 'Still have a question? <a href="/enquire">Send us an enquiry</a> or see <a href="/about-contact">how to reach us</a>.' ),
			)
		);
	}

	/**
	 * Enquire page.
	 */
	private static function enquire(): string {
		return B::join(
			array(
				B::paragraph( 'Tell us a little about you and the plan you’re interested in. We’ll call you within one working day to answer your questions and share prices.' ),
				B::dynamic( 'pz/enquiry-form' ),
				B::paragraph( 'We use these details only to reply to your enquiry. See our <a href="/privacy-policy">privacy policy</a>.' ),
			)
		);
	}
}
