<?php

namespace Autoblue;

class Setup {
	public function init(): void {
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

	public static function activate(): void {
		if ( ! wp_next_scheduled( ConnectionsManager::REFRESH_CONNECTIONS_HOOK ) ) {
			wp_schedule_event( time(), 'weekly', ConnectionsManager::REFRESH_CONNECTIONS_HOOK );
		}
	}

	public static function deactivate(): void {
		wp_clear_scheduled_hook( ConnectionsManager::REFRESH_CONNECTIONS_HOOK );
	}
}
