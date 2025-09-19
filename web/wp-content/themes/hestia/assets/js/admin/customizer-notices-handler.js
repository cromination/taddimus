/* global dismissNotices */

/**
 * Handle notice dismiss in customizer.
 */
jQuery( function ( $ ) {
	$( document ).on( 'click', '.hestia-notice .notice-dismiss', function () {
		var control_id = $( this ).closest('li').attr( 'id' ).replace( 'accordion-section-', '' );
		$.ajax( {
			url: dismissNotices.ajaxurl,
			type: 'POST',
			data: {
				action: 'dismissed_notice_handler',
				control: control_id,
				nonce: dismissNotices.nonce
			},
			success: function () {
				$( '#accordion-section-' + control_id ).fadeOut( 300, function () {
					if ( 'function' === typeof wp.customize ) {
						wp.customize.section.remove( control_id );
					} else {
						$( this ).remove();
					}
				} );
			}
		} );
	} );

	/**
	 * Add an upsell in the WooCommerce main panel.
	 * 
	 * @see https://github.com/woocommerce/woocommerce/blob/37336960f69090bb9464a79ed4b7c7507c800ede/plugins/woocommerce/includes/customizer/class-wc-shop-customizer.php#L279-L293
	 */
	function addWooCommerceUpsell() {
		if ( ! window.dismissNotices?.upsell?.woocommerce_panel ) {
			return;
		}
		$( "#sub-accordion-panel-woocommerce" ).append( `<span class="customize-control-description" style="display: flex; flex-direction: column; padding: 1rem;">${window.dismissNotices?.upsell?.woocommerce_panel}</span>` );
	}

	// Add this after WooCommerce own notices added with JS.
	setTimeout(() => {
		addWooCommerceUpsell();
	}, 1000);
} );