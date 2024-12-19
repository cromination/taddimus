/**
 * Script for the customizer auto scrolling.
 *
 * Sends the section name to the preview.
 *
 * @since    1.1.50
 * @package Hestia
 *
 * @author    ThemeIsle
 */

/* global wp */

var hestia_customize_scroller = function ( $ ) {
	'use strict';

	$(
		function () {
				var customize = wp.customize;

				$( 'ul[id*="hestia_frontpage_sections"] .accordion-section' ).not( '.panel-meta' ).each(
					function () {
						$( this ).on(
							'click', function() {
								var getAriaOwns = $( this ).attr( 'aria-owns' );
								var sectionId = getAriaOwns.split( '-' ).pop();
								wp.customize.section( sectionId ).expand({
									completeCallback: function() {
										var activeTab = $( '.hestia-customizer-tab.active' );
										if (activeTab.length) {
											activeTab.trigger( 'click' );
										}
									}
								});

								var section = getAriaOwns.split( '_' ).pop();
								customize.previewer.send( 'clicked-customizer-section', section );
							}
						);

						$( document ).on( 'change', '[id*="hestia_slider_type"]', function() {
							var activeTab = $( '.hestia-customizer-tab.active' );
							if (activeTab.length) {
								activeTab.trigger( 'click' );
							}
						} );
					}
				);
		}
	);
};

hestia_customize_scroller( jQuery );
