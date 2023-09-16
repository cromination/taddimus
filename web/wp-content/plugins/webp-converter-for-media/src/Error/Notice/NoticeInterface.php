<?php

namespace WebpConverter\Error\Notice;

/**
 * Stores information about server configuration error.
 */
interface NoticeInterface {

	/**
	 * @return string
	 */
	public function get_key(): string;

	/**
	 * @return string[]
	 */
	public function get_message(): array;
}
