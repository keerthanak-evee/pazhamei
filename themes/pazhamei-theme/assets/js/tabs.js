/**
 * Pazhamei tabs: turns a Group block with the "Tabs" style into tabs.
 *
 * In the editor (and without JavaScript) the Group is just its inner groups,
 * one under another, each starting with a heading. Here, each inner group
 * becomes a tab panel and its heading becomes the tab's label.
 * Keyboard: Left/Right move between tabs, Home/End jump to the first/last.
 * Nothing animates, so there is nothing to switch off for reduced motion.
 */
( function () {
	'use strict';

	var count = 0;

	function setUp( container ) {
		var panels = Array.prototype.filter.call( container.children, function ( child ) {
			return child.classList.contains( 'wp-block-group' ) && child.querySelector( 'h2, h3, h4' );
		} );
		if ( panels.length < 2 ) {
			return;
		}

		count++;
		var list = document.createElement( 'div' );
		list.className = 'pz-tabs__list';
		list.setAttribute( 'role', 'tablist' );
		if ( container.getAttribute( 'aria-label' ) ) {
			list.setAttribute( 'aria-label', container.getAttribute( 'aria-label' ) );
		}

		var tabs = panels.map( function ( panel, index ) {
			var heading = panel.querySelector( 'h2, h3, h4' );
			var tab = document.createElement( 'button' );
			var tabId = 'pz-tab-' + count + '-' + index;
			var panelId = panel.id || 'pz-tabpanel-' + count + '-' + index;

			tab.type = 'button';
			tab.id = tabId;
			tab.className = 'pz-tabs__tab';
			tab.textContent = heading.textContent;
			tab.setAttribute( 'role', 'tab' );
			tab.setAttribute( 'aria-controls', panelId );

			panel.id = panelId;
			panel.setAttribute( 'role', 'tabpanel' );
			panel.setAttribute( 'aria-labelledby', tabId );
			panel.setAttribute( 'tabindex', '0' );
			heading.classList.add( 'screen-reader-text' ); // The tab shows the label now.

			tab.addEventListener( 'click', function () {
				select( index, false );
			} );
			list.appendChild( tab );
			return tab;
		} );

		function select( index, focus ) {
			tabs.forEach( function ( tab, i ) {
				var on = i === index;
				tab.setAttribute( 'aria-selected', on ? 'true' : 'false' );
				tab.tabIndex = on ? 0 : -1;
				panels[ i ].hidden = ! on;
			} );
			if ( focus ) {
				tabs[ index ].focus();
			}
		}

		list.addEventListener( 'keydown', function ( event ) {
			var current = tabs.indexOf( document.activeElement );
			var next = { ArrowRight: current + 1, ArrowLeft: current - 1, Home: 0, End: tabs.length - 1 }[ event.key ];
			if ( current === -1 || next === undefined ) {
				return;
			}
			event.preventDefault();
			select( ( next + tabs.length ) % tabs.length, true );
		} );

		container.insertBefore( list, panels[ 0 ] );
		container.classList.add( 'is-tabbed' );
		select( 0, false );
	}

	document.querySelectorAll( '.wp-block-group.is-style-pz-tabs' ).forEach( setUp );
} )();
