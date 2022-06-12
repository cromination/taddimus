<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

echo '<h2 class="nav-tab-wrapper"><a href="?page=http-security&tab=general-options" class="nav-tab">'. __('General options', 'http-security' ).'</a> <a href="?page=http-security&tab=csp-options" class="nav-tab">'. __('CSP options', 'http-security' ).'</a> <a href="?page=http-security&tab=feature-policy" class="nav-tab">'. __('Feature Policy', 'http-security' ).'</a> <a href="?page=http-security&tab=htaccess" class="nav-tab nav-tab-active">'. __('.htaccess (beta)', 'http-security' ).'</a></h2>';
echo '<p>'. __( 'Some cache plug-ins rewrite the HTTP headers. In this case, you may need to have to insert the following content in your .htaccess file. If so, please disable the rewriting of the HTTP headers.', 'http-security' ).'</p>';
echo '<p>'. __( 'Make sure to save the settings for the latest version.', 'http-security' ).'</p>';
echo '<form method="post" action="options.php">';

settings_fields( 'http-security-htaccess' );

echo '<label for="http_security_htaccess_flag"><input name="http_security_htaccess_flag" type="checkbox" id="http_security_htaccess_flag" value="1" ' . checked( 1, get_option( 'http_security_htaccess_flag' ), false ) . ' />'. __( 'Disable header rewriting', 'http-security' ).'</label><br />';
echo '<blockquote><textarea name="htaccess" rows="15" cols="80"># HTTP security settings start'."\n\n";
if ( get_option( 'http_security_sts_flag' ) ) {
	$header_string = 'Header set Strict-Transport-Security:';
	if ( get_option( 'http_security_sts_max_age' ) )
		$header_string .= ' max-age='. esc_html( get_option( 'http_security_sts_max_age' ) ).';';
	if ( get_option( 'http_security_sts_subdomains_flag' ) )
		$header_string .= ' includeSubDomains;';
	if ( get_option( 'http_security_sts_preload_flag' ) )
		$header_string .= ' preload';
	echo($header_string."\n");
}

if ( get_option( 'http_security_expect_ct_flag' ) ) {
	$header_string = 'Header set Expect-CT:';
	if ( get_option( 'http_security_expect_ct_enforce_flag' ) )
		$header_string .= ' enforce;';
	$header_string .= ' max-age='. esc_html( get_option( 'http_security_expect_ct_max_age' ) ).';';
	if ( get_option( 'http_security_expect_ct_report_uri' ) )
		$header_string .= 'report-uri="'. esc_html( get_option( 'http_security_expect_ct_report_uri' ) ).'";';
	echo($header_string."\n");
}

if ( get_option( 'http_security_csp_flag' ) ) {
	$header_string = 'Header set Content-Security-Policy "';
	if ( get_option( 'http_security_csp_reportonly_flag' ) )
		$header_string .= '-Report-Only';
//			$header_string .= ':';
	if ( get_option( 'http_security_csp_child' ) )
		$header_string .= ' child-src '. esc_html( get_option( 'http_security_csp_child' ) ).';';
	if ( get_option( 'http_security_csp_connect' ) )
		$header_string .= ' connect-src '. esc_html( get_option( 'http_security_csp_connect' ) ).';';
	if ( get_option( 'http_security_csp_default' ) )
		$header_string .= ' default-src '. esc_html( get_option( 'http_security_csp_default' ) ).';';
	if ( get_option( 'http_security_csp_font' ) )
		$header_string .= ' font-src '. esc_html( get_option( 'http_security_csp_font' ) ).';';
	if ( get_option( 'http_security_csp_frame' ) )
		$header_string .= ' frame-src '. esc_html( get_option( 'http_security_csp_frame' ) ).';';
	if ( get_option( 'http_security_csp_img' ) )
		$header_string .= ' img-src '. esc_html( get_option( 'http_security_csp_img' ) ).';';
	if ( get_option( 'http_security_csp_manifest' ) )
		$header_string .= ' manifest-src '. esc_html( get_option( 'http_security_csp_manifest' ) ).';';
	if ( get_option( 'http_security_csp_media' ) )
		$header_string .= ' media-src '. esc_html( get_option( 'http_security_csp_media' ) ).';';
	if ( get_option( 'http_security_csp_object' ) )
		$header_string .= ' object-src '. esc_html( get_option( 'http_security_csp_object' ) ).';';
	if ( get_option( 'http_security_csp_script' ) )
		$header_string .= ' script-src '. esc_html( get_option( 'http_security_csp_script' ) ).';';
	if ( get_option( 'http_security_csp_style' ) )
		$header_string .= ' style-src '. esc_html( get_option( 'http_security_csp_style' ) ).';';
	if ( get_option( 'http_security_csp_worker' ) )
		$header_string .= ' worker-src '. esc_html( get_option( 'http_security_csp_worker' ) ).';';
	if ( get_option( 'http_security_csp_base_uri' ) )
		$header_string .= ' base-uri '. esc_html( get_option( 'http_security_base_uri' ) ).';';
	if ( get_option( 'http_security_csp_plugin_types' ) )
		$header_string .= ' plugin-types '. esc_html( get_option( 'http_security_plugin_types' ) ).';';
	if ( get_option( 'http_security_csp_sandbox' ) )
		$header_string .= ' sandbox '. esc_html( get_option( 'http_security_csp_sandbox' ) ).';';
	if ( get_option( 'http_security_csp_form_action' ) )
		$header_string .= ' form-action '. esc_html( get_option( 'http_security_csp_form_action' ) ).';';
	if ( get_option( 'http_security_csp_frame_ancestors' ) )
		$header_string .= ' frame-ancestors '. esc_html( get_option( 'http_security_csp_frame_ancestors' ) ).';';
	if ( get_option( 'http_security_csp_block_all_mixed_content' ) )
		$header_string .= ' block-all-mixed-content;';
	if ( get_option( 'http_security_csp_require_sri_for' ) )
		$header_string .= ' require-sri-for '. esc_html( get_option( 'http_security_csp_require_sri_for' ) ).';';
	if ( get_option( 'http_security_csp_upgrade_insecure_requests' ) )
		$header_string .= ' upgrade-insecure-requests;';
	if ( get_option( 'http_security_csp_reportonly_flag' ) )
		$header_string .= ' report-uri /_/csp-reports';
	echo($header_string.'"'."\n");
}

