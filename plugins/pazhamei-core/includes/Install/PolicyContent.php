<?php
/**
 * PolicyContent: draft text for the four policy pages Razorpay asks for.
 *
 * Terms, Privacy, Refund & Cancellation and Delivery. Each starts with a
 * "DRAFT – review before launch" note and follows the business rules in
 * CLAUDE.md. These are drafts, not legal advice: the owner must review them
 * (ideally with a lawyer) before go-live. [EDIT] marks details to fill in.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Install;

use Pazhamei\Install\BlockMarkup as B;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Policy page drafts.
 */
final class PolicyContent {

	/** Business name used in the policies. */
	private const BUSINESS = 'Pazhamei Fuel Meals';

	/**
	 * Content for a policy page path, or null.
	 *
	 * @param string $path Page path.
	 */
	public static function for_path( string $path ): ?string {
		switch ( $path ) {
			case 'terms':
				return self::terms();
			case 'privacy-policy':
				return self::privacy();
			case 'refund-policy':
				return self::refund();
			case 'delivery-policy':
				return self::delivery();
			default:
				return null;
		}
	}

	/**
	 * The "who we are" line shared by every policy.
	 */
	private static function who(): string {
		return 'This website is run by ' . self::BUSINESS . ' ("Pazhamei", "we"), [EDIT: legal entity type and registered address], Chennai, Tamil Nadu, India. Contact: <a href="mailto:care@pazhameifuelmeals.in">care@pazhameifuelmeals.in</a> [EDIT], +91 98409 72110 [EDIT].';
	}

	/**
	 * Terms & Conditions.
	 */
	private static function terms(): string {
		return B::join(
			array(
				B::draft_note(),
				B::paragraph( self::who() ),
				B::paragraph( 'By buying a plan or using this website you agree to these terms. Last updated: [EDIT: date].' ),
				B::heading( 'Plans' ),
				B::list(
					array(
						'A plan is a prepaid pack of lunches: Trial (1 lunch), 6-day (6 lunches) or 26-day (26 lunches).',
						'You choose Vegetarian, Non-vegetarian or Mixed when you buy. It cannot be changed for that pack.',
						'We deliver one lunch per customer per day.',
						'Lunches are delivered on service days: Monday to Saturday. Sundays and kitchen holidays we announce are not service days.',
						'You choose your start date at checkout. It must be a service day within the next 30 days that is still open for changes.',
						'Your plan ends on the day of its last scheduled lunch. Skips, pauses and holidays move that day later.',
					)
				),
				B::heading( 'Changes, skips and pauses' ),
				B::list(
					array(
						'You can change a day’s lunch (skip it, or start a pause from it) until 8 pm IST on the day before. After that, that day is locked.',
						'A skipped lunch moves to the end of your plan. You can undo a skip until the same cutoff.',
						'You can pause from any day that is not locked, until a date you choose or until you resume. Paused lunches move to the end of your plan.',
						'[EDIT: the longest pause allowed, e.g. 30 days, and what happens after it.]',
					)
				),
				B::heading( 'Renewals' ),
				B::paragraph( 'If you buy a new pack while one is still running, the new pack starts on the next service day after the current pack ends.' ),
				B::heading( 'Trial' ),
				B::paragraph( 'The Trial is offered only when we open it, and once per customer. We check this by account, mobile number and delivery address. We may cancel and refund a trial bought against this rule.' ),
				B::heading( 'Prices and payment' ),
				B::paragraph( 'Prices are in Indian Rupees and include [EDIT: GST details]. You pay in full when you buy, through Razorpay. We may change prices for future packs; a pack you have paid for keeps its price.' ),
				B::heading( 'Food, allergies and delivery' ),
				B::paragraph( 'Tell us about allergies in your account. Our kitchen also handles common allergens, so we cannot guarantee any meal is free from them. Delivery is covered by our <a href="/delivery-policy">Delivery Policy</a>; refunds by our <a href="/refund-policy">Refund &amp; Cancellation Policy</a>.' ),
				B::heading( 'Your account' ),
				B::paragraph( 'Keep your password private and your phone number and address up to date. You are responsible for changes made from your account.' ),
				B::heading( 'Liability and law' ),
				B::paragraph( '[EDIT: limitation of liability, wording to be reviewed by a lawyer.] These terms are governed by the laws of India, and the courts of Chennai have jurisdiction.' ),
			)
		);
	}

