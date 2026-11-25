<?php
/**
 * Environment: which environment-specific tools are allowed to run.
 *
 * Developer tools (`wp pz seed`, the PZ_FAKE_NOW clock override) work only
 * when WordPress's environment type is "local". On staging and production
 * they refuse to run. Plain PHP, so PHPUnit can test it directly.
 *
 * @package Pazhamei
 */

namespace Pazhamei;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Environment rules.
 */
final class Environment {

	/** The only environment type where developer tools may run. */
	public const LOCAL = 'local';

	/**
	 * May developer tools run in this environment?
	 *
	 * @param string $environment_type Value of wp_get_environment_type().
	 */
	public static function allows_dev_tools( string $environment_type ): bool {
		return self::LOCAL === $environment_type;
	}
}
