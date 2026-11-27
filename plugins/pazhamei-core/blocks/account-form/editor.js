/**
 * Editor view of the pz/account-form block.
 *
 * Rendered by PHP (render.php); the editor shows a server preview. One
 * setting in the sidebar: which form this block shows.
 * Plain JavaScript, no build step needed.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, SelectControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'pz/account-form', {
		edit: function Edit( props ) {
			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Account form' },
						el( SelectControl, {
							label: 'Form',
							value: props.attributes.mode,
							options: [
								{ label: 'Sign in', value: 'sign-in' },
								{ label: 'Register', value: 'register' },
								{ label: 'Forgot password', value: 'lost-password' },
								{ label: 'Account pages (orders)', value: 'account' },
							],
							onChange: function ( value ) {
								props.setAttributes( { mode: value } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( ServerSideRender, { block: 'pz/account-form', attributes: props.attributes } )
			);
		},
		// Dynamic block: nothing is saved in the post content.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
