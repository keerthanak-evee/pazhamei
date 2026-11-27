/**
 * Start-date picker for the WooCommerce checkout (block or classic).
 *
 * Opens a small calendar under the "Start date" field. Every date comes
 * from the server (GET /pz/v1/checkout/start-dates) with its status:
 * only "ok" dates can be picked; Sundays, holidays and locked days are
 * greyed out with the reason. The checkout checks the date again on the
 * server when the order is placed, so this is only a convenience.
 *
 * Plain JavaScript, no build step. pzStartDates = { url, nonce, inputId }.
 */
( function () {
	const config = window.pzStartDates || {};
	let data = null;

	/** Fetch the dates once. */
	async function load() {
		if ( data ) {
			return data;
		}
		const response = await fetch( config.url, {
			headers: { 'X-WP-Nonce': config.nonce },
			credentials: 'same-origin',
			cache: 'no-store',
		} );
		const json = await response.json();
		data = json && json.ok ? json.data : { days: [], earliest: '' };
		return data;
	}

	/** Set the input's value so React (block checkout) notices it. */
	function setValue( input, value ) {
		const setter = Object.getOwnPropertyDescriptor( HTMLInputElement.prototype, 'value' ).set;
		setter.call( input, value );
		input.dispatchEvent( new Event( 'input', { bubbles: true } ) );
		input.dispatchEvent( new Event( 'change', { bubbles: true } ) );
	}

	/** Weekday column, Monday first (0..6). */
	function column( ymd ) {
		const day = new Date( ymd + 'T00:00:00Z' ).getUTCDay();
		return ( day + 6 ) % 7;
	}

	function build( input ) {
		const panel = document.createElement( 'div' );
		panel.className = 'pz-date-picker';
		panel.hidden = true;
		panel.setAttribute( 'role', 'dialog' );
		panel.setAttribute( 'aria-label', 'Choose your start date' );

		const hint = document.createElement( 'p' );
		hint.className = 'pz-date-picker__hint';
		hint.id = input.id + '-hint';
		hint.textContent = 'Monday to Saturday, within the next 30 days.';
		input.setAttribute( 'aria-describedby', hint.id );
		input.setAttribute( 'readonly', 'readonly' );
		input.setAttribute( 'inputmode', 'none' );

		const holder = input.closest( '.wc-block-components-text-input, .form-row' ) || input.parentElement;
		holder.after( hint );
		hint.after( panel );

		load().then( ( d ) => {
			if ( d.earliest ) {
				hint.textContent = 'Monday to Saturday, within the next 30 days. Earliest start: ' + label( d, d.earliest ) + '.';
			}
		} );

		async function open() {
			const d = await load();
			panel.innerHTML = '';
			const grid = document.createElement( 'div' );
			grid.className = 'pz-date-picker__grid';
			[ 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun' ].forEach( ( name ) => {
				const head = document.createElement( 'span' );
				head.className = 'pz-date-picker__head';
				head.textContent = name;
				grid.appendChild( head );
			} );
			if ( d.days.length ) {
				for ( let i = 0; i < column( d.days[ 0 ].date ); i++ ) {
					grid.appendChild( document.createElement( 'span' ) );
				}
			}
			d.days.forEach( ( day ) => {
				const button = document.createElement( 'button' );
				button.type = 'button';
				button.className = 'pz-date-picker__day is-' + day.status;
				button.textContent = String( Number( day.date.slice( 8 ) ) );
				const month = day.display.split( ' ' )[ 2 ];
				if ( day.date.slice( 8 ) === '01' || day === d.days[ 0 ] ) {
					button.dataset.month = month;
				}
				button.disabled = day.status !== 'ok';
				button.title = day.reason || day.display;
				button.setAttribute( 'aria-label', day.display + ( day.reason ? '. ' + day.reason : '' ) );
				if ( day.date === input.value ) {
					button.classList.add( 'is-selected' );
					button.setAttribute( 'aria-pressed', 'true' );
				}
				button.addEventListener( 'click', () => {
					setValue( input, day.date );
					close();
					input.focus();
				} );
				grid.appendChild( button );
			} );
			panel.appendChild( grid );
			const legend = document.createElement( 'p' );
			legend.className = 'pz-date-picker__legend';
			legend.textContent = 'Greyed-out days can’t be chosen: Sundays, kitchen holidays and days that are already locked.';
			panel.appendChild( legend );
			panel.hidden = false;
			const first = panel.querySelector( '.is-selected' ) || panel.querySelector( '.is-ok' );
			if ( first ) {
				first.focus();
			}
		}

		function close() {
			panel.hidden = true;
		}

		input.addEventListener( 'click', open );
		input.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Enter' || event.key === ' ' || event.key === 'ArrowDown' ) {
				event.preventDefault();
				open();
			}
		} );
		panel.addEventListener( 'keydown', ( event ) => {
			if ( event.key === 'Escape' ) {
				close();
				input.focus();
			}
		} );
		document.addEventListener( 'click', ( event ) => {
			if ( ! panel.hidden && ! panel.contains( event.target ) && event.target !== input ) {
				close();
			}
		} );
	}

	function label( d, ymd ) {
		const day = d.days.find( ( x ) => x.date === ymd );
		return day ? day.display : ymd;
	}

	/** The block checkout draws its fields late, so watch for the input. */
	function attach() {
		const input = document.getElementById( config.inputId ) || document.getElementById( 'pz_start_date' );
		if ( input && ! input.dataset.pzPicker ) {
			input.dataset.pzPicker = '1';
			if ( input.type === 'date' ) {
				input.type = 'text'; // Classic checkout: use our picker instead of the browser's.
			}
			build( input );
		}
	}

	attach();
	new MutationObserver( attach ).observe( document.body, { childList: true, subtree: true } );
} )();
