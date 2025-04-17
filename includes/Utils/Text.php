<?php

namespace Autoblue\Utils;

class Text {
	/**
	 * Trim to a maximum grapheme length and add an ellipsis if the string is longer.
	 *
	 * @param string $text The text to trim.
	 * @param int    $max_graphemes The maximum grapheme length of the text.
	 * @return string The trimmed text.
	 */
	public function trim_text( string $text, int $max_graphemes ): string {
		$grapheme_length = grapheme_strlen( $text );

		if ( $grapheme_length <= $max_graphemes ) {
			return $text;
		}

		$trimmed_string = grapheme_substr( $text, 0, $max_graphemes );

		if ( $trimmed_string === false ) {
			return '';
		}

		$last_space = grapheme_strrpos( $trimmed_string, ' ' );

		if ( $last_space !== false ) {
			$trimmed_string = grapheme_substr( $trimmed_string, 0, $last_space );
		}

		$trimmed_string .= '…';

		return $trimmed_string;
	}
}
