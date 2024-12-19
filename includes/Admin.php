<?php

namespace Autoblue;

class Admin {
	// TODO: Can we make this a bit better?
	public const AUTOBLUE_ENABLED_BY_DEFAULT = false;
	public const AUTOBLUE_DEFAULT_LOG_LEVEL  = 'info';

	public function register_hooks(): void {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_filter( 'plugin_action_links_' . AUTOBLUE_BASENAME, [ $this, 'add_settings_link' ] );
		add_action( 'init', [ $this, 'register_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public function register_admin_page(): void {
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

	/**
	 * @param array<string> $links The existing links.
	 * @return array<string> The modified links.
	 */
	public function add_settings_link( $links ) {
		$settings_link = '<a href="options-general.php?page=autoblue">' . esc_html__( 'Settings', 'autoblue' ) . '</a>';
		array_unshift( $links, $settings_link );
		return $links;
	}

	public function register_settings(): void {
		$connections_schema = [
			'type'        => 'array',
			'items'       => [
				'type'       => 'object',
				'properties' => [
					'did'         => [
						'type'     => 'string',
						'pattern'  => '^[a-z0-9:]+$', // Allowed characters: a-z, 0-9, colon.
						'required' => true,
						'format'   => 'text-field',
					],
					'access_jwt'  => [
						'type'   => 'string',
						'format' => 'text-field',
					],
					'refresh_jwt' => [
						'type'   => 'string',
						'format' => 'text-field',
					],
				],
			],
			'uniqueItems' => true,
		];

		register_setting(
			'autoblue',
			'autoblue_connections',
			[
				'type'              => 'array',
				'description'       => __( 'List of connected Bluesky accounts.', 'autoblue' ),
				'show_in_rest'      => [ 'schema' => $connections_schema ],
				'default'           => [],
				'sanitize_callback' => function ( $value ) use ( $connections_schema ) {
					return rest_sanitize_value_from_schema( $value, $connections_schema );
				},
			]
		);

		register_setting(
			'autoblue',
			'autoblue_enabled',
			[
				'type'              => 'boolean',
				'description'       => __( 'True is sharing is enabled by default for new posts.', 'autoblue' ),
				'show_in_rest'      => true,
				'default'           => self::AUTOBLUE_ENABLED_BY_DEFAULT,
				'sanitize_callback' => 'rest_sanitize_boolean',
			],
		);

		register_setting(
			'autoblue',
			'autoblue_log_level',
			[
				'type'              => 'string',
				'description'       => __( 'The log level for Autoblue.', 'autoblue' ),
				'show_in_rest'      => [
					'schema' => [
						'type' => 'string',
						'enum' => [ 'debug', 'info', 'error', 'off' ],
					],
				],
				'default'           => self::AUTOBLUE_DEFAULT_LOG_LEVEL,
				'sanitize_callback' => function ( $value ) {
					if ( ! in_array( $value, [ 'debug', 'info', 'error', 'off' ], true ) ) {
						return self::AUTOBLUE_DEFAULT_LOG_LEVEL;
					}
					return $value;
				},
			],
		);
	}
}
