<?php
/**
 * Front-end output of the pz/schedule-calendar block (customer dashboard).
 *
 * One tile per date from today to a little after the last lunch, from
 * Services\DashboardView (the same data as GET /pz/v1/me/dashboard):
 * - lunch days with Skip, skipped days with Undo skip (only while unlocked);
 * - Sundays and kitchen holidays greyed out with a label;
 * - locked days with a lock and the reason.
 * The server decides every button; after Skip or Undo the calendar and the
 * end date refresh without a reload (assets/js/dashboard.js).
 *
 * Phones: a list. Wider screens: a Monday-to-Sunday grid.
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
	echo '<p>' . esc_html__( 'The customer’s calendar with Skip / Undo skip shows here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

wp_interactivity_state( 'pz/dashboard', PortalState::dashboard() );
?>
<section
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-calendar' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/dashboard"
	aria-labelledby="pz-calendar-title"
>
	<h2 id="pz-calendar-title" class="pz-calendar__title"><?php esc_html_e( 'Your lunches', 'pazhamei-core' ); ?></h2>
	<p class="pz-calendar__help"><?php esc_html_e( 'Skip a day and its lunch moves to the end of your pack. You can undo a skip until the cutoff.', 'pazhamei-core' ); ?></p>

	<p class="pz-calendar__message" role="alert" data-wp-bind--hidden="!state.calendarMessage" data-wp-text="state.calendarMessage" hidden></p>

	<div class="pz-calendar__heads" aria-hidden="true">
		<?php foreach ( array( 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ) as $pz_name ) : ?>
			<span><?php echo esc_html( $pz_name ); ?></span>
		<?php endforeach; ?>
	</div>

	<ol class="pz-calendar__days">
		<template data-wp-each--day="state.days" data-wp-each-key="context.day.date">
			<li
				class="pz-day"
				data-wp-bind--data-kind="context.day.kind"
				data-wp-class--is-locked="context.day.locked"
				data-wp-bind--data-col="context.day.col"
			>
				<p class="pz-day__date">
					<span class="pz-day__weekday" data-wp-text="context.day.weekday"></span>
					<span class="pz-day__num" data-wp-text="context.day.dayNum"></span>
					<span class="pz-day__month" data-wp-text="context.day.month"></span>
				</p>
				<p class="pz-day__label">
					<span class="pz-day__lock" data-wp-bind--hidden="!context.day.locked" aria-hidden="true">🔒</span>
					<span data-wp-text="context.day.label"></span>
					<span class="pz-day__type" data-wp-text="context.day.mealType"></span>
				</p>
				<p class="pz-day__reason" data-wp-bind--hidden="!context.day.lockReason" data-wp-text="context.day.lockReason"></p>
				<button type="button" class="pz-day__btn" data-wp-bind--hidden="!context.day.canSkip" data-wp-on--click="actions.skip" data-wp-bind--disabled="state.busy" data-wp-bind--aria-label="state.skipLabel"><?php esc_html_e( 'Skip', 'pazhamei-core' ); ?></button>
				<button type="button" class="pz-day__btn pz-day__btn--undo" data-wp-bind--hidden="!context.day.canUndo" data-wp-on--click="actions.undo" data-wp-bind--disabled="state.busy" data-wp-bind--aria-label="state.undoLabel"><?php esc_html_e( 'Undo skip', 'pazhamei-core' ); ?></button>
			</li>
		</template>
	</ol>
</section>
