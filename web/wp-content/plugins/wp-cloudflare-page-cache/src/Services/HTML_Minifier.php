<?php

namespace SPC\Services;

class HTML_Minifier {
	/**
	 * Inline tags where inter-tag whitespace may affect rendered text spacing.
	 */
	private const INLINE_TAGS = [
		'a',
		'abbr',
		'acronym',
		'audio',
		'b',
		'bdi',
		'bdo',
		'big',
		'button',
		'canvas',
		'cite',
		'code',
		'data',
		'datalist',
		'del',
		'dfn',
		'em',
		'embed',
		'i',
		'iframe',
		'img',
		'input',
		'ins',
		'kbd',
		'label',
		'map',
		'mark',
		'meter',
		'noscript',
		'object',
		'output',
		'picture',
		'progress',
		'q',
		'ruby',
		's',
		'samp',
		'script',
		'select',
		'slot',
		'small',
		'span',
		'strong',
		'sub',
		'sup',
		'svg',
		'textarea',
		'time',
		'u',
		'var',
		'video',
		'wbr',
	];

	/**
	 * Comments matching these markers should survive minification.
	 */
	private const PRESERVED_COMMENT_MARKERS = [
		'noindex',
		'/noindex',
	];

	/**
	 * Regex fragment matching a protected-block placeholder (see generate_placeholder()).
	 */
	private const PLACEHOLDER_PATTERN = '%%SPC_HTML_MINIFIER_BLOCK_[a-f0-9]+%%';

	/**
	 * Minify cached HTML conservatively.
	 *
	 * Removes removable comments and indentation-style whitespace between tags,
	 * while preserving raw blocks and meaningful single spaces between inline tags.
	 *
	 * @param string $html HTML content.
	 *
	 * @return string
	 */
	public function minify( string $html ): string {
		if ( $html === '' ) {
			return $html;
		}

		$original_html             = $html;
		$preserved_comment_markers = $this->get_preserved_comment_markers();

		$html = str_replace( [ "\r\n", "\r" ], "\n", $html );

		$protected_blocks = [];
		$html             = preg_replace_callback(
			'#<(pre|code|textarea|script|style)\b[^>]*>.*?</\1>#is',
			function ( array $matches ) use ( &$protected_blocks, $html ): string {
				$placeholder                      = $this->generate_placeholder( $html, $protected_blocks );
				$protected_blocks[ $placeholder ] = $matches[0];

				return $placeholder;
			},
			$html
		);

		if ( $html === null ) {
			return $original_html;
		}

		$html = preg_replace_callback(
			'#<!--(.*?)-->#s',
			function ( array $matches ) use ( $preserved_comment_markers ): string {
				$body = trim( $matches[1] );

				if ( in_array( strtolower( $body ), $preserved_comment_markers, true ) ) {
					return $matches[0];
				}

				if ( $this->is_conditional_comment( $body ) ) {
					return $matches[0];
				}

				if ( $this->is_spc_debug_comment( $body ) ) {
					return $matches[0];
				}

				return '';
			},
			$html
		);

		if ( $html === null ) {
			return $original_html;
		}

		// Remove formatting whitespace between markup tokens, while preserving
		// a single visible space between adjacent inline elements. Uses a lookahead
		// for the right-hand token so chains like `<a>\n<!--c-->\n<b>` collapse on
		// every gap, not just the first.
		$token_alternation = '<!--.*?-->|<\/?[a-z0-9:-]+\b[^>]*>|' . self::PLACEHOLDER_PATTERN;
		$html              = preg_replace_callback(
			'/(' . $token_alternation . ')\s+(?=(' . $token_alternation . '))/is',
			function ( array $matches ): string {
				if ( $this->should_preserve_inter_tag_space( $matches[1], $matches[2] ) ) {
					return $matches[1] . ' ';
				}

				return $matches[1];
			},
			$html
		);

		if ( $html === null ) {
			return $original_html;
		}

		if ( ! empty( $protected_blocks ) ) {
			$html = strtr( $html, $protected_blocks );
		}

		return trim( $html );
	}

