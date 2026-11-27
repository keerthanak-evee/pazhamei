/**
 * pz/plan-cards front end for signed-in customers (Interactivity API).
 *
 * Each card's context (from render.php) holds the chosen meal type, the price
 * text for each type and the Buy link for each type, all from the server.
 * Choosing Veg / Non-Veg / Mixed only switches which of those is shown:
 * no prices or rules are worked out here.
 */
import { store, getContext } from '@wordpress/interactivity';

store( 'pz/plan-cards', {
	state: {
		get price() {
			const context = getContext();
			return context.prices[ context.type ] || '';
		},
		get link() {
			const context = getContext();
			return context.links[ context.type ] || '';
		},
	},
	actions: {
		choose( event ) {
			getContext().type = event.target.value;
		},
	},
} );
