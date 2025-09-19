<?php
/**
 * Sortable Elements Control
 *
 * A WordPress Customizer control for sorting and toggling visibility of elements.
 *
 * @package    Hestia
 * @since      1.0.0
 */

if ( ! class_exists( 'WP_Customize_Control' ) ) {
	return;
}

/**
 * Sortable Elements Customizer Control
 */
class Hestia_Sortable_Elements extends WP_Customize_Control {

	/**
	 * Control type.
	 *
	 * @var string
	 */
	public $type = 'sortable-elements';

	/**
	 * Additional CSS class for the control.
	 *
	 * @var string
	 */
	public $custom_class = '';

	/**
	 * Toggle label for visibility toggle.
	 *
	 * @var string
	 */
	public $toggle_label = '';

	/**
	 * Elements to display in the control.
	 *
	 * @var array
	 */
	public $elements = array();

	/**
	 * Constructor.
	 *
	 * @param WP_Customize_Manager $manager Customize manager instance.
	 * @param string               $id      Control ID.
	 * @param array                $args    Optional. Arguments to override class property defaults.
	 */
	public function __construct( WP_Customize_Manager $manager, $id, array $args = array() ) {
		parent::__construct( $manager, $id, $args );

		if ( array_key_exists( 'custom_class', $args ) ) {
			$this->custom_class = esc_attr( $args['custom_class'] );
		}

		if ( array_key_exists( 'toggle_label', $args ) ) {
			$this->toggle_label = $args['toggle_label'];
		}
	}

	/**
	 * Enqueue control related scripts and styles.
	 */
	public function enqueue() {
		wp_enqueue_script( 'jquery-ui-sortable' );

		wp_enqueue_script(
			'hestia-customizer-sortable-elements',
			get_template_directory_uri() . '/inc/customizer/controls/custom-controls/sortable-elements/sortable-elements.js',
			array( 'jquery', 'jquery-ui-sortable', 'customize-base' ),
			HESTIA_VERSION,
			true
		);

		wp_enqueue_style(
			'hestia-customizer-sortable-elements',
			get_template_directory_uri() . '/inc/customizer/controls/custom-controls/sortable-elements/sortable-elements.css',
			array(),
			HESTIA_VERSION
		);
	}

	/**
	 * Render the control's content.
	 */
	protected function render_content() {
		if ( empty( $this->elements ) ) {
			return;
		}

		$value = $this->value();
		if ( is_string( $value ) ) {
			$decoded = json_decode( $value, true );
			if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
				$value = $decoded;
			}
		}

		if ( ! is_array( $value ) || ! isset( $value['order'] ) || ! isset( $value['visibility'] ) ) {
			$value = array(
				'order'      => array_keys( $this->elements ),
				'visibility' => array_fill_keys( array_keys( $this->elements ), true ),
			);
		}

		$ordered_elements = array();
		$processed_ids    = array();

		// Add elements in saved order.
		if ( ! empty( $value['order'] ) && is_array( $value['order'] ) ) {
			foreach ( $value['order'] as $element_id ) {
				if ( isset( $this->elements[ $element_id ] ) ) {
					$ordered_elements[] = array(
						'id'      => $element_id,
						'label'   => $this->elements[ $element_id ],
						'visible' => isset( $value['visibility'][ $element_id ] ) ? $value['visibility'][ $element_id ] : true,
					);
					$processed_ids[]    = $element_id;
				}
			}
		}

		foreach ( $this->elements as $element_id => $label ) {
			if ( ! in_array( $element_id, $processed_ids, true ) ) {
				$ordered_elements[] = array(
					'id'      => $element_id,
					'label'   => $label,
					'visible' => isset( $value['visibility'][ $element_id ] ) ? $value['visibility'][ $element_id ] : true,
				);
			}
		}

		$custom_class = ! empty( $this->custom_class ) ? ' ' . esc_attr( $this->custom_class ) : '';
		$json_value   = wp_json_encode( $value );
		?>

		<label class="hestia-sortable-elements-control">
			<?php if ( ! empty( $this->label ) ) : ?>
				<span class="customize-control-title"><?php echo esc_html( $this->label ); ?></span>
			<?php endif; ?>

			<?php if ( ! empty( $this->description ) ) : ?>
				<span class="description customize-control-description"><?php echo wp_kses_post( $this->description ); ?></span>
			<?php endif; ?>

			<div class="hestia-sortable-wrapper<?php echo $custom_class; ?>">
				<input type="hidden" value="<?php echo esc_attr( $json_value ); ?>" <?php $this->link(); ?> />

				<ul class="hestia-sortable-list">
					<?php foreach ( $ordered_elements as $element ) : ?>
						<li class="hestia-sortable-item<?php echo ! $element['visible'] ? ' hestia-item-hidden' : ''; ?>" 
							data-element-id="<?php echo esc_attr( $element['id'] ); ?>">
							<div class="hestia-item-inner">
								<div class="hestia-item-handle">
									<span class="dashicons dashicons-menu" aria-hidden="true"></span>
								</div>

								<div class="hestia-item-content">
									<span class="hestia-item-label"><?php echo esc_html( $element['label'] ); ?></span>
								</div>

								<div class="hestia-item-visibility">
									<a type="button" 
											class="hestia-visibility-toggle" 
											aria-label="<?php echo esc_attr( $this->toggle_label ); ?>"
											data-visible="<?php echo $element['visible'] ? 'true' : 'false'; ?>"
											tabindex="-1">
										<?php if ( $element['visible'] ) : ?>
											<span class="dashicons dashicons-visibility" aria-hidden="true"></span>
										<?php else : ?>
											<span class="dashicons dashicons-hidden" aria-hidden="true"></span>
										<?php endif; ?>
									</a>
								</div>
							</div>
						</li>
					<?php endforeach; ?>
				</ul>
			</div>
		</label>
		<?php
	}

	/**
	 * An Underscore (JS) template for this control's content.
	 *
	 * This is intentionally empty as we're using PHP rendering.
	 */
	protected function content_template() {}
}
