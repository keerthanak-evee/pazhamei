/**
 * pz/profile-form front end (Interactivity API, store "pz/profile").
 *
 * Sends the profile with PUT /pz/v1/me/profile and the password change with
 * POST /pz/v1/me/password. The server checks everything; this only shows
 * its answer: errors under each field, or its "Saved" message. After a
 * password change the page reloads, because signing in again gives the
 * page a new security token.
 */
import { store, getContext } from '@wordpress/interactivity';

const FALLBACK_ERROR = 'Something went wrong. Please try again.';

/**
 * Send JSON to a Pazhamei route.
 *
 * @param {Object} ctx    Block context (restUrl, nonce).
 * @param {string} method HTTP method.
 * @param {string} path   Route under /pz/v1/.
 * @param {Object} body   JSON body.
 * @return {Promise<Object>} The reply ({ ok, data, message, errors }).
 */
async function send( ctx, method, path, body ) {
	try {
		const response = await fetch( ctx.restUrl + path, {
			method,
			credentials: 'same-origin',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ctx.nonce },
			body: JSON.stringify( body ),
		} );
		return await response.json();
	} catch ( e ) {
		return { ok: false, message: FALLBACK_ERROR };
	}
}

store( 'pz/profile', {
	actions: {
		*save( event ) {
			event.preventDefault();
			const ctx = getContext();
			const form = event.target;
			const value = ( name ) => form.elements.namedItem( name ).value;
			const body = {
				name: value( 'name' ),
				mobile: value( 'mobile' ),
				address_1: value( 'address_1' ),
				address_2: value( 'address_2' ),
				city: value( 'city' ),
				pincode: value( 'pincode' ),
				dietary: value( 'dietary' ),
				instructions: value( 'instructions' ),
				allergens: {},
			};
			form.querySelectorAll( 'select[data-allergen]' ).forEach( ( select ) => {
				body.allergens[ select.dataset.allergen ] = select.value;
			} );

			ctx.busy = true;
			ctx.message = '';
			ctx.errors = {};
			const reply = yield send( ctx, 'PUT', 'me/profile', body );
			ctx.busy = false;
			ctx.ok = !! reply.ok;
			ctx.message = reply.ok ? reply.data.message : reply.message || FALLBACK_ERROR;
			ctx.errors = reply.errors || {};
		},
		*password( event ) {
			event.preventDefault();
			const ctx = getContext();
			const form = event.target;
			ctx.busy = true;
			ctx.pwMessage = '';
			const value = ( name ) => form.elements.namedItem( name ).value;
			const reply = yield send( ctx, 'POST', 'me/password', {
				current: value( 'current' ),
				new: value( 'new' ),
				confirm: value( 'confirm' ),
			} );
			ctx.busy = false;
			ctx.pwOk = !! reply.ok;
			ctx.pwMessage = reply.ok ? reply.data.message : reply.message || FALLBACK_ERROR;
			if ( reply.ok ) {
				form.reset();
				setTimeout( () => window.location.reload(), 1500 );
			}
		},
	},
} );
