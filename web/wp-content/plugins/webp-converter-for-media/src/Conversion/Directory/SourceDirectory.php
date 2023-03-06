<?php

namespace WebpConverter\Conversion\Directory;

/**
 * Supports data about source directory.
 */
class SourceDirectory extends DirectoryAbstract {

	/**
	 * @var string
	 */
	private $directory_type;

	/**
	 * @var string
	 */
	private $directory_path;

	public function __construct( string $directory_name ) {
		$this->directory_type = trim( $directory_name, '/\\' );
		$this->directory_path = '%s/' . $this->directory_type;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_type(): string {
		return $this->directory_type;
	}

	/**
	 * {@inheritdoc}
	 */
	public function get_relative_path(): string {
		return sprintf( $this->directory_path, basename( WP_CONTENT_DIR ) );
	}
}
