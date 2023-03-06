<?php

namespace WebpConverter\Conversion\Directory;

use WebpConverter\Service\PathsGenerator;

/**
 * Abstract class for class that supports data about directory.
 */
abstract class DirectoryAbstract implements DirectoryInterface {

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return '/' . $this->get_type();
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_available(): bool {
		return ( file_exists( $this->get_server_path() ) );
	}

	/**
	 * {@inheritdoc}
	 */
	public function is_output_directory(): bool {
		return false;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_server_path(): string {
		$directory_name = apply_filters( 'webpc_dir_name', $this->get_relative_path(), $this->get_type() );
		return sprintf(
			'%1$s/%2$s',
			rtrim( PathsGenerator::get_wordpress_root_path(), DIRECTORY_SEPARATOR ),
			$directory_name
		);
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_path_url(): string {
		$source_url     = apply_filters( 'webpc_site_url', ( defined( 'WP_HOME' ) ) ? WP_HOME : get_site_url() );
		$directory_name = apply_filters( 'webpc_dir_name', $this->get_relative_path(), $this->get_type() );
		return sprintf( '%1$s/%2$s', $source_url, $directory_name );
	}
}
