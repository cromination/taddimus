<?php
/**
 * Patterns Compatibility.
 *
 * @package hestia
 */

/**
 * Class Patterns
 *
 * @package hestia
 */
class Hestia_Patterns {
	/**
	 * Define list of the patterns to load.
	 *
	 * @var string[] Patterns list.
	 */
	private $patterns = array(
		'call-to-action',
		'content-1',
		'features-1',
		'features-2',
		'features-3',
		'hero-1',
		'hero-2',
		'pricing-1',
		'pricing-2',
		'stats',
		'team-1',
		'team-2',
		'testimonials-1',
		'testimonials-2',
	);

	/**
	 * Load patterns.
	 */
	public function define_patterns() {
		if ( ! function_exists( 'register_block_pattern' ) ) {
			return;
		}

		if ( ! function_exists( 'register_block_pattern_category' ) ) {
			return;
		}

		register_block_pattern_category(
			'hestia',
			array( 'label' => esc_html( $this->get_theme_name() ) )
		);

		foreach ( $this->patterns as $pattern ) {
			register_block_pattern(
				'hestia/' . $pattern,
				require __DIR__ . '/block-patterns/' . $pattern . '.php'
			);
		}
	}

	/**
	 * Get theme name.
	 *
	 * @return string
	 */
	private function get_theme_name() {
		$theme = wp_get_theme();

		return apply_filters( 'ti_wl_theme_name', $theme->get( 'Name' ) );
	}
}
