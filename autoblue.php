<?php
/**
 * Plugin Name: Autoblue
 * Plugin URI: https://autoblue.cc
 * Description: Automatically share new posts to Bluesky.
 * Author: Daniel Post
 * Author URI: https://danielpost.com
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 0.0.1
 * Text Domain: autoblue
 * Requires at least: 6.6
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Autoblue\Setup() )->init();

register_activation_hook( __FILE__, [ 'Autoblue\Setup', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Autoblue\Setup', 'deactivate' ] );
