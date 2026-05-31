<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;
use SPC\Utils\Assets_Handler;

class Page_Settings_Metabox implements Module_Interface {

	public const METABOX_ID                = 'swcfpc_cache_mbox';
	public const NONCE_ACTION              = 'swcfpc_page_settings_metabox';
	public const NONCE_FIELD               = 'swcfpc_page_settings_nonce';
	public const BYPASS_CACHE_META_KEY     = 'swcfpc_bypass_cache';
	public const CONTROL_FILTER            = 'spc_page_settings_metabox_controls';
	public const AFTER_CONTROLS_ACTION     = 'spc_page_settings_metabox_after_controls';
	public const AFTER_CONTROL_SAVE_ACTION = 'spc_page_settings_metabox_after_control_save';
	public const VISIBILITY_FILTER         = 'spc_show_page_settings_metabox';

	/**
	 * @var string[]|null
	 */
	private $allowed_post_types = null;

	/**
	 * Initialize the module.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'add_meta_boxes', [ $this, 'add_metaboxes' ] );
		add_action( 'save_post', [ $this, 'save_metabox_values' ], PHP_INT_MAX, 2 );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueue_inline_styles' ], 20 );
	}

	/**
	 * Add metaboxes.
	 *
	 * @return void
	 */
	public function add_metaboxes() {
		$allowed_post_types = $this->get_allowed_post_types();

		foreach ( $allowed_post_types as $post_type ) {

			if ( ! $this->is_metabox_visible( $post_type ) ) {
				continue;
			}

			add_meta_box(
				self::METABOX_ID,
				$this->get_metabox_title(),
				[ $this, 'render_metabox' ],
				$post_type,
				'side',
				'default',
			);

			add_filter( "postbox_classes_{$post_type}_" . self::METABOX_ID, [ $this, 'add_closed_postbox_class' ] );
		}
	}

	/**
	 * Get metabox title.
	 *
	 * @return string
	 */
	private function get_metabox_title() {
		$logo_url = Assets_Handler::get_image_url( 'logo.svg' );
		$output   = '<div class="spc-page-settings-metabox__title">';
		$output  .= '<img src="' . esc_url( $logo_url ) . '" alt="' . esc_attr__( 'Super Page Cache Logo', 'wp-cloudflare-page-cache' ) . '" class="swcfpc-page-settings-metabox__logo" />';
		$output  .= '<span>' . __( 'Super Page Cache', 'wp-cloudflare-page-cache' ) . '</span>';
		$output  .= '</div>';
		return $output;
	}

	/**
	 * Add closed class so the metabox starts collapsed.
	 *
	 * @param string[] $classes Existing classes.
	 *
	 * @return string[]
	 */
	public function add_closed_postbox_class( $classes ) {
		if ( ! in_array( 'closed', $classes, true ) ) {
			$classes[] = 'closed';
		}

		return $classes;
	}

	/**
	 * Enqueue inline styles for the metabox.
	 *
	 * @param string $hook_suffix Current screen hook.
	 *
	 * @return void
	 */
	public function enqueue_inline_styles( $hook_suffix ) {
		if ( ! in_array( $hook_suffix, [ 'post.php', 'post-new.php' ], true ) ) {
			return;
		}

		if ( ! wp_style_is( 'swcfpc_admin_css', 'enqueued' ) && ! wp_style_is( 'swcfpc_admin_css', 'registered' ) ) {
			return;
		}

		wp_add_inline_style( 'swcfpc_admin_css', $this->get_inline_styles() );
	}

