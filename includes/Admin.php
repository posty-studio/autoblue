<?php

namespace BSKY4WP;

class Admin {
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
		add_action( 'admin_init', [ $this, 'register_settings' ] );
		add_action( 'rest_api_init', [ $this, 'register_settings' ] );
	}

	public function register_admin_page() {
		add_submenu_page(
			'options-general.php',
			__( 'Bluesky', 'bsky-for-wp' ),
			__( 'Bluesky', 'bsky-for-wp' ),
			'manage_options',
			'bsky-for-wp',
			function () {
				echo '<div id="bsky-for-wp"></div>';
			}
		);
	}

	public function register_settings() {
		register_setting(
			'bsky4wp',
			'bsky4wp_app_password',
			[
				'type'              => 'string',
				'description'       => __( 'App Password for Bluesky.', 'bsky-for-wp' ),
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'default'           => '',
			]
		);

		register_setting(
			'bsky4wp',
			'bsky4wp_account_dids',
			[
				'type'         => 'array',
				'description'  => __( 'List of DIDs of known accounts.', 'bsky-for-wp' ),
				'show_in_rest' => [
					'schema' => [
						'type'        => 'array',
						'items'       => [
							'type'    => 'string',
							'pattern' => '^[a-z0-9:]+$',
						],
						'uniqueItems' => true,
					],
				],
				'default'      => [],
			]
		);
	}
}
