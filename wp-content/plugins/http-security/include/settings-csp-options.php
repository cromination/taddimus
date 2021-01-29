<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

echo '<h2 class="nav-tab-wrapper"><a href="?page=http-security&tab=general-options" class="nav-tab">'. __('General options', 'http-security' ).'</a> <a href="?page=http-security&tab=csp-options" class="nav-tab nav-tab-active">'. __('CSP options', 'http-security' ).'</a> <a href="?page=http-security&tab=feature-policy" class="nav-tab">'. __('Feature Policy', 'http-security' ).'</a> <a href="?page=http-security&tab=htaccess" class="nav-tab">'. __('.htaccess (beta)', 'http-security' ).'</a></h2>';
echo '<p>'. __( 'For a complete description of these parameters, please refer to: <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Content-Security-Policy" rel="noopener">Content-Security-Policy</a> on the Mozilla Developer Network.', 'http-security' ).'</p>';
echo '<form method="post" action="options.php">';

settings_fields( 'http-security-csp' );

echo '<style>.http_security_csp_options {}</style>';
echo '<label for="http_security_csp_flag"><input name="http_security_csp_flag" type="checkbox" id="http_security_csp_flag" value="1" ' . checked( 1, get_option( 'http_security_csp_flag' ), false ) . ' />'. __( 'Enable Content Security Policy', 'http-security' ).'</label> <label for="http_security_csp_reportonly_flag"><input name="http_security_csp_reportonly_flag" type="checkbox" id="http_security_csp_reportonly_flag" value="1" ' . checked( 1, get_option( 'http_security_csp_reportonly_flag' ), false ) . ' />'. __( 'Report only', 'http-security' ).'</label><br />';
echo '<blockquote><table>';
echo '<tr class="http_security_csp_options"><td colspan="2"><h3>'. __( 'Fetch directives', 'http-security' ).'</h3></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_child">child-src</td><td><input name="http_security_csp_child" type="text" id="http_security_csp_child" value="'. esc_html( get_option( 'http_security_csp_child' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_connect">connect-src</td><td><input name="http_security_csp_connect" type="text" id="http_security_csp_connect" value="'. esc_html( get_option( 'http_security_csp_connect' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_default">default-src</td><td><input name="http_security_csp_default" type="text" id="http_security_csp_default" value="'. esc_html( get_option( 'http_security_csp_default' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_font">font-src</td><td><input name="http_security_csp_font" type="text" id="http_security_csp_font" value="'. esc_html( get_option( 'http_security_csp_font' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_frame">frame-src</td><td><input name="http_security_csp_frame" type="text" id="http_security_csp_frame" value="'. esc_html( get_option( 'http_security_csp_frame' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_img">img-src</td><td><input name="http_security_csp_img" type="text" id="http_security_csp_img" value="'. esc_html( get_option( 'http_security_csp_img' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_manifest">manifest-src</td><td><input name="http_security_csp_manifest" type="text" id="http_security_csp_manifest" value="'. esc_html( get_option( 'http_security_csp_manifest' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_media">media-src</td><td><input name="http_security_csp_media" type="text" id="http_security_csp_media" value="'. esc_html( get_option( 'http_security_csp_media' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_object">object-src</td><td><input name="http_security_csp_object" type="text" id="http_security_csp_object" value="'. esc_html( get_option( 'http_security_csp_object' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_script">script-src</td><td><input name="http_security_csp_script" type="text" id="http_security_csp_script" value="'. esc_html( get_option( 'http_security_csp_script' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_style">style-src</td><td><input name="http_security_csp_style" type="text" id="http_security_csp_style" value="'. esc_html( get_option( 'http_security_csp_style' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_worker">worker-src</td><td><input name="http_security_csp_worker" type="text" id="http_security_csp_worker" value="'. esc_html( get_option( 'http_security_csp_worker' ) ) .'" size="80" /></label></td></tr>';

echo '<tr class="http_security_csp_options"><td colspan="2"><h3>'. __( 'Document directives', 'http-security' ).'</h3></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_base_uri">base-uri</td><td><input name="http_security_csp_base_uri" type="text" id="http_security_csp_base_uri" value="'. esc_html( get_option( 'http_security_csp_base_uri' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_plugin_types">plugin-types</td><td><input name="http_security_csp_plugin_types" type="text" id="http_security_csp_plugin_types" value="'. esc_html( get_option( 'http_security_csp_plugin_types' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_sandbox">sandbox</td><td><input name="http_security_csp_sandbox" type="text" id="http_security_csp_sandbox" value="'. esc_html( get_option( 'http_security_csp_sandbox' ) ) .'" size="80" /></label></td></tr>';

echo '<tr class="http_security_csp_options"><td colspan="2"><h3>'. __( 'Navigation directives', 'http-security' ).'</h3></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_form_action">form-action</td><td><input name="http_security_csp_form_action" type="text" id="http_security_csp_form_action" value="'. esc_html( get_option( 'http_security_csp_form_action' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_frame_ancestors">frame-ancestors</td><td><input name="http_security_csp_frame_ancestors" type="text" id="http_security_csp_frame_ancestors" value="'. esc_html( get_option( 'http_security_csp_frame_ancestors' ) ) .'" size="80" /></label></td></tr>';

echo '<tr class="http_security_csp_options"><td colspan="2"><h3>'. __( 'Other directives', 'http-security' ).'</h3></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_block_all_mixed_content">block-all-mixed-content</td><td><input name="http_security_csp_block_all_mixed_content" type="checkbox" id="http_security_csp_block_all_mixed_content" value="1" ' . checked( 1, get_option( 'http_security_csp_block_all_mixed_content' ), false ) . ' /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_require_sri_for">require-sri-for</td><td><input name="http_security_csp_require_sri_for" type="text" id="http_security_csp_require_sri_for" value="'. esc_html( get_option( 'http_security_csp_require_sri_for' ) ) .'" size="80" /></label></td></tr>';
echo '<tr class="http_security_csp_options"><td><label for="http_security_csp_upgrade_insecure_requests">upgrade-insecure-requests</td><td><input name="http_security_csp_upgrade_insecure_requests" type="checkbox" id="http_security_csp_upgrade_insecure_requests" value="1" ' . checked( 1, get_option( 'http_security_csp_upgrade_insecure_requests' ), false ) . ' /></label></td></tr>';
echo '</table></blockquote>';

submit_button();
echo '</form>';
