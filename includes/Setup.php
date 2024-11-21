<?php

namespace BSKY4WP;

class Setup {
	private function set_constants() {
		define( 'BSKY4WP_VERSION', '1.0.0' );
		define( 'BSKY4WP_SLUG', 'bsky-for-wp' );
		define( 'BSKY4WP_BASEFILE', BSKY4WP_SLUG . '/' . BSKY4WP_SLUG . '.php' );
		define( 'BSKY4WP_PATH', plugin_dir_path( __DIR__ ) );
		define( 'BSKY4WP_BLOCKS_PATH', BSKY4WP_PATH . 'build/js/blocks/' );
		define( 'BSKY4WP_ASSETS_PATH', BSKY4WP_PATH . 'build/' );
		define( 'BSKY4WP_ASSETS_URL', plugin_dir_url( __DIR__ ) . 'build/' );
	}

	public function init() {
		$this->set_constants();

		( new Admin() )->register_hooks();
		( new Assets() )->register_hooks();

		add_action( 'rest_api_init', [ new Endpoints\SearchController(), 'register_routes' ] );
		add_action( 'rest_api_init', [ new Endpoints\AccountController(), 'register_routes' ] );
		add_action( 'rest_api_init', [ new Endpoints\AccountsController(), 'register_routes' ] );
	}
}
