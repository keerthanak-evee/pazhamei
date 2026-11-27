<?php
/**
 * WpEvents: engine events as WordPress actions (pz_<event>).
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Events → do_action().
 */
final class WpEvents implements Events {

	/** {@inheritDoc} */
	public function emit( string $name, array $args ): void {
		do_action( 'pz_' . $name, ...$args );
	}
}
