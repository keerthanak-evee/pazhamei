/**
 * Editor view of the pz/schedule-calendar block (a server preview: it shows the signed-in customer's own data).
 *
 * The block is rendered by PHP (render.php), so in the editor we simply ask
 * the server for a preview. Plain JavaScript, no build step needed.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps } = wp.blockEditor;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'pz/schedule-calendar', {
		edit: function Edit( props ) {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, { block: 'pz/schedule-calendar', attributes: props.attributes } )
			);
		},
		// Dynamic block: nothing is saved in the post content.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
