<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );
define( 'SWCFPC_ADVANCED_CACHE', true );
define( 'SWCFPC_FALLBACK_WARMER_UA', 'ua-swcfpc-fc' );
define( 'SWCFPC_FALLBACK_REFRESH_UA', 'ua-swcfpc-fc-refresh' );
define( 'SWCFPC_FALLBACK_REFRESH_LOCK_TTL', 30 );

if ( ! swcfpc_is_this_page_cachable() ) {
	return;
}

if ( isset( $_SERVER['REQUEST_METHOD'] ) && strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) != 0 ) {
	return;
}

$swcfpc_cache_base_path            = swcfpc_get_cache_base_path();

if ( '' === $swcfpc_cache_base_path ) {
	return;
}

$swcfpc_fallback_cache_config_path = $swcfpc_cache_base_path . '/';
$swcfpc_fallback_cache_path        = $swcfpc_cache_base_path . '/fallback_cache/';

if ( ! file_exists( "{$swcfpc_fallback_cache_config_path}main_config.php" ) ) {
	return;
}

require "{$swcfpc_fallback_cache_config_path}main_config.php";

if ( ! isset( $swcfpc_config ) || ! is_string( $swcfpc_config ) ) {
	return;
}

$swcfpc_config = json_decode( stripslashes( $swcfpc_config ), true );

if ( ! is_array( $swcfpc_config ) ) {
	return;
}

$swcfpc_addon_path = WP_CONTENT_DIR . '/wp-cloudflare-super-page-cache/advanced-cache-pro-addon.php';

if ( file_exists( $swcfpc_addon_path ) ) {
	require $swcfpc_addon_path;
}

/**
 *  Super-Page-Cache rolling HIT / MISS sampler
 *  Drop into advanced-cache.php (runs on every request).
 *
 *  wp-config.php can override behaviour with one constant:
 *
 *      define( 'SPC_METRICS_CONFIG', [
 *          'enabled'  => true,   // collect stats?   (default: true)
 *          'window'   => 24,     // hours to retain  (default: 24)
 *          'sampling' => 10,     // % requests used  (default: 10)
 *      ] );
 *
 */
$__spc_cfg = array_merge(
	['enabled' => true, 'window' => 24, 'sampling' => 10],
	defined('SPC_METRICS_CONFIG') && is_array(SPC_METRICS_CONFIG) ? SPC_METRICS_CONFIG : []
);

$__spc_cfg['window']   = max(1, (int) $__spc_cfg['window']);
$__spc_cfg['sampling'] = min(100, max(1, (int) $__spc_cfg['sampling']));
$__spc_ttl = $__spc_cfg['window'] * HOUR_IN_SECONDS;

$__spc_backend = (function_exists('apcu_inc') ? 'apcu' : 'file');

if ($__spc_backend === 'file') {
	define(
		'SPC_METRICS_DIR',
		$swcfpc_cache_base_path . '/metrics'
	);
	if (! is_dir(SPC_METRICS_DIR)) @mkdir(SPC_METRICS_DIR, 0755, true);
}

$swcfpc_fallback_cache_key = swcfpc_fallback_cache_get_current_page_cache_key();

if ( swcfpc_fallback_cache_is_url_to_exclude() ) {
	return;
}

if ( swcfpc_fallback_cache_is_cookie_to_exclude() ) {
	return;
}

if ( ! swcfpc_is_fallback_refresh_request() ) {
	$swcfpc_entry_state = swcfpc_fallback_cache_get_entry_state( $swcfpc_fallback_cache_key, $swcfpc_fallback_cache_path );
} else {
	$swcfpc_entry_state = 'missing';
}

if ( in_array( $swcfpc_entry_state, [ 'fresh', 'stale' ], true ) ) {

	$cache_controller = swcfpc_get_cache_control_value( $swcfpc_config );
	$stored_headers   = swcfpc_fallback_cache_get_stored_headers( $swcfpc_fallback_cache_path, $swcfpc_fallback_cache_key );

	if ( (int) $swcfpc_config['cf_maxage'] > 0 ) {
		header_remove( 'Set-Cookie' );
	}

	header_remove( 'Pragma' );
	header_remove( 'Expires' );
	header_remove( 'Cache-Control' );
	header_remove( 'X-WP-CF-Super-Cache-Disabled-Reason' );
	header( "Cache-Control: {$cache_controller}" );
	header( 'X-WP-SPC-Disk-Cache: ' . ( 'fresh' === $swcfpc_entry_state ? 'HIT' : strtoupper( $swcfpc_entry_state ) ) );
	header( 'X-WP-CF-Super-Cache-Active: 1' );
	header( "X-WP-CF-Super-Cache-Cache-Control: {$cache_controller}" );

	if ($stored_headers) {
		foreach ( $stored_headers as $single_header ) {
			header( $single_header, false );
		}
	}

	if ( ! empty( $swcfpc_config['cache_tags'] ) && function_exists( 'swcfpc_pro_emit_cache_tag_header' ) ) {
		swcfpc_pro_emit_cache_tag_header( $swcfpc_fallback_cache_config_path, $swcfpc_fallback_cache_key );
	}

	$is_debug = defined( 'WP_DEBUG' ) && WP_DEBUG;

	if ( 'stale' === $swcfpc_entry_state ) {
		swcfpc_trigger_async_refresh( $swcfpc_fallback_cache_key, $swcfpc_fallback_cache_path );
	}

	spc_store_cache_hit();

	die( file_get_contents( $swcfpc_fallback_cache_path . $swcfpc_fallback_cache_key ) . ( $is_debug ? '<!-- ADVANCED CACHE -->' : '' ) );

}

