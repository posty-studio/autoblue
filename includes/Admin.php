<?php

namespace BSKY4WP;

class Admin {
	public function register_hooks() {
		add_action( 'admin_menu', [ $this, 'register_admin_page' ] );
	}

	public function register_admin_page() {
		add_submenu_page(
			'tools.php',
			__( 'Bluesky', 'bsky-for-wp' ),
			__( 'Bluesky', 'bsky-for-wp' ),
			'manage_options',
			'bsky-for-wp',
			function () {
				echo '<div id="bsky-for-wp"></div>';
			}
		);
	}
}
