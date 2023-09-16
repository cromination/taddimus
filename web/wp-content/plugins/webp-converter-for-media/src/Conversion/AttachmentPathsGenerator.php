<?php

namespace WebpConverter\Conversion;

use WebpConverter\PluginData;
use WebpConverter\Settings\Option\SupportedExtensionsOption;

/**
 * Returns all image paths for attachment.
 */
class AttachmentPathsGenerator {

	/**
	 * @var PluginData
	 */
	private $plugin_data;

	/**
	 * Current upload directory path and URL.
	 *
	 * @var mixed[]
	 */
	private $upload_dir;

	public function __construct( PluginData $plugin_data ) {
		$this->plugin_data = $plugin_data;
		$this->upload_dir  = wp_upload_dir();
	}

	/**
	 * Returns server paths to source images of attachment.
	 *
	 * @param int $attachment_id ID of attachment.
	 *
	 * @return string[] Server paths of source images.
	 */
	public function get_attachment_paths( int $attachment_id ): array {
		$settings = $this->plugin_data->get_plugin_settings();
		return $this->get_paths_by_attachment( $attachment_id, $settings );
	}

	/**
	 * Returns server paths to source images of attachment by file extensions.
	 *
	 * @param int     $post_id  ID of attachment.
	 * @param mixed[] $settings Plugin settings.
	 *
	 * @return string[] Server paths of source images.
	 */
	private function get_paths_by_attachment( int $post_id, array $settings ): array {
		$list     = [];
		$metadata = wp_get_attachment_metadata( $post_id );
		if ( ! $metadata || ! isset( $metadata['file'] ) ) {
			return $list;
		}

		$extension = strtolower( pathinfo( $metadata['file'], PATHINFO_EXTENSION ) );
		if ( ! in_array( $extension, $settings[ SupportedExtensionsOption::OPTION_NAME ] ) ) {
			return $list;
		}

		$paths = $this->get_paths_by_sizes( $post_id, $metadata );
		return apply_filters( 'webpc_attachment_paths', $paths, $post_id );
	}

	/**
	 * Returns unique server paths to source images of attachment.
	 *
	 * @param int     $post_id  ID of attachment.
	 * @param mixed[] $metadata Data of attachment.
	 *
	 * @return string[] Server paths of source images.
	 */
	private function get_paths_by_sizes( int $post_id, array $metadata ): array {
		$main_file = str_replace( '\\', '/', ( $this->upload_dir['basedir'] . '/' . $metadata['file'] ) );
		$file_path = dirname( $main_file ) . '/';
		$list      = [ $main_file ];

		foreach ( $metadata['sizes'] ?? [] as $size => $size_data ) {
			$list[] = $file_path . $size_data['file'];
		}
		return array_values( array_unique( $list ) );
	}
}
