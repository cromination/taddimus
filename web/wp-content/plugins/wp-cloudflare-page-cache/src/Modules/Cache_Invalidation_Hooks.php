<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Logger;

defined( 'ABSPATH' ) || die( 'Cheatin&#8217; uh?' );

/**
 * Registers WP hooks that invalidate the cache when content changes.
 *
 * Owns: comment moderation, post create/edit/trash/delete, theme & menu changes,
 * Elementor save events, and the programmatic `swcfpc_purge_cache` action.
 *
 * Each callback delegates to {@see Cache_Controller::purge_all()} or
 * {@see Cache_Controller::purge_urls()} (static facades) so the per-request
 * `$purge_all_already_done` dedup is preserved without taking a Cache_Controller
 * dependency on this class.
 */
class Cache_Invalidation_Hooks implements Module_Interface {

	public function init() {
		// Programmatic purge entry point used by 3rd-party code.
		add_action( 'swcfpc_purge_cache', [ $this, 'purge_cache_programmatically' ], PHP_INT_MAX, 1 );

		// Comments.
		add_action( 'transition_comment_status', [ $this, 'purge_cache_when_comment_is_approved' ], PHP_INT_MAX, 3 );
		add_action( 'comment_post', [ $this, 'purge_cache_when_new_comment_is_added' ], PHP_INT_MAX, 3 );
		add_action( 'delete_comment', [ $this, 'purge_cache_when_comment_is_deleted' ], PHP_INT_MAX );

		// Theme / menu / customizer / permalinks.
		$theme_actions = [
			'wp_update_nav_menu',
			'update_option_theme_mods_' . get_option( 'stylesheet' ),
			'avada_clear_dynamic_css_cache',
			'switch_theme',
			'customize_save_after',
			'permalink_structure_changed',
		];

		foreach ( $theme_actions as $action ) {
			add_action( $action, [ $this, 'purge_cache_on_theme_edit' ], PHP_INT_MAX );
		}

		// Post lifecycle.
		$post_actions = [
			'deleted_post',
			'wp_trash_post',
			'clean_post_cache',
			'edit_post',
			'delete_attachment',
			'elementor/editor/after_save',
			'elementor/core/files/clear_cache',
		];

		foreach ( $post_actions as $action ) {
			add_action( $action, [ $this, 'purge_cache_on_post_edit' ], PHP_INT_MAX, 1 );
		}

		// Dispatch the tag-purge before the post row is gone so listeners can
		// still read post data (`deleted_post` fires after deletion and has no
		// way to look up the author / terms / type).
		add_action( 'before_delete_post', [ $this, 'purge_tags_before_delete_post' ], PHP_INT_MAX, 1 );

		add_action( 'transition_post_status', [ $this, 'purge_cache_when_post_is_published' ], PHP_INT_MAX, 3 );
	}

	/**
	 * Public API — `do_action( 'swcfpc_purge_cache', $urls )`. Empty/non-array `$urls`
	 * triggers a full purge, otherwise the listed URLs are purged. Bypasses the queue.
	 *
	 * @param array<int, string>|mixed $urls
	 * @return void
	 */
	public function purge_cache_programmatically( $urls ) {
		if ( ! is_array( $urls ) || count( $urls ) == 0 ) {
			Cache_Controller::purge_all( true, false );
		} else {
			Cache_Controller::purge_urls( $urls, false );
		}
	}

