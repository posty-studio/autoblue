<?php

namespace Autoblue\CLI;

class Commands {
	public function register_commands() {
		if ( class_exists( '\\WP_CLI_Command' ) ) {
			\WP_CLI::add_command( 'autoblue logs', Logs::class );
		}
	}
}
