/**
 * Sortable Elements Control JavaScript - Improved Version
 *
 * @package Hestia
 */

/* global wp */

( function( $, api ) {
	'use strict';
	
	const CONSTANTS = {
		SELECTORS: {
			SORTABLE_LIST: '.hestia-sortable-list',
			SORTABLE_ITEM: '.hestia-sortable-item',
			VISIBILITY_TOGGLE: '.hestia-visibility-toggle',
			HIDDEN_INPUT: 'input[type="hidden"]',
			ITEM_HANDLE: '.hestia-item-handle',
			DASHICONS: '.dashicons'
		},
		CLASSES: {
			SORTING: 'hestia-item-sorting',
			HIDDEN: 'hestia-item-hidden',
			PLACEHOLDER: 'hestia-sortable-placeholder',
			VISIBILITY_ICON: 'dashicons-visibility',
			HIDDEN_ICON: 'dashicons-hidden'
		},
		DELAYS: {
			PREVIEW_UPDATE: 300
		},
		SORTABLE_OPTIONS: {
			OPACITY: 0.8,
			REVERT: 150,
			TOLERANCE: 'pointer'
		}
	};

	/**
	 * Sortable Elements Control
	 */
	api.controlConstructor['sortable-elements'] = api.Control.extend({
		
		/**
		 * Initialize the control.
		 */
		ready: function() {
			const control = this;

			control.cacheElements();
			
			// Initialize/validate the setting value first.
			control.initializeValue();
			
			// Setup functionality.
			control.setupSortable();
			control.setupLivePreview();
			control.initVisibilityToggler();
			
			// Bind cleanup on control destruction.
			control.bindCleanupEvents();
		},
		
		/**
		 * Cache DOM elements for better performance
		 */
		cacheElements: function() {
			const control = this;
			
			control.sortableList = control.container.find(CONSTANTS.SELECTORS.SORTABLE_LIST);
			control.hiddenInput = control.container.find(CONSTANTS.SELECTORS.HIDDEN_INPUT);
			control.sortableItems = control.container.find(CONSTANTS.SELECTORS.SORTABLE_ITEM);
		},
		
		/**
		 * Initialize and validate the control value.
		 */
		initializeValue: function() {
			const control = this;
			let currentValue = control.setting.get();
			
			try {
				if (typeof currentValue === 'string') {
					currentValue = JSON.parse(currentValue);
				}
				
				if (!control.isValidStructure(currentValue)) {
					throw new Error('Invalid structure detected');
				}
				
			} catch (error) {
				console.warn('Sortable Elements: Invalid data detected, rebuilding from DOM:', error.message);
				currentValue = control.rebuildFromDOM();
			}

			if (!currentValue) {
				currentValue = control.getDefaultValue();
			}
			
			// Save the validated structure.
			control.setting.set(currentValue);
			control.updateHiddenInput(currentValue);
		},
		
		/**
		 * Validate the structure of the value object.
		 */
		isValidStructure: function(value) {
			return value && 
				typeof value === 'object' && 
				Array.isArray(value.order) && 
				typeof value.visibility === 'object' &&
				value.order.length > 0;
		},
		
		/**
		 * Get default value structure
		 */
		getDefaultValue: function() {
			return {
				order: [],
				visibility: {}
			};
		},
		
		/**
		 * Rebuild data structure from DOM elements
		 */
		rebuildFromDOM: function() {
			const control = this;
			const elements = {};
			const order = [];
			
			// Extract data from DOM elements.
			control.sortableItems.each(function() {
				const $item = $(this);
				const elementId = $item.data('element-id');
				
				if (!elementId) {
					console.warn('Sortable item missing element-id attribute');
					return;
				}
				
				const toggleBtn = $item.find(CONSTANTS.SELECTORS.VISIBILITY_TOGGLE);
				const isVisible = toggleBtn.length > 0 ? 
					toggleBtn.attr('data-visible') === 'true' : true;
				
				order.push(elementId);
				elements[elementId] = isVisible;
			});
			
			return {
				order: order,
				visibility: elements
			};
		},
		
		/**
		 * Setup jQuery UI Sortable functionality.
		 */
		setupSortable: function() {
			const control = this;
			
			if (!control.sortableList.length) {
				console.warn('Sortable list element not found');
				return;
			}
			
			// Initialize jQuery UI Sortable.
			control.sortableList.sortable({
				items: CONSTANTS.SELECTORS.SORTABLE_ITEM,
				handle: CONSTANTS.SELECTORS.ITEM_HANDLE,
				axis: 'y',
				placeholder: CONSTANTS.CLASSES.PLACEHOLDER,
				helper: 'clone',
				opacity: CONSTANTS.SORTABLE_OPTIONS.OPACITY,
				tolerance: CONSTANTS.SORTABLE_OPTIONS.TOLERANCE,
				cursor: 'move',
				revert: CONSTANTS.SORTABLE_OPTIONS.REVERT,
				cancel: CONSTANTS.SELECTORS.VISIBILITY_TOGGLE + ', .hestia-item-label',
				
				start: function( event, ui ) {
					ui.item.addClass(CONSTANTS.CLASSES.SORTING);
					ui.placeholder.height(ui.item.height());
				},
				
				stop: function( event, ui ) {
					ui.item.removeClass(CONSTANTS.CLASSES.SORTING);
					control.saveElementsState();
					control.triggerPreviewUpdate();
				},
			});
			
			// Disable text selection on sortable items.
			control.sortableList.disableSelection();
		},
		
		/**
		 * Setup live preview functionality.
		 */
		setupLivePreview: function() {
			const control = this;
			
			// Listen for setting changes and update preview
			control.setting.bind( function( newValue ) {
				try {
					// Validate before sending to preview.
					if (!control.isValidStructure(newValue)) {
						console.warn('Invalid structure, skipping preview update');
						return;
					}
					
					// Send message to preview frame.
					api.previewer.send( 'hestia-sortable-elements-changed', {
						setting: control.id,
						value: newValue
					});
				} catch (error) {
					console.error('Error updating preview:', error);
				}
			});
		},
		
		/**
		 * Trigger preview update with debouncing.
		 */
		triggerPreviewUpdate: function() {
			const control = this;

			if ( control.previewUpdateTimeout ) {
				clearTimeout( control.previewUpdateTimeout );
			}
			
			// Set a new timeout to batch updates
			control.previewUpdateTimeout = setTimeout( function() {
				try {
					// Force refresh of the preview
					api.previewer.refresh();
					
					// Also trigger selective refresh if supported
					if ( api.selectiveRefresh ) {
						api.selectiveRefresh.requestFullRefresh();
					}
				} catch (error) {
					console.error('Error refreshing preview:', error);
				}
			}, CONSTANTS.DELAYS.PREVIEW_UPDATE );
		},

		/**
		 * Initialize visibility toggler functionality.
		 */
		initVisibilityToggler: function() {
			const control = this;

			control.container.on('click', CONSTANTS.SELECTORS.VISIBILITY_TOGGLE, function(event) {
				event.preventDefault();
				event.stopPropagation();
				
				const toggleBtn = $(this);
				const itemElem = toggleBtn.closest(CONSTANTS.SELECTORS.SORTABLE_ITEM);
				
				if (!itemElem.length) {
					console.warn('Could not find parent sortable item');
					return;
				}
				
				control.toggleItemVisibility(toggleBtn, itemElem);
			});
		},
		
		/**
		 * Toggle visibility of a single item
		 */
		toggleItemVisibility: function(toggleBtn, itemElem) {
			const control = this;
			
			try {
				const currentVisibility = toggleBtn.attr('data-visible') === 'true';
				const newVisibility = !currentVisibility;

				toggleBtn.attr('data-visible', newVisibility.toString());

				const iconElem = toggleBtn.find(CONSTANTS.SELECTORS.DASHICONS);
				if (iconElem.length) {
					iconElem
						.removeClass(CONSTANTS.CLASSES.HIDDEN_ICON + ' ' + CONSTANTS.CLASSES.VISIBILITY_ICON)
						.addClass(newVisibility ? CONSTANTS.CLASSES.VISIBILITY_ICON : CONSTANTS.CLASSES.HIDDEN_ICON);
				}
				
				itemElem.toggleClass(CONSTANTS.CLASSES.HIDDEN, !newVisibility);
				
				// Update ARIA attributes for accessibility.
				toggleBtn.attr('aria-pressed', newVisibility.toString());
				itemElem.attr('aria-hidden', (!newVisibility).toString());
				

				control.saveElementsState();
				control.triggerPreviewUpdate();
				
			} catch (error) {
				console.error('Error toggling item visibility:', error);
			}
		},
		
		/**
		 * Save the current elements state.
		 */
		saveElementsState: function() {
			const control = this;
			
			try {
				const state = {
					order: [],
					visibility: {}
				};
				
				// Collect current state.
				control.container.find(CONSTANTS.SELECTORS.SORTABLE_ITEM).each(function() {
					const $item = $(this);
					const elementId = $item.data('element-id');
					
					if (!elementId) {
						console.warn('Sortable item missing element-id, skipping');
						return;
					}
					
					const toggleBtn = $item.find(CONSTANTS.SELECTORS.VISIBILITY_TOGGLE);
					const isVisible = toggleBtn.length > 0 ? 
						toggleBtn.attr('data-visible') === 'true' : true;
					
					state.order.push(elementId);
					state.visibility[elementId] = isVisible;
				});
				
				// Validate before saving.
				if (!control.isValidStructure(state)) {
					throw new Error('Generated invalid state structure');
				}
				
				// Update both the hidden input and the setting.
				control.updateHiddenInput(state);
				control.setting.set(state);
				
			} catch (error) {
				console.error('Error saving elements state:', error);
			}
		},
		
		/**
		 * Update the hidden input with JSON string
		 */
		updateHiddenInput: function(state) {
			const control = this;
			
			try {
				const jsonString = JSON.stringify(state);
				
				if (control.hiddenInput.length) {
					control.hiddenInput.val(jsonString);
				} else {
					console.warn('Hidden input not found');
				}
			} catch (error) {
				console.error('Error updating hidden input:', error);
			}
		},
		
		/**
		 * Bind cleanup events for proper resource management.
		 */
		bindCleanupEvents: function() {
			const control = this;
			
			// Cleanup when control is removed or page is unloaded.
			$(window).on('beforeunload', function() {
				control.cleanup();
			});
		},
		
		/**
		 * Cleanup the memory.
		 */
		cleanup: function() {
			const control = this;
			
			// Clear any pending timeouts
			if (control.previewUpdateTimeout) {
				clearTimeout(control.previewUpdateTimeout);
				control.previewUpdateTimeout = null;
			}
			
			// Destroy sortable if it exists.
			if (control.sortableList && control.sortableList.length) {
				try {
					control.sortableList.sortable('destroy');
				} catch (error) {
					console.warn('Error destroying sortable:', error);
				}
			}
			
			// Remove event listeners
			control.container.off('click', CONSTANTS.SELECTORS.VISIBILITY_TOGGLE);
			
			// Clear cached elements.
			control.sortableList = null;
			control.hiddenInput = null;
			control.sortableItems = null;
		}
	});

})( jQuery, wp.customize );