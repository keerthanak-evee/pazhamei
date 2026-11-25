/**
 * Adds a "Portal" panel to the page sidebar in the block editor.
 *
 * It reads and writes the `_pz_portal` page setting. Plain JavaScript
 * (no build step): it uses the `wp.*` globals WordPress already loads.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerPlugin } = wp.plugins;
	const { PluginDocumentSettingPanel } = wp.editor;
	const { SelectControl } = wp.components;
	const { useSelect, useDispatch } = wp.data;

	const OPTIONS = [
		{ value: '', label: 'Inherit from parent page (or Storefront)' },
		{ value: 'storefront', label: 'Storefront (everyone)' },
		{ value: 'customer', label: 'Customer (/account)' },
		{ value: 'kitchen', label: 'Kitchen (/kitchen)' },
		{ value: 'admin', label: 'Admin (/manage)' },
	];

	function PortalPanel() {
		const meta = useSelect(
			( select ) => select( 'core/editor' ).getEditedPostAttribute( 'meta' ) || {},
			[]
		);
		const { editPost } = useDispatch( 'core/editor' );

		// Hide the panel if the field isn't registered (e.g. the plugin's PHP side is missing).
		if ( ! Object.prototype.hasOwnProperty.call( meta, '_pz_portal' ) ) {
			return null;
		}

		return el(
			PluginDocumentSettingPanel,
			{ name: 'pz-portal-panel', title: 'Portal' },
			el( SelectControl, {
				label: 'Who can open this page',
				help: 'Decides the menu in the header and which roles may open the page.',
				value: meta._pz_portal || '',
				options: OPTIONS,
				onChange: ( value ) => editPost( { meta: { _pz_portal: value } } ),
				__nextHasNoMarginBottom: true,
				__next40pxDefaultSize: true,
			} )
		);
	}

	registerPlugin( 'pz-portal-panel', { render: PortalPanel } );
} )( window.wp );
