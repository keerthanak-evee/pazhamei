<?php
/**
 * Front-end output of the pz/profile-form block (/account/profile).
 *
 * Two forms, filled from Services\ProfileService:
 * 1. Profile: name, mobile, delivery address, dietary preference, allergens
 *    (each with "Not allergic" / Mild / Moderate / Severe) and special
 *    instructions. Saved with PUT /pz/v1/me/profile; the server's errors
 *    show under each field. Address and instruction changes apply from the
 *    next unlocked day (the server's message says which day).
 * 2. Password: current, new and confirm, with POST /pz/v1/me/password.
 *
 * view.js (Interactivity API, store pz/profile) sends the forms without a reload.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\Plans;
use Pazhamei\Domain\ProfileInput;
use Pazhamei\Rest\PortalState;
use Pazhamei\Services\ProfileService;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'The customer’s profile form shows here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

$pz_profile = ProfileService::get( get_current_user_id() );
$pz_context = PortalState::api() + array(
	'errors'    => new stdClass(),
	'message'   => '',
	'ok'        => false,
	'busy'      => false,
	'pwMessage' => '',
	'pwOk'      => false,
);

/**
 * A text field with its error line.
 *
 * @param string $name  Field name.
 * @param string $label Label.
 * @param string $value Value.
 * @param string $extra Extra attributes (fixed strings only).
 * @param string $hint  Hint under the field.
 */
