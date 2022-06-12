<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

echo '<h2 class="nav-tab-wrapper"><a href="?page=http-security&tab=general-options" class="nav-tab nav-tab-active">'. __('General options', 'http-security' ).'</a> <a href="?page=http-security&tab=csp-options" class="nav-tab">'. __('CSP options', 'http-security' ).'</a> <a href="?page=http-security&tab=feature-policy" class="nav-tab">'. __('Feature Policy', 'http-security' ).'</a> <a href="?page=http-security&tab=htaccess" class="nav-tab">'. __('.htaccess (beta)', 'http-security' ).'</a></h2>';

echo '<p>'. __( 'For more information about these security hacks, please make sure to read my article about <a href="https://www.carlconrad.net/en/2016/11/18/secure-website-http-headers-instructions/" target="_blank" rel="noopener">how to improve your website security with HTTP header instructions</a>.', 'http-security' ).'</p>';

echo '<form method="post" action="options.php">';

settings_fields( 'http-security' );

if ( http_security_isSecure() ) {
	echo '<h3>'. __( 'HSTS', 'http-security' ).'</h3>';
	echo '<label for="http_security_sts_flag"><input name="http_security_sts_flag" type="checkbox" id="http_security_sts_flag" value="1" ' . checked( 1, get_option( 'http_security_sts_flag' ), false ) . ' />'. __( 'Force HTTPS protocol', 'http-security' ).'</label><br />';
	echo '<blockquote id="http_security_sts_options"><label for="http_security_sts_subdomains_flag"><input name="http_security_sts_subdomains_flag" type="checkbox" id="http_security_sts_subdomains_flag" value="1" ' . checked( 1, get_option( 'http_security_sts_subdomains_flag' ), false ) . ' />'. __( 'Include subdomains', 'http-security' ).'</label><br /><label for="http_security_sts_preload_flag"><input name="http_security_sts_preload_flag" type="checkbox" id="http_security_sts_preload_flag" value="1" ' . checked( 1, get_option( 'http_security_sts_preload_flag' ), false ) . ' />'. __( 'Preload', 'http-security' ).'</label> <a href="https://hstspreload.appspot.com/" target="_blank">'. __( 'Submit domain preload to browsers', 'http-security' ).'</a><br /><label for="http_security_sts_max_age">'. __('Max age:', 'http-security' ) .' <input name="http_security_sts_max_age" type="text" id="http_security_sts_max_age" value="'. esc_html( get_option( 'http_security_sts_max_age' ) ) .'" size="10" /> '. __('seconds', 'http-security' ) .' (86400 = '. __('one day', 'http-security' ) .', 31536000 = '. __('one year', 'http-security' ) .', 2592000 ('. __('recommended', 'http-security' ) .')</label></blockquote>';
}
else {
	echo '<p class="http-security-warning"><span class="dashicons dashicons-warning"></span> <strong>'. __( 'You are not running HTTPS.', 'http-security' ).'</strong></p>';
}
echo '<h3>'. __( 'Expect-CT', 'http-security' ).'</h3>';
echo '<label for="http_security_expect_ct_flag"><input name="http_security_expect_ct_flag" type="checkbox" id="http_security_expect_ct_flag" value="1" ' . checked( 1, get_option( 'http_security_expect_ct_flag' ), false ) . ' />'. __( 'Enable Expect-CT', 'http-security' ).'</label><br />';

echo '<blockquote id="http_security_expect_ct_options"><label for="http_security_expect_ct_subdomains_flag"><input name="http_security_expect_ct_enforce_flag" type="checkbox" id="http_security_expect_ct_enforce_flag" value="1" ' . checked( 1, get_option( 'http_security_expect_ct_enforce_flag' ), false ) . ' />'. __( 'Enforce', 'http-security' ).'</label><br /><label for="http_security_expect_ct_max_age">'. __('Max age:', 'http-security' ) .' <input name="http_security_expect_ct_max_age" type="text" id="http_security_expect_ct_max_age" value="'. esc_html( get_option( 'http_security_expect_ct_max_age' ) ) .'" size="10" /> '. __('seconds', 'http-security' ) .' (86400 = '. __('one day', 'http-security' ) .', 31536000 = '. __('one year', 'http-security' ) .', 2592000 ('. __('recommended', 'http-security' ) .')</label><br /><label for="http_security_expect_ct_report_uri">'. __( 'Report URI:', 'http-security' ).'<input name="http_security_expect_ct_report_uri" type="text" id="http_security_expect_ct_report_uri" value="'. esc_html( get_option( 'http_security_expect_ct_report_uri' ) ) .'" size="40" /></label></blockquote>';

