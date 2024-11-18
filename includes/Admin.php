<?php

namespace BSKY4WP;

class Admin {
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_filter( 'plugin_action_links_' . BSKY4WP_BASEFILE, [ $this, 'add_settings_link' ] );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public function register_admin_page() {
		add_options_page(
			esc_html__( 'Bluesky', 'bsky-for-wp' ),
			esc_html__( 'Bluesky', 'bsky-for-wp' ),
			'manage_options',
			'bksy-for-wp',
			function () {
				echo '<div id="bsky-for-wp"></div>';
			}
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=bksy-for-wp">' . esc_html__( 'Settings', 'bsky-for-wp' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function register_settings() {
		register_setting(
			'bsky4wp',
			'bsky4wp_accounts',
			[
				'type'         => 'array',
				'description'  => __( 'List of connected Bluesky accounts.', 'bsky-for-wp' ),
				'show_in_rest' => [
					'schema' => [
						'type'        => 'array',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'did'          => [
									'type'     => 'string',
									'pattern'  => '^[a-z0-9:]+$', // Allowed characters: a-z, 0-9, colon
									'required' => true,
								],
								'app_password' => [
									'type'     => 'string',
									'pattern'  => '^[a-z0-9-]+$', // Allowed characters: a-z, 0-9, hyphen
									'required' => true,
								],
								'meta'         => [
									'type'       => 'object',
									'properties' => [
										'name'   => [
											'type' => 'string',
										],
										'handle' => [
											'type' => 'string',
										],
										'avatar' => [
											'type' => 'uri',
										],
									],
								],
							],
						],
						'uniqueItems' => true,
					],
				],
				'default'      => [],
			]
		);
	}
}
