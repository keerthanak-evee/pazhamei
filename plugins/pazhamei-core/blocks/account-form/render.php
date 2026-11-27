<?php
/**
 * Front-end output of the pz/account-form block.
 *
 * The sign-in page (/sign-in, WooCommerce's My Account page) has four views,
 * each a group of core blocks with one of these forms inside:
 *   sign-in        "Email or phone number" + password, Forgot password link
 *   register       full name, email, phone, delivery pincode, password, confirm
 *   lost-password  WooCommerce's forgot-password and reset-password forms
 *   account        WooCommerce's account pages for signed-in customers (order details)
 * Woo\AccountForms::view() picks the view; a body class hides the others, and
 * a form whose view isn't showing prints nothing.
 *
 * The forms post to WooCommerce's own handlers (same field names and nonces),
 * so WooCommerce signs in, registers, emails and resets passwords.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Woo\AccountForms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pz_mode    = in_array( $attributes['mode'] ?? '', AccountForms::VIEWS, true ) ? $attributes['mode'] : 'sign-in';
$pz_preview = wp_is_serving_rest_request(); // Editor preview: always draw the form.

if ( ! $pz_preview && AccountForms::view() !== $pz_mode ) {
	return;
}
if ( ! function_exists( 'WC' ) ) {
	return;
}

// phpcs:disable WordPress.Security.NonceVerification -- Only refills what the customer typed after a failed submit.
$pz_old = static function ( string $key ): string {
	return isset( $_POST[ $key ] ) && is_string( $_POST[ $key ] ) ? sanitize_text_field( wp_unslash( $_POST[ $key ] ) ) : '';
};
// phpcs:enable

$pz_action = wc_get_page_permalink( 'myaccount' );

/**
 * One labelled input.
 *
 * @param string $id          Input id.
 * @param string $name        Input name.
 * @param string $label       Label text.
 * @param string $type        Input type.
 * @param string $value       Value.
 * @param string $placeholder Placeholder.
 * @param string $extra       Extra attributes (fixed strings only).
 */
$pz_field = static function ( string $id, string $name, string $label, string $type, string $value, string $placeholder, string $extra = '' ): void {
	?>
	<p class="pz-auth-form__field">
		<label for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( $label ); ?></label>
		<input type="<?php echo esc_attr( $type ); ?>" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( $name ); ?>" value="<?php echo esc_attr( $value ); ?>" placeholder="<?php echo esc_attr( $placeholder ); ?>" required <?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Fixed strings written below. ?> />
	</p>
	<?php
};
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-auth-form pz-auth-form--' . $pz_mode ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
	<?php
	if ( ! $pz_preview && 'lost-password' !== $pz_mode && 'account' !== $pz_mode ) {
		// Coming back from "choose a new password" (WooCommerce adds ?password-reset=true).
		if ( 'sign-in' === $pz_mode && isset( $_GET['password-reset'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
			wc_add_notice( __( 'Your password has been changed. Please sign in with your new password.', 'pazhamei-core' ) );
		}
		woocommerce_output_all_notices();
	}

	if ( 'sign-in' === $pz_mode ) :
		?>
		<form class="woocommerce-form woocommerce-form-login login" method="post" action="<?php echo esc_url( $pz_action ); ?>">
			<?php do_action( 'woocommerce_login_form_start' ); ?>
			<?php $pz_field( 'username', 'username', __( 'Email or phone number', 'pazhamei-core' ), 'text', $pz_old( 'username' ), 'e.g. arun.k@email.com', 'autocomplete="username" autocapitalize="off" spellcheck="false"' ); ?>
			<p class="pz-auth-form__field">
				<span class="pz-auth-form__label-row">
					<label for="password"><?php esc_html_e( 'Password', 'pazhamei-core' ); ?></label>
					<a class="pz-auth-form__forgot" href="<?php echo esc_url( wc_lostpassword_url() ); ?>"><?php esc_html_e( 'Forgot password?', 'pazhamei-core' ); ?></a>
				</span>
				<input type="password" id="password" name="password" autocomplete="current-password" required />
			</p>
			<?php do_action( 'woocommerce_login_form' ); ?>
			<?php wp_nonce_field( 'woocommerce-login', 'woocommerce-login-nonce' ); ?>
			<button type="submit" class="pz-auth-form__submit wp-element-button" name="login" value="<?php esc_attr_e( 'Sign in', 'pazhamei-core' ); ?>"><?php esc_html_e( 'Sign in', 'pazhamei-core' ); ?> <span aria-hidden="true">→</span></button>
			<?php do_action( 'woocommerce_login_form_end' ); ?>
		</form>
		<?php
	elseif ( 'register' === $pz_mode ) :
		?>
		<form class="woocommerce-form woocommerce-form-register register" method="post" action="<?php echo esc_url( add_query_arg( 'view', 'register', $pz_action ) ); ?>">
			<?php do_action( 'woocommerce_register_form_start' ); ?>
			<?php $pz_field( 'pz_full_name', 'pz_full_name', __( 'Full Name', 'pazhamei-core' ), 'text', $pz_old( 'pz_full_name' ), 'e.g. Arun Kumar', 'autocomplete="name" maxlength="100"' ); ?>
			<?php $pz_field( 'reg_email', 'email', __( 'Email Address', 'pazhamei-core' ), 'email', $pz_old( 'email' ), 'e.g. arun.k@email.com', 'autocomplete="email"' ); ?>
			<?php $pz_field( 'pz_mobile', 'pz_mobile', __( 'Phone Number', 'pazhamei-core' ), 'tel', $pz_old( 'pz_mobile' ), 'e.g. 91234 56789', 'autocomplete="tel-national" inputmode="tel" maxlength="16"' ); ?>
			<?php $pz_field( 'pz_pincode', 'pz_pincode', __( 'Delivery Pincode', 'pazhamei-core' ), 'text', $pz_old( 'pz_pincode' ), 'e.g. 600032', 'autocomplete="postal-code" inputmode="numeric" pattern="[1-9][0-9]{5}" maxlength="6"' ); ?>
			<?php $pz_field( 'reg_password', 'password', __( 'Password', 'pazhamei-core' ), 'password', '', '', 'autocomplete="new-password" minlength="8"' ); ?>
			<?php $pz_field( 'pz_password2', 'pz_password2', __( 'Confirm Password', 'pazhamei-core' ), 'password', '', '', 'autocomplete="new-password" minlength="8"' ); ?>
			<?php do_action( 'woocommerce_register_form' ); ?>
			<?php wp_nonce_field( 'woocommerce-register', 'woocommerce-register-nonce' ); ?>
			<button type="submit" class="pz-auth-form__submit wp-element-button" name="register" value="<?php esc_attr_e( 'Register', 'pazhamei-core' ); ?>"><?php esc_html_e( 'Create Account', 'pazhamei-core' ); ?> <span aria-hidden="true">→</span></button>
			<?php do_action( 'woocommerce_register_form_end' ); ?>
		</form>
		<?php
	elseif ( 'lost-password' === $pz_mode ) :
		if ( $pz_preview ) {
			wc_get_template( 'myaccount/form-lost-password.php' );
		} else {
			WC_Shortcode_My_Account::lost_password(); // Forgot form, "email sent", or the reset form from the email link.
		}
	elseif ( ! $pz_preview ) :
		echo do_shortcode( '[woocommerce_my_account]' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- WooCommerce escapes its own output.
	else :
		?>
		<p><?php esc_html_e( 'Signed-in customers see their WooCommerce order details here.', 'pazhamei-core' ); ?></p>
		<?php
	endif;
	?>
</div>
