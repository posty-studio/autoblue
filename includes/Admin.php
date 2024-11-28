<?php

namespace Autoblue;

class Admin {
	// TODO: Can we make this a bit better?
	public const AUTOBLUE_ENABLED_BY_DEFAULT = false;

	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_filter( 'plugin_action_links_' . AUTOBLUE_BASEFILE, [ $this, 'add_settings_link' ] );
		add_action( 'init', [ $this, 'register_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public function register_admin_page() {
		add_options_page(
			'Autoblue',
			'Autoblue',
			'manage_options',
			'autoblue',
			function () {
				echo '<div id="autoblue"></div>';
			}
		);
	}

	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=autoblue">' . esc_html__( 'Settings', 'autoblue' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function register_settings() {
		register_setting(
			'autoblue',
			'autoblue_connections',
			[
				'type'         => 'array',
				'description'  => __( 'List of connected Bluesky accounts.', 'autoblue' ),
				'show_in_rest' => [
					'schema' => [
						'type'        => 'array',
						'items'       => [
							'type'       => 'object',
							'properties' => [
								'did'         => [
									'type'     => 'string',
									'pattern'  => '^[a-z0-9:]+$', // Allowed characters: a-z, 0-9, colon.
									'required' => true,
								],
								'access_jwt'  => [
									'type' => 'string',
								],
								'refresh_jwt' => [
									'type' => 'string',
								],
							],
						],
						'uniqueItems' => true,
					],
				],
				'default'      => [],
			]
		);

		register_setting(
			'autoblue',
			'autoblue_enabled',
			[
				'type'         => 'boolean',
				'description'  => __( 'True is sharing is enabled by default for new posts.', 'autoblue' ),
				'show_in_rest' => true,
				'default'      => self::AUTOBLUE_ENABLED_BY_DEFAULT,
			],
		);
	}
}
