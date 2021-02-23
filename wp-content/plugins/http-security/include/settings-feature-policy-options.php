<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

echo '<h2 class="nav-tab-wrapper"><a href="?page=http-security&tab=general-options" class="nav-tab">'. __('General options', 'http-security' ).'</a> <a href="?page=http-security&tab=csp-options" class="nav-tab">'. __('CSP options', 'http-security' ).'</a> <a href="?page=http-security&tab=feature-policy" class="nav-tab nav-tab-active">'. __('Feature Policy', 'http-security' ).'</a> <a href="?page=http-security&tab=htaccess" class="nav-tab">'. __('.htaccess (beta)', 'http-security' ).'</a></h2>';
echo '<p>'. __( 'The HTTP Feature-Policy header provides a mechanism to allow and deny the use of browser features in its own frame, and in iframes that it embeds. For a complete description of these parameters, please refer to: <a href="https://developer.mozilla.org/en-US/docs/Web/HTTP/Headers/Feature-Policy" rel="noopener">Feature-Policy</a> on the Mozilla Developer Network.', 'http-security' ).'</p>';
echo '<form method="post" action="options.php">';

settings_fields( 'http-security-feature-policy' );

echo '<style>.http_security_feature_policy_options {}</style>';
echo '<label for="http_security_feature_policy_flag"><input name="http_security_feature_policy_flag" type="checkbox" id="http_security_feature_policy_flag" value="1" ' . checked( 1, get_option( 'http_security_feature_policy_flag' ), false ) . ' />'. __( 'Enable Feature Policy', 'http-security' ).'</label><br />';
echo '<blockquote><table>';
echo "<tr><th>Feature</th><th>Value (please specify)</th></tr>";
echo '<tr><td><label for="http_security_feature_policy_autoplay">autoplay</td><td><input name="http_security_feature_policy_autoplay" type="text" id="http_security_feature_policy_autoplay" value="'. esc_html( get_option( 'http_security_feature_policy_autoplay' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_camera">camera</td><td><input name="http_security_feature_policy_camera" type="text" id="http_security_feature_policy_camera" value="'. esc_html( get_option( 'http_security_feature_policy_camera' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_document_domain">document-domain</td><td><input name="http_security_feature_policy_document_domain" type="text" id="http_security_feature_policy_document_domain" value="'. esc_html( get_option( 'http_security_feature_policy_document_domain' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_encrypted_media">encrypted-media</td><td><input name="http_security_feature_policy_encrypted_media" type="text" id="http_security_feature_policy_encrypted_media" value="'. esc_html( get_option( 'http_security_feature_policy_encrypted_media' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_fullscreen">fullscreen</td><td><input name="http_security_feature_policy_fullscreen" type="text" id="http_security_feature_policy_fullscreen" value="'. esc_html( get_option( 'http_security_feature_policy_fullscreen' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_geolocation">geolocation</td><td><input name="http_security_feature_policy_geolocation" type="text" id="http_security_feature_policy_geolocation" value="'. esc_html( get_option( 'http_security_feature_policy_geolocation' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_microphone">microphone</td><td><input name="http_security_feature_policy_microphone" type="text" id="http_security_feature_policy_microphone" value="'. esc_html( get_option( 'http_security_feature_policy_microphone' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_midi">midi</td><td><input name="http_security_feature_policy_midi" type="text" id="http_security_feature_policy_midi" value="'. esc_html( get_option( 'http_security_feature_policy_midi' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_payment">payment</td><td><input name="http_security_feature_policy_payment" type="text" id="http_security_feature_policy_payment" value="'. esc_html( get_option( 'http_security_feature_policy_payment' ) ) .'" size="80" /></label></td></tr>';
echo '<tr><td><label for="http_security_feature_policy_vr">vr</td><td><input name="http_security_feature_policy_vr" type="text" id="http_security_feature_policy_vr" value="'. esc_html( get_option( 'http_security_feature_policy_vr' ) ) .'" size="80" /></label></td></tr>';
echo '</table></blockquote>';

submit_button();
echo '</form>';
