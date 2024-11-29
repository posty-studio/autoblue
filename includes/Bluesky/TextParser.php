<?php

namespace Autoblue\Bluesky;

/**
 * Parse text for mentions, URLs, and hashtags.
 *
 * This is a fairly naive implementation. Bluesky recommends using one of the supported
 * libraries instead, but there's no PHP version so this will have to do for now.
 *
 * @see https://docs.bsky.app/docs/advanced-guides/post-richtext#producing-facets
 */
class TextParser {
	public const MENTION_REGEX = '/(^|\s|\()(@)([a-zA-Z0-9.-]+)(\b)/u';
	public const URL_REGEX     = '/[$|\W](https?:\/\/(www\.)?[-a-zA-Z0-9@:%._\+~#=]{1,256}\.[a-zA-Z0-9()]{1,6}\b([-a-zA-Z0-9()@:%_\+.~#?&\/\/=]*[-a-zA-Z0-9@%_\+~#\/\/=])?)/u';
	// Hashtag regex pattern - matches tags that:
	// - Start with # and aren't followed by a number.
	// - Can contain letters, numbers, underscores.
	// - Excludes trailing punctuation.
	public const TAG_REGEX = '/(?:^|\s)(#[^\d\s]\S*?)(?:\s|$|[!.,;?])/u';

	/**
	 * The Bluesky API client.
	 *
	 * @var API
	 */
	public $api_client;

	public function __construct() {
		$this->api_client = new API();
	}

	/**
	 * @see https://atproto.com/specs/handle#handle-identifier-syntax
	 */
	private function is_valid_handle( string $handle ): bool {
		return preg_match( '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $handle ) === 1;
	}

	/**
	 * @return array<int,array<string,mixed>> An array of facets representing mentions.
	 */
	public function parse_mentions( string $text ): array {
		$spans = [];
		preg_match_all( self::MENTION_REGEX, $text, $matches, PREG_OFFSET_CAPTURE );

		foreach ( $matches[3] as $i => $match ) {
			$handle = $match[0];

			// Skip if handle doesn't match ATProto spec.
			if ( ! $this->is_valid_handle( $handle ) ) {
				continue;
			}

			$start  = $matches[0][ $i ][1];
			$length = strlen( $matches[0][ $i ][0] );

			$spans[] = [
				'start'  => mb_strlen( substr( $text, 0, $start ), '8bit' ),
				'end'    => mb_strlen( substr( $text, 0, $start + $length ), '8bit' ),
				'handle' => $handle,
			];
		}

		return $spans;
	}

	/**
	 * @return array<int,array<string,mixed>> An array of facets representing URLs.
	 */
	public function parse_urls( string $text ): array {
		$spans = [];
		preg_match_all( self::URL_REGEX, $text, $matches, PREG_OFFSET_CAPTURE );

		foreach ( $matches[1] as $match ) {
			$spans[] = [
				'start' => mb_strlen( substr( $text, 0, $match[1] ), '8bit' ),
				'end'   => mb_strlen( substr( $text, 0, $match[1] + strlen( $match[0] ) ), '8bit' ),
				'url'   => $match[0],
			];
		}

		return $spans;
	}

	/**
	 * @return array<int,array<string,mixed>> An array of facets representing tags.
	 */
	public function parse_tags( string $text ): array {
		$spans = [];
		preg_match_all( self::TAG_REGEX, $text, $matches, PREG_OFFSET_CAPTURE );

		foreach ( $matches[1] as $match ) {
			$tag = $match[0];
			// Clean up the tag.
			$tag = trim( $tag );
			$tag = preg_replace( '/\p{P}+$/u', '', $tag );

			if ( empty( $tag ) ) {
				continue;
			}

			// Skip if tag is too long (over 64 chars including #).
			if ( mb_strlen( $tag ) > 66 ) {
				continue;
			}

			$spans[] = [
				'start' => mb_strlen( substr( $text, 0, $match[1] ), '8bit' ),
				'end'   => mb_strlen( substr( $text, 0, $match[1] + strlen( $tag ) ), '8bit' ),
				'tag'   => ltrim( $tag, '#' ),
			];
		}

		return $spans;
	}

	/**
	 * @return array<int,array<string,mixed>> An array of facets representing mentions, URLs, and tags.
	 */
	public function parse_facets( string $text ): array {
		$facets = [];

		foreach ( $this->parse_mentions( $text ) as $mention ) {
			$did = $this->api_client->get_did_for_handle( $mention['handle'] );

			if ( ! $did ) {
				continue;
			}

			$facets[] = [
				'index'    => [
					'byteStart' => $mention['start'],
					'byteEnd'   => $mention['end'],
				],
				'features' => [
					[
						'$type' => 'app.bsky.richtext.facet#mention',
						'did'   => $did,
					],
				],
			];
		}

		foreach ( $this->parse_urls( $text ) as $url ) {
			$facets[] = [
				'index'    => [
					'byteStart' => $url['start'],
					'byteEnd'   => $url['end'],
				],
				'features' => [
					[
						'$type' => 'app.bsky.richtext.facet#link',
						'uri'   => $url['url'],
					],
				],
			];
		}

		foreach ( $this->parse_tags( $text ) as $tag ) {
			$facets[] = [
				'index'    => [
					'byteStart' => $tag['start'],
					'byteEnd'   => $tag['end'],
				],
				'features' => [
					[
						'$type' => 'app.bsky.richtext.facet#tag',
						'tag'   => $tag['tag'],
					],
				],
			];
		}

		return $facets;
	}
}