<?php

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );
define( 'SWCFPC_ADVANCED_CACHE', true );

if( !swcfpc_is_this_page_cachable() )
    return;

if( strcasecmp($_SERVER['REQUEST_METHOD'], "GET") != 0 )
    return;

$swcfpc_fallback_cache_config_path = WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$_SERVER['HTTP_HOST']}/";
$swcfpc_fallback_cache_path = WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$_SERVER['HTTP_HOST']}/fallback_cache/";
$swcfpc_fallback_cache_key = swcfpc_fallback_cache_get_current_page_cache_key();

if( !file_exists("{$swcfpc_fallback_cache_config_path}main_config.php") )
    return;

include( "{$swcfpc_fallback_cache_config_path}main_config.php" );

$swcfpc_config = json_decode( stripslashes($swcfpc_config), true );

if( !is_array($swcfpc_config) )
    return;

if( swcfpc_fallback_cache_is_url_to_exclude() )
    return;

if( swcfpc_fallback_cache_is_cookie_to_exclude() )
    return;

if( swcfpc_fallback_cache_is_cookie_to_exclude_cf_worker() )
    return;

if( file_exists($swcfpc_fallback_cache_path . $swcfpc_fallback_cache_key) && !swcfpc_fallback_cache_is_expired_page( $swcfpc_fallback_cache_key ) ) {

    $cache_controller = "s-maxage={$swcfpc_config['cf_maxage']}, max-age={$swcfpc_config['cf_browser_maxage']}";
    $stored_headers = swcfpc_fallback_cache_get_stored_headers($swcfpc_fallback_cache_path,  $swcfpc_fallback_cache_key);

    if( (int) $swcfpc_config['cf_maxage'] > 0 ) {
        header_remove('Set-Cookie');
    }

    header_remove('Pragma');
    header_remove('Expires');
    header_remove('Cache-Control');
    header("Cache-Control: {$cache_controller}");
    header('X-WP-CF-Super-Cache: cache');
    header('X-WP-CF-Super-Cache-Active: 1');
    header('X-WP-CF-Fallback-Cache: 1');
    header("X-WP-CF-Super-Cache-Cache-Control: {$cache_controller}");

    if( isset($swcfpc_config['cf_woker_enabled']) && $swcfpc_config['cf_woker_enabled'] > 0 && isset($swcfpc_config['cf_worker_bypass_cookies']) && is_array($swcfpc_config['cf_worker_bypass_cookies']) && count($swcfpc_config['cf_worker_bypass_cookies']) > 0 ) {
        header( 'X-WP-CF-Super-Cache-Cookies-Bypass: ' . trim( implode( '|', $swcfpc_config['cf_worker_bypass_cookies'] ) ) );
    }

    if( $stored_headers ) {

        foreach($stored_headers as $single_header) {
            header($single_header, false);
        }

    }

    die( file_get_contents($swcfpc_fallback_cache_path . $swcfpc_fallback_cache_key).'<!-- ADVANCED CACHE -->');

}

ob_start( 'swcfpc_fallback_cache_end' );