	/**
	 * Render the metabox content.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function render_metabox( $post ) {
		if ( ! $post instanceof \WP_Post ) {
			return;
		}

		$controls          = $this->get_controls( $post );
		$can_purge         = $this->can_current_user_purge_cache();
		$is_cache_enabled  = $this->is_cache_feature_enabled();
		$post_id           = (int) $post->ID;
		$purge_text        = __( 'Purge cache for this page', 'wp-cloudflare-page-cache' );
		$controls_count    = count( $controls );
		$show_empty_notice = empty( $controls ) && ! $is_cache_enabled;
		$can_purge_page    = $is_cache_enabled && $can_purge;

		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		?>
		<div class="swcfpc-page-settings-metabox" id="swcfpc-page-settings-metabox">
			<div class="swcfpc-page-settings-metabox__controls">
				<?php if ( $show_empty_notice ) : ?>
					<p class="swcfpc-page-settings-metabox__description"><?php echo esc_html__( 'No per-page controls are currently available.', 'wp-cloudflare-page-cache' ); ?></p>
				<?php endif; ?>

				<?php foreach ( $controls as $index => $control ) : ?>
					<?php $this->render_control( $control ); ?>
					<?php if ( $index < ( $controls_count - 1 ) ) : ?>
						<hr class="swcfpc-page-settings-metabox__divider" />
					<?php endif; ?>
				<?php endforeach; ?>
			</div>

			<?php do_action( self::AFTER_CONTROLS_ACTION, $post, $controls, $this ); ?>

			<div class="swcfpc-page-settings-metabox__footer">
				<div class="swcfpc-page-settings-metabox__purge">
					<?php if ( $can_purge_page ) : ?>
						<a href="#" class="button button-secondary swcfpc_action_row_single_post_cache_purge" data-post_id="<?php echo esc_attr( (string) $post_id ); ?>"><?php echo esc_html( $purge_text ); ?></a>
					<?php else : ?>
						<button type="button" class="button button-secondary" disabled="disabled"><?php echo esc_html( $purge_text ); ?></button>
						<?php if ( ! $is_cache_enabled ) : ?>
							<p class="swcfpc-page-settings-metabox__notice"><?php echo esc_html__( 'Page Cache and Cloudflare Cache are both disabled. Enable at least one from the Cache settings tab to activate caching.', 'wp-cloudflare-page-cache' ); ?></p>
						<?php else : ?>
							<p class="swcfpc-page-settings-metabox__notice"><?php echo esc_html__( 'You do not have permission to purge cache.', 'wp-cloudflare-page-cache' ); ?></p>
						<?php endif; ?>
					<?php endif; ?>
				</div>
			</div>
		</div>
		<?php
	}

	/**
	 * Save metabox values.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 *
	 * @return void
	 */
	public function save_metabox_values( $post_id, $post ) {
		if ( ! $this->should_save( $post_id, $post ) ) {
			return;
		}

		$controls = $this->get_controls( $post );

		foreach ( $controls as $control ) {
			if ( empty( $control['meta_key'] ) || ! empty( $control['locked'] ) ) {
				continue;
			}

			$field_name = $control['field_name'] ?? $control['meta_key'];

			if ( ! array_key_exists( $field_name, $_POST ) ) {
				continue;
			}

			$value = $this->sanitize_toggle_value( wp_unslash( $_POST[ $field_name ] ) );

			update_post_meta( $post_id, $control['meta_key'], $value );

			do_action( self::AFTER_CONTROL_SAVE_ACTION, $post_id, $control, $value, $post, $this );
		}
	}

