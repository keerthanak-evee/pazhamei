<?php
/**
 * Front-end output of the pz/customer-welcome block (/account).
 *
 * A greeting, the current pack in one line, tomorrow's lunch and whether
 * it can still be changed (Services\WelcomeView), and shortcuts to the other
 * portal pages. A customer with no pack sees "Start with a trial" (only when
 * the trial is on and they can have one) or "Choose a plan". Read-only, so it
 * needs no script.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Install\MediaImporter;
use Pazhamei\Rest\PortalState;
use Pazhamei\Services\Engine;
use Pazhamei\Services\WelcomeView;
use Pazhamei\Settings;
use Pazhamei\Woo\BuyLink;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'The customer’s greeting, pack, tomorrow’s lunch and shortcuts show here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

$pz_user     = wp_get_current_user();
$pz_trial_ok = (bool) Settings::get( 'trial_enabled' ) && null === BuyLink::trial_refusal( $pz_user->ID );
$pz_view     = WelcomeView::build( Engine::live(), $pz_user->ID, $pz_trial_ok );
$pz_name     = '' !== $pz_user->first_name ? $pz_user->first_name : $pz_user->display_name;
$pz_image    = MediaImporter::find( 'member-tiffin' );

$pz_shortcuts = array(
	array( '/account/dashboard/', __( 'Dashboard', 'pazhamei-core' ), __( 'Skip, pause and see your days', 'pazhamei-core' ) ),
	array( '/account/meals/', __( 'Meals', 'pazhamei-core' ), __( 'This week’s menu', 'pazhamei-core' ) ),
	array( '/account/plans/', __( 'Plans', 'pazhamei-core' ), __( 'Buy or renew a pack', 'pazhamei-core' ) ),
	array( '/account/profile/', __( 'Profile', 'pazhamei-core' ), __( 'Address, allergens, password', 'pazhamei-core' ) ),
	array( '/account/support/', __( 'Support', 'pazhamei-core' ), __( 'Ask the kitchen', 'pazhamei-core' ) ),
	array( '/account/history/', __( 'History', 'pazhamei-core' ), __( 'Past packs and payments', 'pazhamei-core' ) ),
);
?>
<section <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-welcome' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
	<div class="pz-welcome__card">
		<div class="pz-welcome__text">
			<p class="pz-welcome__hello"><?php echo esc_html( sprintf( /* translators: %s: first name. */ __( 'Vanakkam, %s!', 'pazhamei-core' ), $pz_name ) ); ?></p>

			<?php if ( '' !== $pz_view['packLine'] ) : ?>
				<p class="pz-welcome__pack"><?php echo esc_html( $pz_view['packLine'] ); ?></p>
			<?php elseif ( ! $pz_view['hasPack'] ) : ?>
				<p class="pz-welcome__pack"><?php esc_html_e( 'You don’t have a pack yet. Fresh Tamil lunches, Monday to Saturday, delivered to your door.', 'pazhamei-core' ); ?></p>
			<?php endif; ?>

			<?php if ( $pz_view['hasPack'] ) : ?>
				<p class="pz-welcome__tomorrow"><?php echo esc_html( $pz_view['tomorrow'] ); ?></p>
				<?php if ( '' !== $pz_view['next'] ) : ?>
					<p class="pz-welcome__next"><?php echo esc_html( $pz_view['next'] ); ?></p>
				<?php endif; ?>
			<?php endif; ?>

			<p class="pz-welcome__actions">
				<?php if ( $pz_view['hasPack'] ) : ?>
					<a class="pz-btn" href="<?php echo esc_url( home_url( '/account/dashboard/' ) ); ?>"><?php esc_html_e( 'Go to my dashboard', 'pazhamei-core' ); ?></a>
				<?php elseif ( $pz_view['offerTrial'] ) : ?>
					<a class="pz-btn" href="<?php echo esc_url( home_url( '/account/plans/' ) ); ?>"><?php esc_html_e( 'Start with a trial', 'pazhamei-core' ); ?></a>
					<a class="pz-btn pz-btn--outline" href="<?php echo esc_url( home_url( '/account/plans/' ) ); ?>"><?php esc_html_e( 'Choose a plan', 'pazhamei-core' ); ?></a>
				<?php else : ?>
					<a class="pz-btn" href="<?php echo esc_url( home_url( '/account/plans/' ) ); ?>"><?php esc_html_e( 'Choose a plan', 'pazhamei-core' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
		<?php if ( $pz_image > 0 ) : ?>
			<?php echo wp_get_attachment_image( $pz_image, 'full', false, array( 'class' => 'pz-welcome__image', 'alt' => '' ) ); ?>
		<?php endif; ?>
	</div>

	<nav class="pz-welcome__shortcuts" aria-label="<?php esc_attr_e( 'Shortcuts', 'pazhamei-core' ); ?>">
		<?php foreach ( $pz_shortcuts as list( $pz_path, $pz_title, $pz_hint ) ) : ?>
			<a class="pz-welcome__shortcut pz-hover-lift" href="<?php echo esc_url( home_url( $pz_path ) ); ?>">
				<strong><?php echo esc_html( $pz_title ); ?></strong>
				<span><?php echo esc_html( $pz_hint ); ?></span>
			</a>
		<?php endforeach; ?>
	</nav>
</section>
