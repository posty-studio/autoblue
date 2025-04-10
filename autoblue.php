<?php
/**
 * Plugin Name: Autoblue
 * Plugin URI: https://autoblue.co
 * Description: Automatically share new posts to Bluesky.
 * Author: Daniel Post
 * Author URI: https://autoblue.co
 * License: GPLv2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Version: 0.0.5
 * Text Domain: autoblue
 * Requires at least: 6.6
 * Requires PHP: 7.4
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

define( 'AUTOBLUE_VERSION', '0.0.5' );
define( 'AUTOBLUE_SLUG', 'autoblue' );
define( 'AUTOBLUE_BASENAME', plugin_basename( __FILE__ ) );
define( 'AUTOBLUE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AUTOBLUE_BLOCKS_PATH', AUTOBLUE_PATH . 'build/js/blocks/' );
define( 'AUTOBLUE_ASSETS_PATH', AUTOBLUE_PATH . 'build/' );
define( 'AUTOBLUE_URL', trailingslashit( plugins_url( plugin_basename( __DIR__ ) ) ) );
define( 'AUTOBLUE_ASSETS_URL', AUTOBLUE_URL . 'build/' );

register_activation_hook( __FILE__, [ 'Autoblue\Setup', 'activate' ] );
register_deactivation_hook( __FILE__, [ 'Autoblue\Setup', 'deactivate' ] );

( new Autoblue\Setup() )->init();