	/**
	 * Render a single metabox control row.
	 *
	 * @param array<string, mixed> $control Control definition.
	 *
	 * @return void
	 */
	private function render_control( array $control ) {
		if ( empty( $control['meta_key'] ) || empty( $control['label'] ) ) {
			return;
		}

		$field_name = $control['field_name'] ?? $control['meta_key'];
		$control_id = 'swcfpc-control-' . sanitize_html_class( $field_name );
		$locked     = ! empty( $control['locked'] );
		$value      = $this->sanitize_toggle_value( $control['value'] ?? 0 );
		$notice     = $control['lock_notice'] ?? '';
		?>
		<div class="swcfpc-page-settings-metabox__control <?php echo $locked ? 'is-locked' : ''; ?>">
			<input type="hidden" name="<?php echo esc_attr( $field_name ); ?>" value="0" />
			<label for="<?php echo esc_attr( $control_id ); ?>" class="swcfpc-page-settings-metabox__checkbox-label">
				<input
					type="checkbox"
					id="<?php echo esc_attr( $control_id ); ?>"
					name="<?php echo esc_attr( $field_name ); ?>"
					value="1"
					class="swcfpc-page-settings-metabox__checkbox"
					<?php checked( 1, $value ); ?>
					<?php disabled( $locked ); ?>
				/>
				<span><?php echo esc_html( $control['label'] ); ?></span>
			</label>
			<?php if ( ! empty( $control['description'] ) ) : ?>
				<p class="swcfpc-page-settings-metabox__description"><?php echo esc_html( $control['description'] ); ?></p>
			<?php endif; ?>
			<?php if ( $locked && ! empty( $notice ) ) : ?>
				<p class="swcfpc-page-settings-metabox__notice"><?php echo esc_html( $notice ); ?></p>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Build controls list for the current post.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return array<int, array<string, mixed>>
	 */
	private function get_controls( \WP_Post $post ) {
		$controls = [];

		if ( $this->is_cache_feature_enabled() ) {
			$lock_notice = $this->get_cache_bypass_lock_notice( $post );
			$is_locked   = ! empty( $lock_notice );
			$bypass      = (int) get_post_meta( $post->ID, self::BYPASS_CACHE_META_KEY, true );

			$controls[] = [
				'meta_key'    => self::BYPASS_CACHE_META_KEY,
				'field_name'  => self::BYPASS_CACHE_META_KEY,
				'label'       => __( 'Bypass cache for this page', 'wp-cloudflare-page-cache' ),
				'description' => __( 'Prevent this page from being served from cache.', 'wp-cloudflare-page-cache' ),
				'value'       => $is_locked ? 1 : $bypass,
				'locked'      => $is_locked,
				'lock_notice' => $lock_notice,
			];
		}

		$controls = apply_filters( self::CONTROL_FILTER, $controls, $post, $this );

		if ( ! is_array( $controls ) ) {
			return [];
		}

		return array_values( array_filter( $controls, 'is_array' ) );
	}

	/**
	 * Get lock notice for cache bypass control.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	private function get_cache_bypass_lock_notice( \WP_Post $post ) {
		$settings = Settings_Store::get_instance();

		if ( $this->is_post_in_excluded_urls( $post ) ) {
			return __( 'Controlled by global Exclude URLs cache rules.', 'wp-cloudflare-page-cache' );
		}

		if ( 'page' === $post->post_type && $settings->get( Constants::SETTING_BYPASS_PAGES ) ) {
			return __( 'Global cache settings bypass all pages.', 'wp-cloudflare-page-cache' );
		}

		if ( 'post' === $post->post_type && $settings->get( Constants::SETTING_BYPASS_SINGLE_POST ) ) {
			return __( 'Global cache settings bypass all posts.', 'wp-cloudflare-page-cache' );
		}

		$front_page_id = (int) get_option( 'page_on_front', 0 );
		if ( $front_page_id > 0 && $front_page_id === (int) $post->ID && $settings->get( Constants::SETTING_BYPASS_FRONT_PAGE ) ) {
			return __( 'Global cache settings bypass the front page.', 'wp-cloudflare-page-cache' );
		}

		$posts_page_id = (int) get_option( 'page_for_posts', 0 );
		if ( $posts_page_id > 0 && $posts_page_id === (int) $post->ID && $settings->get( Constants::SETTING_BYPASS_HOME ) ) {
			return __( 'Global cache settings bypass the posts page.', 'wp-cloudflare-page-cache' );
		}

		return '';
	}

	/**
	 * Check if post permalink matches global excluded URLs.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool
	 */
	private function is_post_in_excluded_urls( \WP_Post $post ) {
		$excluded_urls = Settings_Store::get_instance()->get( Constants::SETTING_EXCLUDED_URLS, [] );

		if ( ! is_array( $excluded_urls ) || empty( $excluded_urls ) ) {
			return false;
		}

		$post_uri = $this->get_post_uri( $post );
		if ( '' === $post_uri ) {
			return false;
		}

		foreach ( $excluded_urls as $pattern ) {
			$pattern = trim( (string) $pattern );
			if ( '' === $pattern ) {
				continue;
			}

			if ( $this->wildcard_match( $pattern, $post_uri ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Get post URI.
	 *
	 * @param \WP_Post $post Post object.
	 *
	 * @return string
	 */
	private function get_post_uri( \WP_Post $post ) {
		$permalink = get_permalink( $post );
		if ( ! is_string( $permalink ) || '' === $permalink ) {
			return '';
		}

		$parts = wp_parse_url( $permalink );
		if ( ! is_array( $parts ) || empty( $parts['path'] ) ) {
			return '';
		}

		$uri = $parts['path'];
		if ( ! empty( $parts['query'] ) ) {
			$uri .= '?' . $parts['query'];
		}

		return $uri;
	}

	/**
	 * Wildcard matcher used by excluded URL rules.
	 *
	 * @param string $pattern Pattern.
	 * @param string $subject Subject.
	 *
	 * @return bool
	 */
	private function wildcard_match( $pattern, $subject ) {
		$pattern = '#^' . preg_quote( $pattern ) . '$#i';
		$pattern = str_replace( '\\*', '.*', $pattern );

		return (bool) preg_match( $pattern, $subject );
	}

	/**
	 * Get allowed post types.
	 *
	 * @return string[]
	 */
	private function get_allowed_post_types() {
		if ( is_array( $this->allowed_post_types ) ) {
			return $this->allowed_post_types;
		}

		$allowed_post_types = array_keys(
			get_post_types(
				[
					'public' => true,
				],
				'objects'
			)
		);

		$allowed_post_types = array_values(
			array_filter(
				array_map( 'sanitize_key', $allowed_post_types ),
				static function ( $post_type ) {
					$post_type_object = get_post_type_object( $post_type );

					return $post_type_object && is_post_type_viewable( $post_type_object );
				}
			)
		);

		$allowed_post_types = apply_filters( 'swcfpc_bypass_cache_metabox_post_types', $allowed_post_types );

		if ( ! is_array( $allowed_post_types ) ) {
			$allowed_post_types = [];
		}

		$this->allowed_post_types = array_values( array_filter( array_map( 'sanitize_key', $allowed_post_types ) ) );

		return $this->allowed_post_types;
	}

	/**
	 * Check whether metabox data should be saved.
	 *
	 * @param int      $post_id Post ID.
	 * @param \WP_Post $post Post object.
	 *
	 * @return bool
	 */
	private function should_save( $post_id, $post ) {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] ) ) {
			return false;
		}

		if ( ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION ) ) {
			return false;
		}

		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return false;
		}

		if ( wp_is_post_autosave( $post_id ) || wp_is_post_revision( $post_id ) ) {
			return false;
		}

		if ( ! $post instanceof \WP_Post ) {
			$post = get_post( $post_id );
		}

		if ( ! $post instanceof \WP_Post ) {
			return false;
		}

		if ( ! in_array( $post->post_type, $this->get_allowed_post_types(), true ) ) {
			return false;
		}

		if ( ! $this->is_metabox_visible( $post->post_type, $post ) ) {
			return false;
		}

		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return false;
		}

