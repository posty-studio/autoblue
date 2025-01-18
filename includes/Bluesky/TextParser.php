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
	public const TAG_REGEX = '/(^|\s)[#＃]((?!\x{fe0f})[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*[^\d\s\p{P}\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]+[^\s\x{00AD}\x{2060}\x{200A}\x{200B}\x{200C}\x{200D}\x{20e2}]*)?/u';

	/**
	 * The Bluesky API client.
	 *
	 * @var API
	 */
	public $api_client;

	/**
	 * @param API|null $api_client The Bluesky API client.
	 */
	public function __construct( $api_client = null ) {
		$this->api_client = $api_client ?? new API();
	}

	/**
	 * @see https://atproto.com/specs/handle#handle-identifier-syntax
	 */
	private function is_valid_handle( string $handle ): bool {
		return preg_match( '/^([a-zA-Z0-9]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?\.)+[a-zA-Z]([a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?$/', $handle ) === 1;
	}

	private function is_valid_domain( string $str ): bool {
		$tlds = \Autoblue\Utils\TLD::get_valid_tlds();

		foreach ( $tlds as $tld ) {
			$i = strrpos( $str, $tld );

			if ( $i === false ) {
				continue;
			}

			if ( $str[ $i - 1 ] === '.' && $i === strlen( $str ) - strlen( $tld ) ) {
				return true;
			}
		}

		return false;
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

			// Probably not a domain.
			if ( ! $this->is_valid_domain( $handle ) && substr( $handle, -5 ) !== '.test' ) {
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
		$spans  = [];
		$offset = 0;

		while ( preg_match( self::URL_REGEX, $text, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			$uri = $match[1][0];

			// If it doesn't start with http, ensure it's a valid domain and add https://
			if ( strpos( $uri, 'http' ) !== 0 ) {
				// Extract domain from URI
				if ( preg_match( '/^(?:www\.)?([^\/]+)/', $uri, $domain_match ) ) {
					$domain = $domain_match[1];
					if ( ! $this->is_valid_domain( $domain ) ) {
						$offset = $match[0][1] + 1;
						continue;
					}
					$uri = 'https://' . $uri;
				} else {
					$offset = $match[0][1] + 1;
					continue;
				}
			}

			$start = $match[1][1];
			$end   = $start + strlen( $match[1][0] );

			// Strip ending punctuation
			if ( preg_match( '/[.,;:!?]$/', $uri ) ) {
				$uri = substr( $uri, 0, -1 );
				--$end;
			}

			// Handle closing parenthesis
			if ( substr( $uri, -1 ) === ')' && strpos( $uri, '(' ) === false ) {
				$uri = substr( $uri, 0, -1 );
				--$end;
			}

			$spans[] = [
				'start' => mb_strlen( substr( $text, 0, $start ), '8bit' ),
				'end'   => mb_strlen( substr( $text, 0, $end ), '8bit' ),
				'url'   => $uri,
			];

			$offset = $match[0][1] + 1;
		}

		return $spans;
	}

	/**
	 * @return array<int,array<string,mixed>> An array of facets representing tags.
	 */
	public function parse_tags( string $text ): array {
		$facets = [];
		$offset = 0;

		while ( preg_match( self::TAG_REGEX, $text, $match, PREG_OFFSET_CAPTURE, $offset ) ) {
			$leading = $match[1][0]; // The space or start of string
			$tag     = $match[2][0] ?? ''; // The tag content without #

			if ( empty( $tag ) ) {
				$offset = $match[0][1] + 1;
				continue;
			}

			// Strip ending punctuation and spaces.
			$tag = trim( $tag );
			$tag = preg_replace( '/[.,!?:;]*$/', '', $tag );

			if ( strlen( $tag ) === 0 || strlen( $tag ) > 64 ) {
				$offset = $match[0][1] + 1;
				continue;
			}

			$index = $match[0][1] + strlen( $leading ); // Match index + leading space length

			$facets[] = [
				'start' => mb_strlen( substr( $text, 0, $index ), '8bit' ),
				'end'   => mb_strlen( substr( $text, 0, $index + strlen( $tag ) + 1 ), '8bit' ), // +1 for #
				'tag'   => $tag,
			];

			$offset = $match[0][1] + 1; // Move past current match
		}

		return $facets;
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
