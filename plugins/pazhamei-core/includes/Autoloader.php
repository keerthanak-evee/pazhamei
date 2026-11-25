<?php
/**
 * Autoloader: loads a class file the first time the class is used.
 *
 * It follows the PSR-4 idea: the namespace after `Pazhamei\` maps to folders
 * under includes/. Example: Pazhamei\Access\PortalGuard is loaded from
 * includes/Access/PortalGuard.php. This means we never need require_once
 * for our own classes.
 *
 * @package Pazhamei
 */

namespace Pazhamei;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Registers the autoloader with PHP.
 */
final class Autoloader {

	/** Every class we own starts with this namespace. */
	private const PREFIX = 'Pazhamei\\';

	/** Folder that holds the class files (ends with a slash). */
	private static string $base_dir = '';

	/**
	 * Tell PHP to call self::load() whenever an unknown class is used.
	 *
	 * @param string $base_dir Absolute path to includes/, with trailing slash.
	 */
	public static function register( string $base_dir ): void {
		self::$base_dir = $base_dir;
		spl_autoload_register( array( self::class, 'load' ) );
	}

	/**
	 * Load the file for one class, if it is ours.
	 *
	 * @param string $class_name Fully qualified class name, e.g. Pazhamei\Access\PortalGuard.
	 */
	public static function load( string $class_name ): void {
		if ( 0 !== strpos( $class_name, self::PREFIX ) ) {
			return; // Not one of ours; let other autoloaders handle it.
		}

		$relative = substr( $class_name, strlen( self::PREFIX ) );
		$file     = self::$base_dir . str_replace( '\\', '/', $relative ) . '.php';

		if ( is_readable( $file ) ) {
			require_once $file;
		}
	}
}