function swcfpc_is_this_page_cachable() {

    if( 
        swcfpc_is_api_request() || 
        ( isset( $GLOBALS['pagenow'] ) && in_array( $GLOBALS['pagenow'], array( 'wp-login.php', 'wp-register.php' ) ) ) || 
        ( substr( $_SERVER['REQUEST_URI'], 0, 16 ) == '/wp-register.php' ) || 
        ( substr( $_SERVER['REQUEST_URI'], 0, 13 ) == '/wp-login.php' ) || 
        strcasecmp( $_SERVER['REQUEST_METHOD'], 'GET' ) != 0 || 
        ( !defined('SWCFPC_CACHE_BUSTER' ) && isset( $_GET['swcfpc'] ) ) || 
        ( defined( 'SWCFPC_CACHE_BUSTER' ) && isset( $_GET[SWCFPC_CACHE_BUSTER] ) ) || 
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

    // Wordpress standard API
    if( (defined('REST_REQUEST') && REST_REQUEST) || strcasecmp( substr($_SERVER['REQUEST_URI'], 0, 8), '/wp-json' ) == 0 )
        return true;

    // WooCommerce standard API
    if( strcasecmp( substr($_SERVER['REQUEST_URI'], 0, 8), '/wc-api/' ) == 0 )
        return true;

    // WooCommerce standard API
    if( strcasecmp( substr($_SERVER['REQUEST_URI'], 0, 9), '/edd-api/' ) == 0 )
        return true;

    return false;

}


function swcfpc_fallback_cache_end( $html ) {

    global $sw_cloudflare_pagecache;

    if( strlen( trim($html) ) == 0 )
        return $html;

    if( !is_object($sw_cloudflare_pagecache) )
        return $html;

    $swcfpc_objects = $sw_cloudflare_pagecache->get_objects();

    if( $sw_cloudflare_pagecache->get_single_config('cf_fallback_cache', 0) == 0 )
        return $html;

    if( $swcfpc_objects['cache_controller']->is_cache_enabled() && !$swcfpc_objects['cache_controller']->is_url_to_bypass() && !$swcfpc_objects['cache_controller']->can_i_bypass_cache() && strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0 ) {

        if (isset($_SERVER['HTTP_USER_AGENT']) && strcasecmp($_SERVER['HTTP_USER_AGENT'], 'ua-swcfpc-fc') == 0)
            return $html;

        $cache_path = $swcfpc_objects['fallback_cache']->fallback_cache_init_directory();
        $cache_key = swcfpc_fallback_cache_get_current_page_cache_key();

        if (!file_exists($cache_path . $cache_key) || $swcfpc_objects['fallback_cache']->fallback_cache_is_expired_page($cache_key)) {

            if ($sw_cloudflare_pagecache->get_single_config('cf_fallback_cache_ttl', 0) == 0)
                $ttl = 0;
            else
                $ttl = time() + $sw_cloudflare_pagecache->get_single_config('cf_fallback_cache_ttl', 0);

            if( $ttl > 0 )
                $html .= "\n<!-- Page retrieved from WP Cloudflare Super Page Cache's fallback cache - page generated @ " . date('Y-m-d H:i:s') . ' - fallback cache expiration @ ' . date('Y-m-d H:i:s', $ttl) . " - cache key {$cache_key} -->";
            else
                $html .= "\n<!-- Page retrieved from WP Cloudflare Super Page Cache's fallback cache - page generated @ " . date('Y-m-d H:i:s') . " - fallback cache expiration @ never expires - cache key {$cache_key} -->";

            file_put_contents($cache_path . $cache_key, $html);

            // Update TTL
            $swcfpc_objects['fallback_cache']->fallback_cache_set_single_ttl($cache_key, $ttl);
            $swcfpc_objects['fallback_cache']->fallback_cache_update_ttl_registry();

            // Store headers
            if ($sw_cloudflare_pagecache->get_single_config('cf_fallback_cache_save_headers', 0) > 0) {
                swcfpc_fallback_cache_save_headers($cache_path, $cache_key);
            }

        }

    }

    return $html;

}


function swcfpc_fallback_cache_get_current_page_cache_key( $url=null ) {

    $replacements = array( '://', '/', '?', '#', '&', '.', ',', '@', '-', '\'', '"', '%', ' ', '\\', '=' );

    if( !is_null($url) ) {

        $parts = parse_url( strtolower($url) );

        if( !$parts )
            return false;

        $current_uri = isset($parts['path']) ? $parts['path'] : '/';

        if( isset($parts['query']) )
            $current_uri .= "?{$parts['query']}";

        if( $current_uri == '/' )
            $current_uri = $parts['host'];

    }
    else {

        $current_uri = $_SERVER['REQUEST_URI'];

        if( $current_uri == '/' )
            $current_uri = $_SERVER['HTTP_HOST'];

    }

    if( substr($current_uri, 0, 1) == '/' )
        $current_uri = substr($current_uri, 1);

    if( substr($current_uri, -1, 1) == '/' )
        $current_uri = substr($current_uri, 0, -1);

    $cache_key = str_replace( $replacements, '_', swcfpc_fallback_cache_remove_url_parameters($current_uri) );

    // Fix error fnmatch(): Filename exceeds the maximum allowed length
    $cache_key = sha1( $cache_key );

    /*
    if( strlen($cache_key) > 250 ) {
        $cache_key = substr($cache_key, 0, -32);
        $cache_key .= md5( $current_uri );
    }
    */

    $cache_key .= '.html';

    return $cache_key;

}


function swcfpc_fallback_cache_remove_url_parameters( $url ) {

    $action = false;

    //to remove query strings for cache if Google Click Identifier are set
    if(preg_match('/gclid\=/i', $url)){
        $action = true;
    }

    //to remove query strings for cache if facebook parameters are set
    if(preg_match('/fbclid\=/i', $url)){
        $action = true;
    }

    //to remove query strings for cache if google analytics parameters are set
    if(preg_match('/utm_(source|medium|campaign|content|term)/i', $url)){
        $action = true;
    }

    if( $action && strlen($_SERVER['REQUEST_URI']) > 1 )
        return preg_replace('/\/*\?.+/', '', $url).'/';

    return $url;

}


function swcfpc_fallback_cache_is_expired_page( $cache_key ) {

    $config_path = WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$_SERVER['HTTP_HOST']}/";

    if( !file_exists("{$config_path}ttl_registry.php") )
        return false;

    include( "{$config_path}ttl_registry.php" );

    $swcfpc_ttl_registry = json_decode( stripslashes($swcfpc_ttl_registry), true );
    $current_ttl = 0;

    if( !is_array($swcfpc_ttl_registry) || !isset($swcfpc_ttl_registry[$cache_key]) )
        $current_ttl = 0;
    else if( is_array($swcfpc_ttl_registry[$cache_key]))
        $current_ttl = $swcfpc_ttl_registry[$cache_key];
    else
        $current_ttl = (int) $swcfpc_ttl_registry[$cache_key];

    if( $current_ttl > 0 && time() > $current_ttl )
        return true;

    return false;


}