ob_start( 'swcfpc_fallback_cache_end' );


function swcfpc_is_this_page_cachable() {
	if (
		swcfpc_is_api_request() ||
		( isset( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], [ 'wp-login.php', 'wp-register.php' ] ) ) ||
		( isset( $_SERVER['REQUEST_URI'] ) && substr( $_SERVER['REQUEST_URI'], 0, 16 ) == '/wp-register.php' ) ||
		( isset( $_SERVER['REQUEST_URI'] ) && substr( $_SERVER['REQUEST_URI'], 0, 13 ) == '/wp-login.php' ) ||
		( isset( $_SERVER['REQUEST_METHOD'] ) && strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) != 0 ) ||
		( ! defined( 'SWCFPC_CACHE_BUSTER' ) && isset( $_GET['swcfpc'] ) ) ||
		( defined( 'SWCFPC_CACHE_BUSTER' ) && isset( $_GET[ SWCFPC_CACHE_BUSTER ] ) ) ||
		is_admin() ||
		( isset( $_SERVER['HTTP_X_REQUESTED_WITH'] ) && $_SERVER['HTTP_X_REQUESTED_WITH'] == 'XMLHttpRequest' ) ||
		( defined( 'DOING_AJAX' ) && DOING_AJAX ) ||
		( defined( 'WP_CLI' ) && WP_CLI ) ||
		( defined( 'DOING_CRON' ) && DOING_CRON )
	) {
		return false;
	}

	return true;

}


function swcfpc_is_api_request() {
	// WordPress standard API
	if ( ( defined( 'REST_REQUEST' ) && REST_REQUEST ) || strcasecmp( substr( $_SERVER['REQUEST_URI'], 0, 8 ), '/wp-json' ) == 0 ) {
		return true;
	}

	// WooCommerce standard API
	if ( strcasecmp( substr( $_SERVER['REQUEST_URI'], 0, 8 ), '/wc-api/' ) == 0 ) {
		return true;
	}

	// WooCommerce standard API
	if ( strcasecmp( substr( $_SERVER['REQUEST_URI'], 0, 9 ), '/edd-api/' ) == 0 ) {
		return true;
	}

	return false;

}


function swcfpc_fallback_cache_end( $html ) {
	/**
	 * The main plugin class.
	 *
	 * @var \SW_CLOUDFLARE_PAGECACHE $sw_cloudflare_pagecache
	 */
	global $sw_cloudflare_pagecache, $swcfpc_config;

	if ( strlen( trim( $html ) ) == 0 ) {
		return $html;
	}

	if ( ! is_object( $sw_cloudflare_pagecache ) ) {
		return $html;
	}

	$fallback_cache = $sw_cloudflare_pagecache->get_core_loader()->fallback_cache();

	if ( empty( $swcfpc_config['cf_fallback_cache'] ) ) {
		return $html;
	}

	if ( ! empty( $swcfpc_config['cf_cache_enabled'] ) && ! \SPC\Services\Bypass_Resolver::is_url_to_bypass() && ! \SPC\Services\Bypass_Resolver::can_i_bypass_cache() && isset( $_SERVER['REQUEST_METHOD'] ) && strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) == 0 ) {

		if ( swcfpc_is_cache_warmer_request() ) {
			return $html;
		}

		$cache_path = $fallback_cache->fallback_cache_init_directory();
		$cache_key  = swcfpc_fallback_cache_get_current_page_cache_key();

		if ( swcfpc_is_fallback_refresh_request() && 'fresh' === swcfpc_fallback_cache_get_entry_state( $cache_key, $cache_path ) ) {
			swcfpc_release_refresh_lock( $cache_key, $cache_path );
			return $html;
		}

		if ( ! file_exists( $cache_path . $cache_key ) || 'fresh' !== swcfpc_fallback_cache_get_entry_state( $cache_key, $cache_path ) ) {

			// Bypass 4xx or 5xx HTTP status codes (security blocks, errors, etc.)
			if ( ! empty( $swcfpc_config['cf_fallback_cache_http_response_code'] ) ) {
				$http_status = http_response_code();
				if ( $http_status !== false && $http_status >= 400 && $http_status < 600 ) {
					if ( swcfpc_is_fallback_refresh_request() ) {
						swcfpc_release_refresh_lock( $cache_key, $cache_path );
					}

					return $html;
				}
			}

			if ( swcfpc_is_fallback_refresh_request() && ! swcfpc_refresh_lock_exists( $cache_key, $cache_path ) ) {
				return $html;
			}

			$metadata = swcfpc_build_cache_entry_metadata( $swcfpc_config );

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$html .= "\n<!-- Page retrieved from Super Page Cache fallback cache - page generated @ " . date( 'Y-m-d H:i:s' ) . ' - fallback cache expiration @ ' . ( $metadata['fresh_until'] > 0 ? date( 'Y-m-d H:i:s', $metadata['fresh_until'] ) : 'never expires' ) . " - cache key {$cache_key} -->";
			}

			// Provide a filter to modify the HTML before it is cached
			$html = apply_filters( 'swcfpc_normal_fallback_cache_html', $html, $cache_key );

			file_put_contents( $cache_path . $cache_key, $html );

			// Update TTL
			$fallback_cache->fallback_cache_set_single_ttl( $cache_key, $metadata );
			$fallback_cache->fallback_cache_update_ttl_registry();
			swcfpc_release_refresh_lock( $cache_key, $cache_path );

			// Store headers
			if ( ! empty( $swcfpc_config['cf_fallback_cache_save_headers'] ) ) {
				swcfpc_fallback_cache_save_headers( $cache_path, $cache_key );
			}

			spc_store_cache_miss();
		}
	}

	return $html;

}

