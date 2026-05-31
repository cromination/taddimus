<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;
use SPC\Utils\Sanitization;

/**
 * Speculative loading module.
 *
 * On WP >= 6.8 drives WordPress Core's Speculation Rules API via the
 * `wp_speculation_rules_configuration` and `wp_speculation_rules_href_exclude_paths`
 * filters. The viewport toggle additionally renders an IntersectionObserver that
 * appends per-URL `<script type="speculationrules">` fragments as internal anchors
 * enter the viewport, so the prefetch path stays inside Core's speculation pipeline.
 * On WP < 6.8 falls back to the legacy instant.page hover prefetch and the inline
 * viewport-driven `<link rel="prefetch">` script.
 */
class Speculative_Loading implements Module_Interface {

	public const PREFETCH_MODE_OFF      = 'off';
	public const PREFETCH_MODE_HOVER    = 'hover';
	public const PREFETCH_MODE_VIEWPORT = 'viewport';

	/**
	 * Initialize the module.
	 *
	 * Core 6.8+ filters are registered unconditionally; on older WP versions Core never
	 * fires them, so they're inert. The legacy script enqueue path is gated by
	 * {@see self::is_available()} so we don't double up with Core speculation.
	 *
	 * @return void
	 */
	public function init() {
		if ( self::is_available() ) {
			add_filter( 'wp_speculation_rules_configuration', [ $this, 'configure_rules' ] );
			add_filter( 'wp_speculation_rules_href_exclude_paths', [ $this, 'merge_excluded_paths' ] );
			if ( self::PREFETCH_MODE_VIEWPORT === Settings_Store::get_instance()->get( Constants::SETTING_PREFETCH_URLS_MODE ) ) {
				add_action( 'wp_footer', [ $this, 'emit_viewport_observer_script' ] );
			}

			return;
		}

		add_action( 'wp_enqueue_scripts', [ $this, 'enqueue_legacy_scripts' ] );
		add_filter( 'script_loader_tag', [ $this, 'set_instantpage_module_attribute' ], 10, 2 );
		add_action( 'wp_footer', [ $this, 'emit_legacy_viewport_script' ], PHP_INT_MAX );
	}

	/**
	 * Whether to defer to Core's Speculation Rules API and suppress the legacy scripts.
	 *
	 * Filterable via `spc_speculative_loading_active` for tests and per-environment opt-out.
	 *
	 * @return bool
	 */
	public static function is_available() {

		$is_available = version_compare( get_bloginfo( 'version' ), '6.8', '>=' );

		return (bool) apply_filters( 'spc_speculative_loading_active', $is_available );
	}

	/**
	 * Map the prefetch mode onto a Core speculation config.
	 *
	 * Mapping:
	 *   off      → pass-through (Core's auto/auto default)
	 *   hover    → prefetch / moderate
	 *   viewport → null (suppress Core's default; observer handles viewport URLs)
	 *
	 * @param array<string, string>|null $config Incoming config from Core (may be null when defaulting to auto/auto).
	 * @return array<string, string>|null
	 */
	public function configure_rules( $config ) {
		$mode = Settings_Store::get_instance()->get( Constants::SETTING_PREFETCH_URLS_MODE );

		if ( self::PREFETCH_MODE_OFF === $mode ) {
			return $config;
		}

		if ( self::PREFETCH_MODE_VIEWPORT === $mode ) {
			return null;
		}

		return [
			'mode'      => 'prefetch',
			'eagerness' => 'moderate',
		];
	}

	/**
	 * Merge the plugin exclusion list into Core's href exclude paths, preserving wildcards
	 * and deduping.
	 *
	 * @param array<string> $paths Existing exclude paths from Core / other plugins.
	 * @return array<string>
	 */
	public function merge_excluded_paths( $paths ) {
		if ( ! is_array( $paths ) ) {
			$paths = [];
		}

		$user_excludes = Sanitization::sanitize_prefetch_excluded_urls_array(
			Settings_Store::get_instance()->get( Constants::SETTING_EXCLUDED_URLS, [] )
		);

		return array_values( array_unique( array_merge( $paths, $user_excludes ) ) );
	}

