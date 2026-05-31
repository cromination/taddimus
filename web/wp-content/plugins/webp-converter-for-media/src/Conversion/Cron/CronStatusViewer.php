<?php

namespace WebpConverter\Conversion\Cron;

use WebpConverter\HookableInterface;
use WebpConverter\Settings\Page\PageIntegrator;

/**
 * Displays converting status on top menu bar in the WordPress Dashboard.
 */
class CronStatusViewer implements HookableInterface {

	private CronStatusManager $cron_status_manager;

	private int $paths_preview_count = 0;

	public function __construct( ?CronStatusManager $cron_status_manager = null ) {
		$this->cron_status_manager = $cron_status_manager ?: new CronStatusManager();
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks(): void {
		add_action( 'admin_init', [ $this, 'init_status_preview' ] );
	}

	/**
	 * @internal
	 */
	public function init_status_preview(): void {
		if ( $this->cron_status_manager->get_conversion_request_id() === null ) {
			return;
		}

		$this->paths_preview_count = $this->cron_status_manager->get_paths_counter();
		if ( ! $this->paths_preview_count ) {
			return;
		}

		add_action( 'admin_bar_menu', [ $this, 'add_menu_to_top_bar' ], 1000 );
	}

	/**
	 * @param \WP_Admin_Bar $wp_admin_bar .
	 *
	 * @internal
	 */
	public function add_menu_to_top_bar( \WP_Admin_Bar $wp_admin_bar ): void {
		$count       = number_format( $this->paths_preview_count, 0, '', ' ' );
		$menu_parent = [
			'id'    => 'webpc-menu',
			'href'  => PageIntegrator::get_settings_page_url(),
			'title' => sprintf(
				'<span class="ab-icon"></span><span class="ab-label">%1$s</span>',
				$count
			),
		];
		$menu_child  = [
			'id'     => 'webpc-menu-message',
			'title'  => sprintf(
			/* translators: %1$s: progress percent */
				__( 'Converting images (%s) is in progress.', 'webp-converter-for-media' ),
				$count
			),
			'parent' => $menu_parent['id'],
		];

		$wp_admin_bar->add_menu( $menu_parent );
		$wp_admin_bar->add_menu( $menu_child );
	}
}
