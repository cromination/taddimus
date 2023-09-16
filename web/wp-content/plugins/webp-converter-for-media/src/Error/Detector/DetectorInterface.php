<?php

namespace WebpConverter\Error\Detector;

use WebpConverter\Error\Notice\NoticeInterface;

/**
 * Interface for class that checks for configuration errors.
 */
interface DetectorInterface {

	/**
	 * @return NoticeInterface|null
	 */
	public function get_error();
}