		return true;
	}

	/**
	 * Sanitize yes/no select value into 0/1.
	 *
	 * @param mixed $value Raw value.
	 *
	 * @return int
	 */
	private function sanitize_toggle_value( $value ) {
		return (int) ( 1 === (int) $value );
	}

	/**
	 * Check if current user can purge cache.
	 *
	 * @return bool
	 */
	private function can_current_user_purge_cache() {
		global $sw_cloudflare_pagecache;

		if ( ! is_object( $sw_cloudflare_pagecache ) || ! method_exists( $sw_cloudflare_pagecache, 'can_current_user_purge_cache' ) ) {
			return false;
		}

		return (bool) $sw_cloudflare_pagecache->can_current_user_purge_cache();
	}

	/**
	 * Check if page cache feature is enabled globally.
	 *
	 * @return bool
	 */
	private function is_cache_feature_enabled() {
		$settings = Settings_Store::get_instance();

		return (bool) $settings->get( Constants::SETTING_ENABLE_FALLBACK_CACHE ) || (bool) $settings->get( Constants::ENABLE_CACHE_RULE );
	}

	/**
	 * Check if page settings metabox should be visible.
	 *
	 * @param string        $post_type Current post type.
	 * @param \WP_Post|null $post Current post, if available.
	 *
	 * @return bool
	 */
	private function is_metabox_visible( $post_type, $post = null ) {
		return (bool) apply_filters( self::VISIBILITY_FILTER, true, $post_type, $post, $this );
	}

	/**
	 * Metabox inline CSS.
	 *
	 * @return string
	 */
	private function get_inline_styles() {
		return '
		.swcfpc-page-settings-metabox { display: grid; gap: 12px; }
		.spc-page-settings-metabox__title { display: inline-block; }
		#' . self::METABOX_ID . ' .spc-page-settings-metabox__title { display: flex; align-items: center; gap: 10px; }
		.spc-page-settings-metabox__title h2 { margin: 0!important; padding: 0!important; }
		.swcfpc-page-settings-metabox__logo { display:none; }
		#' . self::METABOX_ID . ' .swcfpc-page-settings-metabox__logo { display:block; width: 16px; height: 16px; flex-shrink: 0; }
		.swcfpc-page-settings-metabox__controls { display: grid; gap: 10px; }
		.swcfpc-page-settings-metabox__control { display: grid; gap: 6px; }
		.swcfpc-page-settings-metabox__checkbox-label { display: inline-flex; align-items: flex-start; gap: 5px; font-size: 13px; cursor: pointer; }
		.swcfpc-page-settings-metabox__checkbox { appearance: auto; -webkit-appearance: checkbox; box-sizing: border-box; width: 18px !important; height: 18px !important; min-width: 18px; min-height: 18px; margin: 1px 0 0 !important; border-radius: 4px !important; border-color: #8c8f94 !important; }
		.swcfpc-page-settings-metabox__checkbox:focus { box-shadow: 0 0 0 1px #2271b1 !important; outline: none; }
		.swcfpc-page-settings-metabox__control.is-locked .swcfpc-page-settings-metabox__checkbox-label { opacity: 0.85; }
		.swcfpc-page-settings-metabox__divider { margin:0!important; border: 0; border-top: 1px solid #f0f0f1; }
		.swcfpc-page-settings-metabox__description { margin: 0; color: #50575e; font-size: 12px; }
		.swcfpc-page-settings-metabox__notice { margin: 0; color: #a44b00; font-size: 12px; background: #fff4e5; border-left: 3px solid #f0a542; padding: 6px 8px; }
		.swcfpc-page-settings-metabox__footer { padding-top: 2px; border-top: 1px solid #f0f0f1; }
		.swcfpc-page-settings-metabox__purge { display: grid; gap: 8px; }
		.swcfpc-page-settings-metabox__purge a { text-align: center; }
		';
	}
}
