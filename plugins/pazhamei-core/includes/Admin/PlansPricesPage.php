<?php
/**
 * PlansPricesPage: the "Pazhamei → Plans & Prices" screen in wp-admin.
 *
 * Administrators only. One form with:
 * - the price grid: Trial, 6-day, 26-day × Veg, Non-Veg, Mixed, in ₹
 *   (saved to the WooCommerce variations through PriceBook: the only source of prices);
 * - the trial on/off switch (the trial price is the Trial row of the grid);
 * - each plan's "what's included" lines, shown on the plan cards.
 *
 * Saving posts to admin-post.php with a nonce. Nothing is saved if any price
 * is wrong: the screen comes back with the typed values and the errors.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Admin;

use Pazhamei\Domain\EnquiryStatus;
use Pazhamei\Domain\Plans;
use Pazhamei\Domain\PriceGrid;
use Pazhamei\Services\PriceBook;
use Pazhamei\Settings;
use Pazhamei\Woo\WooPriceStore;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plans & Prices screen.
 */
final class PlansPricesPage {

	/** Page slug (admin.php?page=pazhamei-plans). */
	public const SLUG = 'pazhamei-plans';

	/** admin-post.php action. */
	private const ACTION = 'pz_save_plans';

	/** Transient (per user) holding typed values and errors after a failed save. */
	private const FAILED = 'pz_plans_failed_';

	/**
	 * Add the hooks.
	 */
	public static function register(): void {
		add_action( 'admin_menu', array( self::class, 'menu' ), 20 ); // After the Pazhamei menu exists.
		add_action( 'admin_post_' . self::ACTION, array( self::class, 'save' ) );
	}

	/**
	 * Administrators only.
	 */
	private static function allowed(): bool {
		return current_user_can( 'manage_options' ) && EnquiryStatus::can_manage( (array) wp_get_current_user()->roles );
	}

	/**
	 * The price book on WooCommerce.
	 */
	private static function book(): PriceBook {
		return new PriceBook( new WooPriceStore() );
	}

	/**
	 * Submenu item.
	 */
	public static function menu(): void {
		if ( self::allowed() ) {
			add_submenu_page( EnquiriesPage::SLUG, 'Plans & Prices', 'Plans & Prices', 'manage_options', self::SLUG, array( self::class, 'render' ) );
		}
	}

