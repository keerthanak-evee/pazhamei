<?php
/**
 * Front-end output of the pz/subscription-summary block (customer dashboard).
 *
 * Shows, from Services\DashboardView (the same data as GET /pz/v1/me/dashboard):
 * - "Confirming your payment…" while a paid order is still being confirmed;
 * - the cutoff line with a live countdown;
 * - the current pack: plan, meal type, meals left, start, end, status;
 * - queued renewals;
 * - the pause, with Resume; or Pause with two choices ("until I resume" or
 *   "until a date", labelled "Last day without meals"), showing the new end
 *   date from the server before the customer confirms.
 *
 * Everything updates without a reload (assets/js/dashboard.js, store pz/dashboard).
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Rest\PortalState;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'The customer’s pack, the 8 pm countdown and Pause / Resume show here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

$pz_state = PortalState::dashboard();
wp_interactivity_state( 'pz/dashboard', $pz_state );
$pz_context = array(
	'pauseOpen' => false,
	'mode'      => 'until-date',
	'start'     => $pz_state['pauseStarts'][0]['date'] ?? '',
	'end'       => '',
	'preview'   => '',
	'previewOk' => false,
);
?>
<section
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-summary' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/dashboard"
	<?php echo wp_interactivity_data_wp_context( $pz_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-init="callbacks.start"
	aria-label="<?php esc_attr_e( 'Your pack', 'pazhamei-core' ); ?>"
>
	<p class="pz-summary__confirming" role="status" data-wp-bind--hidden="!state.confirming" <?php echo $pz_state['confirming'] ? '' : 'hidden'; ?>>
		<span class="pz-spinner" aria-hidden="true"></span>
		<?php esc_html_e( 'Confirming your payment… Your new pack will appear here in a moment.', 'pazhamei-core' ); ?>
	</p>

	<p class="pz-summary__cutoff">
		<span class="pz-summary__clock" aria-hidden="true">⏱</span>
		<span data-wp-text="state.cutoff.text"><?php echo esc_html( $pz_state['cutoff']['text'] ); ?></span>
		<span class="pz-summary__countdown" data-wp-text="state.countdown"></span>
	</p>

	<div class="pz-summary__none" data-wp-bind--hidden="state.current" <?php echo $pz_state['current'] ? 'hidden' : ''; ?>>
		<h2><?php esc_html_e( 'No pack running right now', 'pazhamei-core' ); ?></h2>
		<p><?php esc_html_e( 'Choose a pack to start your lunches.', 'pazhamei-core' ); ?></p>
		<p class="pz-summary__actions"><a class="pz-btn" href="<?php echo esc_url( home_url( '/account/plans/' ) ); ?>"><?php esc_html_e( 'Choose a plan', 'pazhamei-core' ); ?></a></p>
	</div>

	<div class="pz-summary__pack" data-wp-bind--hidden="!state.current" <?php echo $pz_state['current'] ? '' : 'hidden'; ?>>
		<div class="pz-summary__head">
			<div>
				<p class="pz-summary__eyebrow"><?php esc_html_e( 'Current pack', 'pazhamei-core' ); ?></p>
				<h2 class="pz-summary__name" data-wp-text="state.current.name"><?php echo esc_html( $pz_state['current']['name'] ?? '' ); ?></h2>
			</div>
			<span class="pz-summary__status" data-wp-text="state.current.statusLabel" data-wp-class--is-paused="state.isPaused"><?php echo esc_html( $pz_state['current']['statusLabel'] ?? '' ); ?></span>
		</div>

		<dl class="pz-summary__facts">
			<div><dt><?php esc_html_e( 'Plan', 'pazhamei-core' ); ?></dt><dd data-wp-text="state.current.label"><?php echo esc_html( $pz_state['current']['label'] ?? '' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Meal type', 'pazhamei-core' ); ?></dt><dd data-wp-text="state.current.mealType"><?php echo esc_html( $pz_state['current']['mealType'] ?? '' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Meals left', 'pazhamei-core' ); ?></dt><dd><strong data-wp-text="state.current.left"><?php echo esc_html( (string) ( $pz_state['current']['left'] ?? '' ) ); ?></strong> <?php esc_html_e( 'of', 'pazhamei-core' ); ?> <span data-wp-text="state.current.total"><?php echo esc_html( (string) ( $pz_state['current']['total'] ?? '' ) ); ?></span></dd></div>
			<div><dt><?php esc_html_e( 'Starts', 'pazhamei-core' ); ?></dt><dd data-wp-text="state.current.start"><?php echo esc_html( $pz_state['current']['start'] ?? '' ); ?></dd></div>
			<div><dt><?php esc_html_e( 'Ends', 'pazhamei-core' ); ?></dt><dd class="pz-summary__end" data-wp-text="state.current.end" data-wp-class--is-changed="state.endChanged"><?php echo esc_html( $pz_state['current']['end'] ?? '' ); ?></dd></div>
		</dl>

		<p class="pz-summary__pause-text" data-wp-bind--hidden="!state.pause" data-wp-text="state.pause.text" <?php echo $pz_state['pause'] ? '' : 'hidden'; ?>><?php echo esc_html( $pz_state['pause']['text'] ?? '' ); ?></p>

		<div class="pz-summary__actions">
			<button type="button" class="pz-btn" data-wp-bind--hidden="!state.canResume" data-wp-on--click="actions.resume" data-wp-bind--disabled="state.busy" <?php echo $pz_state['canResume'] ? '' : 'hidden'; ?>><?php esc_html_e( 'Resume deliveries', 'pazhamei-core' ); ?></button>
			<button type="button" class="pz-btn pz-btn--outline" data-wp-bind--hidden="!state.canPause" data-wp-on--click="actions.togglePause" data-wp-bind--aria-expanded="context.pauseOpen" aria-controls="pz-pause-panel" <?php echo $pz_state['canPause'] ? '' : 'hidden'; ?>><?php esc_html_e( 'Pause deliveries', 'pazhamei-core' ); ?></button>
		</div>

		<form id="pz-pause-panel" class="pz-pause" data-wp-bind--hidden="!state.pauseVisible" data-wp-on--submit="actions.confirmPause" hidden>
			<fieldset>
				<legend><?php esc_html_e( 'Pause my lunches', 'pazhamei-core' ); ?></legend>
				<p class="pz-pause__field">
					<label for="pz-pause-start"><?php esc_html_e( 'First day without meals', 'pazhamei-core' ); ?></label>
					<select id="pz-pause-start" data-wp-on--change="actions.setStart">
						<?php foreach ( $pz_state['pauseStarts'] as $pz_i => $pz_day ) : ?>
							<option value="<?php echo esc_attr( $pz_day['date'] ); ?>" <?php selected( 0, $pz_i ); ?>><?php echo esc_html( $pz_day['display'] ); ?></option>
						<?php endforeach; ?>
					</select>
				</p>
				<p class="pz-pause__choice">
					<label><input type="radio" name="pz-pause-mode" value="until-date" checked data-wp-on--change="actions.setMode" /> <?php esc_html_e( 'Until a date', 'pazhamei-core' ); ?></label>
					<label><input type="radio" name="pz-pause-mode" value="until-resume" data-wp-on--change="actions.setMode" /> <?php esc_html_e( 'Until I resume', 'pazhamei-core' ); ?></label>
				</p>
				<p class="pz-pause__field" data-wp-bind--hidden="state.isOpenEnded">
					<label for="pz-pause-end"><?php esc_html_e( 'Last day without meals', 'pazhamei-core' ); ?></label>
					<input type="date" id="pz-pause-end" data-wp-bind--min="context.start" data-wp-on--change="actions.setEnd" />
				</p>
				<p class="pz-pause__preview" role="status" data-wp-text="context.preview"></p>
				<p class="pz-summary__actions">
					<button type="submit" class="pz-btn" data-wp-bind--disabled="!state.canConfirm"><?php esc_html_e( 'Confirm pause', 'pazhamei-core' ); ?></button>
					<button type="button" class="pz-btn pz-btn--text" data-wp-on--click="actions.togglePause"><?php esc_html_e( 'Cancel', 'pazhamei-core' ); ?></button>
				</p>
			</fieldset>
		</form>
	</div>

	<p class="pz-summary__message" role="alert" data-wp-bind--hidden="!state.message" data-wp-text="state.message" hidden></p>

	<div class="pz-summary__queued" data-wp-bind--hidden="!state.queued.length" <?php echo $pz_state['queued'] ? '' : 'hidden'; ?>>
		<h3><?php esc_html_e( 'Coming up next', 'pazhamei-core' ); ?></h3>
		<ul>
			<template data-wp-each--pack="state.queued" data-wp-each-key="context.pack.id">
				<li>
					<strong data-wp-text="context.pack.name"></strong> ·
					<span data-wp-text="context.pack.label"></span> ·
					<span data-wp-text="context.pack.mealType"></span> ·
					<?php esc_html_e( 'Starts', 'pazhamei-core' ); ?> <span data-wp-text="context.pack.start"></span>,
					<?php esc_html_e( 'ends', 'pazhamei-core' ); ?> <span data-wp-text="context.pack.end"></span>
				</li>
			</template>
		</ul>
	</div>
</section>
