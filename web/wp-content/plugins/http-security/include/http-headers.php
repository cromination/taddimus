<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'send_headers', 'http_security_add_security_header' );
function http_security_add_security_header() {
	$secure_connection = false;
	if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] != 'off' && get_option( 'http_security_sts_flag' )) {
		$header_string = 'Strict-Transport-Security:';
		if ( get_option( 'http_security_sts_max_age' ) ) $header_string .= ' max-age='. get_option( 'http_security_sts_max_age' ).';';
		if ( get_option( 'http_security_sts_subdomains_flag' ) ) $header_string .= ' includeSubDomains;';
		if ( get_option( 'http_security_sts_preload_flag' ) ) $header_string .= ' preload';
		header($header_string);
	}

	if ( get_option( 'http_security_expect_ct_flag' ) ) {
		$header_string = 'Expect-CT:';
		if ( get_option( 'http_security_expect_ct_enforce_flag' ) ) $header_string .= ' enforce;';
		$header_string .= ' max-age='. get_option( 'http_security_expect_ct_max_age' ).';';
		if ( get_option( 'http_security_expect_ct_report_uri' ) ) $header_string .= 'report-uri="'. get_option( 'http_security_expect_ct_report_uri' ).'";';
		header($header_string);
	}

	if ( get_option( 'http_security_csp_flag' ) ) {
		$header_string = 'Content-Security-Policy';
		if ( get_option( 'http_security_csp_reportonly_flag' ) ) $header_string .= '-Report-Only';
		$header_string .= ':';
		if ( get_option( 'http_security_csp_child' ) ) $header_string .= ' child-src '. get_option( 'http_security_csp_child' ).';';
		if ( get_option( 'http_security_csp_connect' ) ) $header_string .= ' connect-src '. get_option( 'http_security_csp_connect' ).';';
		if ( get_option( 'http_security_csp_default' ) ) $header_string .= ' default-src '. get_option( 'http_security_csp_default' ).';';
		if ( get_option( 'http_security_csp_font' ) ) $header_string .= ' font-src '. get_option( 'http_security_csp_font' ).';';
		if ( get_option( 'http_security_csp_frame' ) ) $header_string .= ' frame-src '. get_option( 'http_security_csp_frame' ).';';
		if ( get_option( 'http_security_csp_img' ) ) $header_string .= ' img-src '. get_option( 'http_security_csp_img' ).';';
		if ( get_option( 'http_security_csp_manifest' ) ) $header_string .= ' manifest-src '. get_option( 'http_security_csp_manifest' ).';';
		if ( get_option( 'http_security_csp_media' ) ) $header_string .= ' media-src '. get_option( 'http_security_csp_media' ).';';
		if ( get_option( 'http_security_csp_object' ) ) $header_string .= ' object-src '. get_option( 'http_security_csp_object' ).';';
		if ( get_option( 'http_security_csp_script' ) ) $header_string .= ' script-src '. get_option( 'http_security_csp_script' ).';';
		if ( get_option( 'http_security_csp_style' ) ) $header_string .= ' style-src '. get_option( 'http_security_csp_style' ).';';
		if ( get_option( 'http_security_csp_worker' ) ) $header_string .= ' worker-src '. get_option( 'http_security_csp_worker' ).';';
		if ( get_option( 'http_security_csp_base_uri' ) ) $header_string .= ' base-uri '. get_option( 'http_security_base_uri' ).';';
		if ( get_option( 'http_security_csp_plugin_types' ) ) $header_string .= ' plugin-types '. get_option( 'http_security_plugin_types' ).';';
		if ( get_option( 'http_security_csp_sandbox' ) ) $header_string .= ' sandbox '. get_option( 'http_security_csp_sandbox' ).';';
		if ( get_option( 'http_security_csp_form_action' ) ) $header_string .= ' form-action '. get_option( 'http_security_csp_form_action' ).';';
		if ( get_option( 'http_security_csp_frame_ancestors' ) ) $header_string .= ' frame-ancestors '. get_option( 'http_security_csp_frame_ancestors' ).';';
		if ( get_option( 'http_security_csp_block_all_mixed_content' ) ) $header_string .= ' block-all-mixed-content;';
		if ( get_option( 'http_security_csp_require_sri_for' ) ) $header_string .= ' require-sri-for '. get_option( 'http_security_csp_require_sri_for' ).';';
		if ( get_option( 'http_security_csp_upgrade_insecure_requests' ) ) $header_string .= ' upgrade-insecure-requests;';
		if ( get_option( 'http_security_csp_reportonly_flag' ) ) $header_string .= ' report-uri /_/csp-reports';
		header($header_string);
	}

	if ( get_option( 'http_security_feature_policy_flag' ) ) {
		$header_string = 'Feature-Policy:';
		if ( get_option( 'http_security_feature_policy_autoplay' ) ) $header_string .= ' autoplay '. get_option( 'http_security_feature_policy_autoplay' ).';';
		if ( get_option( 'http_security_feature_policy_camera' ) ) $header_string .= ' camera '. get_option( 'http_security_feature_policy_camera' ).';';
		if ( get_option( 'http_security_feature_policy_document_domain' ) ) $header_string .= ' document-domain '. get_option( 'http_security_feature_policy_document_domain' ).';';
		if ( get_option( 'http_security_feature_policy_encrypted_media' ) ) $header_string .= ' encrypted-media '. get_option( 'http_security_feature_policy_encrypted_media' ).';';
		if ( get_option( 'http_security_feature_policy_fullscreen' ) ) $header_string .= ' fullscreen '. get_option( 'http_security_feature_policy_fullscreen' ).';';
		if ( get_option( 'http_security_feature_policy_geolocation' ) ) $header_string .= ' geolocation '. get_option( 'http_security_feature_policy_geolocation' ).';';
		if ( get_option( 'http_security_feature_policy_microphone' ) ) $header_string .= ' microphone '. get_option( 'http_security_feature_policy_microphone' ).';';
		if ( get_option( 'http_security_feature_policy_midi' ) ) $header_string .= ' midi '. get_option( 'http_security_feature_policy_midi' ).';';
		if ( get_option( 'http_security_feature_policy_payment' ) ) $header_string .= ' payment '. get_option( 'http_security_feature_policy_payment' ).';';
		if ( get_option( 'http_security_feature_policy_vr' ) ) $header_string .= ' vr '. get_option( 'http_security_feature_policy_vr' ).';';
		header($header_string);
	}

	if ( get_option( 'http_security_x_frame_flag' ) ) {
		switch ( get_option( 'http_security_x_frame_options' ) ) {
		case 1:
			header( 'X-Frame-Options: DENY' );
			break;
		case 2:
			header( 'X-Frame-Options: SAMEORIGIN' );
			break;
		case 3:
			$path = get_option( 'http_security_x_frame_origin' );
			header( 'X-Frame-Options: ALLOW-FROM ' . $path );
			break;
		}
	}
	if ( get_option( 'http_security_referrer_policy' ) ) header( 'Referrer-Policy: '. get_option( 'http_security_referrer_policy' ) );
	if ( get_option( 'http_security_x_xss_protection' ) ) header( 'X-XSS-Protection: 1; mode=block' );
	if ( get_option( 'http_security_x_content_type_options' ) ) header( 'X-Content-Type-Options: nosniff' );
	if ( get_option( 'http_security_remove_php_version' ) ) header_remove( 'X-Powered-By' );
	if ( get_option( 'http_security_remove_wordpress_version' ) ) remove_action( 'wp_head' , 'wp_generator' );
}
