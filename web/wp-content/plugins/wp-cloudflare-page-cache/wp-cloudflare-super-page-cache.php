<?php
/**
 * Plugin Name:  Super Page Cache for Cloudflare
 * Plugin URI:   https://wordpress.org/plugins/wp-cloudflare-page-cache/
 * Description:  Speed up your website by enabling page caching on a Cloudflare free plans.
 * Version:      4.6.1
 * Author:       Optimole
 * Author URI:   https://optimole.com/
 * License:      GPLv2 or later
 * Text Domain:  wp-cloudflare-page-cache
 * Requires at least: 4.9
 * Requires PHP: 7.0
*/

if( !class_exists('SW_CLOUDFLARE_PAGECACHE') ) {

    define('SWCFPC_PLUGIN_PATH', plugin_dir_path(__FILE__));
    define('SWCFPC_PLUGIN_URL', plugin_dir_url(__FILE__));
    define('SWCFPC_PLUGIN_REVIEWS_URL', 'https://wordpress.org/support/plugin/wp-cloudflare-page-cache/reviews/');
    define('SWCFPC_PLUGIN_FORUM_URL', 'https://wordpress.org/support/plugin/wp-cloudflare-page-cache/');
    define('SWCFPC_AUTH_MODE_API_KEY',   0);
    define('SWCFPC_AUTH_MODE_API_TOKEN', 1);
    define('SWCFPC_LOGS_STANDARD_VERBOSITY', 1);
    define('SWCFPC_LOGS_HIGH_VERBOSITY', 2);

    if( !defined('SWCFPC_PRELOADER_MAX_POST_NUMBER') )
        define('SWCFPC_PRELOADER_MAX_POST_NUMBER', 50);

    if( !defined('SWCFPC_CACHE_BUSTER') )
        define('SWCFPC_CACHE_BUSTER', 'swcfpc');

    if( !defined('SWCFPC_CURL_TIMEOUT') )
        define('SWCFPC_CURL_TIMEOUT', 10);

    if( !defined('SWCFPC_PURGE_CACHE_LOCK_SECONDS') )
        define('SWCFPC_PURGE_CACHE_LOCK_SECONDS', 10);

    if( !defined('SWCFPC_HOME_PAGE_SHOWS_POSTS') )
        define('SWCFPC_HOME_PAGE_SHOWS_POSTS', true);

    class SW_CLOUDFLARE_PAGECACHE {

        private $config   = false;
        private $objects  = array();
        private $version  = '4.6.1';

        function __construct() {

            //add_action( 'plugins_loaded', array($this, 'update_plugin') );
            //register_activation_hook( __FILE__, array($this, 'update_plugin') );
            register_deactivation_hook( __FILE__, array($this, 'deactivate_plugin') );

            if( ! $this->init_config() ) {
                $this->config = $this->get_default_config();
                $this->update_config();
            }

            if( !file_exists( $this->get_plugin_wp_content_directory() ) )
                $this->create_plugin_wp_content_directory();

            $this->update_plugin();
            $this->include_libs();
            $this->actions();

        }


        function load_textdomain() {

            load_plugin_textdomain( 'wp-cloudflare-page-cache', false, basename( dirname( __FILE__ ) ) . '/languages/' );

        }


        function include_libs()
        {

            if( count($this->objects) > 0 )
                return;

            $this->objects = array();

            include_once(ABSPATH . 'wp-includes/pluggable.php');

            // Composer autoload.
            if ( file_exists( SWCFPC_PLUGIN_PATH . 'vendor/autoload.php' ) ) {
                require SWCFPC_PLUGIN_PATH . 'vendor/autoload.php';
            }

            require_once SWCFPC_PLUGIN_PATH . 'libs/preloader.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/cloudflare.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/logs.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/cache_controller.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/backend.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/fallback_cache.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/varnish.class.php';
            require_once SWCFPC_PLUGIN_PATH . 'libs/html_cache.class.php';

            $log_file_path = $this->get_plugin_wp_content_directory().'/debug.log';
            $log_file_url = $this->get_plugin_wp_content_directory_url().'/debug.log';

            $this->objects = apply_filters( 'swcfpc_include_libs_early', $this->objects );

            if( $this->get_single_config('log_enabled', 0) > 0 )
                $this->objects['logs'] = new SWCFPC_Logs( $log_file_path, $log_file_url, true, $this->get_single_config('log_max_file_size', 2), $this );
            else
                $this->objects['logs'] = new SWCFPC_Logs( $log_file_path, $log_file_url, false, $this->get_single_config('log_max_file_size', 2), $this );

            $this->objects['logs']->set_verbosity( $this->get_single_config('log_verbosity', SWCFPC_LOGS_STANDARD_VERBOSITY) );

            $this->objects['cloudflare'] = new SWCFPC_Cloudflare(
                $this->get_single_config('cf_auth_mode'),
                $this->get_cloudflare_api_key(),
                $this->get_cloudflare_api_email(),
                $this->get_cloudflare_api_token(),
                $this->get_cloudflare_api_zone_id(),
                $this->get_cloudflare_worker_mode(),
                $this->get_cloudflare_worker_content(),
                $this->get_cloudflare_worker_id(),
                $this->get_cloudflare_worker_route_id(),
                $this
            );

            $this->objects['fallback_cache'] = new SWCFPC_Fallback_Cache( $this );
            $this->objects['html_cache'] = new SWCFPC_Html_Cache( $this );
            $this->objects['cache_controller'] = new SWCFPC_Cache_Controller( SWCFPC_CACHE_BUSTER, $this );
            $this->objects['varnish'] = new SWCFPC_Varnish( $this );
            $this->objects['backend'] = new SWCFPC_Backend( $this );

            if( ( !defined( 'WP_CLI' ) || (defined('WP_CLI') && WP_CLI === false) ) && strcasecmp($_SERVER['REQUEST_METHOD'], 'GET') == 0 && !is_admin() && !$this->is_login_page() && $this->get_single_config('cf_fallback_cache', 0) > 0 && $this->objects['cache_controller']->is_cache_enabled() ) {
                $this->objects['fallback_cache']->fallback_cache_retrive_current_page();
            }

            $this->objects = apply_filters( 'swcfpc_include_libs_lately', $this->objects );

            // Inizializzo qui la classe del preloader in quanto questo metodo viene richiamato all'evento plugin_loaded. Dopodiche' posso stanziare l'oggetto anche in chiamate Ajax
            new SWCFPC_Preloader_Process( $this );

            $this->enable_wp_cli_support();

        }


        function actions() {

            add_filter( 'plugin_action_links_' . plugin_basename(__FILE__), array($this, 'add_plugin_action_links') );
            add_filter( 'plugin_row_meta' , array($this, 'add_plugin_meta_links'), 10, 2 );

            // Multilanguage
            add_action( 'plugins_loaded', array($this, 'load_textdomain') );

        }


        function get_default_config() {

            $config = array();

            // Cloudflare config
            $config['cf_zoneid']                          = '';
            $config['cf_zoneid_list']                     = array();
            $config['cf_email']                           = '';
            $config['cf_apitoken']                        = '';
            $config['cf_apikey']                          = '';
            $config['cf_token']                           = '';
            $config['cf_apitoken_domain']                 = $this->get_second_level_domain();
            $config['cf_old_bc_ttl']                      = '';
            $config['cf_page_rule_id']                    = '';
            $config['cf_bypass_backend_page_rule_id']     = '';
            $config['cf_bypass_backend_page_rule']        = 0;
            $config['cf_auto_purge']                      = 1;
            $config['cf_auto_purge_all']                  = 0;
            $config['cf_auto_purge_on_comments']          = 0;
            $config['cf_cache_enabled']                   = 0;
            $config['cf_maxage']                          = 31536000; // 1 year
            $config['cf_browser_maxage']                  = 60; // 1 minute
            $config['cf_post_per_page']                   = get_option( 'posts_per_page', 0);
            $config['cf_purge_url_secret_key']            = $this->generate_password(20, false, false);
            $config['cf_strip_cookies']                   = 0;
            $config['cf_fallback_cache']                  = 0;
            $config['cf_fallback_cache_ttl']              = 0;
            $config['cf_fallback_cache_auto_purge']       = 1;
            $config['cf_fallback_cache_curl']             = 0;
            $config['cf_fallback_cache_excluded_urls']    = array();
            $config['cf_fallback_cache_excluded_cookies'] = array('^wordpress_logged_in_', '^wp-', '^comment_', '^woocommerce_', '^wordpressuser_', '^wordpresspass_', '^wordpress_sec_');
            $config['cf_fallback_cache_save_headers']     = 0;
            $config['cf_fallback_cache_prevent_cache_urls_without_trailing_slash'] = 1;
            $config['cf_preloader']                       = 1;
            $config['cf_preloader_start_on_purge']        = 1;
            $config['cf_preloader_nav_menus']             = array();
            $config['cf_preload_last_urls']               = 1;
            $config['cf_preload_excluded_post_types']     = array('attachment', 'jet-menu', 'elementor_library', 'jet-theme-core');
            $config['cf_preload_sitemap_urls']            = array();
            $config['cf_woker_enabled']                   = 0;
            $config['cf_woker_id']                        = 'swcfpc_worker_'.time();
            $config['cf_woker_route_id']                  = '';
            $config['cf_worker_bypass_cookies']           = array();
            $config['cf_purge_only_html']                 = 0;
            $config['cf_disable_cache_purging_queue']     = 0;
            $config['cf_auto_purge_on_upgrader_process_complete'] = 0;

            // Pages
            $config['cf_excluded_urls']                 = array('/*ao_noptirocket*', '/*jetpack=comms*', '/*kinsta-monitor*', '*ao_speedup_cachebuster*', '/*removed_item*', '/my-account*', '/wc-api/*', '/edd-api/*', '/wp-json*');
            $config['cf_bypass_front_page']             = 0;
            $config['cf_bypass_pages']                  = 0;
            $config['cf_bypass_home']                   = 0;
            $config['cf_bypass_archives']               = 0;
            $config['cf_bypass_tags']                   = 0;
            $config['cf_bypass_category']               = 0;
            $config['cf_bypass_author_pages']           = 0;
            $config['cf_bypass_single_post']            = 0;
            $config['cf_bypass_feeds']                  = 1;
            $config['cf_bypass_search_pages']           = 1;
            $config['cf_bypass_404']                    = 1;
            $config['cf_bypass_logged_in']              = 1;
            $config['cf_bypass_amp']                    = 0;
            $config['cf_bypass_file_robots']            = 1;
            $config['cf_bypass_sitemap']                = 1;
            $config['cf_bypass_ajax']                   = 1;
            $config['cf_cache_control_htaccess']        = 0;
            $config['cf_browser_caching_htaccess']      = 0;
            $config['cf_auth_mode']                     = SWCFPC_AUTH_MODE_API_KEY;
            //$config['cf_bypass_post']                   = 0;
            $config['cf_bypass_query_var']              = 0;
            $config['cf_bypass_wp_json_rest']           = 0;

            // Varnish
            $config['cf_varnish_support']               = 0;
            $config['cf_varnish_auto_purge']            = 1;
            $config['cf_varnish_hostname']              = 'localhost';
            $config['cf_varnish_port']                  = 6081;
            $config['cf_varnish_cw']                    = 0;
            $config['cf_varnish_purge_method']          = 'PURGE';
            $config['cf_varnish_purge_all_method']      = 'PURGE';

            // WooCommerce
            $config['cf_bypass_woo_shop_page']           = 0;
            $config['cf_bypass_woo_pages']               = 0;
            $config['cf_bypass_woo_product_tax_page']    = 0;
            $config['cf_bypass_woo_product_tag_page']    = 0;
            $config['cf_bypass_woo_product_cat_page']    = 0;
            $config['cf_bypass_woo_product_page']        = 0;
            $config['cf_bypass_woo_cart_page']           = 1;
            $config['cf_bypass_woo_checkout_page']       = 1;
            $config['cf_bypass_woo_checkout_pay_page']   = 1;
            $config['cf_auto_purge_woo_product_page']    = 1;
            $config['cf_auto_purge_woo_scheduled_sales'] = 1;
            $config['cf_bypass_woo_account_page']        = 1;

            // Swift Performance (Lite/Pro)
            $config['cf_spl_purge_on_flush_all']           = 1;
            $config['cf_spl_purge_on_flush_single_post']   = 1;

            // W3TC
            $config['cf_w3tc_purge_on_flush_minfy']         = 0;
            $config['cf_w3tc_purge_on_flush_posts']         = 0;
            $config['cf_w3tc_purge_on_flush_objectcache']   = 0;
            $config['cf_w3tc_purge_on_flush_fragmentcache'] = 0;
            $config['cf_w3tc_purge_on_flush_dbcache']       = 0;
            $config['cf_w3tc_purge_on_flush_all']           = 1;

            // WP Rocket
            $config['cf_wp_rocket_purge_on_post_flush']               = 1;
            $config['cf_wp_rocket_purge_on_domain_flush']             = 1;
            $config['cf_wp_rocket_purge_on_cache_dir_flush']          = 1;
            $config['cf_wp_rocket_purge_on_clean_files']              = 1;
            $config['cf_wp_rocket_purge_on_clean_cache_busting']      = 1;
            $config['cf_wp_rocket_purge_on_clean_minify']             = 1;
            $config['cf_wp_rocket_purge_on_ccss_generation_complete'] = 1;

            // Litespeed Cache
            $config['cf_litespeed_purge_on_cache_flush']        = 1;
            $config['cf_litespeed_purge_on_ccss_flush']         = 1;
            $config['cf_litespeed_purge_on_cssjs_flush']        = 1;
            $config['cf_litespeed_purge_on_object_cache_flush'] = 1;
            $config['cf_litespeed_purge_on_single_post_flush']  = 1;

            // Flying Press
            $config['cf_flypress_purge_on_cache_flush'] = 1;

            // Hummingbird
            $config['cf_hummingbird_purge_on_cache_flush'] = 1;

            // WP-Optimize
            $config['cf_wp_optimize_purge_on_cache_flush'] = 1;

            // Yasr
            $config['cf_yasr_purge_on_rating'] = 0;

            // WP Asset Clean Up
            $config['cf_wpacu_purge_on_cache_flush'] = 1;

            // Autoptimize
            $config['cf_autoptimize_purge_on_cache_flush'] = 1;

            // WP Asset Clean Up
            $config['cf_nginx_helper_purge_on_cache_flush'] = 1;

            // WP Performance
            $config['cf_wp_performance_purge_on_cache_flush'] = 1;

            // EDD
            $config['cf_bypass_edd_checkout_page']         = 1;
            $config['cf_bypass_edd_success_page']          = 0;
            $config['cf_bypass_edd_failure_page']          = 0;
            $config['cf_bypass_edd_purchase_history_page'] = 1;
            $config['cf_bypass_edd_login_redirect_page']   = 1;
            $config['cf_auto_purge_edd_payment_add']       = 1;

            // WP Engine
            $config['cf_wpengine_purge_on_flush'] = 1;

            // SpinupWP
            $config['cf_spinupwp_purge_on_flush'] = 1;

            // Kinsta
            $config['cf_kinsta_purge_on_flush'] = 1;

            // Siteground
            $config['cf_siteground_purge_on_flush'] = 1;

            // Logs
            $config['log_enabled'] = 1;
            $config['log_max_file_size'] = 2; // Megabytes
            $config['log_verbosity'] = SWCFPC_LOGS_STANDARD_VERBOSITY;

            // Other
            $config['cf_remove_purge_option_toolbar'] = 0;
            $config['cf_disable_single_metabox'] = 1;
            $config['cf_seo_redirect'] = 0;
            $config['cf_opcache_purge_on_flush'] = 0;
            $config['cf_object_cache_purge_on_flush'] = 0;
            $config['cf_purge_roles'] = array();
            $config['cf_prefetch_urls_viewport'] = 0;
            $config['cf_prefetch_urls_viewport_timestamp'] = time();
            $config['cf_prefetch_urls_on_hover'] = 0;
            $config['keep_settings_on_deactivation'] = 1;

            return $config;

        }


        function get_single_config($name, $default=false) {

            if( !is_array($this->config) || !isset($this->config[$name]) )
                return $default;

            if( is_array($this->config[$name]))
                return $this->config[$name];

            return trim($this->config[$name]);

        }


        function set_single_config($name, $value) {

            if( !is_array($this->config) )
                $this->config = array();

            if( is_array($value) )
                $this->config[trim($name)] = $value;
            else
                $this->config[trim($name)] = trim($value);

        }


        function update_config() {

            update_option( 'swcfpc_config', $this->config );

        }


        function init_config() {

            $this->config = get_option( 'swcfpc_config', false );

            if( !$this->config )
                return false;

            // If the option exists, return true
            return true;

        }


        function set_config( $config ) {
            $this->config = $config;
        }


        function get_config() {
            return $this->config;
        }


        function update_plugin() {

            $current_version = get_option( 'swcfpc_version', false );

            if( $current_version === false || version_compare( $current_version, $this->version, '!=') ) {

                require_once SWCFPC_PLUGIN_PATH . 'libs/installer.class.php';

                if( $current_version === false ) {
                    $installer = new SWCFPC_Installer();
                    $installer->start();
                }
                else {

                    if( version_compare( $current_version, '4.3.5', '<') ) {

                        $cf_fallback_cache_excluded_cookies = $this->get_single_config('cf_fallback_cache_excluded_cookies', array());

                        if( !is_array($cf_fallback_cache_excluded_cookies) )
                            $cf_fallback_cache_excluded_cookies = array('^wordpress_logged_in_', '^wp-', '^comment_', '^woocommerce_', '^wordpressuser_', '^wordpresspass_', '^wordpress_sec_');
                        else
                            array_push($cf_fallback_cache_excluded_cookies, '^wordpressuser_', '^wordpresspass_', '^wordpress_sec_');

                        $this->set_single_config('cf_fallback_cache_excluded_cookies', $cf_fallback_cache_excluded_cookies);
                        $this->set_single_config('cf_bypass_edd_checkout_page', 1);
                        $this->set_single_config('cf_bypass_edd_login_redirect_page', 1);
                        $this->set_single_config('cf_bypass_edd_purchase_history_page', 1);
                        $this->set_single_config('cf_bypass_edd_success_page', 0);
                        $this->set_single_config('cf_bypass_edd_failure_page', 0);
                        $this->set_single_config('cf_auto_purge_edd_payment_add', 1);

                        $this->update_config();

                        // If fallback cache via advanced-cache.php is active, disable and re-enable the cache
                        if( defined('SWCFPC_ADVANCED_CACHE') ) {

                            if ( count($this->objects) == 0 )
                                $this->include_libs();

                            $this->objects['fallback_cache']->fallback_cache_advanced_cache_disable();
                            $this->objects['fallback_cache']->fallback_cache_advanced_cache_enable(true);

                        }

                    }

                    if( version_compare( $current_version, '4.3.6', '<') ) {

                        $cf_excluded_urls = $this->get_single_config('cf_excluded_urls', array());

                        if( !is_array($cf_excluded_urls) ) {
                            $cf_excluded_urls = array('/*ao_noptirocket*', '/*jetpack=comms*');
                            $this->set_single_config('cf_excluded_urls', $cf_excluded_urls);
                        }

                        $this->update_config();

                    }

                    if( version_compare( $current_version, '4.3.7', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.7');

                        $this->set_single_config('cf_worker_bypass_cookies', array());
                        $this->set_single_config('cf_object_cache_purge_on_flush', 0);
                        $this->set_single_config('cf_purge_roles', array());
                        $this->set_single_config('cf_nginx_helper_purge_on_cache_flush', 1);
                        $this->set_single_config('cf_fallback_cache_prevent_cache_urls_without_trailing_slash', 1);

                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( defined('SWCFPC_ADVANCED_CACHE') ) {

                                $objects['fallback_cache']->fallback_cache_advanced_cache_disable();
                                $objects['fallback_cache']->fallback_cache_advanced_cache_enable(true);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.7 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.3.7.2', '<') ) {

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.7.2');

                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {

                                $error_msg_cf = '';

                                $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                $objects['cloudflare']->enable_page_cache($error_msg_cf);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.7.2 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.3.7.4', '<') ) {

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.7.4');

                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {

                                $error_msg_cf = '';

                                if( defined('SWCFPC_ADVANCED_CACHE') ) {

                                    $objects['fallback_cache']->fallback_cache_advanced_cache_disable();
                                    $objects['fallback_cache']->fallback_cache_advanced_cache_enable(true);

                                }

                                $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                $objects['cloudflare']->enable_page_cache($error_msg_cf);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.7.4 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.3.8', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.8');

                        $cf_excluded_urls = $this->get_single_config('cf_excluded_urls', array());

                        if( is_array($cf_excluded_urls) && !in_array('/*jetpack=comms*', $cf_excluded_urls) ) {
                            $cf_excluded_urls[] = '/*jetpack=comms*';
                            $this->set_single_config('cf_excluded_urls', $cf_excluded_urls);
                        }

                        $this->set_single_config('cf_wpengine_purge_on_flush', 0);
                        $this->set_single_config('cf_spinupwp_purge_on_flush', 0);
                        $this->set_single_config('cf_kinsta_purge_on_flush', 0);
                        $this->set_single_config('cf_siteground_purge_on_flush', 0);
                        $this->set_single_config('log_verbosity', SWCFPC_LOGS_STANDARD_VERBOSITY);

                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( defined('SWCFPC_ADVANCED_CACHE') ) {

                                $objects['fallback_cache']->fallback_cache_advanced_cache_disable();
                                $objects['fallback_cache']->fallback_cache_advanced_cache_enable(true);

                            }

                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {

                                $error_msg_cf = '';

                                $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                $objects['cloudflare']->enable_page_cache($error_msg_cf);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.8 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.3.9', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.9');

                        $this->set_single_config('cf_wp_performance_purge_on_cache_flush', 1);
                        $this->set_single_config('cf_wp_rocket_purge_on_cache_dir_flush', 1);
                        $this->set_single_config('cf_wp_rocket_purge_on_clean_files', 1);
                        $this->set_single_config('cf_wp_rocket_purge_on_clean_cache_busting', 1);
                        $this->set_single_config('cf_wp_rocket_purge_on_clean_minify', 1);
                        $this->set_single_config('cf_wp_rocket_purge_on_ccss_generation_complete', 1);
                        $this->set_single_config('cf_auto_purge_woo_scheduled_sales', 1);
                        $this->set_single_config('cf_spl_purge_on_flush_all', 1);
                        $this->set_single_config('cf_spl_purge_on_flush_single_post', 1);
                        $this->set_single_config('cf_prefetch_urls_viewport', 0);
                        $this->set_single_config('cf_purge_only_html', 0);
                        $this->set_single_config('cf_disable_cache_purging_queue', 0);
                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {

                                $error_msg_cf = '';

                                $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                $objects['cloudflare']->enable_page_cache($error_msg_cf);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.9 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.3.9.2', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.3.9.2');

                        if( $this->get_single_config('cf_maxage', 0) == 604800 )
                            $this->set_single_config('cf_maxage', 31536000);

                        $cf_fallback_cache_excluded_cookies = $this->get_single_config('cf_fallback_cache_excluded_cookies', array());

                        if( is_array($cf_fallback_cache_excluded_cookies) ) {

                            if( ($key = array_search('wordpress_logged_in_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('wp-', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('comment_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('woocommerce_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('wordpressuser_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('wordpresspass_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            if( ($key = array_search('wordpress_sec_', $cf_fallback_cache_excluded_cookies)) !== false ) {
                                unset($cf_fallback_cache_excluded_cookies[$key]);
                            }

                            $cf_fallback_cache_excluded_cookies[] = '^wordpress_logged_in_';
                            $cf_fallback_cache_excluded_cookies[] = '^wp-';
                            $cf_fallback_cache_excluded_cookies[] = '^comment_';
                            $cf_fallback_cache_excluded_cookies[] = '^woocommerce_';
                            $cf_fallback_cache_excluded_cookies[] = '^wordpressuser_';
                            $cf_fallback_cache_excluded_cookies[] = '^wordpresspass_';
                            $cf_fallback_cache_excluded_cookies[] = '^wordpress_sec_';

                            $this->set_single_config('cf_fallback_cache_excluded_cookies', $cf_fallback_cache_excluded_cookies);

                        }

                        $cf_excluded_urls = $this->get_single_config('cf_excluded_urls', array());

                        if( is_array($cf_excluded_urls) ) {

                            if( !in_array('/*kinsta-monitor*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/*kinsta-monitor*';

                            if( !in_array('*ao_speedup_cachebuster*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '*ao_speedup_cachebuster*';

                            $this->set_single_config('cf_excluded_urls', $cf_excluded_urls);

                        }

                        $this->set_single_config('cf_litespeed_purge_on_ccss_flush', 1);
                        $this->set_single_config('cf_litespeed_purge_on_cssjs_flush', 1);
                        $this->set_single_config('cf_litespeed_purge_on_object_cache_flush', 1);
                        $this->set_single_config('cf_litespeed_purge_on_single_post_flush', 1);
                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( defined('SWCFPC_ADVANCED_CACHE') ) {

                                $objects['fallback_cache']->fallback_cache_advanced_cache_disable();
                                $objects['fallback_cache']->fallback_cache_advanced_cache_enable(true);

                            }

                            $this->objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.3.9.2 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.4.0', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.4.0');

                        $this->set_single_config('cf_prefetch_urls_viewport_timestamp', time());
                        $this->set_single_config('cf_prefetch_urls_on_hover', 0);
                        $this->set_single_config('keep_settings_on_deactivation', 0);
                        $this->set_single_config('cf_wpengine_purge_on_flush', 1);
                        $this->set_single_config('cf_spinupwp_purge_on_flush', 1);
                        $this->set_single_config('cf_kinsta_purge_on_flush', 1);
                        $this->set_single_config('cf_siteground_purge_on_flush', 1);
                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( $this->objects['cache_controller']->is_cache_enabled() && $sw_cloudflare_pagecache->get_single_config('cf_purge_only_html', 0) > 0 || $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {
                                $this->objects['logs']->add_log('swcfpc::update_plugin', 'Purge whole cache before to update to v4.4.0');
                                $objects['cache_controller']->purge_all(false, false, true);
                            }

                            delete_option('swcfpc_pages_list');

                            $this->objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.4.0 complete');

                        }, PHP_INT_MAX);

                    }


                    if( version_compare( $current_version, '4.4.1', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.4.1');

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            $error_msg = '';
                            $objects['cache_controller']->write_htaccess( $error_msg );

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.4.1 complete');

                        }, PHP_INT_MAX);

                    }


                    if( version_compare( $current_version, '4.4.2', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.4.2');

                        $cf_excluded_urls = $this->get_single_config('cf_excluded_urls', array());

                        if( is_array($cf_excluded_urls) ) {

                            if( !in_array('/*removed_item*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/*removed_item*';

                            $this->set_single_config('cf_excluded_urls', $cf_excluded_urls);

                        }

                        $this->update_config();


                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( $this->objects['cache_controller']->is_cache_enabled() && $sw_cloudflare_pagecache->get_single_config('cf_prefetch_urls_viewport', 0) > 0 ) {
                                $objects['logs']->add_log('swcfpc::update_plugin', 'Purge whole cache before to update to v4.4.2');
                                $objects['cache_controller']->purge_all(false, false, true);
                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.4.2 complete');

                        }, PHP_INT_MAX);

                    }


                    if( version_compare( $current_version, '4.4.3', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.4.3');

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.4.3 complete');

                        }, PHP_INT_MAX);

                    }


                    if( version_compare( $current_version, '4.4.4', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.4.4');

                        $this->set_single_config('cf_flypress_purge_on_cache_flush', 1);
                        $this->update_config();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.4.4 complete');

                        }, PHP_INT_MAX);

                    }


                    if( version_compare( $current_version, '4.5', '<') ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.5');

                        $this->set_single_config('cf_auto_purge_on_upgrader_process_complete', 0);
                        $this->set_single_config('cf_bypass_wp_json_rest', 0);
                        $this->set_single_config('cf_bypass_woo_account_page', 1);
                        $this->set_single_config('keep_settings_on_deactivation', 1);

                        $cf_excluded_urls = $this->get_single_config('cf_excluded_urls', array());

                        if( is_array($cf_excluded_urls) ) {

                            if( !in_array('/my-account*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/my-account*';

                            if( !in_array('/wc-api/*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/wc-api/*';

                            if( !in_array('/edd-api/*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/edd-api/*';

                            if( !in_array('/wp-json*', $cf_excluded_urls) )
                                $cf_excluded_urls[] = '/wp-json*';

                            $this->set_single_config('cf_excluded_urls', $cf_excluded_urls);

                        }

                        $this->update_config();

                        // Called to force the creation of nginx.conf inside the plugin's directory inside the wp-content one
                        $this->create_plugin_wp_content_directory();

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;

                            $objects = $sw_cloudflare_pagecache->get_objects();

                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {

                                $error_msg_cf = '';

                                $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                $objects['cloudflare']->enable_page_cache($error_msg_cf);

                            }

                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.5 complete');

                        }, PHP_INT_MAX);

                    }

                    if( version_compare( $current_version, '4.5.6', '<' ) ) {

                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.5.6');

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Initiating the removal of double serialization for swcfpc_config');

                        // Get the serialized version of the swcfpc_config
                        $serialized_swcfpc_config = get_option( 'swcfpc_config', false );

                        if( !$serialized_swcfpc_config ) {
                            
                            $this->objects['logs']->add_log('swcfpc::update_plugin', 'Serialized swcfpc_config not present');

                        } else {

                            // Unserialize the data to be further stored
                            if( is_string( $serialized_swcfpc_config ) ) {
                                $unserialized_swcfpc_config = unserialize( $serialized_swcfpc_config );

                                // Now store the same data again to swcfpc_config,
                                // But this time we won't serialize the data, instead WP will automatically do it.
                                update_option( 'swcfpc_config', $unserialized_swcfpc_config );
                            } else {
                                $this->objects['logs']->add_log('swcfpc::update_plugin', 'Unfortunately swcfpc_config did not returned a string. So, we can\'t unserialize it.');
                            }

                        }
                        

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Initiating the removal of double serialization for swcfpc_fc_ttl_registry');

                        // Get the serialized version of the swcfpc_fc_ttl_registry
                        $serialized_swcfpc_fc_ttl_registry = get_option( 'swcfpc_fc_ttl_registry', false );

                        if( !$serialized_swcfpc_fc_ttl_registry ) {

                            $this->objects['logs']->add_log('swcfpc::update_plugin', 'Serialized swcfpc_fc_ttl_registry not present');

                        } else {

                            if( is_string( $serialized_swcfpc_fc_ttl_registry ) ) {
                                // Unserialize the data to be further stored
                                $unserialized_swcfpc_fc_ttl_registry = unserialize( $serialized_swcfpc_fc_ttl_registry );
    
                                // Now store the same data again to swcfpc_fc_ttl_registry,
                                // But this time we won't serialize the data, instead WP will automatically do it.
                                update_option( 'swcfpc_fc_ttl_registry', serialize( $unserialized_swcfpc_fc_ttl_registry ) );
                            } else {
                                $this->objects['logs']->add_log('swcfpc::update_plugin', 'Unfortunately swcfpc_fc_ttl_registry did not returned a string. So, we can\'t unserialize it.');
                            }
                        }

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;
                        
                            $objects = $sw_cloudflare_pagecache->get_objects();
                        
                            if( $sw_cloudflare_pagecache->get_single_config('cf_woker_enabled', 0) > 0 ) {
                        
                                    $error_msg_cf = '';
                        
                                    $objects['cloudflare']->disable_page_cache($error_msg_cf);
                                    $objects['cloudflare']->enable_page_cache($error_msg_cf);
                        
                            }
                        
                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.5.6 complete');
                        
                        }, PHP_INT_MAX);
                    }

                    if( version_compare( $current_version, '4.6.1', '<' ) ) {
                        if ( count($this->objects) == 0 )
                            $this->include_libs();

                        $this->objects['logs']->add_log('swcfpc::update_plugin', 'Updating to v4.6.1');

                        add_action('shutdown', function() {

                            global $sw_cloudflare_pagecache;
                        
                            $objects = $sw_cloudflare_pagecache->get_objects();
                        
                            $error_msg_cf = '';
                    
                            // Enable Disable the Page Cache to take effect of the changes
                            $objects['cloudflare']->disable_page_cache($error_msg_cf);
                            $objects['cloudflare']->enable_page_cache($error_msg_cf);
                        
                            $objects['logs']->add_log('swcfpc::update_plugin', 'Update to v4.6.1 complete');
                        
                        }, PHP_INT_MAX);
                    }

                }

            }

            update_option('swcfpc_version', $this->version);

        }


        function deactivate_plugin() {

            if( $this->get_single_config('keep_settings_on_deactivation', 1) > 0 )
                $this->objects['cache_controller']->reset_all( true );
            else
                $this->objects['cache_controller']->reset_all();

            $this->delete_plugin_wp_content_directory();
        }


        function get_objects() {
            return $this->objects;
        }


        function add_plugin_action_links( $links ) {

            $mylinks = array(
                '<a href="' . admin_url( 'options-general.php?page=wp-cloudflare-super-page-cache-index' ) . '">'.__( 'Settings', 'wp-cloudflare-page-cache' ).'</a>',
            );

            return array_merge( $links, $mylinks );

        }


        function add_plugin_meta_links($meta_fields, $file) {

            if ( plugin_basename(__FILE__) == $file ) {

                $meta_fields[] = '<a href="' . esc_url(SWCFPC_PLUGIN_REVIEWS_URL.'?rate=5#new-post') .'" target="_blank" title="' . esc_html__('Rate', 'wp-cloudflare-page-cache') . '">
                <i class="ampforwp-rate-stars">'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                . '<svg xmlns="http://www.w3.org/2000/svg" width="15" height="15" viewBox="0 0 24 24" fill="#ffb900" stroke="#ffb900" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="feather feather-star"><polygon points="12 2 15.09 8.26 22 9.27 17 14.14 18.18 21.02 12 17.77 5.82 21.02 7 14.14 2 9.27 8.91 8.26 12 2"/></svg>'
                . '</i></a>';

            }

            return $meta_fields;
        }


        function get_cloudflare_api_zone_id() {

            if( defined('SWCFPC_CF_API_ZONE_ID') )
                return SWCFPC_CF_API_ZONE_ID;

            return $this->get_single_config('cf_zoneid', '');

        }


        function get_cloudflare_api_key() {

            if( defined('SWCFPC_CF_API_KEY') )
                return SWCFPC_CF_API_KEY;

            return $this->get_single_config('cf_apikey', '');

        }


        function get_cloudflare_api_email() {

            if( defined('SWCFPC_CF_API_EMAIL') )
                return SWCFPC_CF_API_EMAIL;

            return $this->get_single_config('cf_email', '');

        }


        function get_cloudflare_api_token() {

            if( defined('SWCFPC_CF_API_TOKEN') )
                return SWCFPC_CF_API_TOKEN;

            return $this->get_single_config('cf_apitoken', '');

        }


        function get_cloudflare_worker_mode() {

            if( defined('SWCFPC_CF_WOKER_ENABLED') )
                return SWCFPC_CF_WOKER_ENABLED;

            return $this->get_single_config('cf_woker_enabled', 0);

        }


        function get_cloudflare_worker_id() {

            if( defined('SWCFPC_CF_WOKER_ID') )
                return SWCFPC_CF_WOKER_ID;

            return $this->get_single_config('cf_woker_id', 'swcfpc_worker_'.time());

        }


        function get_cloudflare_worker_route_id() {

            if( defined('SWCFPC_CF_WOKER_ROUTE_ID') )
                return SWCFPC_CF_WOKER_ROUTE_ID;

            return $this->get_single_config('cf_woker_route_id', '');

        }


        function get_cloudflare_worker_content() {

            $worker_content = '';

            if( defined('SWCFPC_CF_WOKER_FULL_PATH') && file_exists( SWCFPC_CF_WOKER_FULL_PATH ) )
                $worker_content = file_get_contents( SWCFPC_CF_WOKER_FULL_PATH );
            else if ( file_exists( SWCFPC_PLUGIN_PATH . 'assets/js/worker_template.js' ) )
                $worker_content = file_get_contents( SWCFPC_PLUGIN_PATH . 'assets/js/worker_template.js' );

            return $worker_content;

        }


        function get_plugin_wp_content_directory() {

            $parts = parse_url( home_url() );

            return WP_CONTENT_DIR . "/wp-cloudflare-super-page-cache/{$parts['host']}";

        }


        function get_plugin_wp_content_directory_url() {

            $parts = parse_url( home_url() );

            return content_url("wp-cloudflare-super-page-cache/{$parts['host']}");

        }


        function get_plugin_wp_content_directory_uri() {

            $parts = parse_url( home_url() );

            return str_replace( array("https://{$parts['host']}", "http://{$parts['host']}"), '', content_url("wp-cloudflare-super-page-cache/{$parts['host']}") );

        }


        function create_plugin_wp_content_directory() {

            $parts = parse_url( home_url() );
            $path = WP_CONTENT_DIR . '/wp-cloudflare-super-page-cache/';

            if( ! file_exists( $path ) && wp_mkdir_p($path, 0755) ) {
                file_put_contents("{$path}index.php", '<?php // Silence is golden');
            }

            $path .= $parts['host'];

            if( ! file_exists( $path ) && wp_mkdir_p( $path, 0755 ) ) {
                file_put_contents("{$path}/index.php", '<?php // Silence is golden');
            }

            $nginx_conf = "{$path}/nginx.conf";

            if( ! file_exists( $nginx_conf ) ) {
                file_put_contents($nginx_conf, '');
            }

        }


        function delete_plugin_wp_content_directory() {

            $parts = parse_url( home_url() );
            $path = WP_CONTENT_DIR . '/wp-cloudflare-super-page-cache/';
            $path .= $parts['host'];

            if( file_exists( $path ) )
                $this->delete_directory_recursive( $path );

        }


        function delete_directory_recursive($dir) {

            if( !class_exists('RecursiveDirectoryIterator') || !class_exists('RecursiveIteratorIterator') )
                return false;

            $it    = new RecursiveDirectoryIterator($dir, RecursiveDirectoryIterator::SKIP_DOTS);
            $files = new RecursiveIteratorIterator($it,RecursiveIteratorIterator::CHILD_FIRST);

            foreach($files as $file) {

                if ($file->isDir())
                    rmdir($file->getRealPath());
                else
                    unlink($file->getRealPath());

            }

            rmdir($dir);

            return true;

        }


        function generate_password( $length = 12, $special_chars = true, $extra_special_chars = false ) {

            $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
            $password = '';

            if ( $special_chars ) {
                $chars .= '!@#$%^&*()';
            }
            if ( $extra_special_chars ) {
                $chars .= '-_ []{}<>~`+=,.;:/?|';
            }

            for ( $i = 0; $i < $length; $i++ ) {
                $password .= substr( $chars, rand( 0, strlen( $chars ) - 1 ), 1 );
            }

            return $password;

        }


        function is_login_page() {

            return in_array($GLOBALS['pagenow'], array('wp-login.php', 'wp-register.php'));

        }


        function get_second_level_domain() {

            $site_hostname = parse_url( home_url(), PHP_URL_HOST );

            if( is_null( $site_hostname ) ) {
                return '';
            }

            // get the domain name from the hostname
            $site_domain = preg_replace('/^www\./', '', $site_hostname);

            return $site_domain;

        }


        function enable_wp_cli_support() {

            if ( defined( 'WP_CLI' ) && WP_CLI && !class_exists( 'SWCFPC_WP_CLI' ) && class_exists( 'WP_CLI_Command' ) ) {

                require_once SWCFPC_PLUGIN_PATH . 'libs/wpcli.class.php';

                $wpcli = new SWCFPC_WP_CLI( $this );

                WP_CLI::add_command('cfcache', $wpcli);


            }

        }


        function can_current_user_purge_cache() {

            if( !is_user_logged_in() )
                return false;

            if( current_user_can('manage_options') )
                return true;

            $allowed_roles = $this->get_single_config('cf_purge_roles', array());

            if( count($allowed_roles) > 0 ) {

                $user = wp_get_current_user();

                foreach($allowed_roles as $role_name) {

                    if ( in_array($role_name, (array)$user->roles) )
                        return true;

                }

            }

            return false;

        }


        function get_wordpress_roles() {

            global $wp_roles;
            $wordpress_roles = array();

            foreach( $wp_roles->roles as $role => $role_data )
                $wordpress_roles[] = $role;

            return $wordpress_roles;

        }


        function does_current_url_have_trailing_slash() {

            if( !preg_match('/\/$/', $_SERVER['REQUEST_URI']) )
                return false;

            return true;

        }


        function is_api_request() {

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


        function wildcard_match($pattern, $subject) {

            $pattern='#^'.preg_quote($pattern).'$#i'; // Case insensitive
            $pattern=str_replace('\*', '.*', $pattern);
            //$pattern=str_replace('\.', '.', $pattern);

            if(!preg_match($pattern, $subject, $regs))
                return false;

            return true;

        }


        function get_current_lang_code() {

            $current_language_code = false;

            if( has_filter('wpml_current_language') )
                $current_language_code = apply_filters( 'wpml_current_language', null );

            return $current_language_code;

        }


        function get_permalink($post_id) {

            $url = get_the_permalink( $post_id );

            if( has_filter('wpml_permalink') )
                $url = apply_filters( 'wpml_permalink', $url , $this->get_current_lang_code() );

            return $url;

        }


        function get_home_url( $blog_id = null, $path = '', $scheme = null ) {

            global $pagenow;

            if ( empty( $blog_id ) || ! is_multisite() ) {
                $url = get_option( 'home' );
            } else {
                switch_to_blog( $blog_id );
                $url = get_option( 'home' );
                restore_current_blog();
            }

            if ( ! in_array( $scheme, array( 'http', 'https', 'relative' ), true ) ) {

                if ( is_ssl() && ! is_admin() && 'wp-login.php' !== $pagenow )
                    $scheme = 'https';
                else
                    $scheme = parse_url( $url, PHP_URL_SCHEME );

            }

            $url = set_url_scheme( $url, $scheme );

            if ( $path && is_string( $path ) )
                $url .= '/' . ltrim( $path, '/' );

            return $url;

        }


        function home_url( $path = '', $scheme = null ) {
            return $this->get_home_url( null, $path, $scheme );
        }

    }

    // Activate this plugin as last plugin
    add_action('plugins_loaded', function () {

        if( !isset( $GLOBALS['sw_cloudflare_pagecache'] ) || empty( $GLOBALS['sw_cloudflare_pagecache'] ) )
            $GLOBALS['sw_cloudflare_pagecache'] = new SW_CLOUDFLARE_PAGECACHE();

    }, PHP_INT_MAX);

}

//$sw_cloudflare_pagecache = new SW_CLOUDFLARE_PAGECACHE();
