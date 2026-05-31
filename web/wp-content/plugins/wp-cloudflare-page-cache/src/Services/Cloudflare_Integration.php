<?php

namespace SPC\Services;

use SPC\Constants;
use SPC\Modules\Preloader_Process;
use SPC\Utils\Htaccess_Writer;
use SPC\Utils\Logger;

class Cloudflare_Integration {

	/**
	 * @var Settings_Store
	 */
	private $settings;

	private string $api_key               = '';
	private string $email                 = '';
	private string $api_token             = '';
	private string $cache_ruleset_id      = ''; // Ruleset related to `http_request_cache_settings` phase.
	private string $cache_ruleset_rule_id = '';

	/**
	 * @var Cloudflare_Client
	 */
	private $client;

	public function __construct() {
		$this->settings              = Settings_Store::get_instance();
		$this->api_key               = $this->settings->get_cloudflare_api_key();
		$this->email                 = $this->settings->get_cloudflare_api_email();
		$this->api_token             = $this->settings->get_cloudflare_api_token();
		$this->cache_ruleset_id      = $this->settings->get( Constants::RULESET_ID_CACHE, '' );
		$this->cache_ruleset_rule_id = $this->settings->get( Constants::RULE_ID_CACHE, '' );
		$this->client                = new Cloudflare_Client();
	}

	/**
	 * Check if the Cloudflare API is enabled.
	 *
	 * @return bool
	 */
	public function is_enabled() {
		return (
			$this->settings->is_cloudflare_connected() &&
			(
				! empty( $this->email ) && ! empty( $this->api_key ) ||
				! empty( $this->api_token )
			)
		);
	}

	/**
	 * Check if the cache rule is set.
	 *
	 * @return bool
	 */
	public function has_cache_rule() {
		return ! empty( $this->cache_ruleset_id ) && ! empty( $this->cache_ruleset_rule_id );
	}

	/**
	 * Get the zone ID list.
	 *
	 * @param string $error The error message.
	 *
	 * @return array<string, string>|false
	 */
	public function get_zone_id_list( &$error ) {
		return $this->client->get_zone_id_list( $error );
	}

	/**
	 * Change the browser TTL.
	 *
	 * @param number $ttl The new TTL value.
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function change_browser_cache_ttl( $ttl, &$error ) {
		return $this->client->change_browser_cache_ttl( $ttl, $error );
	}

	/**
	 * Delete the Page Rule
	 *
	 * @param string $page_rule_id The page rule id to delete.
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function delete_page_rule( $page_rule_id, &$error ) {
		return $this->client->delete_page_rule( $page_rule_id, $error );
	}

	/**
	 * Purge the Cloudflare cache
	 *
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function purge_cache( &$error ) {
		$purge = $this->client->purge_cache( $error );

		if ( $purge ) {
			Logger::log( 'cloudflare::purge_cache', 'Cache purged successfully.' );

			if ( $this->settings->get( Constants::SETTING_ENABLE_PRELOADER, 1 ) > 0 ) {
				Preloader_Process::start_for_all_urls();
			}
		}

		return $purge;
	}

	/**
	 * Purge specific URLs from Cloudflare Cache
	 *
	 * @param string[] $urls The URLs to purge.
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function purge_cache_urls( $urls, &$error ) {
		return $this->client->purge_cache_urls( $urls, $error );
	}

	/**
	 * Get the current account ID.
	 *
	 * @param string $error The error message.
	 *
	 * @return string
	 */
	public function get_current_account_id( &$error ) {
		return $this->client->get_account_id( $error );
	}

	/**
	 * Disable page cache.
	 *
	 * @param string $error The error message.
	 * @param bool $disable_cache If true, it will disable the cache.
	 *
	 * @return bool
	 */
	public function disable_page_cache( &$error = '', $disable_cache = true ) {
		// Reset old browser cache TTL
		if ( (int) $this->settings->get( Constants::SETTING_OLD_BC_TTL, 0 ) !== 0 ) {
			$this->change_browser_cache_ttl( $this->settings->get( Constants::SETTING_OLD_BC_TTL, 0 ), $error );
		}

		// Delete page rules
		if ( $this->settings->get( Constants::RULE_ID_PAGE, '' ) !== '' && ! $this->delete_page_rule( $this->settings->get( Constants::RULE_ID_PAGE, '' ), $error ) ) {
			return false;
		} else {
			$this->settings->set( Constants::RULE_ID_PAGE, '' );
		}

		if ( $this->settings->get( Constants::RULE_ID_BYPASS_BACKEND, '' ) !== '' && ! $this->delete_page_rule( $this->settings->get( Constants::RULE_ID_BYPASS_BACKEND, '' ), $error ) ) {
			return false;
		} else {
			$this->settings->set( Constants::RULE_ID_BYPASS_BACKEND, '' );
		}

		$this->delete_cache_rule( $error );
		$this->settings->set( Constants::RULE_ID_CACHE, '' );
		$this->settings->set( Constants::RULESET_ID_CACHE, '' );
		$this->purge_cache( $error );

		Htaccess_Writer::reset();

		if ( $disable_cache ) {
			$this->settings->set( Constants::SETTING_CF_CACHE_ENABLED, 0 );
		}
		$this->settings->save();

		return true;
	}

