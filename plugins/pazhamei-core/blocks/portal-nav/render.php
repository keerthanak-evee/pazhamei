<?php
/**
 * Front-end output of the pz/portal-nav block.
 *
 * 1. The Navigation menu for the current page's portal (Storefront if none).
 * 2. Account links: Sign in when logged out; otherwise My account / Kitchen /
 *    Manage (whichever the user may open) and Sign out.
 *
 * Variables WordPress provides: $attributes, $content, $block.
 *
 * @package Pazhamei
 */

use Pazhamei\Access\PagePortal;
use Pazhamei\Access\PortalMenus;
use Pazhamei\Access\Portals;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$pz_menu_id = PortalMenus::id_for( PagePortal::current() );

// Build the account links.
$pz_links = array();
if ( is_user_logged_in() ) {
	foreach ( Portals::account_links( (array) wp_get_current_user()->roles ) as $pz_link ) {
		$pz_links[] = array(
			'label' => $pz_link['label'],
			'url'   => home_url( $pz_link['path'] ),
			'style' => 'outline',
		);
	}
	$pz_links[] = array(
		'label' => __( 'Sign out', 'pazhamei-core' ),
		'url'   => wp_logout_url( home_url( '/' ) ),
		'style' => 'fill',
	);
} else {
	$pz_links[] = array(
		'label' => __( 'Sign in', 'pazhamei-core' ),
		'url'   => home_url( '/sign-in/' ),
		'style' => 'fill',
	);
}
?>
<div <?php echo get_block_wrapper_attributes( array( 'class' => 'pz-portal-nav' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core escapes this. ?>>
	<?php
	if ( $pz_menu_id > 0 ) {
		// Let core render the menu, so it gets the mobile overlay and editor support for free.
		echo do_blocks( '<!-- wp:navigation {"ref":' . (int) $pz_menu_id . ',"overlayMenu":"mobile","layout":{"type":"flex","justifyContent":"right"}} /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Core block output.
	}
	?>
	<div class="pz-portal-nav__account wp-block-buttons">
		<?php foreach ( $pz_links as $pz_link ) : ?>
			<div class="wp-block-button<?php echo 'outline' === $pz_link['style'] ? ' is-style-outline' : ''; ?>">
				<a class="wp-block-button__link wp-element-button" href="<?php echo esc_url( $pz_link['url'] ); ?>"><?php echo esc_html( $pz_link['label'] ); ?></a>
			</div>
		<?php endforeach; ?>
	</div>
</div>
