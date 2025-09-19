<?php
/**
 * Metabox radio button control.
 *
 * @package Hestia
 */

/**
 * Class Checkbox
 *
 * @package Hestia
 */
class Hestia_Metabox_Checkbox extends Hestia_Metabox_Control_Base {
	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'checkbox';

	/**
	 * Render control.
	 *
	 * @param string $post_id the post id.
	 *
	 * @return void
	 */
	public function render_content( $post_id ) {
		$value         = $this->get_value( $post_id );
		$markup        = '';
		$show_notice   = 'show' === $this->settings['show_notice'];
		$label_opicity = $show_notice ? 'opacity: .7;' : '';

		$markup .= '<p>';
		$markup .= '<div class="checkbox-toggle-wrap">';
		$markup .= '<label for="' . esc_attr( $this->id ) . '" style="' . esc_attr( $label_opicity ) . '">';
		$markup .= '<input type="checkbox" id="' . esc_attr( $this->id ) . '" name="' . esc_attr( $this->id ) . '" ' . '';
		if ( $value === 'on' ) {
			$markup .= ' checked="checked" ';
		}
		if ( $show_notice ) {
			$markup .= ' disabled="disabled" ';
		}
		$markup .= '/>';
		$markup .= esc_html( $this->settings['input_label'] ) . '</label>';
		if ( $show_notice && ! empty( $this->settings['upsell_data'] ) ) {
			$upsell_link = tsdk_translate_link( tsdk_utmify( 'https://themeisle.com/themes/hestia/upgrade/', $this->settings['upsell_data']['utm_tag'] ), 'query' );
				$markup .= '<p class="notice notice-info" style="margin: 5px 0;">'
				. sprintf(
					// translators: %1$s: field name, %2$s: a link with label 'Hestia PRO'.
					__( 'Refine %1$s with %2$s', 'hestia' ),
					$this->settings['upsell_data']['text'],
					'<a href="' . esc_url( $upsell_link ) . '" target="_blank">' . __( 'Hestia PRO', 'hestia' ) . '!</a>'
				) . '</p>';
		}
		$markup .= '</div>';
		$markup .= '</p>';

		echo $markup;
	}
}
