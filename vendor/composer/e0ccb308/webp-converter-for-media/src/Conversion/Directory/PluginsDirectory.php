<?php

namespace WebpConverter\Conversion\Directory;

/**
 * Supports data about /plugins directory.
 */
class PluginsDirectory extends DirectoryAbstract {

	const DIRECTORY_TYPE = 'plugins';
	const DIRECTORY_PATH = '%s/plugins';

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return self::DIRECTORY_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_label(): string {
		return '/' . self::DIRECTORY_TYPE;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_relative_path(): string {
		return sprintf( self::DIRECTORY_PATH, basename( WP_CONTENT_DIR ) );
	}
}
