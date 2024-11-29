<?php

namespace Autoblue;

class Blocks {
	public function register_hooks(): void {
		// add_action( 'init', [ $this, 'register' ] );
	}

	public function register(): void {
		if ( file_exists( AUTOBLUE_BLOCKS_PATH ) ) {
			$blocks = glob( AUTOBLUE_BLOCKS_PATH . '*/block.json' );

			if ( ! is_array( $blocks ) ) {
				return;
			}

			foreach ( $blocks as $block ) {
				register_block_type( dirname( $block ) );
			}
		}
	}
}