function swcfpc_normalize_url( $url = null ) {

	if ( ! is_null( $url ) ) {

		$parts = parse_url( strtolower( $url ) );

		if ( ! $parts ) {
			return false;
		}

		$current_uri = isset( $parts['path'] ) ? $parts['path'] : '/';

		if ( isset( $parts['query'] ) ) {
			$current_uri .= "?{$parts['query']}";
		}

		if ( $current_uri == '/' ) {
			$current_uri = $parts['host'];
		}
	} else {
		$current_uri = $_SERVER['REQUEST_URI'];

		if ( $current_uri == '/' ) {
			$current_uri = $_SERVER['HTTP_HOST'];
		}

		$current_uri = trim( $current_uri, '/' );

		if ( strpos( $current_uri, '?' ) === 0 ) {
			$current_uri = $_SERVER['HTTP_HOST'] . $current_uri;
		}
	}

	$current_uri = apply_filters( 'swcfpc_fc_modify_current_url', $current_uri );

	return swcfpc_fallback_cache_remove_url_parameters( $current_uri );
}
function swcfpc_fallback_cache_get_current_page_cache_key( $url = null ) {
	
	$replacements = [ '://', '/', '?', '#', '&', '.', ',', '@', '-', '\'', '"', '%', ' ', '\\', '=' ];
	$cache_key = str_replace( $replacements, '_', swcfpc_normalize_url( $url ) );
	$cache_key = trim( $cache_key, '_' );
	$cache_key = sha1( $cache_key );

	return $cache_key . '.html';
}

function swcfpc_get_unparsed_url( $parsed_url ) {
	// PHP_URL_SCHEME
	$scheme   = isset( $parsed_url['scheme'] ) ? $parsed_url['scheme'] . '://' : '';
	$host     = isset( $parsed_url['host'] ) ? $parsed_url['host'] : '';
	$port     = isset( $parsed_url['port'] ) ? ':' . $parsed_url['port'] : '';
	$user     = isset( $parsed_url['user'] ) ? $parsed_url['user'] : '';
	$pass     = isset( $parsed_url['pass'] ) ? ':' . $parsed_url['pass'] : '';
	$pass     = ( $user || $pass ) ? "$pass@" : '';
	$path     = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '';
	$query    = isset( $parsed_url['query'] ) ? '?' . $parsed_url['query'] : '';
	$fragment = isset( $parsed_url['fragment'] ) ? '#' . $parsed_url['fragment'] : '';

	return "{$scheme}{$user}{$pass}{$host}{$port}{$path}{$query}{$fragment}";
}

