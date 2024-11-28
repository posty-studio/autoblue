<?php

namespace Autoblue;

class Setup {
	private function set_constants() {
		define( 'AUTOBLUE_VERSION', '1.0.0' );
		define( 'AUTOBLUE_SLUG', 'autoblue' );
		define( 'AUTOBLUE_BASEFILE', AUTOBLUE_SLUG . '/' . AUTOBLUE_SLUG . '.php' );
		define( 'AUTOBLUE_PATH', plugin_dir_path( __DIR__ ) );
		define( 'AUTOBLUE_BLOCKS_PATH', AUTOBLUE_PATH . 'build/js/blocks/' );
		define( 'AUTOBLUE_ASSETS_PATH', AUTOBLUE_PATH . 'build/' );
		define( 'AUTOBLUE_ASSETS_URL', plugin_dir_url( __DIR__ ) . 'build/' );
	}

	public function init() {
		$this->set_constants();

		( new Admin() )->register_hooks();
		( new Meta() )->register_hooks();
		( new Blocks() )->register_hooks();
		( new Assets() )->register_hooks();
		( new PostHandler() )->register_hooks();
		( new ConnectionsManager() )->register_hooks();

		add_action( 'rest_api_init', [ new Endpoints\SearchController(), 'register_routes' ] );
		add_action( 'rest_api_init', [ new Endpoints\AccountController(), 'register_routes' ] );
		add_action( 'rest_api_init', [ new Endpoints\ConnectionsController(), 'register_routes' ] );
	}

	public static function activate() {
		if ( ! wp_next_scheduled( ConnectionsManager::REFRESH_CONNECTIONS_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', ConnectionsManager::REFRESH_CONNECTIONS_HOOK );
		}
	}

	public static function deactivate() {
		wp_clear_scheduled_hook( ConnectionsManager::REFRESH_CONNECTIONS_HOOK );
	}
}