	/**
	 * Determine whether an HTML comment is a conditional comment.
	 *
	 * @param string $body Comment body without delimiters.
	 *
	 * @return bool
	 */
	private function is_conditional_comment( string $body ): bool {
		// Downlevel-revealed openers like `<!--[if !IE]><!-->` parse with body
		// `[if !IE]><!` because the regex's `-->` close consumes the trailing `--`.
		return (bool) preg_match( '/^\[if\s[^\]]+\]>.*<!\[endif\]$/is', $body )
			|| (bool) preg_match( '/^\[if\s[^\]]+\]>(?:<!)?$/i', $body )
			|| (bool) preg_match( '/^<!\[endif\]$/i', $body );
	}

	/**
	 * Determine whether a comment is an SPC fallback-cache debug marker emitted under WP_DEBUG.
	 *
	 * @param string $body Comment body without delimiters.
	 *
	 * @return bool
	 */
	private function is_spc_debug_comment( string $body ): bool {
		return strpos( trim( $body ), 'Page retrieved from Super Page Cache' ) === 0;
	}

	/**
	 * Determine whether a visible space should remain between two markup tokens.
	 *
	 * @param string $left_token Left-side markup token.
	 * @param string $right_token Right-side markup token.
	 *
	 * @return bool
	 */
	private function should_preserve_inter_tag_space( string $left_token, string $right_token ): bool {
		// Placeholders stand in for raw blocks (script/style/pre/code/textarea) — no visible
		// inline text on either side, so adjacent whitespace is purely formatting.
		if ( $this->is_placeholder( $left_token ) || $this->is_placeholder( $right_token ) ) {
			return false;
		}

		$left_tag  = $this->extract_tag_name( $left_token );
		$right_tag = $this->extract_tag_name( $right_token );

		if ( $left_tag === null || $right_tag === null ) {
			return false;
		}

		return in_array( $left_tag, self::INLINE_TAGS, true ) && in_array( $right_tag, self::INLINE_TAGS, true );
	}

	/**
	 * Determine whether a token is a protected-block placeholder.
	 *
	 * @param string $token Markup token.
	 *
	 * @return bool
	 */
	private function is_placeholder( string $token ): bool {
		return (bool) preg_match( '/^' . self::PLACEHOLDER_PATTERN . '$/', $token );
	}

	/**
	 * Extract the lowercase HTML tag name from a tag token.
	 *
	 * @param string $token Markup token.
	 *
	 * @return string|null
	 */
	private function extract_tag_name( string $token ): ?string {
		if ( preg_match( '/^<\/?\s*([a-z0-9:-]+)/i', $token, $matches ) !== 1 ) {
			return null;
		}

		return strtolower( $matches[1] );
	}

	/**
	 * Get preserved comment markers, allowing extensions to add custom markers.
	 *
	 * @return string[]
	 */
	private function get_preserved_comment_markers(): array {
		$markers = apply_filters( 'spc_html_minifier_preserved_comment_markers', self::PRESERVED_COMMENT_MARKERS );

		if ( ! is_array( $markers ) ) {
			return self::PRESERVED_COMMENT_MARKERS;
		}

		$markers = array_filter(
			array_map(
				function ( $marker ) {
					return is_string( $marker ) ? strtolower( trim( $marker ) ) : '';
				},
				$markers
			)
		);

		return array_values( array_unique( $markers ) );
	}

	/**
	 * Generate a collision-safe placeholder for protected blocks.
	 *
	 * @param string $html Source HTML.
	 * @param array<string, string> $protected_blocks Existing placeholders.
	 *
	 * @return string
	 */
	private function generate_placeholder( string $html, array $protected_blocks ): string {
		do {
			$placeholder = '%%SPC_HTML_MINIFIER_BLOCK_' . md5( uniqid( '', true ) ) . '%%';
		} while ( strpos( $html, $placeholder ) !== false || isset( $protected_blocks[ $placeholder ] ) );

		return $placeholder;
	}
}
