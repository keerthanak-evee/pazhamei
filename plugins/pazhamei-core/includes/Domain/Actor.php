<?php
/**
 * Actor: who made a change, for the change log.
 *
 * REST endpoints build it from the signed-in user, scheduled jobs use
 * Actor::system() and WP-CLI uses Actor::cli().
 *
 * @package Pazhamei
 */

namespace Pazhamei\Domain;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The person or job behind a change.
 */
final class Actor {

	public int $id;
	public string $role;
	public string $ip;

	/**
	 * @param int    $id   WordPress user ID (0 for the system).
	 * @param string $role customer, admin, kitchen, system or cli.
	 * @param string $ip   Client IP address, or '' when not a web request.
	 */
	public function __construct( int $id, string $role, string $ip = '' ) {
		$this->id   = $id;
		$this->role = $role;
		$this->ip   = $ip;
	}

	/**
	 * A scheduled job or WooCommerce hook.
	 */
	public static function system(): self {
		return new self( 0, 'system' );
	}

	/**
	 * A WP-CLI command.
	 *
	 * @param int $user_id User running it, if known.
	 */
	public static function cli( int $user_id = 0 ): self {
		return new self( $user_id, 'cli' );
	}

	/**
	 * Is this an admin (or the CLI, which only runs locally and for admins)?
	 */
	public function is_staff(): bool {
		return in_array( $this->role, array( 'admin', 'cli', 'system' ), true );
	}
}