	/**
	 * Privacy Policy.
	 */
	private static function privacy(): string {
		return B::join(
			array(
				B::draft_note(),
				B::paragraph( self::who() ),
				B::paragraph( 'This policy explains what personal data we collect, why, and your rights under India’s Digital Personal Data Protection Act, 2023. Last updated: [EDIT: date].' ),
				B::heading( 'What we collect' ),
				B::list(
					array(
						'<strong>Enquiries:</strong> name, mobile number, email (optional), area and pincode, the plan and meal type you are interested in, preferred start date and your message.',
						'<strong>Customer accounts:</strong> name, email, mobile number, delivery address, meal type, allergies and delivery instructions, and your plan, delivery and order history.',
						'<strong>Payments:</strong> handled by Razorpay. We receive the payment status and reference, never your full card or bank details.',
						'<strong>Technical data:</strong> cookies needed for signing in and checkout. To stop spam, the enquiry form briefly keeps a scrambled (hashed) form of your IP address for 15 minutes; we never store the IP address itself.',
					)
				),
				B::heading( 'Why we use it' ),
				B::list(
					array(
						'To reply to your enquiry.',
						'To cook and deliver your lunches, including showing your allergies and instructions to our kitchen and delivery staff.',
						'To send you emails about your orders, plan and account.',
						'To meet tax and accounting law.',
					)
				),
				B::heading( 'Who we share it with' ),
				B::paragraph( 'Only the services we need to run Pazhamei: Razorpay (payments), our website host [EDIT: Hostinger], our email delivery service [EDIT], and our delivery staff or partners [EDIT]. We do not sell your data.' ),
				B::heading( 'How long we keep it' ),
				B::paragraph( '[EDIT: e.g. enquiries for 12 months; account and order records for as long as your account is open, and order records for 8 years for tax law.]' ),
				B::heading( 'Your rights' ),
				B::paragraph( 'You can ask to see, correct or delete your personal data, or withdraw your consent, by writing to us. We will reply within [EDIT: 30] days. If you are not satisfied, you may complain to the Data Protection Board of India.' ),
				B::heading( 'Grievance officer' ),
				B::paragraph( '[EDIT: name, email and phone of the person who handles privacy complaints.]' ),
			)
		);
	}

	/**
	 * Refund & Cancellation Policy.
	 */
	private static function refund(): string {
		return B::join(
			array(
				B::draft_note(),
				B::paragraph( self::who() ),
				B::heading( 'Skips and pauses are not refunds' ),
				B::paragraph( 'Skipping or pausing never loses a lunch: the lunch moves to the end of your plan. So no refund is due for a skip or pause.' ),
				B::heading( 'Cancelling a plan' ),
				B::list(
					array(
						'You can cancel a plan by contacting us. Cancellation takes effect from the next day that is not yet locked (changes for a day close at 8 pm IST the day before).',
						'We refund the lunches you have not received: the per-lunch price of your pack × lunches left, minus a cancellation fee of [EDIT: fee in rupees, or a percentage].',
						'Lunches already delivered, and the next day’s lunch once it is locked, are not refunded.',
						'A Trial cannot be cancelled once its day is locked. [EDIT: confirm]',
						'A pack that has not started yet (for example a renewal waiting its turn) is refunded in full, minus [EDIT: fee, if any].',
					)
				),
				B::heading( 'Missed or failed deliveries' ),
				B::paragraph( 'If a lunch does not reach you because of a problem on our side, tell us the same day. We will add a lunch to your plan or refund it; we decide which case by case. [EDIT]' ),
				B::heading( 'How refunds are paid' ),
				B::paragraph( 'Refunds go back to your original payment method through Razorpay within [EDIT: 5–7] working days of approval.' ),
				B::heading( 'Contact' ),
				B::paragraph( 'For cancellations and refunds, email <a href="mailto:care@pazhameifuelmeals.in">care@pazhameifuelmeals.in</a> [EDIT] with your name and registered mobile number.' ),
			)
		);
	}

	/**
	 * Delivery Policy.
	 */
	private static function delivery(): string {
		return B::join(
			array(
				B::draft_note(),
				B::paragraph( self::who() ),
				B::heading( 'Where we deliver' ),
				B::paragraph( '[EDIT: delivery areas and pincodes.] We check your pincode before you buy. If we can’t deliver to you, we won’t take your order.' ),
				B::heading( 'When we deliver' ),
				B::list(
					array(
						'Monday to Saturday, between noon and 1 pm [EDIT: confirm window].',
						'No deliveries on Sundays or on kitchen holidays. We announce holidays in advance; your plan moves on to the next service day, so you don’t lose a lunch.',
						'Your first lunch arrives on the start date you chose at checkout.',
					)
				),
				B::heading( 'Changes before delivery' ),
				B::paragraph( 'Skips, pauses and address changes for a day must be made by 8 pm IST the day before. After that the order has gone to the kitchen and can’t be changed.' ),
				B::heading( 'At delivery' ),
				B::list(
					array(
						'Please make sure someone can receive the lunch, or leave clear instructions in your account (for example "leave with security").',
						'If we can’t deliver because nobody was available or the address was wrong, the lunch counts as delivered. [EDIT: confirm]',
						'If a lunch is late, damaged or missing because of us, tell us the same day and we will make it right (see our <a href="/refund-policy">Refund &amp; Cancellation Policy</a>).',
					)
				),
				B::heading( 'Delivery charges' ),
				B::paragraph( '[EDIT: delivery is included in the plan price, or state the charge.]' ),
			)
		);
	}
}