	/**
	 * Print the inline IntersectionObserver script that observes internal anchors and,
	 * as each one enters the viewport, queues its URL into a single rolling
	 * `<script type="speculationrules">` rule (debounced) — driving Core's Speculation
	 * Rules pipeline with `eagerness: immediate`.
	 *
	 * @return void
	 */
	public function emit_viewport_observer_script() {
		$excluded = $this->merge_excluded_paths( [] );
		?>
		<script id="spc-speculative-viewport">
			(function () {
				var origin = window.location.origin;
				var excluded = <?php echo wp_json_encode( $excluded, JSON_UNESCAPED_SLASHES ); ?>;
				var prefetchedUrls = new Set();
				var observedAnchors = new WeakSet();
				var ruleScript = null;
				var flushTimer = null;
				var FLUSH_DEBOUNCE_MS = 150;
				var INTERACTION_EVENTS = ['scroll', 'touchstart', 'keydown', 'pointerdown'];

				if (typeof IntersectionObserver === 'undefined') return;

				function wildcardCheck(str, rule) {
					var escapeRegex = function (value) {
						return value.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
					};
					return new RegExp("^" + rule.split("*").map(escapeRegex).join(".*") + "$").test(str);
				}

				function canPrefetchUrl(url) {
					var path = url.pathname + url.search;
					for (var i = 0; i < excluded.length; i++) {
						if (wildcardCheck(path, excluded[i])) return false;
					}
					return true;
				}

				function flushRule() {
					flushTimer = null;
					if (prefetchedUrls.size === 0) return;
					if (ruleScript) ruleScript.remove();
					ruleScript = document.createElement('script');
					ruleScript.type = 'speculationrules';
					ruleScript.textContent = JSON.stringify({
						prefetch: [{
							source: 'list',
							urls: Array.from(prefetchedUrls),
							eagerness: 'immediate'
						}]
					});
					document.head.appendChild(ruleScript);
				}

				function scheduleFlush() {
					if (flushTimer) clearTimeout(flushTimer);
					flushTimer = setTimeout(flushRule, FLUSH_DEBOUNCE_MS);
				}

				function prefetchUrl(href) {
					if (prefetchedUrls.has(href)) return;
					prefetchedUrls.add(href);
					scheduleFlush();
				}

				function isInternal(anchor) {
					if (!anchor.href) return false;
					try {
						var url = new URL(anchor.href, origin);
						return url.origin === origin && canPrefetchUrl(url);
					} catch (e) {
						return false;
					}
				}

				var observer = new IntersectionObserver(function (entries) {
					entries.forEach(function (entry) {
						if (!entry.isIntersecting) return;
						var href = entry.target.href.split('#')[0];
						prefetchUrl(href);
						observer.unobserve(entry.target);
					});
				}, { rootMargin: '0px', threshold: 0 });

				function observeAnchor(anchor) {
					if (!anchor || anchor.tagName !== 'A') return;
					if (observedAnchors.has(anchor)) return;
					if (!isInternal(anchor)) return;
					observedAnchors.add(anchor);
					observer.observe(anchor);
				}

				function observeAnchorsIn(root) {
					if (!root || typeof root.querySelectorAll !== 'function') return;
					root.querySelectorAll('a[href]').forEach(observeAnchor);
				}

				function start() {
					observeAnchorsIn(document);
					if (typeof MutationObserver === 'undefined') return;
					new MutationObserver(function (mutations) {
						mutations.forEach(function (mutation) {
							mutation.addedNodes.forEach(function (node) {
								if (node.nodeType !== 1) return;
								if (node.tagName === 'A') observeAnchor(node);
								else observeAnchorsIn(node);
							});
						});
					}).observe(document.body, { childList: true, subtree: true });
				}

				var started = false;
				function tryStart() {
					if (started) return;
					started = true;
					INTERACTION_EVENTS.forEach(function (ev) {
						window.removeEventListener(ev, tryStart);
					});
					start();
				}

				INTERACTION_EVENTS.forEach(function (ev) {
					window.addEventListener(ev, tryStart, { once: true, passive: true });
				});
			}());
		</script>
		<?php
	}

