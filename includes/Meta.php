<?php

namespace Autoblue;

class Meta {
	public function register_hooks() {
		add_action( 'init', [ $this, 'register_post_meta' ] );
	}

	public function register_post_meta() {
		// TODO: Add support for multiple post types.
		register_post_meta(
			'post',
			'autoblue_enabled',
			[
				'type'         => 'boolean',
				'description'  => __( 'Whether the post should be shared to Bluesky.', 'autoblue' ),
				'single'       => true,
				'show_in_rest' => true,
				'default'      => Utils::is_autoblue_enabled_by_default(),
			]
		);

		register_post_meta(
			'post',
			'autoblue_custom_message',
			[
				'type'              => 'string',
				'description'       => __( 'An optional custom message to include with the post.', 'autoblue' ),
				'single'            => true,
				'show_in_rest'      => true,
				'sanitize_callback' => 'sanitize_text_field',
			]
		);

		register_post_meta(
			'post',
			'autoblue_shares',
			[
				'type'         => 'object',
				'description'  => __( 'A list of shares of the post.', 'autoblue' ),
				'single'       => true,
				'show_in_rest' => [
					'schema' => [
						'type'  => 'array',
						'items' => [
							'type'       => 'object',
							'properties' => [
								'did'      => [
									'type'     => 'string',
									'required' => true,
								],
								'date'     => [
									'type'     => 'string',
									'format'   => 'date-time',
									'required' => true,
								],
								'uri'      => [
									'type'     => 'uri',
									'required' => true,
								],
								'at_uri'   => [
									'type' => 'uri',
								],
								'response' => [
									'type' => 'string',
								],
							],
						],
					],
				],
			]
		);
	}
}
