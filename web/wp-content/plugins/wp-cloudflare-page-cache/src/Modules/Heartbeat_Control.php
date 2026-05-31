<?php

namespace SPC\Modules;

use SPC\Constants;
use SPC\Services\Settings_Store;

class Heartbeat_Control implements Module_Interface {
	private const MODE_DEFAULT                       = 'default';
	private const MODE_REDUCED                       = 'reduced';
	private const MODE_DISABLED                      = 'disabled';
	private const REDUCED_INTERVAL                   = 120;
	private const HEARTBEAT_DEPENDENT_ADMIN_SCRIPTS  = [
		'wp-auth-check',
	];
	private const HEARTBEAT_DEPENDENT_EDITOR_SCRIPTS = [
		'autosave',
		'wp-auth-check',
	];

	public function init() {
		add_filter( 'heartbeat_settings', [ $this, 'filter_heartbeat_settings' ] );
		add_action( 'wp_enqueue_scripts', [ $this, 'maybe_disable_frontend_heartbeat' ], 100 );
		add_action( 'admin_enqueue_scripts', [ $this, 'maybe_disable_admin_heartbeat' ], 100 );
	}

	/**
	 * Reduce the Heartbeat interval for contexts configured as "reduced".
	 *
	 * @param array<string, mixed> $settings Existing Heartbeat settings.
	 *
	 * @return array<string, mixed>
	 */
	public function filter_heartbeat_settings( array $settings ): array {
		if ( self::MODE_REDUCED !== $this->get_current_mode() ) {
			return $settings;
		}

		$settings['interval'] = self::REDUCED_INTERVAL;

		return $settings;
	}

	/**
	 * Disable Heartbeat on the frontend when configured.
	 *
	 * @return void
	 */
	public function maybe_disable_frontend_heartbeat(): void {
		if ( self::MODE_DISABLED !== $this->get_current_mode() ) {
			return;
		}

		wp_dequeue_script( 'heartbeat' );
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Disable Heartbeat in wp-admin when configured for the current admin context.
	 *
	 * @return void
	 */
	public function maybe_disable_admin_heartbeat(): void {
		if ( self::MODE_DISABLED !== $this->get_current_mode() ) {
			return;
		}

		$this->dequeue_heartbeat_dependents();
		wp_dequeue_script( 'heartbeat' );
		wp_deregister_script( 'heartbeat' );
	}

	/**
	 * Resolve the configured mode for the current request context.
	 *
	 * @return string
	 */
	public function get_current_mode(): string {
		$settings = Settings_Store::get_instance();

		if ( ! is_admin() ) {
			return (string) $settings->get( Constants::SETTING_HEARTBEAT_FRONTEND, self::MODE_DEFAULT );
		}

		if ( $this->is_editor_screen() ) {
			return (string) $settings->get( Constants::SETTING_HEARTBEAT_EDITOR, self::MODE_DEFAULT );
		}

		return (string) $settings->get( Constants::SETTING_HEARTBEAT_ADMIN, self::MODE_DEFAULT );
	}

	/**
	 * Determine whether the current admin page is a post editor screen.
	 *
	 * @return bool
	 */
	private function is_editor_screen(): bool {
		if ( ! function_exists( 'get_current_screen' ) ) {
			return false;
		}

		$screen = get_current_screen();

		return is_object( $screen ) && 'post' === $screen->base;
	}

	/**
	 * Dequeue admin scripts that depend directly on Heartbeat before unregistering it.
	 *
	 * @return void
	 */
	private function dequeue_heartbeat_dependents(): void {
		$handles = $this->is_editor_screen()
			? self::HEARTBEAT_DEPENDENT_EDITOR_SCRIPTS
			: self::HEARTBEAT_DEPENDENT_ADMIN_SCRIPTS;

		foreach ( $handles as $handle ) {
			wp_dequeue_script( $handle );
			wp_deregister_script( $handle );
		}
	}
}
