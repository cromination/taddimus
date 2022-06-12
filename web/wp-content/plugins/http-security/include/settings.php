<?php

if ( ! defined( 'ABSPATH' ) ) { 
    exit;
}

function http_security_network_options_page() {
	add_submenu_page( 'settings.php', __( 'HTTP Security Network Options', 'http-security' ), 'HTTP Security', 'manage_network_options', 'http-security', 'http_security_network_options_page_html');
}

function http_security_network_options_page_html() {
	echo '<div class="wrap">';
	echo '<h1>'. __( 'HTTP Security Network Options', 'http-security' ).'</h1>';
	echo '<p>'. __( 'The HTTP protocol provides various header instructions allowing simple improvement of your web site security. As usual, make sure to run full tests on your web site as some options may result in some features stop working.', 'http-security' ).'</p>';
	echo '<p>'. __( 'Values of the following options from the main site will be set as default for newly created sites.', 'http-security' ).'</p>';
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

	echo '<label for="http_security_remove_php_version"><input name="http_security_remove_php_version" type="checkbox" id="http_security_remove_php_version" value="1" ' . checked( 1, get_option( 'http_security_remove_php_version' ), false ) . ' />'. __( 'Remove PHP version information from HTTP header', 'http-security' ).'</label><br />';

	echo '<label for="http_security_remove_wordpress_version"><input name="http_security_remove_wordpress_version" type="checkbox" id="http_security_remove_wordpress_version" value="1" ' . checked( 1, get_option( 'http_security_remove_wordpress_version' ), false ) . ' />'. __( 'Remove WordPress version information from header', 'http-security' ).'</label><br />';

	submit_button();
	echo '</form>';
	if ( username_exists( 'admin' ) ) {
		echo '<p class="http-security-warning"><span class="dashicons dashicons-warning"></span> '. __('You still have an administrator account with the user name security <strong>admin</strong>. This is a major security flaw, you should consider renaming this account.', 'http-security') .'</p>';
	}
	echo '</div>';
}

function http_security_options_page() {
//	add_submenu_page( 'options-general.php', 'HTTP Security Options', 'HTTP Security', 'manage_options', 'http-security', 'http_security_options', '', '');
	$count = http_security_issues_count();
	if ( $count > 0 )
		$menu_entry = 'HTTP Security <span class="update-plugins count-1"><span class="update-count">'.$count.'</span></span>';
	else
		$menu_entry = __( 'HTTP Security', 'http-security' );
	add_options_page( __( 'HTTP Security Options', 'http-security' ), $menu_entry, 'manage_options', 'http-security', 'http_security_options_page_html');
}

function http_security_options_page_html() {
	if ( !current_user_can( 'manage_options' ) )
		wp_die( __( 'You do not have sufficient permissions to access this page.', 'http-security' ) );

	echo '<style>.http-security-warning {background-color:yellow;padding:5px;text-align:center;}</style>';
	echo '<div class="wrap">';
	echo '<h1>'. __( 'HTTP Security Options', 'http-security' ).'</h1>';

	echo '<p>'. __( 'The HTTP protocol provides various header instructions allowing simple improvement of your web site security. As usual, make sure to run full tests on your web site as some options may result in some features stop working.', 'http-security' ).'</p>';

	if ( !isset( $_GET['tab'] ) )
		$tab = 'general-options';
	else
		$tab = $_GET['tab'];

	switch( $tab ) {
		default:
		case 'general-options':
			include 'settings-general-options.php';
			break;
		case 'csp-options':
			include 'settings-csp-options.php';
			break;
		case 'feature-policy':
			include 'settings-feature-policy-options.php';
			break;
		case 'htaccess':
			include 'settings-htaccess.php';
			break;
	}

	if ( username_exists( 'admin' ) ) {
		echo '<p class="http-security-warning"><span class="dashicons dashicons-warning"></span> '. __('You still have an administrator account with the user name <strong>admin</strong>. This is a major security flaw, you should consider renaming this account.', 'http-security') .'</p>';
	}
	echo '</div>';
	?>
	<script>
	jQuery(function() {
		jQuery("#http_security_sts_flag").change(function() {
			if( jQuery('input[name=http_security_sts_flag]').is(':checked') ){
				jQuery("#http_security_sts_options").show();
			} else {
				jQuery("#http_security_sts_options").hide();
			}
		})

		jQuery("#http_security_expect_ct_flag").change(function() {
			if( jQuery('input[name=http_security_expect_ct_flag]').is(':checked') ){
				jQuery("#http_security_expect_ct_options").show();
			} else {
				jQuery("#http_security_expect_ct_options").hide();
			}
		})

		jQuery("#http_security_csp_flag").change(function() {
			if( jQuery('input[name=http_security_csp_flag]').is(':checked') ){
				jQuery(".http_security_csp_options").show();
			} else {
				jQuery(".http_security_csp_options").hide();
			}
		})

		jQuery("#http_security_feature_policy_flag").change(function() {
			if( jQuery('input[name=http_security_feature_policy_flag]').is(':checked') ){
				jQuery(".http_security_feature_policy_options").show();
			} else {
				jQuery(".http_security_feature_policy_options").hide();
			}
		})

		jQuery("#http_security_x_frame_flag").change(function() {
			if( jQuery('input[name=http_security_x_frame_flag]').is(':checked') ){
				jQuery(".http_security_x_frame_options").show();
			} else {
				jQuery(".http_security_x_frame_options").hide();
			}
		})
	});
	</script>
	<?php

}

