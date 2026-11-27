<?php
/**
 * Front-end output of the pz/support-tickets block (/account/support).
 *
 * A form to raise a ticket (topic, message, optional lunch date) and the
 * customer's tickets with their messages and status. Posting uses
 * POST /pz/v1/support/tickets (the admin gets an email); the list then
 * updates without a reload (view.js, store pz/support). Admin replies on
 * the website come with the admin portal (Phase 8).
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\TicketInput;
use Pazhamei\Rest\PortalState;
use Pazhamei\Rest\SupportController;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'The customer’s support form and tickets show here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() ) {
	return;
}

wp_interactivity_state( 'pz/support', array( 'tickets' => SupportController::tickets( get_current_user_id() ) ) );
$pz_context = PortalState::api() + array(
	'errors'  => new stdClass(),
	'message' => '',
	'ok'      => false,
	'busy'    => false,
);
?>
<div
	<?php echo get_block_wrapper_attributes( array( 'class' => 'pz-support pz-portal-block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
	data-wp-interactive="pz/support"
	<?php echo wp_interactivity_data_wp_context( $pz_context ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>
>
	<form class="pz-panel pz-support__form" data-wp-on--submit="actions.raise" novalidate>
		<h2><?php esc_html_e( 'Ask the kitchen', 'pazhamei-core' ); ?></h2>
		<p class="pz-field">
			<label for="pz-ticket-topic"><?php esc_html_e( 'Topic', 'pazhamei-core' ); ?></label>
			<select id="pz-ticket-topic" name="topic" aria-describedby="pz-ticket-topic-error">
				<option value=""><?php esc_html_e( 'Choose a topic', 'pazhamei-core' ); ?></option>
				<?php foreach ( TicketInput::TOPICS as $pz_value => $pz_label ) : ?>
					<option value="<?php echo esc_attr( $pz_value ); ?>"><?php echo esc_html( $pz_label ); ?></option>
				<?php endforeach; ?>
			</select>
			<span class="pz-field__error" id="pz-ticket-topic-error" data-wp-text="context.errors.topic"></span>
		</p>
		<p class="pz-field">
			<label for="pz-ticket-date"><?php esc_html_e( 'Lunch date it’s about (optional)', 'pazhamei-core' ); ?></label>
			<input id="pz-ticket-date" name="date" type="date" aria-describedby="pz-ticket-date-error" />
			<span class="pz-field__error" id="pz-ticket-date-error" data-wp-text="context.errors.date"></span>
		</p>
		<p class="pz-field">
			<label for="pz-ticket-message"><?php esc_html_e( 'Message', 'pazhamei-core' ); ?></label>
			<textarea id="pz-ticket-message" name="message" maxlength="<?php echo (int) TicketInput::MAX_MESSAGE; ?>" aria-describedby="pz-ticket-message-error"></textarea>
			<span class="pz-field__error" id="pz-ticket-message-error" data-wp-text="context.errors.message"></span>
		</p>
		<p class="pz-alert" role="alert" data-wp-bind--hidden="!context.message" data-wp-class--pz-alert--ok="context.ok" data-wp-text="context.message" hidden></p>
		<p><button type="submit" class="pz-btn" data-wp-bind--disabled="context.busy"><?php esc_html_e( 'Send to the kitchen', 'pazhamei-core' ); ?></button></p>
	</form>

	<section class="pz-support__list" aria-labelledby="pz-support-title">
		<h2 id="pz-support-title"><?php esc_html_e( 'Your tickets', 'pazhamei-core' ); ?></h2>
		<p class="pz-support__empty" data-wp-bind--hidden="state.tickets.length"><?php esc_html_e( 'No tickets yet.', 'pazhamei-core' ); ?></p>
		<template data-wp-each--ticket="state.tickets" data-wp-each-key="context.ticket.id">
			<article class="pz-ticket" data-wp-bind--data-status="context.ticket.status">
				<header class="pz-ticket__head">
					<strong>#<span data-wp-text="context.ticket.id"></span> · <span data-wp-text="context.ticket.topic"></span></strong>
					<span class="pz-ticket__status" data-wp-text="context.ticket.statusLabel"></span>
				</header>
				<p class="pz-ticket__meta">
					<?php esc_html_e( 'Raised', 'pazhamei-core' ); ?> <span data-wp-text="context.ticket.created"></span>
					<span data-wp-bind--hidden="!context.ticket.about"> · <?php esc_html_e( 'about', 'pazhamei-core' ); ?> <span data-wp-text="context.ticket.about"></span></span>
				</p>
				<ol class="pz-ticket__messages">
					<template data-wp-each--msg="context.ticket.messages" data-wp-each-key="context.msg.id">
						<li class="pz-ticket__message" data-wp-class--is-mine="context.msg.mine">
							<span class="pz-ticket__who"><span data-wp-text="context.msg.who"></span> · <span data-wp-text="context.msg.when"></span></span>
							<span class="pz-ticket__body" data-wp-text="context.msg.body"></span>
						</li>
					</template>
				</ol>
			</article>
		</template>
	</section>
</div>
