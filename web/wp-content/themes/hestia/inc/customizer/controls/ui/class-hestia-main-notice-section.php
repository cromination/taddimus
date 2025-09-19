<?php
/**
 * ThemeIsle Customizer Notification Section Class.
 *
 * @package Hestia
 */

/**
 * Themeisle_Customizer_Notify_Section class
 */
class Hestia_Main_Notice_Section extends Hestia_Generic_Notice_Section {
	/**
	 * The type of customize section being rendered.
	 *
	 * @since  1.0.0
	 * @access public
	 * @var    string
	 */
	public $type = 'main-customizer-notice';

	/**
	 * The plugin name.
	 *
	 * @var array
	 */
	public $plugin_name;

	/**
	 * Slug of recommended plugin.
	 *
	 * @var string
	 */
	public $slug;

	/**
	 * Notice dismissed button is visible.
	 *
	 * @var bool
	 */
	public $dismissed_button = true;

	/**
	 * Notice type.
	 *
	 * @var string
	 */
	public $notice_type = 'info';

	/**
	 * Control options.
	 *
	 * Ex: redirect link after install
	 *
	 * @var array
	 */
	public $options = array();

	/**
	 * Hestia_Main_Notice_Section constructor.
	 *
	 * @param WP_Customize_Manager $manager The customizer object.
	 * @param string               $id The control id.
	 * @param array                $args The control args.
	 */
	public function __construct( $manager, $id, $args = array() ) {
		parent::__construct( $manager, $id, $args );
		if ( empty( $this->slug ) ) {
			return;
		}
	}

	/**
	 * Add custom parameters to pass to the JS via JSON.
	 *
	 * @since  1.0.0
	 * @access public
	 * @return array
	 */
	public function json() {
		$json                          = parent::json();
		$json['name']                  = ! empty( $this->plugin_name ) ? $this->plugin_name : '';
		$json['description']           = ! empty( $this->description ) ? $this->description : '';
		$json['plugin_install_button'] = $this->create_plugin_install_button( $this->slug, $this->options );
		$json['hide_notice']           = $this->hide_notice;
		$json['dismissed_button']      = $this->dismissed_button;
		$json['notice_type']           = $this->notice_type;
		$json['options']               = $this->options;

		return $json;

	}

	/**
	 * Outputs the structure for the customizer control
	 *
	 * @since  1.0.0
	 * @access public
	 * @return void
	 */
	protected function render_template() {
		?>
		<# if ( ! data.hide_notice ) { #>
		<li id="accordion-section-{{ data.id }}"
				class="hestia-notice control-section control-section-{{ data.type }} cannot-expand" style="margin-bottom: 1px;">
			<# if ( data.title ) { #>
			<h3 class="accordion-section-title">
				{{{ data.title }}}
			</h3>
			<# } #>
			<div class="<# if ( data.options && data.options.fixed ) { #>hestia-notice-body<# } else { #>notice<# } #> notice-{{{data.notice_type}}}" style="position: relative; margin-top:0; margin-bottom: 1px;">
				<# if ( data.dismissed_button ) { #>
					<button type="button" class="notice-dismiss" style="z-index: 1;"></button>
				<# } #>
				<# if ( data.name ) { #>
				<h3 style="padding-right: 36px">
					{{{data.name}}}
				</h3>

				<# } #>
				<# if( data.description ) { #>
				<p>
					{{{ data.description }}}
				</p>
				<# } #>
				<# if ( data.plugin_install_button ) { #>
				{{{data.plugin_install_button}}}
				<# } else if ( data.options && data.options['redirect'] && data.options['title'] )  { #>
					<a href="{{{data.options['redirect']}}}" style="text-decoration: none;">{{{data.options['title']}}}</a>
				<# } #>
			</div>
		</li>
		<# } #>
		<?php
	}
}