function swcfpc_fallback_cache_remove_url_parameters( $url ) {
	$url_parsed       = parse_url( $url );
	$url_query_params = [];

	if ( array_key_exists( 'query', $url_parsed ) ) {

		if ( $url_parsed['query'] === '' ) {

			// this means the URL ends with just ? i.e. /example-page/? - so just remove the last character ? from the URL
			$url = substr( trim( $url ), 0, - 1 );

		} else {

			// Set the ignored query param array
			$ignored_query_params = [
				'Browser',
				'C',
				'GCCON',
				'MCMP',
				'MarketPlace',
				'PD',
				'Refresh',
				'Sens',
				'ServiceVersion',
				'Source',
				'Topic',
				'__WB_REVISION__',
				'__cf_chl_jschl_tk__',
				'__d',
				'__hsfp',
				'__hssc',
				'__hstc',
				'__s',
				'_branch_match_id',
				'_bta_c',
				'_bta_tid',
				'_com',
				'_escaped_fragment_',
				'_ga',
				'_ga-ft',
				'_gl',
				'_hsmi',
				'_ke',
				'_kx',
				'_paged',
				'_sm_byp',
				'_sp',
				'_szp',
				'_thumbnail_id',
				'3x',
				'a',
				'a_k',
				'ac',
				'acpage',
				'action-box',
				'action_object_map',
				'action_ref_map',
				'action_type_map',
				'activecampaign_id',
				'ad',
				'ad_frame_full',
				'ad_frame_root',
				'ad_name',
				'adclida',
				'adid',
				'adlt',
				'adsafe_ip',
				'adset_name',
				'advid',
				'aff_sub2',
				'afftrack',
				'afterload',
				'ak_action',
				'alt_id',
				'am',
				'amazingmurphybeds',
				'amp;',
				'amp;amp',
				'amp;amp;amp',
				'amp;amp;amp;amp',
				'amp;utm_campaign',
				'amp;utm_medium',
				'amp;utm_source',
				'amp%3Butm_content',
				'ampStoryAutoAnalyticsLinker',
				'ampstoryautoanalyticslinke',
				'an',
				'ap',
				'ap_id',
				'apif',
				'apipage',
				'as_occt',
				'as_q',
				'as_qdr',
				'askid',
				'atFileReset',
				'atfilereset',
				'aucid',
				'auct',
				'audience',
				'author',
				'awt_a',
				'awt_l',
				'awt_m',
				'b2w',
				'back',
				'bannerID',
				'blackhole',
				'blockedAdTracking',
				'blog-reader-used',
				'blogger',
				'body',
				'br',
				'bsft_aaid',
				'bsft_clkid',
				'bsft_eid',
				'bsft_ek',
				'bsft_lx',
				'bsft_mid',
				'bsft_mime_type',
				'bsft_tv',
				'bsft_uid',
				'bvMethod',
				'bvTime',
				'bvVersion',
				'bvb64',
				'bvb64resp',
				'bvplugname',
				'bvprms',
				'bvprmsmac',
				'bvreqmerge',
				'cacheburst',
				'campaign',
				'campaign_id',
				'campaign_name',
				'campid',
				'catablog-gallery',
				'channel',
				'checksum',
				'ck_subscriber_id',
				'cmplz_region_redirect',
				'cmpnid',
				'cn-reloaded',
				'code',
				'comment',
				'content_ad_widget',
				'cost',
				'cr',
				'crl8_id',
				'crlt.pid',
				'crlt_pid',
				'crrelr',
				'crtvid',
				'ct',
				'cuid',
				'daksldlkdsadas',
				'dcc',
				'dfp',
				'dm_i',
				'domain',
				'dosubmit',
				'dsp_caid',
				'dsp_crid',
				'dsp_insertion_order_id',
				'dsp_pub_id',
				'dsp_tracker_token',
				'dt',
				'dur',
				'durs',
				'e',
				'ee',
				'ef_id',
				'el',
				'emailID',
				'env',
				'epik',
				'erprint',
				'et_blog',
				'exch',
				'externalid',
				'fb_action_ids',
				'fb_action_types',
				'fb_ad',
				'fb_source',
				'fbclid',
				'fbzunique',
				'fg-aqp',
				'fireglass_rsn',
				'firstName',
				'fo',
				'fp_sid',
				'fpa',
				'fref',
				'fs',
				'furl',
				'fwp_lunch_restrictions',
				'ga_action',
				'gclid',
				'gclsrc',
				'gdffi',
				'gdfms',
				'gdftrk',
				'gf_page',
				'gidzl',
				'goal',
				'gooal',
				'gpu',
				'gtVersion',
				'haibwc',
				'hash',
				'hc_location',
				'hemail',
				'hid',
				'highlight',
				'hl',
				'home',
				'hsa_acc',
				'hsa_ad',
				'hsa_cam',
				'hsa_grp',
				'hsa_kw',
				'hsa_mt',
				'hsa_net',
				'hsa_src',
				'hsa_tgt',
				'hsa_ver',
				'ias_campId',
				'ias_chanId',
				'ias_dealId',
				'ias_dspId',
				'ias_impId',
				'ias_placementId',
				'ias_pubId',
				'ical',
				'ict',
				'ie',
				'igshid',
				'im',
				'ipl',
				'jw_start',
				'jwsource',
				'k',
				'key1',
				'key2',
				'klaviyo',
				'ksconf',
				'ksref',
				'l',
				'label',
				'lang',
				'ldtag_cl',
				'level1',
				'level2',
				'level3',
				'level4',
				'limit',
				'lng',
				'load_all_comments',
				'lt',
				'ltclid',
				'ltd',
				'lucky',
				'm',
				'm?sales_kw',
				'matomo_campaign',
				'matomo_cid',
				'matomo_content',
				'matomo_group',
				'matomo_keyword',
				'matomo_medium',
				'matomo_placement',
				'matomo_source',
				'max-results',
				'mc_cid',
				'mc_eid',
				'mdrv',
				'mediaserver',
				'memset',
				'mibextid',
				'mkcid',
				'mkevt',
				'mkrid',
				'mkwid',
				'mkt_tok',
				'ml_subscriber',
				'ml_subscriber_hash',
				'mobileOn',
				'mode',
				'moderation-hash',
				'modernpatio',
				'month',
				'msID',
				'msclkid',
				'msg',
				'mtm_campaign',
				'mtm_cid',
				'mtm_content',
				'mtm_group',
				'mtm_keyword',
				'mtm_medium',
				'mtm_placement',
				'mtm_source',
				'murphybedstoday',
				'mwprid',
				'n',
				'name',
				'native_client',
				'navua',
				'nb',
				'nb_klid',
				'nowprocketcache',
				'o',
				'okijoouuqnqq',
				'org',
				'pa_service_worker',
				'partnumber',
				'pcmtid',
				'pcode',
				'pcrid',
				'pfstyle',
				'phrase',
				'pid',
				'piwik_campaign',
				'piwik_keyword',
				'piwik_kwd',
				'pk_campaign',
				'pk_keyword',
				'pk_kwd',
				'placement',
				'plat',
				'platform',
				'playsinline',
				'position',
				'pp',
				'pr',
				'preview',
				'preview_id',
				'preview_nonce',
				'prid',
				'print',
				'q',
				'q1',
				'qsrc',
				'r',
				'rd',
				'rdt_cid',
				'redig',
				'redir',
				'ref',
				'reftok',
				'relatedposts_hit',
				'relatedposts_origin',
				'relatedposts_position',
				'remodel',
				'replytocom',
				'rest_route',
				'reverse-paginate',
				'rid',
				'rnd',
				'rndnum',
				'robots_txt',
				'rq',
				'rsd',
				's_kwcid',
				'sa',
				'safe',
				'said',
				'sales_cat',
				'sales_kw',
				'sb_referer_host',
				'scrape',
				'script',
				'scrlybrkr',
				'search',
				'sellid',
				'sersafe',
				'sfn_data',
				'sfn_trk',
				'sfns',
				'sfw',
				'sha1',
				'share',
				'shared',
				'showcomment',
				'showComment',
				'si',
				'sid',
				'sid1',
				'sid2',
				'sidewalkShow',
				'sig',
				'site',
				'site_id',
				'siteid',
				'slicer1',
				'slicer2',
				'source',
				'spref',
				'spvb',
				'sra',
				'src',
				'srk',
				'srp',
				'ssp_iabi',
				'ssts',
				'stylishmurphybeds',
				'subId1 ',
				'subId2 ',
				'subId3',
				'subid',
				'swcfpc',
				'tail',
				'teaser',
				'test',
				'timezone',
				'toWww',
				'triplesource',
				'trk_contact',
				'trk_module',
				'trk_msg',
				'trk_sid',
				'tsig',
				'turl',
				'u',
				'unapproved',
				'up_auto_log',
				'upage',
				'updated-max',
				'uptime',
				'us_privacy',
				'usegapi',
				'userConsent',
				'usqp',
				'utm',
				'utm_campa',
				'utm_campaign',
				'utm_content',
				'utm_expid',
				'utm_id',
				'utm_medium',
				'utm_reader',
				'utm_referrer',
				'utm_source',
				'utm_sq',
				'utm_ter',
				'utm_term',
				'v',
				'vc',
				'vf',
				'vgo_ee',
				'vp',
				'vrw',
				'vz',
				'wbraid',
				'webdriver',
				'wing',
				'wpdParentID',
				'wpmp_switcher',
				'wref',
				'wswy',
				'wtime',
				'x',
				'zMoatImpID',
				'zarsrc',
				'zeffdn',
			];

			$ignored_query_params = apply_filters( 'swcfpc_fallback_cache_ignored_query_params', $ignored_query_params );

			// First parse the query params to an array to manage it better
			parse_str( $url_parsed['query'], $url_query_params );

			// Loop though $ignored_query_params
			foreach ( $ignored_query_params as $ignored_query_param ) {

				// Check if that query param is present in $url_query_params
				if ( array_key_exists( $ignored_query_param, $url_query_params ) ) {

					// The ignored query param is present in the $url_query_params. So, unset it from there
					unset( $url_query_params[ $ignored_query_param ] );
				}
			}

			// Now lets check if we have any query params left in $url_query_params
			if ( count( $url_query_params ) > 0 ) {

				$new_url_query_params = http_build_query( $url_query_params );
				$url_parsed['query']  = $new_url_query_params;

			} else {
				// Remove the query section from parsed URL
				unset( $url_parsed['query'] );
			}

			// Get the new current URL without the marketing query params
			$url = swcfpc_get_unparsed_url( $url_parsed );
		}
	}

	return $url;

}

