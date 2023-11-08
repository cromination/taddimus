<?php

namespace WebpConverter\Action;

use WebpConverter\Conversion\AttachmentPathsGenerator;
use WebpConverter\HookableInterface;
use WebpConverter\PluginData;

/**
 * Initializes conversion of all image sizes for attachment.
 */
class ConvertAttachmentAction implements HookableInterface {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
	}

	/**
	 * {@inheritdoc}
	 */
	public function init_hooks() {
		add_action( 'webpc_convert_attachment', [ $this, 'convert_files_by_attachment' ], 10, 3 );
	}

	/**
	 * Converts all sizes of attachment to output formats.
	 *
	 * @param int  $post_id          ID of attachment.
	 * @param bool $regenerate_force .
	 * @param int  $quality_level    .
	 *
	 * @return void
	 * @internal
	 */
	public function convert_files_by_attachment( int $post_id, bool $regenerate_force = false, int $quality_level = null ) {
		$attachment = new AttachmentPathsGenerator( $this->plugin_data );

		if ( $quality_level === 0 ) {
			do_action( 'webpc_delete_paths', $attachment->get_attachment_paths( $post_id ), true );
		} else {
			do_action( 'webpc_convert_paths', $attachment->get_attachment_paths( $post_id ), $regenerate_force, $quality_level );
		}
	}
}
