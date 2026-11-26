<?php
/**
 * Front-end output of the pz/enquiry-form block.
 *
 * A normal HTML form, made interactive by view.js (Interactivity API):
 * - On load, view.js fetches a fresh nonce from GET /pz/v1/enquiries/nonce, so a
 *   cached copy of this page still works.
 * - ?plan=trial|6-day|26-day pre-selects the plan (here, and again in view.js
 *   in case the page came from a cache).
 * - "company_url" is the honeypot: hidden from people, filled in by bots.
 * - On submit it posts JSON to /pz/v1/enquiries and shows a thank-you message
 *   or the errors for each field, without reloading.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Clock;
use Pazhamei\Domain\EnquiryValidator;
use Pazhamei\Domain\Plans;
use Pazhamei\Services\Honeypot;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Plan from the URL, if it is one we know.
$pz_requested = isset( $_GET['plan'] ) ? sanitize_key( wp_unslash( $_GET['plan'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Read-only pre-selection.
$pz_selected  = Plans::is_enquiry_choice( $pz_requested ) ? $pz_requested : '';

$pz_today    = Clock::now();
$pz_min_date = $pz_today->modify( '+1 day' )->format( 'Y-m-d' );
$pz_max_date = $pz_today->modify( '+' . EnquiryValidator::MAX_START_DAYS . ' days' )->format( 'Y-m-d' );

$pz_context = array(
	'restUrl'  => rest_url( 'pz/v1/enquiries' ),
	'nonceUrl' => rest_url( 'pz/v1/enquiries/nonce' ),
	'nonce'    => '',
	'status'   => 'idle',
	'message'  => '',
	'errors'   => new stdClass(),
);

// Each text field: name => label, input type, required, autocomplete, extra attributes.
$pz_fields = array(
	'name'    => array( __( 'Your name', 'pazhamei-core' ), 'text', true, 'name', 'maxlength="100"' ),
	'mobile'  => array( __( 'Mobile number', 'pazhamei-core' ), 'tel', true, 'tel', 'inputmode="tel" maxlength="16" placeholder="98765 43210"' ),
	'email'   => array( __( 'Email (optional)', 'pazhamei-core' ), 'email', false, 'email', 'maxlength="100"' ),
	'area'    => array( __( 'Area (optional)', 'pazhamei-core' ), 'text', false, 'address-level3', 'maxlength="100" placeholder="Guindy"' ),
	'pincode' => array( __( 'Pincode', 'pazhamei-core' ), 'text', true, 'postal-code', 'inputmode="numeric" pattern="[1-9][0-9]{5}" maxlength="6"' ),
);

/**
 * Print the error line for a field. Hidden until the server reports an error.
 *
 * @param string $field Field name.
 */
