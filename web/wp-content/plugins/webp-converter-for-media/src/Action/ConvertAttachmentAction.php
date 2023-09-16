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
		add_action( 'webpc_convert_attachment', [ $this, 'convert_files_by_attachment' ], 10, 2 );
	}

	/**
	 * Converts all sizes of attachment to output formats.
	 *
	 * @param int  $post_id          ID of attachment.
	 * @param bool $regenerate_force .
	 *
	 * @return void
	 * @internal
	 */
	public function convert_files_by_attachment( int $post_id, bool $regenerate_force = false ) {
		$attachment = new AttachmentPathsGenerator( $this->plugin_data );

		do_action( 'webpc_convert_paths', $attachment->get_attachment_paths( $post_id ), $regenerate_force );
	}
}
