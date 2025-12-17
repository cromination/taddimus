<?php

/**
 * Plugin Name: Converter for Media
 * Plugin URI: https://mattplugins.com/products/webp-converter-for-media-pro
 * Description: Speed up your website by using our WebP & AVIF Converter. Optimize images and serve WebP and AVIF images instead of standard formats!
 * Version: 6.4.0
 * Author: matt plugins
 * Author URI: https://url.mattplugins.com/converter-plugin-author-link
 * Text Domain: webp-converter-for-media
 * Network: true
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

new WebpConverter\WebpConverter(
	new WebpConverter\PluginInfo( __FILE__, '6.4.0' )
);
