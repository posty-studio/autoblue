<?php
/**
 * Plugin Name: Autoblue
 * Description: Add Bluesky to your WordPress website. Automatically share new posts to Bluesky and integrate Bluesky replies with the comments on your website.
 * Author: Daniel Post
 * Author URI: https://danielpost.com
 * License: GPL-3.0
 * Version: 1.0.0
 * Text Domain: autoblue
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Autoblue\Setup() )->init();
