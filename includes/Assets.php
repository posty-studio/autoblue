<?php

namespace BSKY4WP;

class Assets {
	public function register_hooks() {
		add_action( 'enqueue_block_editor_assets', [ $this, 'register_block_editor_assets' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'register_admin_assets' ] );
	}

	private function get_asset_data( string $name ): array {
		$asset_filepath = BSKY4WP_ASSETS_PATH . $name . '.asset.php';
		$asset_file     = file_exists( $asset_filepath ) ? include $asset_filepath : [
			'dependencies' => [],
			'version'      => BSKY4WP_VERSION,
		];

		return $asset_file;
	}

	private function enqueue_style( string $name, array $dependencies = [] ) {
		$data = $this->get_asset_data( str_replace( 'style-', '', $name ) );

		wp_enqueue_style(
			"bsky-for-wp-{$name}-style",
			BSKY4WP_ASSETS_URL . $name . '.css',
			$dependencies,
			$data['version']
		);
	}

	private function enqueue_script( string $name, array $params = [], $dependencies = [] ) {
		$data = $this->get_asset_data( $name );

		wp_register_script(
			"bsky-for-wp-{$name}-plugin-script",
			BSKY4WP_ASSETS_URL . $name . '.js',
			array_merge( $data['dependencies'], $dependencies ),
			$data['version'],
			true
		);

		if ( ! empty( $params ) ) {
			wp_add_inline_script( "bsky-for-wp-{$name}-plugin-script", 'const BSKY4WP = ' . wp_json_encode( $params ), 'before' );
		}

		wp_enqueue_script( "bsky-for-wp-{$name}-plugin-script" );
	}

	public function register_block_editor_assets() {
		$this->enqueue_script( 'editor' );
		$this->enqueue_style( 'editor' );
	}

	public function register_admin_assets() {
		wp_enqueue_style( 'wp-components' );
		$this->enqueue_script( 'admin' );
		$this->enqueue_style( 'admin' );
	}
}
