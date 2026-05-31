<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Loader;
use SPC\Services\Settings_Store;
use SPC\Utils\Sanitization;

/**
 * Frontend module.
 */
class Frontend implements Module_Interface {
	private const BG_LAZYLOADED_CLASS = 'spc-bg-lazyloaded';

	const LAZY_LOAD_BEHAVIOUR_ALL   = 'all';
	const LAZY_LOAD_BEHAVIOUR_FIXED = 'fixed';

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {

		add_filter( 'wp_resource_hints', [ $this, 'add_external_domain_hints' ], 10, 2 );

		if ( ! Loader::is_cached_page() ) {
			return;
		}

		add_action( 'wp_print_scripts', [ $this, 'add_bg_lazyload_script' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_frontend' ] );
	}

	/**
	 * Add background lazy loading.
	 *
	 * @return void
	 */
	public function add_bg_lazyload_script() {
		if ( is_admin() || ! $this->is_background_lazyload_enabled() ) {
			return;
		}

		$bg_selectors = Sanitization::sanitize_background_selectors_array( $this->get_lazyload_background_selectors() );

		?>

			<script type="text/javascript" id="spc-lazy-bg">
				(function () {
				const loadedClass = '<?php echo esc_js( self::BG_LAZYLOADED_CLASS ); ?>';
				const bgSelectors = <?php echo wp_json_encode( $bg_selectors ); ?>;

			function observerCallback(entries, observer) {
				entries.forEach(function (entry) {
				if (!entry.isIntersecting) return;

				if (entry.target.classList.contains(loadedClass)) return;

				entry.target.classList.add(loadedClass);
				observer.unobserve(entry.target);
				});
			}

				const intersectionObserver = new IntersectionObserver(observerCallback, {
					root: null,
					rootMargin: "150px 0px 500px",
					threshold: [0.1, 0.3, 0.5, 0.6, 0.8, 1],
				});

				function start() {
					bgSelectors.forEach(function (selector) {
						if (typeof selector !== 'string' || selector.length === 0) return;

						try {
							document.querySelectorAll(selector).forEach(function (el) {
								intersectionObserver.observe(el);
							});
						} catch (error) {
							// Skip invalid selectors so one bad value cannot break all lazy-load processing.
						}
					});
				}

				document.addEventListener('DOMContentLoaded', start);
				}());
			</script>
		<?php
	}

	/**
	 * Enqueue scripts & styles.
	 *
	 * @return void
	 */
	public function enqueue_frontend() {
		if ( (int) Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOADING ) !== 1 ) {
			return;
		}

		wp_enqueue_script( 'spc-lazysizes', SWCFPC_PLUGIN_URL . 'assets/js/lazysizes.min.js', [], '5.3.2' );

		if ( ! $this->is_background_lazyload_enabled() ) {
			return;
		}

		wp_register_style( 'spc_bg_lazy', false );
		wp_enqueue_style( 'spc_bg_lazy' );
		$lazyload_css = $this->get_background_lazy_css();
		if ( empty( $lazyload_css ) ) {
			remove_action( 'wp_print_scripts', [ $this, 'add_bg_lazyload_script' ] );
		}
		wp_add_inline_style( 'spc_bg_lazy', $lazyload_css );
	}

	/**
	 * Add style classes for lazy loading background images.
	 *
	 * @return string
	 */
	private function get_background_lazy_css() {
		$selectors = $this->get_lazyload_background_selectors();

		if ( empty( $selectors ) ) {
			return '';
		}

		$formatted_selectors = array_map(
			function ( $selector ) {
				return sprintf( 'html %s:not(.%s)', $selector, self::BG_LAZYLOADED_CLASS );
			},
			$selectors
		);

		return apply_filters( 'spc_lazyload_bg_lazyload_css', strip_tags( implode( ",\n", $formatted_selectors ) . ' { background-image: none !important; }' ), $selectors );
	}

	/**
	 * Get CSS selectors for background lazy loading.
	 *
	 * @return string[]
	 */
	public function get_lazyload_background_selectors() {
		$custom_selectors = Sanitization::sanitize_background_selectors_array(
			Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOAD_BG_SELECTORS, [] )
		);

		return array_values(
			array_unique(
				array_merge(
					$custom_selectors,
					Constants::DEFAULT_BG_LAZYLOAD_SELECTORS,
					$this->get_compatibilities_lazyload_background_selectors()
				)
			)
		);
	}

	/**
	 * Check if background lazy loading is enabled.
	 *
	 * @return bool
	 */
	public function is_background_lazyload_enabled() {
		return (int) Settings_Store::get_instance()->get( Constants::SETTING_LAZY_LOAD_BG ) === 1;
	}

	public static function get_lazyload_behaviours() {
		return apply_filters(
			'spc_lazyload_behaviours',
			[
				self::LAZY_LOAD_BEHAVIOUR_ALL   => true,
				self::LAZY_LOAD_BEHAVIOUR_FIXED => true,
			]
		);
	}
	/**
	 * Get compatibility selectors for other plugins.
	 *
	 * @return string[]
	 */
	private function get_compatibilities_lazyload_background_selectors() {
		include_once ABSPATH . 'wp-admin/includes/plugin.php';

		// Keep only the selectors for active plugins.
		$active_compatibilities = array_filter( Constants::COMPAT_BG_LAZYLOAD_SELECTORS, 'is_plugin_active', ARRAY_FILTER_USE_KEY );

		if ( empty( $active_compatibilities ) ) {
			return [];
		}

		// Merge all selectors into a single array & ensure unique selectors.
		// @phpstan-ignore-next-line - false positive, as array is checked above
		return array_unique( array_merge( ...array_values( $active_compatibilities ) ) );
	}

	/**
	 * Check if we're on an AMP page or in the customizer.
	 *
	 * @return bool
	 */
	private function is_amp_or_customizer() {
		return (
			( function_exists( 'amp_is_request' ) && amp_is_request() ) ||
			( function_exists( 'ampforwp_is_amp_endpoint' ) && ampforwp_is_amp_endpoint() ) ||
			is_customize_preview()
		);
	}

	/**
	 * Add user-configured external domains to the wp_resource_hints filter.
	 *
	 * Renders dns-prefetch entries as `//host` and preconnect entries as `https://host`
	 *
	 * @param array<int, string|array<string, string>> $hints         Existing resource hint entries.
	 * @param string                                   $relation_type Relation type being filtered.
	 *
	 * @return array<int, string|array<string, string>>
	 */
	public function add_external_domain_hints( $hints, $relation_type ) {
		if ( $relation_type !== 'dns-prefetch' && $relation_type !== 'preconnect' ) {
			return $hints;
		}

		if ( is_admin() || $this->is_amp_or_customizer() ) {
			return $hints;
		}

		$key     = $relation_type === 'dns-prefetch' ? Constants::SETTING_DNS_PREFETCH_DOMAINS : Constants::SETTING_PRECONNECT_DOMAINS;
		$prefix  = $relation_type === 'dns-prefetch' ? '//' : 'https://';
		$raw     = Settings_Store::get_instance()->get( $key );
		$domains = array_filter( explode( "\n", Sanitization::sanitize_prefetch_domains( $raw ) ) );

		foreach ( $domains as $host ) {
			$hints[] = $prefix . $host;
		}

		return $hints;
	}
}
