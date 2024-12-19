<?php

namespace Autoblue;

class PostHandler {
	/** @var Logging\Log */
	private $log;

	public function __construct() {
		$this->log = new Logging\Log();
	}

	public function register_hooks(): void {
		add_action( 'wp_after_insert_post', [ $this, 'maybe_schedule_bluesky_share' ], 10, 4 );
		add_action( 'autoblue_share_to_bluesky', [ $this, 'process_scheduled_share' ], 10, 1 );
	}

	/**
	 * Maybe schedule a post to be shared to Bluesky.
	 *
	 * @param int           $post_id The post ID.
	 * @param \WP_Post      $post The post object.
	 * @param bool          $update Whether this is an existing post being updated.
	 * @param \WP_Post|null $post_before The post object before the update.
	 * @return void
	 */
	public function maybe_schedule_bluesky_share( $post_id, $post, $update, $post_before ) {
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		$enabled = get_post_meta( $post_id, 'autoblue_enabled', true );

		if ( ! $enabled ) {
			$this->log->debug(
				__( 'Skipping share for post `{post_title}` with ID `{post_id}` because it is not enabled.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
				]
			);
			return;
		}

		// TODO: Add support for multiple post types.
		$valid_post_types = [ 'post' ];
		if ( ! in_array( $post->post_type, $valid_post_types, true ) ) {
			$this->log->debug(
				__( 'Skipping share for post `{post_title}` with ID `{post_id}` because it is not a supported post type. Valid post types are: `{valid_post_types}`, but got `{post_type}`.', 'autoblue' ),
				[
					'post_id'          => $post_id,
					'post_title'       => $post->post_title,
					'post_type'        => $post->post_type,
					'valid_post_types' => $valid_post_types,
				]
			);
			return;
		}

		// Don't run this when saving already published posts.
		if ( $post_before && $post_before->post_status === 'publish' ) {
			$this->log->debug(
				__( 'Skipping share for post `{post_title}` with ID `{post_id}` because the post is already published.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
				]
			);
			return;
		}

		if ( wp_next_scheduled( 'autoblue_share_to_bluesky', [ $post_id ] ) ) {
			$this->log->debug(
				__( 'Skipping share for post `{post_title}` with ID `{post_id}` because a share is already scheduled.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
				]
			);
			return;
		}

		// If we're running a cron job, process the share immediately.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->log->debug(
				__( 'Processing share for post `{post_title}` with ID `{post_id}` immediately.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
				]
			);
			$this->process_scheduled_share( $post_id );
		} else {
			$this->log->debug(
				__( 'Scheduling share for post `{post_title}` with ID `{post_id}`.', 'autoblue' ),
				[
					'post_id'    => $post_id,
					'post_title' => $post->post_title,
				]
			);
			wp_schedule_single_event( time(), 'autoblue_share_to_bluesky', [ $post_id ] );
		}
	}

	/**
	 * @param int $post_id The post ID.
	 */
	public function process_scheduled_share( $post_id ): void {
		$post = get_post( $post_id );

		if ( ! $post ) {
			$this->log->error(
				__( 'Share failed: Post with ID {post_id} not found.', 'autoblue' ),
				[ 'post_id' => $post_id ]
			);
			return;
		}

		$this->log->debug(
			__( 'Processing share for post `{post_title}` with ID `{post_id}`.', 'autoblue' ),
			[
				'post_id'    => $post_id,
				'post_title' => $post->post_title,
			]
		);

		$bluesky = new Bluesky();
		$bluesky->share_to_bluesky( $post_id );
	}
}