$pz_error = static function ( string $field ): void {
	printf(
		'<p class="pz-enquiry__error" id="pz-enquiry-%1$s-error" data-wp-text="context.errors.%1$s" data-wp-bind--hidden="!context.errors.%1$s" hidden></p>',
		esc_attr( $field )
	);
};
?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-enquiry' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/enquiry-form"
	<?php echo wp_interactivity_data_wp_context( $pz_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-init="callbacks.init"
>
	<form class="pz-enquiry__form" novalidate data-wp-on--submit="actions.submit" data-wp-bind--hidden="state.isDone">
		<div class="pz-enquiry__grid">
			<?php foreach ( $pz_fields as $pz_name => list( $pz_label, $pz_type, $pz_required, $pz_auto, $pz_extra ) ) : ?>
				<div class="pz-enquiry__field">
					<label for="pz-enquiry-<?php echo esc_attr( $pz_name ); ?>"><?php echo esc_html( $pz_label ); ?></label>
					<input
						id="pz-enquiry-<?php echo esc_attr( $pz_name ); ?>"
						name="<?php echo esc_attr( $pz_name ); ?>"
						type="<?php echo esc_attr( $pz_type ); ?>"
						autocomplete="<?php echo esc_attr( $pz_auto ); ?>"
						aria-describedby="pz-enquiry-<?php echo esc_attr( $pz_name ); ?>-error"
						<?php echo $pz_required ? 'required' : ''; ?>
						<?php echo $pz_extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed strings written above. ?>
					/>
					<?php $pz_error( $pz_name ); ?>
				</div>
			<?php endforeach; ?>

			<div class="pz-enquiry__field">
				<label for="pz-enquiry-plan_interest"><?php esc_html_e( 'Plan you are interested in', 'pazhamei-core' ); ?></label>
				<select id="pz-enquiry-plan_interest" name="plan_interest" required aria-describedby="pz-enquiry-plan_interest-error">
					<option value=""><?php esc_html_e( 'Choose a plan', 'pazhamei-core' ); ?></option>
					<?php foreach ( Plans::all() as $pz_slug => $pz_plan ) : ?>
						<option value="<?php echo esc_attr( $pz_slug ); ?>" <?php selected( $pz_selected, $pz_slug ); ?>><?php echo esc_html( Plans::short_label( $pz_slug ) ); ?></option>
					<?php endforeach; ?>
					<option value="<?php echo esc_attr( Plans::NOT_SURE ); ?>" <?php selected( $pz_selected, Plans::NOT_SURE ); ?>><?php esc_html_e( 'Not sure yet', 'pazhamei-core' ); ?></option>
				</select>
				<?php $pz_error( 'plan_interest' ); ?>
			</div>

			<div class="pz-enquiry__field">
				<label for="pz-enquiry-meal_type"><?php esc_html_e( 'Meal type', 'pazhamei-core' ); ?></label>
				<select id="pz-enquiry-meal_type" name="meal_type" aria-describedby="pz-enquiry-meal_type-error">
					<option value=""><?php esc_html_e( 'Not sure yet', 'pazhamei-core' ); ?></option>
					<?php foreach ( Plans::MEAL_TYPES as $pz_code => $pz_label ) : ?>
						<option value="<?php echo esc_attr( $pz_code ); ?>"><?php echo esc_html( $pz_label ); ?></option>
					<?php endforeach; ?>
				</select>
				<?php $pz_error( 'meal_type' ); ?>
			</div>

			<div class="pz-enquiry__field">
				<label for="pz-enquiry-preferred_start"><?php esc_html_e( 'Preferred start date (optional)', 'pazhamei-core' ); ?></label>
				<input id="pz-enquiry-preferred_start" name="preferred_start" type="date" min="<?php echo esc_attr( $pz_min_date ); ?>" max="<?php echo esc_attr( $pz_max_date ); ?>" aria-describedby="pz-enquiry-preferred_start-error pz-enquiry-start-hint" />
				<span class="pz-enquiry__hint" id="pz-enquiry-start-hint"><?php esc_html_e( 'Monday to Saturday. We don’t deliver on Sundays.', 'pazhamei-core' ); ?></span>
				<?php $pz_error( 'preferred_start' ); ?>
			</div>
		</div>

		<div class="pz-enquiry__field">
			<label for="pz-enquiry-message"><?php esc_html_e( 'Anything else? (optional)', 'pazhamei-core' ); ?></label>
			<textarea id="pz-enquiry-message" name="message" rows="4" maxlength="1000" aria-describedby="pz-enquiry-message-error"></textarea>
			<?php $pz_error( 'message' ); ?>
		</div>

		<?php // Honeypot: hidden from people and screen readers. Bots fill it in. ?>
		<div class="pz-enquiry__hp" aria-hidden="true">
			<label for="pz-enquiry-hp">Company website</label>
			<input id="pz-enquiry-hp" type="text" name="<?php echo esc_attr( Honeypot::FIELD ); ?>" value="" tabindex="-1" autocomplete="off" />
		</div>

		<p class="pz-enquiry__alert" role="alert" data-wp-text="context.message" data-wp-bind--hidden="!state.isError" hidden></p>

		<div class="wp-block-buttons">
			<div class="wp-block-button">
				<button type="submit" class="wp-block-button__link wp-element-button" data-wp-bind--disabled="state.isSending">
					<span data-wp-bind--hidden="state.isSending"><?php esc_html_e( 'Send enquiry', 'pazhamei-core' ); ?></span>
					<span data-wp-bind--hidden="!state.isSending" hidden><?php esc_html_e( 'Sending…', 'pazhamei-core' ); ?></span>
				</button>
			</div>
		</div>
	</form>

	<div class="pz-enquiry__thanks" role="status" data-wp-bind--hidden="!state.isDone" hidden>
		<h2><?php esc_html_e( 'Thank you!', 'pazhamei-core' ); ?></h2>
		<p data-wp-text="context.message"></p>
	</div>
</div>
