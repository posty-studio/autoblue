<?php

namespace Autoblue;

class Blocks {
	public function register_hooks() {
		// add_action( 'init', [ $this, 'register' ] );
	}

	public function register() {
		if ( file_exists( AUTOBLUE_BLOCKS_PATH ) ) {
			$blocks = glob( AUTOBLUE_BLOCKS_PATH . '*/block.json' );

			foreach ( $blocks as $block ) {
				register_block_type( dirname( $block ) );
			}
		}
	}
}
