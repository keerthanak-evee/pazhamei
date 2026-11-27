/**
 * pz/menu-week front end (Interactivity API, store "pz/menu-week").
 * The day tabs switch which day's menu is shown, without a reload.
 * Arrow keys move between tabs.
 */
import { store, getContext, getElement } from '@wordpress/interactivity';

store( 'pz/menu-week', {
	state: {
		get isSelected() {
			return getContext().day === getElement().attributes[ 'data-date' ];
		},
	},
	actions: {
		show( event ) {
			getContext().day = event.currentTarget.dataset.date;
		},
	},
	callbacks: {},
} );

// Arrow keys between the day tabs (standard tab pattern).
document.addEventListener( 'keydown', ( event ) => {
	const tab = event.target.closest && event.target.closest( '.pz-menu-week__tab' );
	if ( ! tab || ( event.key !== 'ArrowRight' && event.key !== 'ArrowLeft' ) ) {
		return;
	}
	const tabs = [ ...tab.parentElement.querySelectorAll( '.pz-menu-week__tab' ) ];
	const next = tabs[ ( tabs.indexOf( tab ) + ( event.key === 'ArrowRight' ? 1 : tabs.length - 1 ) ) % tabs.length ];
	next.focus();
	next.click();
} );
