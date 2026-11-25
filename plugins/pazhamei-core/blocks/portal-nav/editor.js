/**
 * Editor view of the pz/portal-nav block.
 *
 * The block is rendered by PHP (render.php), so in the editor we simply ask
 * the server for a preview. Plain JavaScript, no build step needed.
 */
( function ( wp ) {
	const el = wp.element.createElement;
	const { registerBlockType } = wp.blocks;
	const { useBlockProps } = wp.blockEditor;
	const ServerSideRender = wp.serverSideRender;

	registerBlockType( 'pz/portal-nav', {
		edit: function Edit() {
			return el(
				'div',
				useBlockProps(),
				el( ServerSideRender, { block: 'pz/portal-nav' } )
			);
		},
		// Dynamic block: nothing is saved in the post content.
		save: function () {
			return null;
		},
	} );
} )( window.wp );