function register_http_security_settings() {
	register_setting( 'http-security', 'http_security_remove_php_version' );
//	https://wpengineer.com/2139/adding-settings-to-an-existing-page-using-the-settings-api/
	register_setting( 'http-security', 'http_security_remove_wordpress_version' );
	register_setting( 'http-security', 'http_security_sts_flag' );
	register_setting( 'http-security', 'http_security_sts_subdomains_flag' );
	register_setting( 'http-security', 'http_security_sts_preload_flag' );
	register_setting( 'http-security', 'http_security_sts_max_age', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security', 'http_security_expect_ct_flag' );
	register_setting( 'http-security', 'http_security_expect_ct_max_age', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security', 'http_security_expect_ct_enforce_flag' );
	register_setting( 'http-security', 'http_security_expect_ct_report_uri', 'http_security_options_sanitize_text_field' );
// 	register_setting( 'http-security', 'http_security_pkp_flag' );
// 	register_setting( 'http-security', 'http_security_pkp_keys' );
// 	register_setting( 'http-security', 'http_security_pkp_subdomains_flag' );
// 	register_setting( 'http-security', 'http_security_pkp_reportonly_flag' );
	register_setting( 'http-security', 'http_security_referrer_policy' );
	register_setting( 'http-security', 'http_security_x_frame_flag' );
	register_setting( 'http-security', 'http_security_x_frame_options' );
	register_setting( 'http-security', 'http_security_x_frame_origin', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security', 'http_security_x_xss_protection' );
	register_setting( 'http-security', 'http_security_x_content_type_options' );

	register_setting( 'http-security-csp', 'http_security_csp_flag' );
	register_setting( 'http-security-csp', 'http_security_csp_reportonly_flag' );
	register_setting( 'http-security-csp', 'http_security_csp_child', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_connect', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_default', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_font', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_frame', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_img', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_manifest', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_media', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_object', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_script', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_style', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_worker', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_base_uri', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_plugin_types', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_sandbox', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_form_action', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_frame_ancestors', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_block_all_mixed_content' );
	register_setting( 'http-security-csp', 'http_security_csp_require_sri_for', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-csp', 'http_security_csp_upgrade_insecure_requests' );

	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_flag' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_autoplay', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_autoplay_origin', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_camera', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_document_domain', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_encrypted_media', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_fullscreen', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_geolocation', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_microphone', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_midi', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_payment', 'http_security_options_sanitize_text_field' );
	register_setting( 'http-security-feature-policy', 'http_security_feature_policy_vr', 'http_security_options_sanitize_text_field' );

	register_setting( 'http-security-htaccess', 'http_security_htaccess_flag' );
}

function http_security_options_sanitize_text_field( $input ) {
	$input = sanitize_text_field( $input );
	return $input;
}
function http_security_copy_main_site_options( $blog_id, $user_id, $domain, $path, $site_id, $meta ) {
    $mainsite = get_option( 'pagenavi_options' );
    switch_to_blog( $blog_id );
    update_option( 'pagenavi_options', $mainsite );
    restore_current_blog();
}