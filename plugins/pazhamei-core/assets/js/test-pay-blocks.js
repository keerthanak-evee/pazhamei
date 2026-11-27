/**
 * Registers "Pazhamei Test Pay" in WooCommerce's block checkout (local only:
 * PHP loads this file only when the environment type is "local").
 * Plain JavaScript, no build step.
 */
( function ( wc, wp ) {
	const el = wp.element.createElement;
	const data = wc.wcSettings.getSetting( 'pz_test_pay_data', {} );
	const label = data.title || 'Pazhamei Test Pay';

	const Content = function () {
		return el( 'p', null, data.description || '' );
	};

	wc.wcBlocksRegistry.registerPaymentMethod( {
		name: 'pz_test_pay',
		label: el( 'span', null, label ),
		ariaLabel: label,
		content: el( Content ),
		edit: el( Content ),
		canMakePayment: function () {
			return true;
		},
		supports: { features: [ 'products' ] },
	} );
} )( window.wc, window.wp );