function swcfpc_fallback_cache_is_cookie_to_exclude() {

    global $swcfpc_config;

    if( count($_COOKIE) == 0 )
        return false;

    if( is_array($swcfpc_config) && !isset($swcfpc_config['cf_fallback_cache_excluded_cookies']) )
        return false;

    $excluded_cookies = $swcfpc_config['cf_fallback_cache_excluded_cookies'];

    if( count($excluded_cookies) == 0 )
        return false;

    $cookies = array_keys( $_COOKIE );

    foreach ($excluded_cookies as $single_cookie) {

        if( count( preg_grep("#{$single_cookie}#", $cookies) ) > 0 )
            return true;

    }

    return false;

}


function swcfpc_fallback_cache_is_cookie_to_exclude_cf_worker() {

    global $swcfpc_config;

    if( count($_COOKIE) == 0 )
        return false;

    if( !is_array($swcfpc_config) )
        return false;

    if( !isset($swcfpc_config['cf_worker_bypass_cookies']) || !isset($swcfpc_config['cf_woker_enabled']) )
        return false;

    if( (int) $swcfpc_config['cf_woker_enabled'] == 0 )
        return false;

    $excluded_cookies = $swcfpc_config['cf_worker_bypass_cookies'];

    if( count($excluded_cookies) == 0 )
        return false;

    $cookies = array_keys( $_COOKIE );

    foreach ($excluded_cookies as $single_cookie) {

        if( count( preg_grep("#{$single_cookie}#", $cookies) ) > 0 )
            return true;

    }

    return false;

}


function swcfpc_fallback_cache_is_url_to_exclude($url=false) {

    global $swcfpc_config;

    if( is_array($swcfpc_config) && isset($swcfpc_config['cf_fallback_cache_prevent_cache_urls_without_trailing_slash']) && $swcfpc_config['cf_fallback_cache_prevent_cache_urls_without_trailing_slash'] > 0 && !preg_match('/\/$/', $_SERVER['REQUEST_URI']) )
        return true;

    if( is_array($swcfpc_config) && !isset($swcfpc_config['cf_fallback_cache_excluded_urls']) )
        return false;

    $excluded_urls = $swcfpc_config['cf_fallback_cache_excluded_urls'];

    if( is_array($excluded_urls) && count($excluded_urls) > 0 ) {

        if( $url === false ) {

            $current_url = $_SERVER['REQUEST_URI'];

            if( isset($_SERVER['QUERY_STRING']) && strlen($_SERVER['QUERY_STRING']) > 0 )
                $current_url .= "?{$_SERVER['QUERY_STRING']}";

        }
        else {
            $current_url = $url;
        }

        foreach( $excluded_urls as $url_to_exclude ) {

            if( swcfpc_wildcard_match($url_to_exclude, $current_url) )
                return  true;

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

    $headers_file = "{$fallback_cache_path}{$cache_key}.headers.php";

    $headers_list = headers_list();
    $headers_count = count( $headers_list );

    for( $i=0; $i<$headers_count; ++$i ) {

        list($header_name, $header_value) = explode(':', $headers_list[$i]);

        if(
            strcasecmp($header_name, 'cache-control') == 0 ||
            strcasecmp($header_name, 'set-cookie') == 0 ||
            strcasecmp( substr($header_name, 0, 19), 'X-WP-CF-Super-Cache' ) == 0
        ) {
            unset($headers_list[$i]);
            continue;
        }

    }

    if( count($headers_list) == 0 ) {

        if( file_exists($headers_file) )
            @unlink($headers_file);

        return false;

    }

    file_put_contents( $headers_file, '<?php $swcfpc_headers=\''.addslashes( json_encode($headers_list) ).'\'; ?>');

    return true;

}


function swcfpc_fallback_cache_get_stored_headers( $fallback_cache_path, $cache_key ) {

    $headers_file = "{$fallback_cache_path}{$cache_key}.headers.php";

    if( file_exists($headers_file) ) {

        include( $headers_file );

        $swcfpc_headers = json_decode( stripslashes($swcfpc_headers), true );

        if( is_array($swcfpc_headers) && count($swcfpc_headers) > 0 )
            return $swcfpc_headers;

    }

    return false;

}


function swcfpc_wildcard_match($pattern, $subject) {

    $pattern='#^'.preg_quote($pattern).'$#i'; // Case insensitive
    $pattern=str_replace('\*', '.*', $pattern);
    //$pattern=str_replace('\.', '.', $pattern);

    if(!preg_match($pattern, $subject, $regs))
        return false;

    return true;

}