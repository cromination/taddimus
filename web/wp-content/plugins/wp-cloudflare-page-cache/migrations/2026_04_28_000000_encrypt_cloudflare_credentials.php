<?php

use SPC\Modules\Settings_Manager;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;

return new class extends \ThemeisleSDK\Modules\Abstract_Migration {
	public function up() {
		$stored_config = get_option( Settings_Store::CONFIG_OPTION, [] );

		if ( ! is_array( $stored_config ) || empty( $stored_config ) ) {
			return;
		}

		$settings_manager = new Settings_Manager();
		$store            = Settings_Store::get_instance();
		$needs_save       = false;

		foreach ( $settings_manager->get_fields( [ 'encrypted' => true ] ) as $key => $field ) {
			if ( $store->is_overridden( $key ) || ! array_key_exists( $key, $stored_config ) ) {
				continue;
			}

			$value = $stored_config[ $key ];

			if ( ! is_string( $value ) || '_enc' === substr( $value, -4 ) || '' === $value ) {
				continue;
			}

			$store->delete( $key );
			$store->set( $key, $value );
			$needs_save = true;
		}

		if ( $needs_save ) {
			Logger::log( 'migration::encrypt_cloudflare_credentials', 'Encrypting plaintext Cloudflare credentials.' );
			$store->save();
		}
	}
};
