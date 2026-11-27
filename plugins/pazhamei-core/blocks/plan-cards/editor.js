/**
 * Editor view of the pz/plan-cards block.
 *
 * The block is rendered by PHP (render.php), so in the editor we simply ask
 * the server for a preview. One setting in the sidebar: show the plan photos.
 * Plain JavaScript, no build step needed.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps, InspectorControls } = wp.blockEditor;
	const { PanelBody, ToggleControl } = wp.components;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'pz/plan-cards', {
		edit: function Edit( props ) {
			const showImages = !! props.attributes.showImages;

			return el(
				'div',
				useBlockProps(),
				el(
					InspectorControls,
					null,
					el(
						PanelBody,
						{ title: 'Plan cards' },
						el( ToggleControl, {
							label: 'Show plan photos',
							checked: showImages,
							onChange: function ( value ) {
								props.setAttributes( { showImages: value } );
							},
							__nextHasNoMarginBottom: true,
						} )
					)
				),
				el( ServerSideRender, { block: 'pz/plan-cards', attributes: props.attributes } )
			);
		},
		// Dynamic block: nothing is saved in the post content.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
