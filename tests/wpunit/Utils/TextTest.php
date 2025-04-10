<?php

namespace Tests\Text;

use lucatume\WPBrowser\TestCase\WPTestCase;
use Autoblue\Utils\Text;

class TextTest extends WPTestCase {
	private Text $text;

	protected function setUp(): void {
		$this->text = new Text();
	}

	public function test_trim_if_shorter_than_max_graphemes() {
		$input  = 'Short text';
		$result = $this->text->trim_text( $input, 20 );
		$this->assertEquals( $input, $result, 'Text should not be trimmed if shorter than max graphemes.' );
	}

	public function test_trim_text_if_exact_length() {
		$input         = 'Exact length here';
		$max_graphemes = grapheme_strlen( $input );
		$result        = $this->text->trim_text( $input, $max_graphemes );
		$this->assertEquals( $input, $result, 'Text should not be trimmed if exactly equal to max graphemes.' );
	}

	public function test_trim_text_with_long_text_and_space() {
		$input    = 'This is a very long text that needs trimming';
		$result   = $this->text->trim_text( $input, 10 );
		$expected = 'This is a…';
		$this->assertEquals( $expected, $result, 'Text should be trimmed on the last space before exceeding max graphemes.' );
	}

	public function test_trim_text_without_space() {
		$input    = 'Supercalifragilisticexpialidocious';
		$result   = $this->text->trim_text( $input, 10 );
		$expected = 'Supercalif…';
		$this->assertEquals( $expected, $result, 'Text should be trimmed to max graphemes without space.' );
	}

	public function test_trim_text_with_zero_max_graphemes() {
		$input    = 'No text should show';
		$result   = $this->text->trim_text( $input, 0 );
		$expected = '…';
		$this->assertEquals( $expected, $result, 'Only ellipsis should be returned when max graphemes is zero.' );
	}

	public function test_trim_text_with_grapheme_clusters() {
		$input    = '🙂👌👏👋👏';
		$result   = $this->text->trim_text( $input, 2 );
		$expected = '🙂👌…';
		$this->assertEquals( $expected, $result, 'Text should consider grapheme clusters correctly.' );
	}
}
