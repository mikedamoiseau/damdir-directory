/**
 * Admin Settings Page JavaScript
 *
 * @package APD
 * @since 1.0.0
 */

(function($) {
	'use strict';

	/**
	 * APD Settings Module
	 */
	var APDSettings = {
		/**
		 * Initialize the settings page.
		 */
		init: function() {
			this.initColorPickers();
			this.initConditionalFields();
			this.initFormValidation();
		},

		/**
		 * Initialize WordPress color pickers.
		 */
		initColorPickers: function() {
			if ($.fn.wpColorPicker) {
				$('.apd-color-picker').wpColorPicker();
			}
		},

		/**
		 * Initialize conditional field visibility.
		 */
		initConditionalFields: function() {
			var $whoCanSubmit = $('#who_can_submit');
			var $guestSubmission = $('#guest_submission').closest('tr');
			var $submissionRoles = $('#submission_roles-wrapper');
			var $submissionRolesRow = $submissionRoles.closest('tr');
			var $submissionRolesDesc = $('#submission_roles-description');

			function toggleSubmissionFields() {
				var value = $whoCanSubmit.val();

				// Show/hide guest submission option
				if (value === 'anyone') {
					$guestSubmission.show();
				} else {
					$guestSubmission.hide();
				}

				// Show/hide roles selector
				if (value === 'specific_roles') {
					$submissionRoles.removeClass('hidden');
					$submissionRolesRow.show();
					$submissionRolesDesc.show();
				} else {
					$submissionRoles.addClass('hidden');
					$submissionRolesRow.hide();
					$submissionRolesDesc.hide();
				}
			}

			if ($whoCanSubmit.length) {
				toggleSubmissionFields();
				$whoCanSubmit.on('change', toggleSubmissionFields);
			}

			// Show/hide custom redirect URL field
			var $redirectAfter = $('#redirect_after');
			var $customUrlRow = $('#redirect_custom_url').closest('tr');

			function toggleCustomUrl() {
				if ($redirectAfter.val() === 'custom') {
					$customUrlRow.show();
				} else {
					$customUrlRow.hide();
				}
			}

			if ($redirectAfter.length) {
				toggleCustomUrl();
				$redirectAfter.on('change', toggleCustomUrl);
			}
		},

		/**
		 * Initialize form validation feedback.
		 */
		initFormValidation: function() {
			var $form = $('.apd-settings-form');

			// Add visual feedback for invalid email fields
			$form.on('blur', 'input[type="email"]', function() {
				var $input = $(this);
				var value = $input.val();

				if (value && !APDSettings.isValidEmail(value)) {
					$input.addClass('apd-invalid');
				} else {
					$input.removeClass('apd-invalid');
				}
			});

			// Add visual feedback for number fields out of range
			$form.on('blur change', 'input[type="number"]', function() {
				var $input = $(this);
				var value = parseInt($input.val(), 10);
				var min = parseInt($input.attr('min'), 10);
				var max = parseInt($input.attr('max'), 10);

				if (isNaN(value)) {
					$input.removeClass('apd-invalid');
					return;
				}

				if ((!isNaN(min) && value < min) || (!isNaN(max) && value > max)) {
					$input.addClass('apd-invalid');
				} else {
					$input.removeClass('apd-invalid');
				}
			});
		},

		/**
		 * Validate email format.
		 *
		 * @param {string} email Email address to validate.
		 * @return {boolean} Whether email is valid.
		 */
		isValidEmail: function(email) {
			var pattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
			return pattern.test(email);
		}
	};

	// Initialize on document ready
	$(document).ready(function() {
		APDSettings.init();
	});

})(jQuery);