	/**
	 * Delete the legacy page rules.
	 *
	 * @param string $error The error message.
	 *
	 * @return void
	 */
	public function delete_legacy_page_rules( &$error ) {
		// Delete page rule.
		$page_rule_id = $this->settings->get( Constants::RULE_ID_PAGE, '' );
		if ( ! empty( $page_rule_id ) && $this->delete_page_rule( $page_rule_id, $error ) ) {
			$this->settings->set( Constants::RULE_ID_PAGE, '' );
		}

		// Delete the legacy backend bypass page rule.
		$legacy_page_rule_id = $this->settings->get( Constants::RULE_ID_BYPASS_BACKEND, '' );
		if ( ! empty( $legacy_page_rule_id ) && $this->delete_page_rule( $legacy_page_rule_id, $error ) ) {
			$this->settings->set( Constants::RULE_ID_BYPASS_BACKEND, '' );
		}
	}

	/**
	 * Enable page cache.
	 *
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function enable_page_cache( &$error ) {
		$current_cf_browser_ttl = $this->client->get_current_browser_cache_ttl( $error );

		if ( $current_cf_browser_ttl !== false ) {
			$this->settings->set( Constants::SETTING_OLD_BC_TTL, $current_cf_browser_ttl );
		}

		// Step 1 - set browser cache ttl to zero (Respect Existing Headers)
		if ( ! $this->change_browser_cache_ttl( 0, $error ) ) {
			$this->settings->set( Constants::SETTING_CF_CACHE_ENABLED, 0 );
			$this->settings->save();

			return false;
		}

		// Step 2 - Delete the current cache configuration and page rule.
		$this->delete_legacy_page_rules( $error );

		// Get existing cache ruleset id.
		if ( empty( $this->cache_ruleset_id ) ) {
			$this->cache_ruleset_id = $this->client->get_ruleset_id( $error );
			$this->settings->set( Constants::RULESET_ID_CACHE, $this->cache_ruleset_id );
		}

		// Step 3a - create a new cache ruleset if it does not exist.
		if ( empty( $this->cache_ruleset_id ) ) {
			$this->cache_ruleset_id = $this->client->create_ruleset( $error );
			$this->settings->set( Constants::RULESET_ID_CACHE, $this->cache_ruleset_id );
		}

		// If we still haven't got the cache ruleset id, then we can't proceed.
		if ( empty( $this->cache_ruleset_id ) ) {
			return false;
		}

		// Ensure the ruleset id is persisted in settings even when it was populated
		// earlier (e.g. via pull_existing_cache_rule) and a later refresh() wiped it.
		$this->settings->set( Constants::RULESET_ID_CACHE, $this->cache_ruleset_id );

		// Setp 3b - create a standard rule for the cache ruleset.
		$this->cache_ruleset_rule_id = $this->create_cache_rule( $error );
		$this->settings->set( Constants::RULE_ID_CACHE, $this->cache_ruleset_rule_id );

		// If we still haven't got the cache ruleset rule id, then we can't proceed.
		if ( empty( $this->cache_ruleset_rule_id ) ) {
			return false;
		}

		// Update config data
		$this->settings->save();

		// Step 4 - purge cache
		$this->purge_cache( $error );

		$this->settings->set( Constants::SETTING_CF_CACHE_ENABLED, 1 );
		$this->settings->save();
		Htaccess_Writer::write( $error );

		return true;
	}

	/**
	 * Create a cache rule in the Cloudflare API.
	 *
	 * @param string $error The error message.
	 *
	 * @return string The created rule ID. Empty string if the rule was not created.
	 */
	public function create_cache_rule( &$error ) {
		$this->cache_ruleset_rule_id = $this->client->create_rule( $error );

		if ( ! empty( $this->cache_ruleset_rule_id ) ) {
			$this->settings->set( Constants::RULE_ID_CACHE, $this->cache_ruleset_rule_id );
			$this->settings->save();
		}

		do_action( 'swcfpc_after_create_cache_rule' );

		return $this->cache_ruleset_rule_id;
	}