function swcfpc_get_cache_control_value( array $config ) {
	$cache_control = 's-maxage=' . ( isset( $config['cf_maxage'] ) ? (int) $config['cf_maxage'] : 604800 )
		. ', max-age=' . ( isset( $config['cf_browser_maxage'] ) ? (int) $config['cf_browser_maxage'] : 60 );
	$stale_ttl     = max( 0, isset( $config['stale_while_revalidate_ttl'] ) ? (int) $config['stale_while_revalidate_ttl'] : 60 );

	if ( swcfpc_is_stale_while_revalidate_active( $config ) ) {
		$cache_control .= ', stale-while-revalidate=' . $stale_ttl;
	}

	return $cache_control;
}

function swcfpc_build_cache_entry_metadata( array $config ) {
	$lifespan = isset( $config['cf_fallback_cache_ttl'] ) ? (int) $config['cf_fallback_cache_ttl'] : 0;

	if ( 0 === $lifespan ) {
		return [
			'fresh_until' => 0,
			'stale_until' => 0,
		];
	}

	$fresh_until = time() + $lifespan;
	$stale_until = $fresh_until;

	if ( ! empty( $config['stale_while_revalidate'] ) ) {
		$stale_until += max( 0, isset( $config['stale_while_revalidate_ttl'] ) ? (int) $config['stale_while_revalidate_ttl'] : 60 );
	}

	return [
		'fresh_until' => $fresh_until,
		'stale_until' => $stale_until,
	];
}

