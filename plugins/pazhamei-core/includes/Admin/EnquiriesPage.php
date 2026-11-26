<?php
/**
 * EnquiriesPage: the "Pazhamei → Enquiries" screen in wp-admin.
 *
 * Lists storefront enquiries newest first, with a status filter. The only
 * change allowed is the status (new, contacted, converted, closed); everything
 * else is read-only. Administrators only (EnquiryStatus::can_manage()).
 *
 * Status changes post to admin-post.php with a nonce, then come back here.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Admin;

use Pazhamei\Domain\EnquiryStatus;
use Pazhamei\Domain\Plans;
use Pazhamei\Repositories\EnquiryRepository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Enquiries list screen.
 */
final class EnquiriesPage {

	/** Menu slug (admin.php?page=pazhamei). */
	public const SLUG = 'pazhamei';

	/** Rows per page. */
	private const PER_PAGE = 50;

	/** admin-post.php action for status changes. */
	private const ACTION = 'pz_enquiry_status';

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ) );
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'save_status' ) );
	}

	/**
	 * Is the current user allowed on this screen?
	 */
	private static function allowed(): bool {
		return current_user_can( 'manage_options' ) && EnquiryStatus::can_manage( (array) wp_get_current_user()->roles );
	}

	/**
	 * "Pazhamei" top-level menu with "Enquiries" as its first page.
	 */
	public static function menu(): void {
		if ( ! self::allowed() ) {
			return;
		}

		$new   = EnquiryRepository::count_by_status()['new'] ?? 0;
		$badge = $new > 0 ? ' <span class="awaiting-mod">' . (int) $new . '</span>' : '';

		add_menu_page( 'Pazhamei', 'Pazhamei', 'manage_options', self::SLUG, array( self::class, 'render' ), 'dashicons-carrot', 56 );
		add_submenu_page( self::SLUG, 'Enquiries', 'Enquiries' . $badge, 'manage_options', self::SLUG, array( self::class, 'render' ) );
	}

	/**
	 * Handle a status change, then return to the list.
	 */
	public static function save_status(): void {
		$id = isset( $_POST['enquiry_id'] ) ? absint( $_POST['enquiry_id'] ) : 0;

		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'Only administrators can change enquiries.', 'pazhamei-core' ), 403 );
		}
		check_admin_referer( self::ACTION . '_' . $id );

		$status = isset( $_POST['status'] ) ? sanitize_key( wp_unslash( $_POST['status'] ) ) : '';
		$ok     = $id > 0 && EnquiryStatus::is_valid( $status ) && EnquiryRepository::update_status( $id, $status );

		$back = isset( $_POST['back'] ) ? esc_url_raw( wp_unslash( $_POST['back'] ) ) : '';
		$back = wp_validate_redirect( $back, admin_url( 'admin.php?page=' . self::SLUG ) );

		wp_safe_redirect( add_query_arg( 'pz_updated', $ok ? $id : 0, $back ) );
		exit;
	}

	/**
	 * Print the screen.
	 */
	public static function render(): void {
		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'Only administrators can view enquiries.', 'pazhamei-core' ), 403 );
		}

		// phpcs:disable WordPress.Security.NonceVerification.Recommended -- Read-only filters and paging.
		$status  = isset( $_GET['status'] ) ? sanitize_key( wp_unslash( $_GET['status'] ) ) : '';
		$status  = EnquiryStatus::is_valid( $status ) ? $status : '';
		$paged   = isset( $_GET['paged'] ) ? max( 1, absint( $_GET['paged'] ) ) : 1;
		$updated = isset( $_GET['pz_updated'] ) ? absint( $_GET['pz_updated'] ) : -1;
		// phpcs:enable

		$counts = EnquiryRepository::count_by_status();
		$total  = '' === $status ? array_sum( $counts ) : ( $counts[ $status ] ?? 0 );
		$rows   = EnquiryRepository::list( $status, self::PER_PAGE, ( $paged - 1 ) * self::PER_PAGE );
		$pages  = (int) ceil( $total / self::PER_PAGE );
		$base   = admin_url( 'admin.php?page=' . self::SLUG );
		$here   = add_query_arg( array_filter( array( 'status' => $status, 'paged' => $paged > 1 ? $paged : null ) ), $base );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Enquiries', 'pazhamei-core' ); ?></h1>

			<?php if ( $updated > 0 ) : ?>
				<div class="notice notice-success is-dismissible"><p><?php echo esc_html( sprintf( 'Enquiry #%d updated.', $updated ) ); ?></p></div>
			<?php elseif ( 0 === $updated ) : ?>
				<div class="notice notice-error is-dismissible"><p><?php esc_html_e( 'The status could not be changed.', 'pazhamei-core' ); ?></p></div>
			<?php endif; ?>

			<ul class="subsubsub">
				<?php
				$links = array( '' => 'All' ) + EnquiryStatus::ALL;
				$last  = array_key_last( $links );
				foreach ( $links as $key => $label ) :
					$count = '' === $key ? array_sum( $counts ) : ( $counts[ $key ] ?? 0 );
					$url   = '' === $key ? $base : add_query_arg( 'status', $key, $base );
					?>
					<li>
						<a href="<?php echo esc_url( $url ); ?>" <?php echo $key === $status ? 'class="current" aria-current="page"' : ''; ?>>
							<?php echo esc_html( $label ); ?> <span class="count">(<?php echo (int) $count; ?>)</span>
						</a><?php echo $key !== $last ? ' |' : ''; ?>
					</li>
				<?php endforeach; ?>
			</ul>

			<table class="widefat striped" style="clear:both">
				<thead>
					<tr>
						<th><?php esc_html_e( 'Date', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Name', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Mobile', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Email', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Pincode', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Plan', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Meal type', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Start date', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Message', 'pazhamei-core' ); ?></th>
						<th><?php esc_html_e( 'Status', 'pazhamei-core' ); ?></th>
					</tr>
				</thead>
				<tbody>
					<?php if ( ! $rows ) : ?>
						<tr><td colspan="10"><?php esc_html_e( 'No enquiries yet.', 'pazhamei-core' ); ?></td></tr>
					<?php endif; ?>
					<?php foreach ( $rows as $row ) : ?>
						<?php self::row( $row, $here ); ?>
					<?php endforeach; ?>
				</tbody>
			</table>

			<?php if ( $pages > 1 ) : ?>
				<div class="tablenav"><div class="tablenav-pages">
					<?php
					echo wp_kses_post(
						paginate_links(
							array(
								'base'    => add_query_arg( 'paged', '%#%', '' === $status ? $base : add_query_arg( 'status', $status, $base ) ),
								'format'  => '',
								'current' => $paged,
								'total'   => $pages,
							)
						)
					);
					?>
				</div></div>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Print one table row.
	 *
	 * @param array<string, string|null> $row  Enquiry row.
	 * @param string                     $back URL to return to after a status change.
	 */
	private static function row( array $row, string $back ): void {
		$id    = (int) $row['id'];
		$plan  = Plans::short_label( (string) $row['plan_interest'] );
		$meal  = Plans::MEAL_TYPES[ (string) $row['meal_type'] ] ?? '';
		$start = $row['preferred_start'] ? self::date( (string) $row['preferred_start'] ) : '—';
		?>
		<tr>
			<td><?php echo esc_html( self::date( (string) $row['created_at'], true ) ); ?></td>
			<td><?php echo esc_html( (string) $row['name'] ); ?></td>
			<td><a href="<?php echo esc_url( 'tel:+91' . $row['mobile'] ); ?>"><?php echo esc_html( (string) $row['mobile'] ); ?></a></td>
			<td><?php echo '' !== $row['email'] ? '<a href="' . esc_url( 'mailto:' . $row['email'] ) . '">' . esc_html( (string) $row['email'] ) . '</a>' : '—'; ?></td>
			<td><?php echo esc_html( (string) $row['pincode'] ); ?><?php echo '' !== $row['area'] ? '<br><small>' . esc_html( (string) $row['area'] ) . '</small>' : ''; ?></td>
			<td><?php echo esc_html( '' !== $plan ? $plan : 'Not sure yet' ); ?></td>
			<td><?php echo esc_html( '' !== $meal ? $meal : 'Not sure yet' ); ?></td>
			<td><?php echo esc_html( $start ); ?></td>
			<td style="max-width:22em"><?php echo '' !== $row['message'] ? nl2br( esc_html( (string) $row['message'] ) ) : '—'; ?></td>
			<td>
				<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
					<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
					<input type="hidden" name="enquiry_id" value="<?php echo (int) $id; ?>" />
					<input type="hidden" name="back" value="<?php echo esc_url( $back ); ?>" />
					<?php wp_nonce_field( self::ACTION . '_' . $id ); ?>
					<label class="screen-reader-text" for="pz-status-<?php echo (int) $id; ?>"><?php esc_html_e( 'Status', 'pazhamei-core' ); ?></label>
					<select id="pz-status-<?php echo (int) $id; ?>" name="status">
						<?php foreach ( EnquiryStatus::ALL as $key => $label ) : ?>
							<option value="<?php echo esc_attr( $key ); ?>" <?php selected( $row['status'], $key ); ?>><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
					<button type="submit" class="button button-small"><?php esc_html_e( 'Save', 'pazhamei-core' ); ?></button>
				</form>
			</td>
		</tr>
		<?php
	}

	/**
	 * Format a stored IST date like "26 Nov 2026" (or "26 Nov 2026, 11:18 pm").
	 *
	 * @param string $value     Y-m-d or Y-m-d H:i:s, in IST.
	 * @param bool   $with_time Add the time.
	 */
	private static function date( string $value, bool $with_time = false ): string {
		$date = \DateTimeImmutable::createFromFormat( $with_time ? 'Y-m-d H:i:s' : '!Y-m-d', $value, wp_timezone() );
		if ( ! $date ) {
			return $value;
		}
		return $date->format( $with_time ? 'j M Y, g:i a' : 'j M Y' );
	}
}