	/**
	 * Delete the cache rule.
	 *
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function delete_cache_rule( &$error = '' ) {
		$was_deleted = $this->client->delete_rule( $error );

		// Update the settings to reflect this deletion.
		if ( $was_deleted === true ) {
			$this->cache_ruleset_rule_id = '';
			$this->settings->set( Constants::RULE_ID_CACHE, '' );
			$this->settings->save();
		}

		do_action( 'swcfpc_after_delete_cache_rule' );

		return $was_deleted;
	}

	/**
	 * Update cache rule with the default values.
	 *
	 * @param string $error The error message.
	 *
	 * @return string
	 */
	public function sync_cache_rule( &$error ) {
		$rule_id = $this->client->update_rule( $error );

		if ( ! empty( $rule_id ) ) {
			Logger::log( 'cloudflare::sync_cache_rule', sprintf( 'Cache %s rule updated successfully.', $rule_id ) );

			$this->purge_cache( $error );

			return $rule_id;
		}

		Logger::log( 'cloudflare::sync_cache_rule', 'Failed to update cache rule.' );

		update_option( Constants::KEY_RULE_UPDATE_FAILED, true );

		return '';
	}

	/**
	 * Pull the existing cache rule from the Cloudflare API if it is not set.
	 *
	 * @param bool $auto_save If true, it will save the cache rule ID. But not commit the changes.
	 *
	 * @return void
	 */
	public function pull_existing_cache_rule( $auto_save = true ) {
		if ( ! $this->settings->is_cloudflare_connected() ) {
			return;
		}

		$this->cache_ruleset_id = $this->client->get_ruleset_id();

		if ( empty( $this->cache_ruleset_id ) ) {
			return;
		}

		if ( $auto_save ) {
			$this->settings->set( Constants::RULESET_ID_CACHE, $this->cache_ruleset_id );
		}

		$this->cache_ruleset_rule_id = $this->client->get_rule_id();

		if ( empty( $this->cache_ruleset_rule_id ) ) {
			return;
		}

		if ( $auto_save ) {
			$this->settings->set( Constants::RULE_ID_CACHE, $this->cache_ruleset_rule_id );

			$this->settings->save();
		}
	}

	/**
	 * Reset the Cloudflare cache rule.
	 *
	 * @param string $error The error message.
	 *
	 * @return bool
	 */
	public function reset_cf_rule( &$error = '' ) {
		$disable = $this->disable_page_cache( $error );
		$enable  = $this->enable_page_cache( $error );

		return $disable && $enable;
	}

	/**
	 * Update the cache rule if it's different from the one we build.
	 *
	 * @param string $error The error message.
	 *
	 * @return void
	 */
	public function update_cache_rule_if_diff( &$error = '' ) {
		if ( ! $this->is_enabled() || ! $this->has_cache_rule() ) {
			Logger::log( 'cloudflare::update_cache_rule_if_diff', 'Cloudflare API is not enabled or cache rule is not set. Enabled: ' . $this->is_enabled() . ' Rule set: ' . $this->has_cache_rule() );

			return;
		}

		Logger::log( 'cloudflare::update_cache_rule_if_diff', 'Start cache rule upgrade.' );

		do_action( 'swcfpc_before_cache_rule_sync_start' );

		$existing_rule = $this->client->get_rule();

		Logger::log( 'cloudflare::update_cache_rule_if_diff', 'Existing rule: ' . print_r( $existing_rule, true ) );

		if ( ! isset( $existing_rule['expression'] ) ) {
			return;
		}

		$new_rule_expression = $this->client->get_rule_expression();

		if ( $existing_rule['expression'] === $new_rule_expression ) {
			Logger::log( 'cloudflare::update_cache_rule_if_diff', 'Cache rule is up to date.' );

			return;
		}

		$this->sync_cache_rule( $error );
	}

	/**
	 * Disconnect from Cloudflare.
	 *
	 * @param string $error The error message.
	 *
	 * @return void
	 */
	public function disconnect( &$error = '' ) {
		Logger::log( 'cloudflare::disconnect', 'Disconnecting from Cloudflare.' );
		$this->disable_page_cache( $error, false );

		$this->settings->save();
		Logger::log( 'cloudflare::disconnect', 'Disconnected from Cloudflare.' );
	}
}
