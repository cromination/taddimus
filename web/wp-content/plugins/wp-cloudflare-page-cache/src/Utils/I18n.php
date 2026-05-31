<?php

namespace SPC\Utils;

class I18n {
	private static $dashboard_translations = null;

	/**
	 * Get translated string by key
	 *
	 * @param string $key
	 * @return string
	 */
	public static function get( $key ) {
		if ( self::$dashboard_translations === null ) {
			self::load_dashboard_translations();
		}

		return self::$dashboard_translations[ $key ] ?? $key;
	}

	public static function get_dashboard_translations() {
		if ( self::$dashboard_translations === null ) {
			self::load_dashboard_translations();
		}

		return self::$dashboard_translations;
	}

	/**
	 * Load dashboard translations array
	 */
	private static function load_dashboard_translations() {
		self::$dashboard_translations = [
			'success'                            => __( 'Success', 'wp-cloudflare-page-cache' ),
			'close'                              => __( 'Close', 'wp-cloudflare-page-cache' ),
			'error'                              => __( 'Error', 'wp-cloudflare-page-cache' ),
			'genericError'                       => __( 'An error occurred. Please reload the page and try again.', 'wp-cloudflare-page-cache' ),
			'cacheTestFetchFailed'               => __( 'Could not fetch the test URL from your browser. This usually means a Content Security Policy, privacy extension, or upstream error is blocking the request. Open the test URL directly in a new tab to check.', 'wp-cloudflare-page-cache' ),
			// translators: %s is 'wp-config.php' (file).
			'wpConfigNotWritable'                => sprintf( __( 'The file %s is not writable. Please add write permission to activate the fallback cache.', 'wp-cloudflare-page-cache' ), '<code>wp-config.php</code>' ),
			'wpContentNotWritable'               => sprintf(
				// translators: %s is 'wp-content' (directory).
				__( 'The directory %s is not writable. Please add write permission or you have to use the fallback cache with cURL.', 'wp-cloudflare-page-cache' ),
				'<code>wp-content</code> '
			),
			'warningJsSection'                   => sprintf(
				/* translators: %1$s: 'Optimization Type'. %2$s: 'General'. */
				__( '%1$s only works if the Disk Page Cache is enabled. Turn it on in the %2$s tab before these options can take effect.', 'wp-cloudflare-page-cache' ),
				__( 'Javascript optimizations', 'wp-cloudflare-page-cache' ),
				'<strong>' . __( 'General', 'wp-cloudflare-page-cache' ) . '</strong>'
			),
			'warningCSSSection'                  => sprintf(
				/* translators: %1$s: 'Optimization Type'. %2$s: 'General'. */
				__( '%1$s only works if the Disk Page Cache is enabled. Turn it on in the %2$s tab before these options can take effect.', 'wp-cloudflare-page-cache' ),
				__( 'CSS optimizations', 'wp-cloudflare-page-cache' ),
				'<strong>' . __( 'General', 'wp-cloudflare-page-cache' ) . '</strong>'
			),
			'excludePagesDescription'            => sprintf(
				/* translators: %s: '/' character. */
				__( 'Enter keywords (one per line) to be matched against URL paths. Use %s for home page.', 'wp-cloudflare-page-cache' ),
				'<code>/</code>'
			),
			'warningMediaSection'                => sprintf(
				/* translators: %s: 'General'. */
				__( 'Media optimizations only work if the Disk Page Cache is enabled. Turn it on in the %s tab before these options can take effect.', 'wp-cloudflare-page-cache' ),
				'<strong>' . __( 'General', 'wp-cloudflare-page-cache' ) . '</strong>'
			),
			'bypassLazyLoadDescription'          => sprintf(
				/* translators: %s: 0 */
				__( 'Indicate how many images at the top of each page should bypass lazy loading, ensuring they\'re instantly visible. Enter %s to not exclude any images from the lazy loading process.', 'wp-cloudflare-page-cache' ),
				'0'
			),
			'metaboxDisableUnusedCssLabel'       => __( 'Disable unused CSS for this page', 'wp-cloudflare-page-cache' ),
			'metaboxDisableUnusedCssDescription' => __( 'Exclude this page from Remove Unused CSS processing.', 'wp-cloudflare-page-cache' ),
			'metaboxDisableUnusedCssLockNotice'  => __( 'Controlled by global Remove Unused CSS page exclusions.', 'wp-cloudflare-page-cache' ),
			'metaboxDisableDelayJsLabel'         => __( 'Disable delay JS for this page', 'wp-cloudflare-page-cache' ),
			'metaboxDisableDelayJsDescription'   => __( 'Exclude this page from Delay JavaScript processing.', 'wp-cloudflare-page-cache' ),
			'metaboxDisableDelayJsLockNotice'    => __( 'Controlled by global Delay JavaScript page exclusions.', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileDisabled'           => __( 'Unused CSS profile: Disabled', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileFrontendOnly'       => __( 'Unused CSS profile: Frontend pages only', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileExcluded'           => __( 'Unused CSS profile: Excluded', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileUnknown'            => __( 'Unused CSS profile: Unknown', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileNotGenerated'       => __( 'Unused CSS profile: Not generated', 'wp-cloudflare-page-cache' ),
			/* translators: %s is a comma-separated list of missing device profile names. */
			'unusedCssProfilePending'            => __( 'Unused CSS profile: Pending (%s)', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileCacheMissing'       => __( 'Unused CSS profile: Ready (page cache missing)', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileReady'              => __( 'Unused CSS profile: Ready', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileApplied'            => __( 'Unused CSS profile: Applied', 'wp-cloudflare-page-cache' ),
			'unusedCssProfileRebuildPending'     => __( 'Unused CSS profile: Ready (cache rebuild pending)', 'wp-cloudflare-page-cache' ),
			'deviceMobile'                       => __( 'mobile', 'wp-cloudflare-page-cache' ),
			'deviceDesktop'                      => __( 'desktop', 'wp-cloudflare-page-cache' ),
			'ruleFixTitle'                       => __( 'Super Page Cache could not update the Cloudflare cache rule. Edge caching may not work until this is fixed.', 'wp-cloudflare-page-cache' ),
			// translators: %s: Enable Cloudflare CDN & Caching
			'ruleFixDescription'                 => sprintf( __( 'We can attempt to reset the rule automatically for you, or you could toggle the %s setting on and off to fix this.', 'wp-cloudflare-page-cache' ), sprintf( '<code>%s</code>', __( 'Enable Cloudflare CDN & Caching', 'wp-cloudflare-page-cache' ) ) ),
			'nonceRefreshTooManyActions'         => __( 'Too many actions requested.', 'wp-cloudflare-page-cache' ),
			'invalidDataFormat'                  => __( 'Invalid data format provided.', 'wp-cloudflare-page-cache' ),
			'cacheTags'                          => __( 'Cache tags', 'wp-cloudflare-page-cache' ),
			'clickToCopy'                        => __( 'Click to copy', 'wp-cloudflare-page-cache' ),
		];
	}
}