$pz_text = static function ( string $name, string $label, string $value, string $extra = '', string $hint = '' ): void {
	$id = 'pz-profile-' . $name;
	?>
	<p class="pz-field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" type="text" value="<?php echo esc_attr( $value ); ?>" aria-describedby="<?php echo esc_attr( $id . '-error' ); ?>" <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed strings written below. ?> />
		<?php if ( '' !== $hint ) : ?>
			<span class="pz-field__hint"><?php echo esc_html( $hint ); ?></span>
		<?php endif; ?>
		<span class="pz-field__error" id="<?php echo esc_attr( $id . '-error' ); ?>" data-wp-text="context.errors.<?php echo esc_attr( $name ); ?>"></span>
	</p>
	<?php
};
?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-profile pz-portal-block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/profile"
	<?php echo wp_interactivity_data_wp_context( $pz_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
>
	<form class="pz-panel pz-profile__form" data-wp-on--submit="actions.save" novalidate>
		<h2><?php esc_html_e( 'Your details', 'pazhamei-core' ); ?></h2>
		<p class="pz-field__hint"><?php echo esc_html( sprintf( /* translators: %s: email address. */ __( 'Signed in as %s.', 'pazhamei-core' ), $pz_profile['email'] ) ); ?></p>
		<?php $pz_text( 'name', __( 'Full name', 'pazhamei-core' ), $pz_profile['name'], 'autocomplete="name" maxlength="100"' ); ?>
		<?php $pz_text( 'mobile', __( 'Mobile number', 'pazhamei-core' ), $pz_profile['mobile'], 'type="tel" inputmode="tel" autocomplete="tel-national" maxlength="16"', __( 'You can sign in with this number too.', 'pazhamei-core' ) ); ?>

		<h3><?php esc_html_e( 'Delivery address', 'pazhamei-core' ); ?></h3>
		<?php $pz_text( 'address_1', __( 'House / flat number and street', 'pazhamei-core' ), $pz_profile['address_1'], 'autocomplete="address-line1" maxlength="200"' ); ?>
		<?php $pz_text( 'address_2', __( 'Area, landmark (optional)', 'pazhamei-core' ), $pz_profile['address_2'], 'autocomplete="address-line2" maxlength="200"' ); ?>
		<div class="pz-profile__row">
			<?php $pz_text( 'city', __( 'City', 'pazhamei-core' ), $pz_profile['city'], 'autocomplete="address-level2" maxlength="100"' ); ?>
			<?php $pz_text( 'pincode', __( 'Pincode', 'pazhamei-core' ), $pz_profile['pincode'], 'inputmode="numeric" autocomplete="postal-code" maxlength="6"' ); ?>
		</div>

		<h3><?php esc_html_e( 'Food', 'pazhamei-core' ); ?></h3>
		<p class="pz-field">
			<label for="pz-profile-dietary"><?php esc_html_e( 'Dietary preference', 'pazhamei-core' ); ?></label>
			<select id="pz-profile-dietary" name="dietary" aria-describedby="pz-profile-dietary-error">
				<option value=""><?php esc_html_e( 'No preference', 'pazhamei-core' ); ?></option>
				<?php foreach ( Plans::MEAL_TYPES as $pz_code => $pz_label ) : ?>
					<option value="<?php echo esc_attr( $pz_code ); ?>" <?php selected( $pz_profile['dietary'], $pz_code ); ?>><?php echo esc_html( $pz_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="pz-field__hint"><?php esc_html_e( 'Used to pre-select the meal type when you buy a pack. Your current pack’s type doesn’t change.', 'pazhamei-core' ); ?></span>
			<span class="pz-field__error" id="pz-profile-dietary-error" data-wp-text="context.errors.dietary"></span>
		</p>

		<fieldset class="pz-profile__allergens" aria-describedby="pz-profile-allergens-error">
			<legend class="pz-field__label"><?php esc_html_e( 'Allergies', 'pazhamei-core' ); ?></legend>
			<p class="pz-field__hint"><?php esc_html_e( 'The kitchen sees these with every order. Leave “Not allergic” for the rest.', 'pazhamei-core' ); ?></p>
			<?php foreach ( $pz_profile['allergens'] as $pz_allergen ) : ?>
				<?php $pz_id = 'pz-allergen-' . (int) $pz_allergen['id']; ?>
				<p class="pz-profile__allergen">
					<label for="<?php echo esc_attr( $pz_id ); ?>"><?php echo esc_html( $pz_allergen['name'] ); ?></label>
					<select id="<?php echo esc_attr( $pz_id ); ?>" name="<?php echo esc_attr( 'allergens[' . (int) $pz_allergen['id'] . ']' ); ?>" data-allergen="<?php echo esc_attr( (string) (int) $pz_allergen['id'] ); ?>">
						<option value=""><?php esc_html_e( 'Not allergic', 'pazhamei-core' ); ?></option>
						<?php foreach ( ProfileInput::SEVERITIES as $pz_value => $pz_label ) : ?>
							<option value="<?php echo esc_attr( $pz_value ); ?>" <?php selected( $pz_allergen['severity'], $pz_value ); ?>><?php echo esc_html( $pz_label ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
			<?php endforeach; ?>
			<span class="pz-field__error" id="pz-profile-allergens-error" data-wp-text="context.errors.allergens"></span>
		</fieldset>

		<p class="pz-field">
			<label for="pz-profile-instructions"><?php esc_html_e( 'Special instructions (optional)', 'pazhamei-core' ); ?></label>
			<textarea id="pz-profile-instructions" name="instructions" maxlength="<?php echo (int) ProfileInput::MAX_INSTRUCTIONS; ?>" aria-describedby="pz-profile-instructions-error"><?php echo esc_textarea( $pz_profile['instructions'] ); ?></textarea>
			<span class="pz-field__hint"><?php esc_html_e( 'For example: gate number, “call on arrival”. Changes apply from the next day you can still change.', 'pazhamei-core' ); ?></span>
			<span class="pz-field__error" id="pz-profile-instructions-error" data-wp-text="context.errors.instructions"></span>
		</p>

		<p class="pz-alert" role="alert" data-wp-bind--hidden="!context.message" data-wp-class--pz-alert--ok="context.ok" data-wp-text="context.message" hidden></p>
		<p><button type="submit" class="pz-btn" data-wp-bind--disabled="context.busy"><?php esc_html_e( 'Save profile', 'pazhamei-core' ); ?></button></p>
	</form>

	<form class="pz-panel pz-profile__password" data-wp-on--submit="actions.password" novalidate>
		<h2><?php esc_html_e( 'Change password', 'pazhamei-core' ); ?></h2>
		<p class="pz-field">
			<label for="pz-pw-current"><?php esc_html_e( 'Current password', 'pazhamei-core' ); ?></label>
			<input id="pz-pw-current" name="current" type="password" autocomplete="current-password" />
		</p>
		<p class="pz-field">
			<label for="pz-pw-new"><?php esc_html_e( 'New password', 'pazhamei-core' ); ?></label>
			<input id="pz-pw-new" name="new" type="password" autocomplete="new-password" minlength="8" />
			<span class="pz-field__hint"><?php esc_html_e( 'At least 8 characters.', 'pazhamei-core' ); ?></span>
		</p>
		<p class="pz-field">
			<label for="pz-pw-confirm"><?php esc_html_e( 'New password again', 'pazhamei-core' ); ?></label>
			<input id="pz-pw-confirm" name="confirm" type="password" autocomplete="new-password" minlength="8" />
		</p>
		<p class="pz-alert" role="alert" data-wp-bind--hidden="!context.pwMessage" data-wp-class--pz-alert--ok="context.pwOk" data-wp-text="context.pwMessage" hidden></p>
		<p><button type="submit" class="pz-btn pz-btn--outline" data-wp-bind--disabled="context.busy"><?php esc_html_e( 'Change password', 'pazhamei-core' ); ?></button></p>
	</form>
</div>
