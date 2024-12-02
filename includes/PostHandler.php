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

		// Don't run this when saving already published posts.
		if ( $post_before && $post_before->post_status === 'publish' ) {
			$this->log->debug( sprintf( __( 'Skipping share for post `%1$s` with ID `%2$d` because the post is already published.', 'autoblue' ), $post->post_title, $post_id ), [ 'post_id' => $post_id ] );
			return;
		}

		// TODO: Add support for multiple post types.
		if ( ! in_array( $post->post_type, [ 'post' ], true ) ) {
			$this->log->debug(
				sprintf(
					__( 'Skipping share for post `%1$s` with ID `%2$d` because it is not a supported post type. Valid post types are: %3$s, but got `%4$s`.', 'autoblue' ),
					$post->post_title,
					$post_id,
					implode( ', ', [ 'post' ] ),
					$post->post_type
				),
				[ 'post_id' => $post_id ]
			);
			return;
		}

		$enabled = get_post_meta( $post_id, 'autoblue_enabled', true );

		if ( ! $enabled ) {
			$this->log->debug( sprintf( __( 'Skipping share for post `%1$s` with ID `%2$d` because it is not enabled.', 'autoblue' ), $post->post_title, $post_id, [ 'post_id' => $post_id ] ) );
			return;
		}

		if ( wp_next_scheduled( 'autoblue_share_to_bluesky', [ $post_id ] ) ) {
			$this->log->debug( sprintf( __( 'Skipping share for post `%1$s` with ID `%2$d` because a share is already scheduled.', 'autoblue' ), $post->post_title, $post_id ), [ 'post_id' => $post_id ] );
			return;
		}

		// If we're running a cron job, process the share immediately.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->log->debug( sprintf( __( 'Processing share for post `%1$s` with ID `%2$d` immediately.', 'autoblue' ), $post->post_title, $post_id ), [ 'post_id' => $post_id ] );
			$this->process_scheduled_share( $post_id );
		} else {
			$this->log->debug( sprintf( __( 'Scheduling share for post `%1$s` with ID `%2$d`.', 'autoblue' ), $post->post_title, $post_id ), [ 'post_id' => $post_id ] );
			wp_schedule_single_event( time(), 'autoblue_share_to_bluesky', [ $post_id ] );
		}
	}

	/**
	 * @param int $post_id The post ID.
	 */
	public function process_scheduled_share( $post_id ): void {
		$bluesky = new Bluesky();
		$bluesky->share_to_bluesky( $post_id );
	}
}