	/**
	 * @param string      $new_status
	 * @param string      $old_status
	 * @param \WP_Comment $comment
	 * @return void
	 */
	public function purge_cache_when_comment_is_approved( $new_status, $old_status, $comment ) {
		if ( Settings_Store::get_instance()->get( Constants::SETTING_PURGE_ON_COMMENT, 0 ) == 0 || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		if ( $old_status == $new_status || $new_status !== 'approved' ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';
		$post_id        = (int) $comment->comment_post_ID;

		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );
		self::dispatch_post_tag_purge( $post_id, [ 'scope' => 'comment' ] );

		Logger::log( 'cache_invalidation_hooks::purge_cache_when_comment_is_approved', "Purge Cloudflare cache for only post {$post_id} - Fired action: {$current_action}" );
	}

	/**
	 * @param int                  $comment_id
	 * @param int|string           $comment_approved
	 * @param array<string, mixed> $commentdata
	 * @return void
	 */
	public function purge_cache_when_new_comment_is_added( $comment_id, $comment_approved, $commentdata ) {
		if ( Settings_Store::get_instance()->get( Constants::SETTING_PURGE_ON_COMMENT, 0 ) == 0 || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		if ( ! isset( $commentdata['comment_post_ID'] ) ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';
		$post_id        = (int) $commentdata['comment_post_ID'];

		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );
		self::dispatch_post_tag_purge( $post_id, [ 'scope' => 'comment' ] );

		Logger::log( 'cache_invalidation_hooks::purge_cache_when_new_comment_is_added', "Purge Cloudflare cache for only post {$post_id} - Fired action: {$current_action}" );
	}

	/**
	 * @return void
	 */
	public function purge_cache_when_comment_is_deleted( int $comment_id ) {
		if ( Settings_Store::get_instance()->get( Constants::SETTING_PURGE_ON_COMMENT, 0 ) == 0 || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		$comment = get_comment( $comment_id );
		$post_id = (int) $comment->comment_post_ID;
		Cache_Controller::purge_urls( [ get_permalink( $post_id ) ] );
		self::dispatch_post_tag_purge( $post_id, [ 'scope' => 'comment' ] );

		Logger::log( 'cache_invalidation_hooks::purge_cache_when_comment_is_deleted', "Purge Cloudflare cache for only post {$post_id} - Fired action: {$current_action}" );
	}

	/**
	 * @param string   $new_status
	 * @param string   $old_status
	 * @param \WP_Post $post
	 * @return void
	 */
	public function purge_cache_when_post_is_published( $new_status, $old_status, $post ) {
		$auto_purge       = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE );
		$auto_purge_whole = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE_WHOLE );

		if ( ( ! $auto_purge && ! $auto_purge_whole ) || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		$going_live   = in_array( $old_status, [ 'future', 'draft', 'pending' ], true ) && in_array( $new_status, [ 'publish', 'private' ], true );
		$being_hidden = 'publish' === $old_status && in_array( $new_status, [ 'future', 'draft', 'pending' ], true );

		if ( ! $going_live && ! $being_hidden ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		if ( $auto_purge_whole ) {
			Cache_Controller::purge_all();
			self::dispatch_post_tag_purge( $post->ID );
			Logger::log( 'cache_invalidation_hooks::purge_cache_when_post_is_published', "Purge whole cache (fired action: {$current_action}" );
			return;
		}

		Cache_Controller::purge_urls( self::get_post_related_links( $post->ID ) );
		self::dispatch_post_tag_purge( $post->ID );
		Logger::log( 'cache_invalidation_hooks::purge_cache_when_post_is_published', "Purge cache for only post id {$post->ID} and related contents - Fired action: {$current_action}" );
	}

	/**
	 * @param mixed $post_id
	 * @return void
	 */
	public function purge_cache_on_post_edit( $post_id ) {
		static $done = [];
		$post_id     = (int) $post_id;

		if ( isset( $done[ $post_id ] ) ) {
			return;
		}

		global $pagenow;
		if ( $pagenow === 'nav-menus.php' ) {
			return;
		}

		$auto_purge       = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE );
		$auto_purge_whole = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE_WHOLE );

		if ( ( ! $auto_purge && ! $auto_purge_whole ) || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		$valid_post_status = [ 'publish', 'trash', 'private' ];
		$this_post_status  = get_post_status( $post_id );

		if ( ! get_permalink( $post_id ) || ! in_array( $this_post_status, $valid_post_status, true ) ) {
			return;
		}

		if ( is_int( wp_is_post_autosave( $post_id ) ) || is_int( wp_is_post_revision( $post_id ) ) ) {
			return;
		}

		if ( $auto_purge_whole ) {
			Cache_Controller::purge_all();
			return;
		}

		if ( ! ( get_post( $post_id ) instanceof \WP_Post ) ) {
			return;
		}

		Cache_Controller::purge_urls( self::get_post_related_links( $post_id ) );
		self::dispatch_post_tag_purge( $post_id );
		Logger::log( 'cache_invalidation_hooks::purge_cache_on_post_edit', "Purge Cloudflare cache for only post id {$post_id} and related contents - Fired action: {$current_action}" );

		$done[ $post_id ] = true;
	}

	/**
	 * @param mixed $post_id
	 * @return void
	 */
	public function purge_tags_before_delete_post( $post_id ) {
		$post_id = (int) $post_id;

		$auto_purge       = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE );
		$auto_purge_whole = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE_WHOLE );

		if ( ( ! $auto_purge && ! $auto_purge_whole ) || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		if ( ! ( get_post( $post_id ) instanceof \WP_Post ) ) {
			return;
		}

		self::dispatch_post_tag_purge( $post_id );
	}

	/**
	 * Signal that any tag-based caches associated with `$post_id` should be
	 * invalidated. Listeners (e.g. the pro Cache_Tags module) translate the
	 * post id into a tag set and dispatch the actual purge — when the listener
	 * is absent (free plugin or feature disabled), the call is a no-op.
	 *
	 * @param int                 $post_id
	 * @param array<string,mixed> $args Optional. `scope=comment` narrows the
	 *                                  invalidation to just the post tag.
	 *
	 * @return void
	 */
	private static function dispatch_post_tag_purge( $post_id, array $args = [] ) {
		do_action( 'spc_purge_post_tags', (int) $post_id, $args );
	}

	/**
	 * @return void
	 */
	public function purge_cache_on_theme_edit() {
		$auto_purge       = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE );
		$auto_purge_whole = Settings_Manager::is_on( Constants::SETTING_AUTO_PURGE_WHOLE );

		if ( ( ! $auto_purge && ! $auto_purge_whole ) || ! Settings_Store::get_instance()->is_cache_enabled() ) {
			return;
		}

		$current_action = function_exists( 'current_action' ) ? current_action() : '';

		Cache_Controller::purge_all();
		Logger::log( 'cache_invalidation_hooks::purge_cache_on_theme_edit', "Purge whole cache - Fired action: {$current_action}" );
	}