function swcfpc_normalize_cache_entry_metadata( $metadata ) {
	if ( is_array( $metadata ) ) {
		$fresh_until = isset( $metadata['fresh_until'] ) ? (int) $metadata['fresh_until'] : 0;
		$stale_until = isset( $metadata['stale_until'] ) ? (int) $metadata['stale_until'] : $fresh_until;

		return [
			'fresh_until' => $fresh_until,
			'stale_until' => max( $fresh_until, $stale_until ),
		];
	}

	$legacy_ttl = (int) $metadata;

	return [
		'fresh_until' => $legacy_ttl,
		'stale_until' => $legacy_ttl,
	];
}

function swcfpc_is_stale_while_revalidate_active( array $config ) {
	$stale_ttl = isset( $config['stale_while_revalidate_ttl'] ) ? (int) $config['stale_while_revalidate_ttl'] : 60;

	return ! empty( $config['stale_while_revalidate'] ) && $stale_ttl > 0;
}

function swcfpc_get_sanitized_http_host() {
	if ( empty( $_SERVER['HTTP_HOST'] ) ) {
		return '';
	}

	$host = preg_replace( '/[\x00-\x1F\x7F].*/', '', (string) $_SERVER['HTTP_HOST'] );

	if ( ! is_string( $host ) || '' === $host || in_array( $host, [ '.', '..' ], true ) || ! preg_match( '/^[A-Za-z0-9.\-:\[\]]+$/', $host ) ) {
		return '';
	}

	return $host;
}

function swcfpc_get_host_without_port( $host ) {
	if ( preg_match( '/^\[([^\]]+)\](?::\d+)?$/', $host, $matches ) ) {
		return $matches[1];
	}

	if ( 1 === substr_count( $host, ':' ) && preg_match( '/^([^:]+):\d+$/', $host, $matches ) ) {
		return $matches[1];
	}

	return $host;
}

function swcfpc_get_cache_base_path() {
	$host = swcfpc_get_sanitized_http_host();

	if ( '' === $host ) {
		return '';
	}

	$bare_host = swcfpc_get_host_without_port( $host );

	if ( $bare_host !== $host ) {
		$bare_host_path = WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$bare_host}";

		if ( file_exists( "{$bare_host_path}/main_config.php" ) ) {
			return $bare_host_path;
		}
	}

	return WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$host}";
}

function swcfpc_is_cache_warmer_request() {
	return isset( $_SERVER['HTTP_USER_AGENT'] ) && 0 === strcasecmp( (string) $_SERVER['HTTP_USER_AGENT'], SWCFPC_FALLBACK_WARMER_UA );
}

function swcfpc_is_fallback_refresh_request() {
	return isset( $_SERVER['HTTP_USER_AGENT'] )
		&& 0 === strcasecmp( (string) $_SERVER['HTTP_USER_AGENT'], SWCFPC_FALLBACK_REFRESH_UA )
		&& swcfpc_is_loopback_request();
}

function swcfpc_is_loopback_request() {
	if ( empty( $_SERVER['REMOTE_ADDR'] ) ) {
		return false;
	}

	$remote_addr = (string) $_SERVER['REMOTE_ADDR'];

	if ( in_array( $remote_addr, [ '127.0.0.1', '::1' ], true ) ) {
		return true;
	}

	return ! empty( $_SERVER['SERVER_ADDR'] ) && $remote_addr === (string) $_SERVER['SERVER_ADDR'];
}

function swcfpc_get_refresh_lock_path( $cache_key, $fallback_cache_path ) {
	return "{$fallback_cache_path}{$cache_key}.refresh.lock";
}

function swcfpc_acquire_refresh_lock( $cache_key, $fallback_cache_path ) {
	$lock_path = swcfpc_get_refresh_lock_path( $cache_key, $fallback_cache_path );
	$handle    = @fopen( $lock_path, 'x' );

	if ( false !== $handle ) {
		fwrite( $handle, (string) time() );
		fclose( $handle );

		return true;
	}

	if ( file_exists( $lock_path ) ) {
		$lock_time = (int) file_get_contents( $lock_path );

		if ( $lock_time > 0 && ( time() - $lock_time ) < SWCFPC_FALLBACK_REFRESH_LOCK_TTL ) {
			return false;
		}

		@unlink( $lock_path );
	}

	$handle = @fopen( $lock_path, 'x' );

	if ( false === $handle ) {
		return false;
	}

	fwrite( $handle, (string) time() );
	fclose( $handle );

	return true;
}

function swcfpc_release_refresh_lock( $cache_key, $fallback_cache_path ) {
	$lock_path = swcfpc_get_refresh_lock_path( $cache_key, $fallback_cache_path );

	if ( file_exists( $lock_path ) ) {
		@unlink( $lock_path );
	}
}

function swcfpc_refresh_lock_exists( $cache_key, $fallback_cache_path ) {
	return file_exists( swcfpc_get_refresh_lock_path( $cache_key, $fallback_cache_path ) );
}

