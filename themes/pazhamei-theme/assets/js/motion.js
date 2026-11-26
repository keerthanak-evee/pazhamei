/**
 * Pazhamei motion: reveals blocks with the `pz-reveal` or `pz-reveal-stagger`
 * class as they scroll into view, by adding `is-visible`. The animation itself
 * is CSS (assets/css/motion.css).
 *
 * Runs only when <head> switched motion on (html.pz-motion), which never
 * happens when the visitor prefers reduced motion.
 */
( function () {
	'use strict';

	var root = document.documentElement;
	if ( ! root.classList.contains( 'pz-motion' ) ) {
		return;
	}

	var targets = document.querySelectorAll( '.pz-reveal, .pz-reveal-stagger' );

	var observer = new IntersectionObserver(
		function ( entries ) {
			entries.forEach( function ( entry ) {
				if ( entry.isIntersecting ) {
					entry.target.classList.add( 'is-visible' );
					observer.unobserve( entry.target ); // Reveal once, then stop watching.
				}
			} );
		},
		{ threshold: 0.12, rootMargin: '0px 0px -40px 0px' }
	);

	targets.forEach( function ( element ) {
		observer.observe( element );
	} );

	// If reduced motion is switched on while the page is open, show everything.
	var reduce = window.matchMedia( '(prefers-reduced-motion: reduce)' );
	var showAll = function () {
		if ( reduce.matches ) {
			targets.forEach( function ( element ) {
				element.classList.add( 'is-visible' );
			} );
		}
	};
	if ( reduce.addEventListener ) {
		reduce.addEventListener( 'change', showAll );
	}
} )();
