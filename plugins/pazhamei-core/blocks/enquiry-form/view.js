/**
 * pz/enquiry-form front end (Interactivity API).
 *
 * - init: pre-selects ?plan= and fetches a fresh nonce (so page caching can't break the form).
 * - submit: posts the form as JSON to /pz/v1/enquiries, then shows the thank-you
 *   message or the error for each field. No page reload.
 *
 * The block's context (from render.php) holds: restUrl, nonceUrl, nonce,
 * status ('idle' | 'sending' | 'done' | 'error'), message, errors.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

const PLANS = [ 'trial', '6-day', '26-day', 'not-sure' ];

/**
 * Ask the server for a fresh nonce.
 *
 * @param {string} url Nonce endpoint.
 * @return {Promise<string>} The nonce.
 */
async function fetchNonce( url ) {
	const response = await fetch( url, { cache: 'no-store' } );
	const data = await response.json();
	return data.nonce || '';
}

/**
 * Post the enquiry.
 *
 * @param {string} url   Enquiry endpoint.
 * @param {string} nonce Nonce from fetchNonce().
 * @param {Object} body  Form values.
 * @return {Promise<{status: number, data: Object}>} HTTP status and JSON reply.
 */
async function postEnquiry( url, nonce, body ) {
	const response = await fetch( url, {
		method: 'POST',
		headers: { 'Content-Type': 'application/json', 'X-PZ-Nonce': nonce },
		body: JSON.stringify( body ),
	} );
	let data = {};
	try {
		data = await response.json();
	} catch ( e ) {
		// Not JSON (for example a server error page): handled as a failure below.
	}
	return { status: response.status, data };
}

store( 'pz/enquiry-form', {
	state: {
		get isSending() {
			return getContext().status === 'sending';
		},
		get isDone() {
			return getContext().status === 'done';
		},
		get isError() {
			return getContext().status === 'error';
		},
	},
	actions: {
		*submit( event ) {
			event.preventDefault();
			const context = getContext();
			if ( context.status === 'sending' ) {
				return;
			}

			const form = event.target;
			const body = Object.fromEntries( new FormData( form ) );
			body.source_page = window.location.href;

			context.status = 'sending';
			context.message = '';
			context.errors = {};

			try {
				if ( ! context.nonce ) {
					context.nonce = yield fetchNonce( context.nonceUrl );
				}

				const { status, data } = yield postEnquiry( context.restUrl, context.nonce, body );

				if ( status >= 200 && status < 300 && data.ok ) {
					context.message = data.message;
					context.status = 'done';
					form.reset();
					return;
				}

				if ( status === 403 ) {
					context.nonce = ''; // Expired: the next try fetches a fresh one.
				}

				context.errors = data.errors || {};
				context.message = data.message || 'Sorry, something went wrong. Please try again.';
				context.status = 'error';

				// Move focus to the first field with a problem.
				const first = Object.keys( context.errors )[ 0 ];
				const field = first ? form.querySelector( '[name="' + first + '"]' ) : null;
				if ( field ) {
					field.focus();
				}
			} catch ( e ) {
				context.message = 'We could not send your enquiry. Please check your connection and try again.';
				context.status = 'error';
			}
		},
	},
	callbacks: {
		*init() {
			const context = getContext();
			const { ref } = getElement();

			const plan = new URLSearchParams( window.location.search ).get( 'plan' );
			const select = ref.querySelector( 'select[name="plan_interest"]' );
			if ( select && plan && PLANS.includes( plan ) ) {
				select.value = plan;
			}

			try {
				context.nonce = yield fetchNonce( context.nonceUrl );
			} catch ( e ) {
				// Leave it empty; submit() tries again.
			}
		},
	},
} );
