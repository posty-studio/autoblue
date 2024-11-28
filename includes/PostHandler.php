<?php

namespace Autoblue;

class PostHandler {
	public function register_hooks() {
		add_action( 'wp_after_insert_post', [ $this, 'maybe_schedule_bluesky_share' ], 10, 4 );
		add_action( 'autoblue_share_to_bluesky', [ $this, 'process_scheduled_share' ], 10, 1 );
	}

	public function maybe_schedule_bluesky_share( $post_id, $post, $update, $post_before ) {
		if ( $post->post_status !== 'publish' ) {
			return;
		}

		// Don't run this when saving already published posts.
		if ( $post_before && $post_before->post_status === 'publish' ) {
			return;
		}

		// TODO: Add support for multiple post types.
		if ( ! in_array( $post->post_type, [ 'post' ], true ) ) {
			return;
		}

		$enabled = get_post_meta( $post_id, 'autoblue_enabled', true );

		if ( ! $enabled ) {
			return;
		}

		if ( wp_next_scheduled( 'autoblue_share_to_bluesky', [ $post_id ] ) ) {
			return;
		}

		// If we're running a cron job, process the share immediately.
		if ( defined( 'DOING_CRON' ) && DOING_CRON ) {
			$this->process_scheduled_share( $post_id );
		} else {
			wp_schedule_single_event( time(), 'autoblue_share_to_bluesky', [ $post_id ] );
		}
	}

	public function process_scheduled_share( $post_id ) {
		$bluesky = new Bluesky();
		$bluesky->share_to_bluesky( $post_id );
	}
}
