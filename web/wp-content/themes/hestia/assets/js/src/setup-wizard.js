jQuery( document ).ready( function( $ ) {
	var wizardId = '#hestiawizard';
	$(wizardId).smartWizard({
		autoAdjustHeight: false,
		transition: {
			animation: 'fade',
			speed: '400',
		},
		keyboard: {
			keyNavigation: false,
		},
		anchor: {
			enableNavigation: false,
			enableNavigationAlways: false,
		},
	});

	/**
	 * Next step.
	 */
	var nextStep = function() {
		var nextStepElement = $( '.step-progress-bar:visible li.active' ).next( 'li' );
		if ( nextStepElement.length === 0 ) {
			$(wizardId).smartWizard( 'next' );
			return;
		}
		nextStepElement.addClass( 'trigger-next' ).click();
	};

	/**
	 * Prev step.
	 */
	var prevStep = function() {
		var prevStepElement = $( '.step-progress-bar:visible li.active' ).prev( 'li' );
		if ( prevStepElement.length === 0 ) {
			$(wizardId).smartWizard( 'prev' );
			return;
		}
		prevStepElement.addClass( 'trigger-prev' ).click();
	};
	
	/**
	 * Hide footer action.
	 */
	var hideFooterAction = function( hide ) {
		if ( hide ) {
			$( '.hestia-wizard__footer .left, .hestia-wizard__footer .right' ).addClass( 'disabled' );
		} else {
			$( '.hestia-wizard__footer .left, .hestia-wizard__footer .right' ).removeClass( 'disabled' );
		}
	};

	/**
	 * Leave step and show step.
	 */
	$(wizardId).on( 'leaveStep showStep', function( e, anchorObject, stepIndex, stepDirection, stepPosition ) {
		$( '.hestia-hide-skip-btn .hestia-wizard__footer .right' ).addClass( 'hidden' );
		$( '.step-progress-bar li' ).removeClass( 'trigger-next trigger-prev' );
		if ( window.location.hash && ( '#step-1' === window.location.hash || '#step-5' === window.location.hash ) ) {
			$( '.hestia-dashboard-link' ).removeClass( 'hidden' );
			if ( '#step-5' === window.location.hash ) {
				$( '.gif-animation' ).addClass( 'show' );
				setTimeout( function() {
					$( '.gif-animation' ).removeClass( 'show' );
				}, 6000 );
			}
		} else {
			$( '.hestia-dashboard-link' ).addClass( 'hidden' );
		}
	} );

	$('.prev-wizard').on('click', function () {
		// Navigate previous
		prevStep();
		return true;
	});
	
	$('.next-wizard').on('click', function () {
		// Navigate next.
		nextStep();
		return true;
	});

	$('.hestia-accordion .hestia-accordion-item .hestia-accordion-item__button').on('click', function () {
			var current_item = $(this).parents();
			$('.hestia-accordion .hestia-accordion-item .hestia-accordion-item__content').each(
				function (i, el) {
					if ($(el).parent().is(current_item)) {
						$(el).prev().toggleClass('is-active');
						$(el).slideToggle();
						$(this).toggleClass('is-active');
					} else {
						$(el).prev().removeClass('is-active');
						$(el).slideUp();
						$(this).removeClass('is-active');
					}
				}
			);
		}
	);

	// Click to next step.
	$( document ).on( 'click', '.hestia-card-box button:not(.hestia-btn,.add-new,.add-btn,.hestia-accordion-item__button)', function() {
		nextStep();
	} );

	// Remove disabled class from send me access button.
	$( document ).on( 'input', 'input[name="wizard[email]"]', function() {
		if ( $( this ).val() !== '' ) {
			$( this ).parents( '.hestia-card-box' ).find( '.hestia-btn' ).removeClass( 'disabled' );
		} else {
			$( this ).parents( '.hestia-card-box' ).find( '.hestia-btn' ).addClass( 'disabled' );
		}
	} );

	// Save and Continue.
	$( document ).on( 'click', '.hestia-card-box .form-footer button[data-action]', function() {
		var _currentButton = $(this);
		var currentStep = _currentButton.parents( 'div.tab-pane' );
		var actionId    = _currentButton.data( 'action' );

		// Validation for email field.
		if ( 'hestia_newsletter_subscribe' === actionId ) {
			var emailElement = $( 'input[name="wizard[email]"]' );
			emailElement.next(".hestia-field-error").remove();

			var subscribeEmail = emailElement.val();
			var EmailTest = /^[\w\-\.\+]+\@[a-zA-Z0-9\.\-]+\.[a-zA-z0-9]{2,4}$/;
			var errorMessage = "";
			if ( '' === subscribeEmail ) {
				errorMessage = hestiaSetupWizardData.errorMessages.requiredEmail;
			} else if ( ! EmailTest.test( subscribeEmail ) ) {
				errorMessage = hestiaSetupWizardData.errorMessages.invalidEmail;
			}
			if ("" !== errorMessage) {
				$('<span class="hestia-field-error">' + errorMessage + "</span>").insertAfter(emailElement);
				return;
			}
		}

		var data        = 'action=hestia_wizard_step_process&security=' + hestiaSetupWizardData.ajax.security + '&_action=' + actionId + '&' + jQuery( 'input' ).serialize();
		currentStep.find( '.spinner' ).addClass( 'is-active' );
		currentStep.find( '.hestia-card-box .hestia-error-notice' ).addClass( 'hidden' );
		_currentButton.addClass( 'disabled' );
		hideFooterAction( true );

		$.post(
			hestiaSetupWizardData.ajax.url,
			data,
			function( res ) {
				hideFooterAction( false );
				currentStep.find( '.spinner' ).removeClass( 'is-active' );
				_currentButton.removeClass( 'disabled' );
				if ( res.status === 1 ) {
					nextStep();
				} else if( res.status === 0 && res.message !== '' ) {
					currentStep.find( '.hestia-card-box .hestia-error-notice' ).html( '<p>' + res.message + '</p>' ).removeClass( 'hidden' );
				}
			},
			'json'
		)
		.fail( function( response ) {
			hideFooterAction( false );
			currentStep.find( '.spinner' ).removeClass( 'is-active' );
			currentStep.find( 'button.hestia-btn' ).removeClass( 'disabled' );
		} );
	} );

	// Hide save button.
	$( document ).on( 'change', 'input[name="wizard[install_plugin][]"]', function() {
		var checkedOption = $( 'input[name="wizard[install_plugin][]"]' ).filter( ':checked' );
		var saveButton = $( this ).parents( '.hestia-card-box' ).find( '.hestia-btn' );
		if ( checkedOption.length > 0 ) {
			saveButton.removeClass( 'disabled' );
		} else {
			saveButton.addClass( 'disabled' );
		}
	} );
});