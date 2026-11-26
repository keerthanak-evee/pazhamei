<?php
/**
 * ClientIp: the one place that works out the visitor's IP address.
 *
 * For now it reads REMOTE_ADDR only. Headers like X-Forwarded-For are easy to
 * fake, so they are ignored until Phase 2, when we know Hostinger's proxy
 * setup. Then the trusted proxy header is added here, and only here.
 *
 * Raw IPs are never stored. Callers use hash(), a keyed (salted) SHA-256,
 * so a stored key can't be turned back into an IP.
 *
 * @package Pazhamei
 */

namespace Pazhamei\Services;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Visitor IP lookup and hashing.
 */
final class ClientIp {

	/**
	 * The visitor's IP from server variables, or '' if it is missing or not a valid IP.
	 *
	 * @param array<string, mixed> $server Usually $_SERVER.
	 */
	public static function from_server( array $server ): string {
		$ip = isset( $server['REMOTE_ADDR'] ) && is_string( $server['REMOTE_ADDR'] ) ? trim( $server['REMOTE_ADDR'] ) : '';

		return false !== filter_var( $ip, FILTER_VALIDATE_IP ) ? $ip : '';
	}

	/**
	 * Salted hash of an IP. The same IP and salt always give the same hash.
	 *
	 * @param string $ip   IP address.
	 * @param string $salt Secret salt.
	 */
	public static function hash( string $ip, string $salt ): string {
		return hash_hmac( 'sha256', $ip, $salt );
	}

	/**
	 * Salted hash of the current visitor's IP, using a secret from wp-config.php.
	 */
	public static function current_hash(): string {
		return self::hash( self::from_server( $_SERVER ), wp_salt( 'pz_client_ip' ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput -- Validated as an IP in from_server().
	}
}