	/**
	 * Save the form, then return to the screen.
	 */
	public static function save(): void {
		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'Only administrators can change plans and prices.', 'pazhamei-core' ), 403 );
		}
		check_admin_referer( self::ACTION );

		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Checked by PriceGrid below.
		$prices   = isset( $_POST['price'] ) && is_array( $_POST['price'] ) ? wp_unslash( $_POST['price'] ) : array();
		$includes = isset( $_POST['includes'] ) && is_array( $_POST['includes'] ) ? wp_unslash( $_POST['includes'] ) : array();
		// phpcs:enable
		$trial_on = ! empty( $_POST['trial_enabled'] );

		// Check every part first; save nothing unless all of it is fine.
		$errors = PriceGrid::check_prices( $prices )['errors'];
		$lines  = array();
		foreach ( PriceGrid::PLANS as $code ) {
			$checked = PriceGrid::check_includes( sanitize_textarea_field( (string) ( $includes[ $code ] ?? '' ) ) );
			if ( null !== $checked['error'] ) {
				$errors[ 'includes.' . $code ] = $checked['error'];
			}
			$lines[ $code ] = $checked['lines'];
		}

		if ( ! $errors ) {
			$errors = self::book()->save( $prices );
		}
		if ( $errors ) {
			set_transient(
				self::FAILED . get_current_user_id(),
				array(
					'errors'   => $errors,
					'price'    => map_deep( $prices, 'sanitize_text_field' ),
					'includes' => array_map( 'sanitize_textarea_field', array_map( 'strval', $includes ) ),
					'trial'    => $trial_on,
				),
				300
			);
			wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'pz_saved' => 0 ), admin_url( 'admin.php' ) ) );
			exit;
		}

		Settings::set( 'trial_enabled', $trial_on );
		Settings::set( 'plan_includes', $lines );
		wp_safe_redirect( add_query_arg( array( 'page' => self::SLUG, 'pz_saved' => 1 ), admin_url( 'admin.php' ) ) );
		exit;
	}

	/**
	 * Draw the screen.
	 */
	public static function render(): void {
		if ( ! self::allowed() ) {
			wp_die( esc_html__( 'Only administrators can see this page.', 'pazhamei-core' ), 403 );
		}

		$failed = get_transient( self::FAILED . get_current_user_id() );
		delete_transient( self::FAILED . get_current_user_id() );
		$failed = is_array( $failed ) ? $failed : null;
		$errors = $failed['errors'] ?? array();
		$grid   = self::book()->grid();
		$trial  = $failed ? (bool) $failed['trial'] : (bool) Settings::get( 'trial_enabled' );
		$saved  = isset( $_GET['pz_saved'] ) && '1' === $_GET['pz_saved']; // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Display only.
		?>
		<div class="wrap">
			<h1>Plans &amp; Prices</h1>
			<p>Prices are what customers pay for a whole pack, in rupees. They are saved on the WooCommerce plan products, so the plan cards and the checkout always show the same numbers. Visitors who are not signed in never see prices.</p>

			<?php if ( $saved ) : ?>
				<div class="notice notice-success"><p>Saved.</p></div>
			<?php elseif ( $errors ) : ?>
				<div class="notice notice-error"><p>Nothing was saved. Please fix the fields marked below.</p></div>
			<?php endif; ?>

			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
				<input type="hidden" name="action" value="<?php echo esc_attr( self::ACTION ); ?>" />
				<?php wp_nonce_field( self::ACTION ); ?>

				<h2>Prices (₹ per pack)</h2>
				<table class="widefat striped" style="max-width:760px">
					<thead>
						<tr>
							<th scope="col">Plan</th>
							<?php foreach ( Plans::MEAL_TYPES as $type_label ) : ?>
								<th scope="col"><?php echo esc_html( $type_label ); ?></th>
							<?php endforeach; ?>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( Plans::all() as $slug => $plan ) : ?>
							<tr>
								<th scope="row"><?php echo esc_html( Plans::short_label( $slug ) ); ?></th>
								<?php foreach ( array_keys( Plans::MEAL_TYPES ) as $type ) : ?>
									<?php
									$key   = $plan['code'] . '.' . $type;
									$value = $failed ? (string) ( $failed['price'][ $plan['code'] ][ $type ] ?? '' ) : self::plain( $grid[ $plan['code'] ][ $type ] ?? null );
									$id    = 'pz-price-' . strtolower( $plan['code'] . '-' . $type );
									?>
									<td>
										<label class="screen-reader-text" for="<?php echo esc_attr( $id ); ?>"><?php echo esc_html( Plans::short_label( $slug ) . ', ' . Plans::MEAL_TYPES[ $type ] ); ?></label>
										₹ <input type="text" inputmode="decimal" size="9" id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( 'price[' . $plan['code'] . '][' . $type . ']' ); ?>" value="<?php echo esc_attr( $value ); ?>" <?php echo isset( $errors[ $key ] ) ? 'aria-invalid="true" style="border-color:#d63638"' : ''; ?> />
										<?php if ( isset( $errors[ $key ] ) ) : ?>
											<br /><span style="color:#d63638"><?php echo esc_html( $errors[ $key ] ); ?></span>
										<?php endif; ?>
									</td>
								<?php endforeach; ?>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>
				<p class="description">The Trial row is the trial price (it can be 0 for a free trial). 6-day and 26-day prices must be more than 0.</p>

				<h2>Trial</h2>
				<label><input type="checkbox" name="trial_enabled" value="1" <?php checked( $trial ); ?> /> Sell the trial (once per customer)</label>

				<h2>What's included</h2>
				<p class="description">One point per line, up to <?php echo (int) PriceGrid::MAX_LINES; ?> lines. Shown as the tick list on each plan card.</p>
				<?php foreach ( Plans::all() as $slug => $plan ) : ?>
					<?php
					$key  = 'includes.' . $plan['code'];
					$text = $failed ? (string) ( $failed['includes'][ $plan['code'] ] ?? '' ) : implode( "\n", Settings::plan_includes( $slug ) );
					$id   = 'pz-includes-' . strtolower( $plan['code'] );
					?>
					<p>
						<label for="<?php echo esc_attr( $id ); ?>"><strong><?php echo esc_html( $plan['name'] . ' (' . Plans::short_label( $slug ) . ')' ); ?></strong></label><br />
						<textarea id="<?php echo esc_attr( $id ); ?>" name="<?php echo esc_attr( 'includes[' . $plan['code'] . ']' ); ?>" rows="4" cols="70"><?php echo esc_textarea( $text ); ?></textarea>
						<?php if ( isset( $errors[ $key ] ) ) : ?>
							<br /><span style="color:#d63638"><?php echo esc_html( $errors[ $key ] ); ?></span>
						<?php endif; ?>
					</p>
				<?php endforeach; ?>

				<?php submit_button( 'Save plans and prices' ); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * A price for the input box: "900" or "99.5" ('' if missing).
	 *
	 * @param float|null $amount Rupees.
	 */
	private static function plain( ?float $amount ): string {
		if ( null === $amount ) {
			return '';
		}
		return floor( $amount ) === $amount ? (string) (int) $amount : number_format( $amount, 2, '.', '' );
	}
}
