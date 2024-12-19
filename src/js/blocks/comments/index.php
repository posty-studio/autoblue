<?php
if ( ! function_exists( 'autoblue_render_comment' ) ) {
	function autoblue_render_comment( $comment ) {
		$name = $comment['post']['author']['displayName'] ?? $comment['post']['author']['handle'];
		?>
	<li class="wp-block-autoblue-comment">
		<div class="wp-block-autoblue-comment-avatar">
			<img src="<?php echo esc_url( $comment['post']['author']['avatar'] ); ?>"
				alt="<?php echo esc_attr( $comment['post']['author']['displayName'] ); ?>">
		</div>
		<div class="wp-block-autoblue-comment-author">
			<span class="wp-block-autoblue-comment-author__name">
				<?php echo esc_html( $name ); ?>
			</span>
			<span class="wp-block-autoblue-comment-author__handle">
				@<?php echo esc_html( $comment['post']['author']['handle'] ); ?>
			</span>
		</div>

		<div class="wp-block-autoblue-comment-content">
			<p class="wp-block-autoblue-comments-comment-text">
				<?php echo wp_kses_post( $comment['post']['record']['text'] ); ?>
			</p>
		</div>

		<span class="wp-block-autoblue-comment-date">
			<?php echo esc_html( wp_date( get_option( 'date_format' ), strtotime( $comment['post']['record']['createdAt'] ) ) ); ?>
		</span>

		<?php
		if ( ! empty( $comment['replies'] ) ) {
			echo '<ol class="wp-block-autoblue-comment-replies">';
			foreach ( $comment['replies'] as $reply ) {
				autoblue_render_comment( $reply );
			}
			echo '</ol>';
		}
		?>
	</li>
		<?php
	}
}

$comments_list = ( new Autoblue\Comments() )->get_comments( get_the_ID() );

if ( empty( $comments_list ) ) {
	return;
}
?>

<div
	<?php echo get_block_wrapper_attributes(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
>
	<h3 class="wp-block-autoblue-comments-title">
		<?php esc_html_e( 'Comments', 'autoblue' ); ?>
	</h3>
	<p><a class="wp-block-autoblue-comments-link" href=<?php echo esc_url( $comments_list['url'] ); ?>><?php esc_html_e( 'Join the conversation on Bluesky', 'autoblue' ); ?></a></p>

	<ol class="wp-block-autoblue-comment-template">
		<?php
		foreach ( $comments_list['comments']['replies'] as $comment_item ) {
			autoblue_render_comment( $comment_item );
		}
		?>
	</ol>
</div>
