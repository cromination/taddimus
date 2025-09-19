<?php
/**
 * Separator section.
 *
 * @package Hestia
 */

/**
 * Class Hestia_Separator_Section
 *
 * @access public
 */
class Hestia_Separator_Section extends WP_Customize_Section {

	/**
	 * The type of customize section being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'separator';

	/**
	 * Render the separator.
	 *
	 * @return void
	 */
	public function render() {
		$output  = '<li id="accordion-section-' . $this->id . '" class="neve-separator-section">';
		$output .= '<hr/>';
		$output .= '</li>';

		echo wp_kses_post( $output );
	}
}
