<?php

/**
 * Plugin Name: WebP Converter for Media
 * Description: Speed up your website by using our WebP & AVIF Converter. Serve WebP and AVIF images instead of standard formats JPEG, PNG and GIF just now!
 * Version: 4.4.0
 * Author: Mateusz Gbiorczyk
 * Author URI: https://mattplugins.com/
 * Text Domain: webp-converter-for-media
 * Network: true
 */

require_once __DIR__ . '/vendor/autoload.php';

new WebpConverter\WebpConverter(
	new WebpConverter\PluginInfo( __FILE__, '4.4.0' )
);