echo '<h3>'. __( 'X-frame-options', 'http-security' ).'</h3>';
echo '<label for="http_security_x_frame_flag"><input name="http_security_x_frame_flag" type="checkbox" id="http_security_x_frame_flag" value="1" ' . checked( 1, get_option( 'http_security_x_frame_flag' ), false ) . ' />'. __( 'Manage display in remote frames', 'http-security' ).'</label><br />';
echo '<blockquote><table>';
echo '<tr class="http_security_x_frame_options"><td></td><td><label for="http_security_x_frame_deny"><input name="http_security_x_frame_options" type="radio" id="http_security_x_frame_deny" value="1" ' . checked( 1, get_option( 'http_security_x_frame_options' ), false ) . ' />DENY</label></td></tr>';
echo '<tr class="http_security_x_frame_options"><td></td><td><label for="http_security_x_frame_sameorigin"><input name="http_security_x_frame_options" type="radio" id="http_security_x_frame_sameorigin" value="2" ' . checked( 2, get_option( 'http_security_x_frame_options' ), false ) . '/>SAMEORIGIN</label></td></tr>';
echo '<tr class="http_security_x_frame_options"><td></td><td><label for="http_security_x_frame_allow_from"><input name="http_security_x_frame_options" type="radio" id="http_security_x_frame_allow_from" value="3"  ' . checked( 3, get_option( 'http_security_x_frame_options' ), false ) . '/>ALLOW-FROM</label></td></tr>';
echo '<tr class="http_security_x_frame_options"><td></td><td><label for="http_security_x_frame_origin">Allow from: <input name="http_security_x_frame_origin" type="text" id="http_security_x_frame_origin" value="'. esc_html( get_option( 'http_security_x_frame_origin' ) ) .'" size="80" /></label></td></tr>';
echo '</table></blockquote>';

echo '<h3>'. __( 'Referrer policy', 'http-security' ).'</h3>';
echo '<label for="http_security_referrer_policy">'. __( 'Referrer policy', 'http-security' ).': <select name="http_security_referrer_policy"><option value="" ' . selected( '', get_option( 'http_security_referrer_policy' ), false ) . '></option><option value="no-referrer" ' . selected( 'no-referrer', get_option( 'http_security_referrer_policy' ), false ) . '>no-referrer</option><option value="no-referrer-when-downgrade" ' . selected( 'no-referrer-when-downgrade', get_option( 'http_security_referrer_policy' ), false ) . '>no-referrer-when-downgrade</option><option value="same-origin" ' . selected( 'same-origin', get_option( 'http_security_referrer_policy' ), false ) . '>same-origin</option><option value="origin" ' . selected( 'origin', get_option( 'http_security_referrer_policy' ), false ) . '>origin</option><option value="strict-origin" ' . selected( 'strict-origin', get_option( 'http_security_referrer_policy' ), false ) . '>strict-origin</option><option value="origin-when-cross-origin" ' . selected( 'origin-when-cross-origin', get_option( 'http_security_referrer_policy' ), false ) . '>origin-when-cross-origin</option><option value="strict-origin-when-cross-origin" ' . selected( 'strict-origin-when-cross-origin', get_option( 'http_security_referrer_policy' ), false ) . '>strict-origin-when-cross-origin</option><option value="unsafe-url" ' . selected( 'unsafe-url', get_option( 'http_security_referrer_policy' ), false ) . '>unsafe-url</option></select></label><br />';

echo '<h3>'. __( 'Other options', 'http-security' ).'</h3>';
echo '<label for="http_security_x_xss_protection"><input name="http_security_x_xss_protection" type="checkbox" id="http_security_x_xss_protection" value="1" ' . checked( 1, get_option( 'http_security_x_xss_protection' ), false ) . ' />'. __( 'Force XSS protection', 'http-security' ).'</label><br />';

echo '<label for="http_security_x_content_type_options"><input name="http_security_x_content_type_options" type="checkbox" id="http_security_x_content_type_options" value="1" ' . checked( 1, get_option( 'http_security_x_content_type_options' ), false ) . ' />'. __( 'Disable content sniffing', 'http-security' ).'</label><br />';

echo '<label for="http_security_remove_php_version"><input name="http_security_remove_php_version" type="checkbox" id="http_security_remove_php_version" value="1" ' . checked( 1, get_option( 'http_security_remove_php_version' ), false ) . ' />'. __( 'Remove PHP version information from HTTP header', 'http-security' ).'</label><br />';

echo '<label for="http_security_remove_wordpress_version"><input name="http_security_remove_wordpress_version" type="checkbox" id="http_security_remove_wordpress_version" value="1" ' . checked( 1, get_option( 'http_security_remove_wordpress_version' ), false ) . ' />'. __( 'Remove WordPress version information from header', 'http-security' ).'</label><br />';

submit_button();
echo '</form>';
