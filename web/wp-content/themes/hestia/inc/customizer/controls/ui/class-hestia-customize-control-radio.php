<?php
/**
 * This class allows developers to display a radio in customizer
 *
 * @package Hestia
 */

/**
 * Class Hestia_Radio
 *
 * @access public
 */
class Hestia_Customize_Control_Radio extends WP_Customize_Control {

	/**
	 * The type of customize control being rendered.
	 *
	 * @since  1.1.60
	 * @access public
	 * @var    string
	 */
	public $type = 'radio';

	/**
	 * Radio has icons.
	 *
	 * @access public
	 * @var    bool
	 */
	public $has_icon = false;

	/**
	 * Constructor.
	 *
	 * @param WP_Customize_Manager $manager Customize manager instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		parent::__construct( $manager, $id, $args );
		if ( array_key_exists( 'has_icon', $args ) ) {
			$this->has_icon = esc_attr( $args['has_icon'] );
		}
	}

	/**
	 * Get the layout content.
	 */
	public function render_content() {
		if ( empty( $this->choices ) || ! is_array( $this->choices ) ) {
			return; // Ensure choices are valid before rendering
		}

		if ( ! empty( $this->label ) ) {
			echo '<span class="customize-control-title">' . esc_html( $this->label ) . '</span>';
		}

		if ( ! empty( $this->description ) ) {
			echo '<span class="description customize-control-description">' . esc_html( $this->description ) . '</span>';
		}

		echo '<div class="radio-pagination">';
		foreach ( $this->choices as $value => $label ) {
			?>
				<input
					type="radio"
					id="<?php echo esc_attr( $this->id . '-' . $value ); ?>"
					name="<?php echo esc_attr( $this->id ); ?>"
					value="<?php echo esc_attr( $value ); ?>" 
					<?php
					$this->link();
					checked( $this->value(), $value );
					?>
				/>
				<label for="<?php echo esc_attr( $this->id . '-' . $value ); ?>" class="button-choice <?php echo $this->value() === $value ? 'active' : ''; ?>">
					<?php
					if ( ! $this->has_icon ) {
						echo esc_html( $label );
					} else {
						echo wp_kses( $label, hestia_allow_icon_tag() );
					}
					?>
				</label>
			<?php
		}
		echo '</div>';
	}
}