function swcfpc_trigger_async_refresh( $cache_key, $fallback_cache_path ) {
	if ( ! swcfpc_acquire_refresh_lock( $cache_key, $fallback_cache_path ) ) {
		return;
	}

	$host = swcfpc_get_sanitized_http_host();
	$uri  = isset( $_SERVER['REQUEST_URI'] ) ? preg_replace( '/[\x00-\x1F\x7F].*/', '', (string) $_SERVER['REQUEST_URI'] ) : '';

	if ( '' === $host || '' === $uri || strpos( $uri, '/' ) !== 0 ) {
		swcfpc_release_refresh_lock( $cache_key, $fallback_cache_path );
		return;
	}

	$scheme      = ( ! empty( $_SERVER['HTTPS'] ) && 'off' !== strtolower( (string) $_SERVER['HTTPS'] ) ) ? 'https' : 'http';
	$hostname    = isset( $_SERVER['SERVER_ADDR'] ) ? (string) $_SERVER['SERVER_ADDR'] : '127.0.0.1';
	$port        = isset( $_SERVER['SERVER_PORT'] ) ? (int) $_SERVER['SERVER_PORT'] : ( 'https' === $scheme ? 443 : 80 );
	$socket_host = swcfpc_normalize_loopback_host_for_socket( $hostname, 'https' === $scheme );
	$errno       = 0;
	$errstr      = '';
	$socket      = @fsockopen( $socket_host, $port, $errno, $errstr, 0.05 );

	if ( false === $socket ) {
		swcfpc_release_refresh_lock( $cache_key, $fallback_cache_path );
		return;
	}

	stream_set_blocking( $socket, false );

	$request  = "GET {$uri} HTTP/1.1\r\n";
	$request .= "Host: {$host}\r\n";
	$request .= 'Connection: Close' . "\r\n";
	$request .= 'User-Agent: ' . SWCFPC_FALLBACK_REFRESH_UA . "\r\n\r\n";

	$bytes_written = fwrite( $socket, $request );
	fclose( $socket );

	if ( false === $bytes_written || 0 === $bytes_written ) {
		swcfpc_release_refresh_lock( $cache_key, $fallback_cache_path );
		return;
	}
}

function swcfpc_normalize_loopback_host_for_socket( $host, $use_ssl ) {
	if ( false !== strpos( $host, ':' ) && '[' !== $host[0] ) {
		$host = '[' . $host . ']';
	}

	return $use_ssl ? 'ssl://' . $host : $host;
}

function swcfpc_fallback_cache_get_entry_state( $cache_key, $fallback_cache_path ) {
	global $swcfpc_config;

	if ( ! file_exists( $fallback_cache_path . $cache_key ) ) {
		return 'missing';
	}

	$config_path = swcfpc_get_cache_base_path();
	$metadata    = [ 'fresh_until' => 0, 'stale_until' => 0 ];

	if ( '' !== $config_path && file_exists( "{$config_path}/ttl_registry.json" ) ) {
		$swcfpc_ttl_registry = json_decode( file_get_contents( "{$config_path}/ttl_registry.json" ), true );

		if ( is_array( $swcfpc_ttl_registry ) && isset( $swcfpc_ttl_registry[ $cache_key ] ) ) {
			$metadata = swcfpc_normalize_cache_entry_metadata( $swcfpc_ttl_registry[ $cache_key ] );
		}
	}

	if ( 0 === $metadata['fresh_until'] ) {
		return 'fresh';
	}

	$now = time();

	if ( $now <= $metadata['fresh_until'] ) {
		return 'fresh';
	}

	if ( swcfpc_is_stale_while_revalidate_active( $swcfpc_config ) && $now <= $metadata['stale_until'] ) {
		return 'stale';
	}

	return 'expired';
}


function swcfpc_fallback_cache_is_expired_page( $cache_key ) {
	$cache_base_path = swcfpc_get_cache_base_path();

	if ( '' === $cache_base_path ) {
		return false;
	}

	$fallback_cache_path = $cache_base_path . '/fallback_cache/';

	return 'expired' === swcfpc_fallback_cache_get_entry_state( $cache_key, $fallback_cache_path );
}


function swcfpc_fallback_cache_is_cookie_to_exclude() {
	global $swcfpc_config;

	if ( count( $_COOKIE ) == 0 ) {
		return false;
	}

	if ( is_array( $swcfpc_config ) && ! isset( $swcfpc_config['cf_fallback_cache_excluded_cookies'] ) ) {
		return false;
	}

	$excluded_cookies = $swcfpc_config['cf_fallback_cache_excluded_cookies'];

	if ( count( $excluded_cookies ) == 0 ) {
		return false;
	}

	$cookies = array_filter(
		array_keys( $_COOKIE ),
		function ( $cookie_name ) {
			return $cookie_name !== 'wordpress_test_cookie';
		}
	);

	foreach ( $excluded_cookies as $single_cookie ) {
		if ( count( preg_grep( "#{$single_cookie}#", $cookies ) ) > 0 ) {
			swcfpc_bypass_reason_header( sprintf( 'Cookie - %s', $single_cookie ) );

			return true;
		}
	}

	return false;

}

