<?php

namespace Autoblue;

class Admin {
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_filter( 'plugin_action_links_' . AUTOBLUE_BASEFILE, [ $this, 'add_settings_link' ] );
		add_action( 'init', [ $this, 'register_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public function register_admin_page() {
		add_options_page(
			esc_html__( 'Bluesky', 'autoblue' ),
			esc_html__( 'Bluesky', 'autoblue' ),
			'manage_options',
			'bksy-for-wp',
			function () {
				echo '<div id="autoblue"></div>';
			}
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=bksy-for-wp">' . esc_html__( 'Settings', 'autoblue' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function register_settings() {
		register_setting(
			'autoblue',
			'autoblue_accounts',
			[
				'type'         => 'array',
				'description'  => __( 'List of connected Bluesky accounts.', 'autoblue' ),
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