if ( get_option( 'http_security_feature_policy_flag' ) ) {
	$header_string = 'Feature-Policy:';
	if ( get_option( 'http_security_feature_policy_autoplay' ) ) $header_string .= ' autoplay '. esc_html( get_option( 'http_security_feature_policy_autoplay' ) ).';';
	if ( get_option( 'http_security_feature_policy_camera' ) ) $header_string .= ' camera '. esc_html( get_option( 'http_security_feature_policy_camera' ) ).';';
	if ( get_option( 'http_security_feature_policy_document_domain' ) ) $header_string .= ' document-domain '. esc_html( get_option( 'http_security_feature_policy_document_domain' ) ).';';
	if ( get_option( 'http_security_feature_policy_encrypted_media' ) ) $header_string .= ' encrypted-media '. esc_html( get_option( 'http_security_feature_policy_encrypted_media' ) ).';';
	if ( get_option( 'http_security_feature_policy_fullscreen' ) ) $header_string .= ' fullscreen '. esc_html( get_option( 'http_security_feature_policy_fullscreen' ) ).';';
	if ( get_option( 'http_security_feature_policy_geolocation' ) ) $header_string .= ' geolocation '. esc_html( get_option( 'http_security_feature_policy_geolocation' ) ).';';
	if ( get_option( 'http_security_feature_policy_microphone' ) ) $header_string .= ' microphone '. esc_html( get_option( 'http_security_feature_policy_microphone' ) ).';';
	if ( get_option( 'http_security_feature_policy_midi' ) ) $header_string .= ' midi '. esc_html( get_option( 'http_security_feature_policy_midi' ) ).';';
	if ( get_option( 'http_security_feature_policy_payment' ) ) $header_string .= ' payment '. esc_html( get_option( 'http_security_feature_policy_payment' ) ).';';
	if ( get_option( 'http_security_feature_policy_vr' ) ) $header_string .= ' vr '. esc_html( get_option( 'http_security_feature_policy_vr' ) ).';';
	echo($header_string."\n");
}

if ( get_option( 'http_security_x_frame_flag' ) ) {
	switch ( get_option( 'http_security_x_frame_options' ) ) {
	case 1:
		echo( 'Header set X-Frame-Options: DENY'  ."\n");
		break;
	case 2:
		echo( 'Header set X-Frame-Options: SAMEORIGIN'  ."\n");
		break;
	case 3:
		$path = esc_html( get_option( 'http_security_x_frame_origin' ) );
		echo( 'Header set X-Frame-Options: ALLOW-FROM ' . $path  ."\n");
		break;
	}
}
if ( get_option( 'http_security_referrer_policy' ) )
	echo( 'Header set Referrer-Policy: '. esc_html( get_option( 'http_security_referrer_policy' ) ) ."\n");
if ( get_option( 'http_security_x_xss_protection' ) )
	echo( 'Header set X-XSS-Protection: "1; mode=block"' ."\n");
if ( get_option( 'http_security_x_content_type_options' ) )
	echo( 'Header set X-Content-Type-Options: nosniff' ."\n");
echo "\n".'# HTTP security settings end</textarea></blockquote>';

submit_button();
echo '</form>';

