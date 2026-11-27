/**
 * Customer dashboard (Interactivity API store "pz/dashboard"), shared by the
 * pz/subscription-summary and pz/schedule-calendar blocks.
 *
 * The state is Services\DashboardView from the server (GET /pz/v1/me/dashboard).
 * Every button calls a REST route; the server checks the rules and either
 * refuses with a message (shown as it is) or changes the schedule. Then the
 * whole view is fetched again, so the calendar and the end date update
 * without a reload. Nothing here decides what is allowed.
 */
import { store, getContext } from '@wordpress/interactivity';

const FALLBACK_ERROR = 'Something went wrong. Please try again.';

/**
 * Call a Pazhamei REST route with the nonce.
 *
 * @param {string} method HTTP method.
 * @param {string} path   Route under /pz/v1/, e.g. "me/resume".
 * @param {Object} body   JSON body (optional).
 * @return {Promise<{ok: boolean, data: any, message: string}>} The reply.
 */
async function api( method, path, body ) {
	try {
		const response = await fetch( state.restUrl + path, {
			method,
			credentials: 'same-origin',
			cache: 'no-store',
			headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': state.nonce },
			body: body ? JSON.stringify( body ) : undefined,
		} );
		const json = await response.json();
		return { ok: !! json.ok, data: json.data, message: json.message || FALLBACK_ERROR };
	} catch ( e ) {
		return { ok: false, data: null, message: FALLBACK_ERROR };
	}
}

/** Fetch the whole view again and swap it in. */
async function refresh() {
	const before = state.current ? state.current.end : '';
	const reply = await api( 'GET', 'me/dashboard' );
	if ( reply.ok ) {
		Object.assign( state, reply.data );
		state.endChanged = !! ( state.current && before && state.current.end !== before );
	}
	return reply;
}

let timer = null;
let poll = null;
let refreshing = false;

const { state, actions } = store( 'pz/dashboard', {
	state: {
		busy: false,
		message: '',
		calendarMessage: '',
		endChanged: false,
		get isPaused() {
			return !! state.current && state.current.status === 'paused';
		},
		get countdown() {
			const s = state.cutoff ? state.cutoff.secondsLeft : 0;
			if ( s <= 0 ) {
				return '';
			}
			const h = Math.floor( s / 3600 );
			const m = Math.floor( ( s % 3600 ) / 60 );
			return h > 0 ? `(in ${ h } h ${ m } min)` : `(in ${ m } min ${ s % 60 } s)`;
		},
		get pauseVisible() {
			return getContext().pauseOpen && state.canPause;
		},
		get isOpenEnded() {
			return getContext().mode === 'until-resume';
		},
		get canConfirm() {
			const ctx = getContext();
			return ! state.busy && !! ctx.start && ( ctx.mode === 'until-resume' || ( !! ctx.end && !! ctx.previewOk ) );
		},
		get skipLabel() {
			return 'Skip ' + getContext().day.display;
		},
		get undoLabel() {
			return 'Undo skip on ' + getContext().day.display;
		},
	},
	actions: {
		*skip() {
			yield actions.change( 'POST', getContext().day.date );
		},
		*undo() {
			yield actions.change( 'DELETE', getContext().day.date );
		},
		*change( method, date ) {
			state.busy = true;
			state.calendarMessage = '';
			const reply = yield api( method, `schedule/${ date }/skip` );
			if ( ! reply.ok ) {
				state.calendarMessage = reply.message;
			}
			yield refresh();
			state.busy = false;
		},
		togglePause() {
			const ctx = getContext();
			ctx.pauseOpen = ! ctx.pauseOpen;
			state.message = '';
		},
		*setStart( event ) {
			getContext().start = event.target.value;
			yield actions.preview();
		},
		*setMode( event ) {
			getContext().mode = event.target.value;
			yield actions.preview();
		},
		*setEnd( event ) {
			getContext().end = event.target.value;
			yield actions.preview();
		},
		*preview() {
			const ctx = getContext();
			ctx.preview = '';
			ctx.previewOk = false;
			if ( ! ctx.start || ( ctx.mode === 'until-date' && ! ctx.end ) ) {
				return;
			}
			const body = { start_date: ctx.start };
			if ( ctx.mode === 'until-date' ) {
				body.end_date = ctx.end;
			}
			const reply = yield api( 'POST', 'me/pause/preview', body );
			if ( ! reply.ok ) {
				ctx.preview = reply.message;
				return;
			}
			ctx.previewOk = true;
			ctx.preview = reply.data.openEnded
				? 'Your lunches stop from the first day until you press Resume. Paused days move to the end of your pack.'
				: 'Your pack will now end on ' + reply.data.endDisplay + '.';
		},
		*confirmPause( event ) {
			event.preventDefault();
			const ctx = getContext();
			const body = { start_date: ctx.start };
			if ( ctx.mode === 'until-date' ) {
				body.end_date = ctx.end;
			}
			state.busy = true;
			state.message = '';
			const reply = yield api( 'POST', 'me/pause', body );
			if ( reply.ok ) {
				ctx.pauseOpen = false;
				ctx.preview = '';
			} else {
				state.message = reply.message;
			}
			yield refresh();
			state.busy = false;
		},
		*resume() {
			state.busy = true;
			state.message = '';
			const reply = yield api( 'POST', 'me/resume' );
			if ( ! reply.ok ) {
				state.message = reply.message;
			}
			yield refresh();
			state.busy = false;
		},
	},
	callbacks: {
		start() {
			// The countdown ticks every second; at zero the view is fetched again (days lock).
			if ( ! timer ) {
				timer = setInterval( () => {
					if ( ! state.cutoff ) {
						return;
					}
					if ( state.cutoff.secondsLeft > 0 ) {
						state.cutoff.secondsLeft -= 1;
					} else if ( ! refreshing ) {
						refreshing = true;
						refresh().finally( () => ( refreshing = false ) );
					}
				}, 1000 );
			}
			// While a payment is being confirmed, check again every 5 seconds.
			if ( ! poll ) {
				poll = setInterval( () => {
					if ( state.confirming && ! state.busy ) {
						refresh();
					}
				}, 5000 );
			}
		},
	},
} );
