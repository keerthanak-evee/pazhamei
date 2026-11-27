/**
 * pz/support-tickets front end (Interactivity API, store "pz/support").
 *
 * Raising a ticket posts to POST /pz/v1/support/tickets. The server checks
 * the fields, saves the ticket, emails the admin and returns the updated
 * list, which replaces state.tickets. Errors show under each field.
 */
import { store, getContext } from '@wordpress/interactivity';

const FALLBACK_ERROR = 'Something went wrong. Please try again.';

const { state } = store( 'pz/support', {
	actions: {
		*raise( event ) {
			event.preventDefault();
			const ctx = getContext();
			const form = event.target;
			const value = ( name ) => form.elements.namedItem( name ).value;

			ctx.busy = true;
			ctx.message = '';
			ctx.errors = {};
			let reply;
			try {
				const response = yield fetch( ctx.restUrl + 'support/tickets', {
					method: 'POST',
					credentials: 'same-origin',
					headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': ctx.nonce },
					body: JSON.stringify( { topic: value( 'topic' ), date: value( 'date' ), message: value( 'message' ) } ),
				} );
				reply = yield response.json();
			} catch ( e ) {
				reply = { ok: false, message: FALLBACK_ERROR };
			}
			ctx.busy = false;
			ctx.ok = !! reply.ok;
			ctx.errors = reply.errors || {};
			if ( reply.ok ) {
				ctx.message = reply.data.message;
				state.tickets = reply.data.tickets;
				form.reset();
			} else {
				ctx.message = reply.message || FALLBACK_ERROR;
			}
		},
	},
} );