	/**
	 * Enqueue the legacy instant.page hover script and the inline viewport-helper script.
	 *
	 * @return void
	 */
	public function enqueue_legacy_scripts() {
		if ( $this->is_amp_or_customizer() ) {
			return;
		}

		$mode = Settings_Store::get_instance()->get( Constants::SETTING_PREFETCH_URLS_MODE );

		if ( self::PREFETCH_MODE_HOVER === $mode ) {
			wp_enqueue_script( 'swcfpc_instantpage', SWCFPC_PLUGIN_URL . 'assets/js/instantpage.min.js', [], SWCFPC_VERSION, true );
			return;
		}

		if ( self::PREFETCH_MODE_VIEWPORT === $mode ) {
			$this->enqueue_legacy_viewport_helpers();
		}
	}

	/**
	 * Emit the inline JS that defines the wildcard-aware exclusion check used by the
	 * viewport prefetch script.
	 *
	 * @return void
	 */
	private function enqueue_legacy_viewport_helpers() {
		$prefetch_urls_to_exclude = Sanitization::sanitize_prefetch_excluded_urls_array(
			Settings_Store::get_instance()->get( Constants::SETTING_EXCLUDED_URLS, [] )
		);

		wp_register_script( 'swcfpc_auto_prefetch_url', '', [], '', true );
		wp_enqueue_script( 'swcfpc_auto_prefetch_url' );

			ob_start();
		?>

			function swcfpc_wildcard_check(str, rule) {
			let escapeRegex = (str) => str.replace(/([.*+?^=!:${}()|\[\]\/\\])/g, "\\$1");
			return new RegExp("^" + rule.split("*").map(escapeRegex).join(".*") + "$").test(str);
			}

			function swcfpc_can_url_be_prefetched(href) {

			if( href.length == 0 )
			return false;

			if( href.startsWith("mailto:") )
			return false;

			if( href.startsWith("https://") )
			href = href.split("https://"+location.host)[1];
			else if( href.startsWith("http://") )
			href = href.split("http://"+location.host)[1];

			for( let i=0; i < swcfpc_prefetch_urls_to_exclude.length; i++) {

			if( swcfpc_wildcard_check(href, swcfpc_prefetch_urls_to_exclude[i]) )
			return false;

			}

			return true;

			}

			let swcfpc_prefetch_urls_to_exclude = <?php echo wp_json_encode( $prefetch_urls_to_exclude ); ?>;

			<?php

			$inline_js = ob_get_contents();
			ob_end_clean();

			wp_add_inline_script( 'swcfpc_auto_prefetch_url', $inline_js, 'before' );
	}

