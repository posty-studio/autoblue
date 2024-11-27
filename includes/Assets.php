<?php

namespace Autoblue;

class Assets {
	public function register_hooks() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'register_block_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
	}

	private function get_asset_data( string $name ): array {
		$asset_filepath = AUTOBLUE_ASSETS_PATH . $name . '.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : [
			'dependencies' => [],
			'version'      => AUTOBLUE_VERSION,
		];

		return $asset_file;
	}

	private function enqueue_style( string $name, array $dependencies = [] ) {
		$data = $this->get_asset_data( str_replace( 'style-', '', $name ) );

		wp_enqueue_style(
			"autoblue-{$name}-style",
			AUTOBLUE_ASSETS_URL . $name . '.css',
			$dependencies,
			$data['version']
		);
	}

	private function enqueue_script( string $name, array $params = [], $dependencies = [] ) {
		// TODO: Find a better way to prevent duplication.
		static $autoblue_defined = false;
		$data                    = $this->get_asset_data( $name );

		wp_register_script(
			"autoblue-{$name}-plugin-script",
			AUTOBLUE_ASSETS_URL . $name . '.js',
			array_merge( $data['dependencies'], $dependencies ),
			$data['version'],
			true
		);

		if ( ! empty( $params ) && ! $autoblue_defined ) {
			wp_add_inline_script( "autoblue-{$name}-plugin-script", 'const autoblue = ' . wp_json_encode( $params ), 'before' );
			$autoblue_defined = true;
		}

		wp_enqueue_script( "autoblue-{$name}-plugin-script" );
	}

	public function register_block_editor_assets() {
		$this->enqueue_script(
			'editor',
			[
				'initialState' => $this->get_initial_state(),
				'version'      => AUTOBLUE_VERSION,
			]
		);
		$this->enqueue_style( 'editor' );
	}

	public function register_admin_assets() {
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style( 'dataviews' );
		$this->enqueue_script(
			'admin',
			[
				'initialState' => $this->get_initial_state(),
				'version'      => AUTOBLUE_VERSION,
			]
		);
		$this->enqueue_style( 'admin' );
	}

	private function get_initial_state(): array {
		$current_page     = get_current_screen();
		$refresh_accounts = $current_page && $current_page->id === 'settings_page_autoblue';
		$connections      = ( new ConnectionsManager() )->get_all_connections( $refresh_accounts );

		return [
			'accounts' => [
				'items' => $connections,
			],
			'settings' => [
				'enabled' => get_option( 'autoblue_enabled', false ),
			],
		];
	}
}