	/**
	 * Build the list of URLs that should be purged when a given post changes.
	 *
	 * @param int $post_id
	 *
	 * @return array<int, string>
	 */
	public static function get_post_related_links( $post_id ) {
		$settings   = Settings_Store::get_instance();
		$listofurls = apply_filters( 'swcfpc_post_related_url_init', [], $post_id );
		$post_type  = get_post_type( $post_id );

		$listofurls[] = get_permalink( $post_id );

		foreach ( get_object_taxonomies( $post_type ) as $taxonomy ) {
			if ( is_object( $taxonomy ) && ( $taxonomy->public == false || $taxonomy->rewrite == false ) ) {
				continue;
			}

			$terms = get_the_terms( $post_id, $taxonomy );

			if ( empty( $terms ) || is_wp_error( $terms ) ) {
				continue;
			}

			foreach ( $terms as $term ) {
				$term_link = get_term_link( $term );

				if ( is_wp_error( $term_link ) ) {
					continue;
				}

				$listofurls[] = $term_link;

				$per_page = (int) $settings->get( Constants::SETTING_POSTS_PER_PAGE, 0 );
				if ( $per_page > 0 ) {
					$pages_number = ceil( $term->count / $per_page );
					$max_pages    = $pages_number > 10 ? 10 : $pages_number;

					for ( $i = 2; $i <= $max_pages; $i++ ) {
						$listofurls[] = trailingslashit( $term_link ) . 'page/' . user_trailingslashit( (string) $i );
					}
				}
			}
		}

		$listofurls[] = get_author_posts_url( get_post_field( 'post_author', $post_id ) );
		$listofurls[] = get_author_feed_link( get_post_field( 'post_author', $post_id ) );

		if ( get_post_type_archive_link( $post_type ) == true ) {
			$listofurls[] = get_post_type_archive_link( $post_type );
			$listofurls[] = get_post_type_archive_feed_link( $post_type );
		}

		if ( get_post_status( $post_id ) == 'trash' ) {
			$trash_post   = get_permalink( $post_id );
			$trash_post   = str_replace( '__trashed', '', $trash_post );
			$listofurls[] = $trash_post;
			$listofurls[] = "{$trash_post}feed/";
		}

		if ( defined( 'SWCFPC_HOME_PAGE_SHOWS_POSTS' ) && \SWCFPC_HOME_PAGE_SHOWS_POSTS ) {
			$listofurls[] = home_url( '/' );
		}

		$page_link = get_permalink( get_option( 'page_for_posts' ) );
		if ( is_string( $page_link ) && ! empty( $page_link ) && get_option( 'show_on_front' ) == 'page' ) {
			$listofurls[] = $page_link;
		}

		return $listofurls;
	}
}
