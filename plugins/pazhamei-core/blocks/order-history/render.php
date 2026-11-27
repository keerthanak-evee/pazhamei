<?php
/**
 * Front-end output of the pz/order-history block (/account/history).
 *
 * - Packs (Services\HistoryView), newest first: plan, meal type, dates,
 *   status, and the delivered / not-delivered days in a fold.
 * - Payments: the customer's WooCommerce orders (HPOS-safe wc_get_orders),
 *   with a link to each order page, which works as the receipt.
 * Read-only, so it needs no script.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Domain\Money;
use Pazhamei\Rest\PortalState;
use Pazhamei\Services\Engine;
use Pazhamei\Services\HistoryView;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( wp_is_serving_rest_request() ) {
	echo '<p>' . esc_html__( 'The customer’s packs and payments show here.', 'pazhamei-core' ) . '</p>';
	return;
}
if ( ! PortalState::is_customer() || ! function_exists( 'wc_get_orders' ) ) {
	return;
}

$pz_user_id = get_current_user_id();
$pz_packs   = HistoryView::packs( Engine::live(), $pz_user_id );
$pz_orders  = wc_get_orders(
	array(
		'customer_id' => $pz_user_id,
		'limit'       => 50,
		'orderby'     => 'date',
		'order'       => 'DESC',
		'status'      => array_keys( wc_get_order_statuses() ),
	)
);
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-history pz-portal-block' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
	<section aria-labelledby="pz-history-packs">
		<h2 id="pz-history-packs"><?php esc_html_e( 'Your packs', 'pazhamei-core' ); ?></h2>
		<?php if ( ! $pz_packs ) : ?>
			<p><?php esc_html_e( 'No packs yet.', 'pazhamei-core' ); ?> <a href="<?php echo esc_url( home_url( '/account/plans/' ) ); ?>"><?php esc_html_e( 'Choose a plan', 'pazhamei-core' ); ?></a></p>
		<?php endif; ?>
		<?php foreach ( $pz_packs as $pz_pack ) : ?>
			<article class="pz-history__pack" data-status="<?php echo esc_attr( $pz_pack['status'] ); ?>">
				<header class="pz-history__head">
					<strong><?php echo esc_html( $pz_pack['name'] . ' · ' . $pz_pack['label'] ); ?></strong>
					<span class="pz-history__status"><?php echo esc_html( $pz_pack['statusLabel'] ); ?></span>
				</header>
				<p class="pz-history__meta">
					<?php
					echo esc_html(
						sprintf(
							/* translators: 1: meal type, 2: start date, 3: end date, 4: lunches eaten, 5: lunches in the pack. */
							__( '%1$s · %2$s to %3$s · %4$d of %5$d lunches used', 'pazhamei-core' ),
							$pz_pack['mealType'],
							$pz_pack['start'],
							$pz_pack['end'],
							$pz_pack['eaten'],
							$pz_pack['total']
						)
					);
					?>
				</p>
				<?php if ( $pz_pack['delivered'] || $pz_pack['failed'] || $pz_pack['skipped'] ) : ?>
					<details class="pz-history__days">
						<summary>
							<?php
							echo esc_html(
								sprintf(
									/* translators: 1: delivered count, 2: not delivered count, 3: skipped count. */
									__( '%1$d delivered · %2$d not delivered · %3$d skipped', 'pazhamei-core' ),
									count( $pz_pack['delivered'] ),
									count( $pz_pack['failed'] ),
									$pz_pack['skipped']
								)
							);
							?>
						</summary>
						<?php if ( $pz_pack['delivered'] ) : ?>
							<p><strong><?php esc_html_e( 'Delivered:', 'pazhamei-core' ); ?></strong> <?php echo esc_html( implode( ', ', $pz_pack['delivered'] ) ); ?></p>
						<?php endif; ?>
						<?php if ( $pz_pack['failed'] ) : ?>
							<p><strong><?php esc_html_e( 'Not delivered:', 'pazhamei-core' ); ?></strong> <?php echo esc_html( implode( ', ', $pz_pack['failed'] ) ); ?></p>
						<?php endif; ?>
					</details>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
	</section>

	<section aria-labelledby="pz-history-payments">
		<h2 id="pz-history-payments"><?php esc_html_e( 'Payments', 'pazhamei-core' ); ?></h2>
		<?php if ( ! $pz_orders ) : ?>
			<p><?php esc_html_e( 'No payments yet.', 'pazhamei-core' ); ?></p>
		<?php else : ?>
			<ul class="pz-history__orders">
				<?php foreach ( $pz_orders as $pz_order ) : ?>
					<?php
					if ( ! $pz_order instanceof WC_Order ) {
						continue;
					}
					$pz_items = array();
					foreach ( $pz_order->get_items() as $pz_item ) {
						$pz_items[] = $pz_item->get_name();
					}
					$pz_created = $pz_order->get_date_created();
					?>
					<li class="pz-history__order">
						<span class="pz-history__order-main">
							<a href="<?php echo esc_url( $pz_order->get_view_order_url() ); ?>"><?php echo esc_html( sprintf( /* translators: %s: order number. */ __( 'Order #%s', 'pazhamei-core' ), $pz_order->get_order_number() ) ); ?></a>
							<span><?php echo esc_html( $pz_created ? wp_date( 'j M Y', $pz_created->getTimestamp() ) : '' ); ?></span>
						</span>
						<span class="pz-history__order-items"><?php echo esc_html( implode( ', ', $pz_items ) ); ?></span>
						<span class="pz-history__order-total">
							<strong><?php echo esc_html( Money::rupees( (float) $pz_order->get_total() ) ); ?></strong>
							<span class="pz-history__status"><?php echo esc_html( wc_get_order_status_name( $pz_order->get_status() ) ); ?></span>
						</span>
					</li>
				<?php endforeach; ?>
			</ul>
			<p class="pz-field__hint"><?php esc_html_e( 'Open an order to see its details; it works as your receipt.', 'pazhamei-core' ); ?></p>
		<?php endif; ?>
	</section>
</div>
