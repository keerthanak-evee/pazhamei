<?php
/**
 * Test bootstrap: loads Composer's autoloader.
 *
 * These tests cover plain-PHP rule classes only, so WordPress is not loaded.
 * Our files exit early when ABSPATH is missing, so we define it here.
 *
 * @package Pazhamei
 */

define( 'ABSPATH', __DIR__ . '/' );

require_once dirname( __DIR__ ) . '/vendor/autoload.php';
