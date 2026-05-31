<?php

use SPC\Constants;
use SPC\Modules\Speculative_Loading;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;

return new class extends \ThemeisleSDK\Modules\Abstract_Migration {
	public function up() {
		$stored_config = get_option( Settings_Store::CONFIG_OPTION, [] );

		if ( ! is_array( $stored_config ) ) {
			return;
		}

		$hover    = ! empty( $stored_config['cf_prefetch_urls_on_hover'] ) && (int) $stored_config['cf_prefetch_urls_on_hover'] === 1;
		$viewport = ! empty( $stored_config['cf_prefetch_urls_viewport'] ) && (int) $stored_config['cf_prefetch_urls_viewport'] === 1;

		// Neither were set, so we don't need to migrate.
		if( ! $hover && ! $viewport ) {
			return;
		}

		if( $viewport ) {
			$mode = Speculative_Loading::PREFETCH_MODE_VIEWPORT;
		} elseif( $hover ) {
			$mode = Speculative_Loading::PREFETCH_MODE_HOVER;
		} else {
			$mode = Speculative_Loading::PREFETCH_MODE_OFF;
		}

		$settings_store = Settings_Store::get_instance();

		// If the mode is already set to the new value, we don't need to migrate - includes the default value.
		if( $mode === $settings_store->get( Constants::SETTING_PREFETCH_URLS_MODE ) ) {
			return;
		}

		$settings_store->set( Constants::SETTING_PREFETCH_URLS_MODE, $mode )->save();

		Logger::log( 'migration::combine_prefetch_urls_mode', 'Combined prefetch toggles into mode=' . $mode );
	}
};