	/**
	 * Inject the viewport-driven prefetch script for anonymous visitors. The server-side
	 * timestamp (bumped after every purge via {@see self::bump_prefetch_timestamp()})
	 * invalidates the client's localStorage-cached prefetch list. Uses the
	 * `swcfpc_can_url_be_prefetched()` helper injected by
	 * {@see self::enqueue_legacy_viewport_helpers()}.
	 *
	 * @return void
	 */
	public function emit_legacy_viewport_script() {
		$settings = Settings_Store::get_instance();

		if ( ! $settings->is_cache_enabled() || is_user_logged_in() ) {
			return;
		}

		if ( self::PREFETCH_MODE_VIEWPORT !== $settings->get( Constants::SETTING_PREFETCH_URLS_MODE ) ) {
			return;
		}

		$timestamp = $settings->get( Constants::SETTING_PREFETCH_URLS_TIMESTAMP, time() );
		?>
		<script id="swcfpc">
			const swcfpc_prefetch_urls_timestamp_server = '<?php echo $timestamp; ?>';

			let swcfpc_prefetched_urls = localStorage.getItem("swcfpc_prefetched_urls");
			swcfpc_prefetched_urls = (swcfpc_prefetched_urls) ? JSON.parse(swcfpc_prefetched_urls) : [];

			let swcfpc_prefetch_urls_timestamp_client = localStorage.getItem("swcfpc_prefetch_urls_timestamp_client");

			if (swcfpc_prefetch_urls_timestamp_client == undefined || swcfpc_prefetch_urls_timestamp_client != swcfpc_prefetch_urls_timestamp_server) {
				swcfpc_prefetch_urls_timestamp_client = swcfpc_prefetch_urls_timestamp_server;
				swcfpc_prefetched_urls = new Array();
				localStorage.setItem("swcfpc_prefetched_urls", JSON.stringify(swcfpc_prefetched_urls));
				localStorage.setItem("swcfpc_prefetch_urls_timestamp_client", swcfpc_prefetch_urls_timestamp_client);
			}

			function swcfpc_element_is_in_viewport(element) {
				let bounding = element.getBoundingClientRect();
				return bounding.top >= 0 && bounding.left >= 0 && bounding.right <= (window.innerWidth || document.documentElement.clientWidth) && bounding.bottom <= (window.innerHeight || document.documentElement.clientHeight);
			}

			function swcfpc_prefetch_urls() {
				let comp = new RegExp(location.host);

				document.querySelectorAll("a").forEach((item) => {
				if (item.href) {
					let href = item.href.split("#")[0];

					if (swcfpc_can_url_be_prefetched(href) && swcfpc_prefetched_urls.includes(href) == false && comp.test(item.href) && swcfpc_element_is_in_viewport(item)) {
					swcfpc_prefetched_urls.push(href);

					let prefetch_element = document.createElement('link');
					prefetch_element.rel = "prefetch";
					prefetch_element.href = href;

					document.getElementsByTagName('body')[0].appendChild(prefetch_element);
					}
				}
				})

				localStorage.setItem("swcfpc_prefetched_urls", JSON.stringify(swcfpc_prefetched_urls));
			}

			window.addEventListener("load", function(event) {
				swcfpc_prefetch_urls();
			});

			window.addEventListener("scroll", function(event) {
				swcfpc_prefetch_urls();
			});
		</script>
		<?php
	}

	/**
	 * Bump the server-side prefetch timestamp so legacy clients refresh their
	 * localStorage prefetch lists on next page load. Inert on WP 6.8+ (Core handles
	 * invalidation), but remains correct if a site downgrades.
	 *
	 * @return int
	 */
	public static function bump_prefetch_timestamp() {
		$settings          = Settings_Store::get_instance();
		$current_timestamp = (int) $settings->get( Constants::SETTING_PREFETCH_URLS_TIMESTAMP, time() );

		if ( $current_timestamp < time() ) {
			$current_timestamp = time() + 120;
			$settings->set( Constants::SETTING_PREFETCH_URLS_TIMESTAMP, $current_timestamp )->save();

			Logger::log( 'speculative_loading::bump_prefetch_timestamp', "New timestamp generated: {$current_timestamp}", true );
		}

		return $current_timestamp;
	}

	/**
	 * Tag the instant.page script as a module per upstream guidance.
	 *
	 * @param string $tag    The script tag.
	 * @param string $handle The script handle.
	 * @return string
	 */
	public function set_instantpage_module_attribute( $tag, $handle ) {
		if ( empty( $tag ) || $handle !== 'swcfpc_instantpage' ) {
			return $tag;
		}

		if ( strpos( $tag, 'text/javascript' ) !== false ) {
			$tag = str_replace( 'text/javascript', 'module', $tag );
		} else {
			$tag = str_replace( ' src', ' type="module" src', $tag );
		}

		return $tag;
	}

	/**
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
	 * @return bool
	 */
	public static function is_legacy_engine() {
		return ! self::is_available() && self::PREFETCH_MODE_VIEWPORT === Settings_Store::get_instance()->get( Constants::SETTING_PREFETCH_URLS_MODE );
	}
}