function swcfpc_fallback_cache_is_url_to_exclude( $url = false ) {
	global $swcfpc_config;

	if (
		is_array( $swcfpc_config ) &&
		isset( $swcfpc_config['cf_fallback_cache_prevent_cache_urls_without_trailing_slash'] ) &&
		$swcfpc_config['cf_fallback_cache_prevent_cache_urls_without_trailing_slash'] > 0 &&
		! preg_match( '/\/$/', $_SERVER['REQUEST_URI'] ) &&
		apply_filters( 'swcfpc_fallback_cache_skip_unslashed', true, $_SERVER['REQUEST_URI'] )
	) {
		swcfpc_bypass_reason_header( 'URL Without Trailing Slash' );

		return true;
	}

	if ( is_array( $swcfpc_config ) && ! isset( $swcfpc_config['cf_fallback_cache_excluded_urls'] ) ) {
		return false;
	}

	$excluded_urls = $swcfpc_config['cf_fallback_cache_excluded_urls'];

	if ( is_array( $excluded_urls ) && count( $excluded_urls ) > 0 ) {

		if ( $url === false ) {

			$current_url = $_SERVER['REQUEST_URI'];

			if ( isset( $_SERVER['QUERY_STRING'] ) && strlen( $_SERVER['QUERY_STRING'] ) > 0 ) {
				$current_url .= "?{$_SERVER['QUERY_STRING']}";
			}
		} else {
			$current_url = $url;
		}

		foreach ( $excluded_urls as $url_to_exclude ) {

			if ( swcfpc_wildcard_match( $url_to_exclude, $current_url ) ) {
				swcfpc_bypass_reason_header( 'Excluded URL' );

				return true;
			}

			/*
			if( fnmatch($url_to_exclude, $current_url, FNM_CASEFOLD) ) {
				return true;
			}
			*/

		}
	}

	return false;

}


function swcfpc_fallback_cache_save_headers( $fallback_cache_path, $cache_key ) {
	$headers_file = "{$fallback_cache_path}{$cache_key}.headers.json";

	$headers_list  = headers_list();
	$headers_count = count( $headers_list );

	for ( $i = 0; $i < $headers_count; ++ $i ) {

		list( $header_name, $header_value ) = explode( ':', $headers_list[ $i ] );

		if (
			strcasecmp( $header_name, 'cache-control' ) == 0 ||
			strcasecmp( $header_name, 'set-cookie' ) == 0 ||
			strcasecmp( $header_name, 'X-WP-CF-Super-Cache-Disabled-Reason' ) == 0 ||
			strcasecmp( substr( $header_name, 0, 19 ), 'X-WP-CF-Super-Cache' ) == 0
		) {
			unset( $headers_list[ $i ] );
			continue;
		}
	}

	if ( count( $headers_list ) == 0 ) {

		if ( file_exists( $headers_file ) ) {
			@unlink( $headers_file );
		}

		return false;

	}

	file_put_contents( $headers_file, json_encode( $headers_list ) );

	return true;

}


function swcfpc_fallback_cache_get_stored_headers( $fallback_cache_path, $cache_key ) {
	$headers_file = "{$fallback_cache_path}{$cache_key}.headers.json";

	if ( file_exists( $headers_file ) ) {

		$swcfpc_headers = json_decode( file_get_contents( $headers_file ), true );

		if ( is_array( $swcfpc_headers ) && count( $swcfpc_headers ) > 0 ) {
			return $swcfpc_headers;
		}
	}

	return false;

}


function swcfpc_wildcard_match( $pattern, $subject ) {
	// Case insensitive
	$pattern = '#^' . preg_quote( $pattern ) . '$#i';
	$pattern = str_replace( '\*', '.*', $pattern );
	// $pattern = str_replace('\.', '.', $pattern);

	if ( ! preg_match( $pattern, $subject, $regs ) ) {
		return false;
	}

	swcfpc_bypass_reason_header( sprintf( 'Excluded URL - %s', $pattern ) );

	return true;

}

function swcfpc_bypass_reason_header( $reason = '' ) {
	if ( ! is_string( $reason ) || empty( $reason ) ) {
		return;
	}

	header( sprintf( 'X-WP-CF-Super-Cache-Disabled-Reason: %s', $reason ) );
}

/**
 * Record one cache verdict in the current UTC-hour bucket.
 *
 * @param string $status  'hit' | 'miss'   (case-insensitive)
 */
function spc_store_cache_status(string $status): void {

	global $__spc_cfg, $__spc_backend, $__spc_ttl;

	/* early outs for disabled / unsampled requests */
	if (! $__spc_cfg['enabled']) return;
	if (
		$__spc_cfg['sampling'] < 100 &&
		mt_rand(1, 100) > $__spc_cfg['sampling']
	) return;

	$status = strtolower($status);
	if ($status !== 'hit' && $status !== 'miss') return;

	$bucket = gmdate('YmdH');             // e.g. 2025061213 (UTC hour)
	$key    = "spc_{$status}_{$bucket}";          // for cache back-ends
	$file   = "{$status}_{$bucket}.txt";          // for file back-end (fixed .txt)

	if ($__spc_backend === 'apcu') {
		if (false === \apcu_inc($key)) {
			\apcu_add($key, 1, $__spc_ttl);
		}

		return;
	}

	file_put_contents(SPC_METRICS_DIR . "/{$file}", '+', FILE_APPEND);
}

function spc_store_cache_hit(): void {
	spc_store_cache_status('hit');
}
function spc_store_cache_miss(): void {
	spc_store_cache_status('miss');
}
