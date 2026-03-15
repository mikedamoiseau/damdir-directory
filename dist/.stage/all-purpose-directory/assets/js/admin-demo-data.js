/**
 * Admin Demo Data Page JavaScript
 *
 * Handles tabbed interface, per-tab AJAX generation/deletion,
 * and shared Users section management.
 *
 * @package APD
 * @since 1.2.0
 */

(function($) {
	'use strict';

	/**
	 * APD Demo Data Module
	 */
	var APDDemoData = {
		/**
		 * Configuration from localized script.
		 */
		config: window.apdDemoData || {},

		/**
		 * Initialize the demo data page.
		 */
		init: function() {
			this.bindEvents();
			this.initTabs();
		},

		/**
		 * Bind event handlers.
		 */
		bindEvents: function() {
			// Tab navigation.
			$('.apd-demo-tabs').on('click', '.nav-tab', this.handleTabClick.bind(this));

			// Per-tab generate forms.
			$('.apd-generate-tab-form').on('submit', this.handleTabGenerate.bind(this));

			// Per-tab delete buttons.
			$(document).on('click', '.apd-delete-tab-btn', this.handleTabDelete.bind(this));

			// Users section.
			$('#apd-generate-users-form').on('submit', this.handleGenerateUsers.bind(this));
			$('#apd-delete-users-btn').on('click', this.handleDeleteUsers.bind(this));

			// Toggle count inputs based on checkbox state.
			$('.apd-generate-tab-form').on('change', 'input[type="checkbox"]', function() {
				var $row = $(this).closest('.apd-form-row');
				var $number = $row.find('input[type="number"]');
				$number.prop('disabled', !this.checked);
			});
		},

		/**
		 * Initialize tabs from URL hash or default to first.
		 */
		initTabs: function() {
			var hash = window.location.hash.replace('#', '');
			var tabs = this.config.tabs || [];
			var activeTab = tabs.indexOf(hash) !== -1 ? hash : (tabs[0] || 'general');

			this.activateTab(activeTab);
		},

		/**
		 * Handle tab click.
		 *
		 * @param {Event} e Click event.
		 */
		handleTabClick: function(e) {
			e.preventDefault();
			var tab = $(e.currentTarget).data('tab');
			this.activateTab(tab);
			window.location.hash = tab;
		},

		/**
		 * Activate a tab by slug.
		 *
		 * @param {string} slug Tab slug.
		 */
		activateTab: function(slug) {
			// Update tab navigation.
			$('.apd-demo-tabs .nav-tab').removeClass('nav-tab-active').attr('aria-selected', 'false');
			$('.apd-demo-tabs .nav-tab[data-tab="' + slug + '"]').addClass('nav-tab-active').attr('aria-selected', 'true');

			// Show/hide tab content.
			$('.apd-tab-content').hide();
			$('#apd-tab-' + slug).show();
		},

		/**
		 * Handle per-tab generate form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleTabGenerate: function(e) {
			e.preventDefault();

			var self = this;
			var $form = $(e.currentTarget);
			var module = $form.data('module');
			var $btn = $form.find('button[type="submit"]');
			var $tabContent = $form.closest('.apd-tab-content');
			var $progress = $tabContent.find('.apd-tab-progress');
			var $results = $tabContent.find('.apd-tab-results');

			// Check if at least one option is selected.
			if (!$form.find('input[type="checkbox"]:checked').length) {
				alert(this.config.strings.error || 'Please select at least one data type to generate.');
				return;
			}

			// Disable form.
			$form.addClass('is-loading');
			$btn.prop('disabled', true);

			// Show progress.
			$results.hide();
			$progress.show().addClass('is-active');
			this.updateTabProgress($tabContent, 0, this.config.strings.generating);

			// Collect form data.
			var formData = {
				action: 'apd_generate_demo',
				nonce: this.config.generateNonce,
				module: module
			};

			// Collect all form inputs.
			$form.find('input[type="checkbox"]').each(function() {
				formData[this.name] = $(this).is(':checked') ? 1 : 0;
			});
			$form.find('input[type="number"]').each(function() {
				formData[this.name] = parseInt($(this).val(), 10) || 0;
			});

			// Simulate progress.
			var progress = 0;
			var progressInterval = setInterval(function() {
				if (progress < 90) {
					progress += Math.random() * 15;
					progress = Math.min(progress, 90);
					self.updateTabProgress($tabContent, progress, self.config.strings.generating);
				}
			}, 500);

			// Send AJAX request.
			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: formData,
				success: function(response) {
					clearInterval(progressInterval);
					self.updateTabProgress($tabContent, 100, self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							self.showTabResults($tabContent, response.data, 'success');
							self.updateTabStats($tabContent, response.data.counts, module);
							self.updateDeleteSection($tabContent, response.data);

							// Update users count.
							if (response.data.counts && response.data.counts.users !== undefined) {
								$('.apd-demo-users .apd-stat-count[data-type="users"]').text(
									self.formatNumber(response.data.counts.users)
								);
							}

							// Update delete users button state.
							if (response.data.has_module_data !== undefined) {
								self.updateDeleteUsersButton(response.data.has_module_data);
							}
						} else {
							self.showTabResults($tabContent, {
								message: response.data.message || self.config.strings.error
							}, 'error');
						}

						$form.removeClass('is-loading');
						$btn.prop('disabled', false);
					}, 500);
				},
				error: function() {
					clearInterval(progressInterval);
					$progress.removeClass('is-active').hide();

					self.showTabResults($tabContent, {
						message: self.config.strings.error
					}, 'error');

					$form.removeClass('is-loading');
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Handle per-tab delete button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleTabDelete: function(e) {
			e.preventDefault();

			var self = this;
			var $btn = $(e.currentTarget);
			var module = $btn.data('module');

			if (!confirm(this.config.strings.confirmDelete)) {
				return;
			}

			var $tabContent = $btn.closest('.apd-tab-content');
			var $progress = $tabContent.find('.apd-tab-progress');
			var $results = $tabContent.find('.apd-tab-results');

			$btn.prop('disabled', true);

			$results.hide();
			$progress.show().addClass('is-active');
			this.updateTabProgress($tabContent, 50, this.config.strings.deleting);

			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apd_delete_demo',
					nonce: this.config.deleteNonce,
					module: module
				},
				success: function(response) {
					self.updateTabProgress($tabContent, 100, self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							self.showDeleteResults($tabContent, response.data);
							self.updateTabStats($tabContent, response.data.counts, module);
							self.updateDeleteSectionEmpty($tabContent, module);

							// Update users section.
							if (response.data.users !== undefined) {
								$('.apd-demo-users .apd-stat-count[data-type="users"]').text(
									self.formatNumber(response.data.users)
								);
							}

							// Update delete users button state.
							if (response.data.has_module_data !== undefined) {
								self.updateDeleteUsersButton(response.data.has_module_data);
							}
						} else {
							self.showTabResults($tabContent, {
								message: response.data.message || self.config.strings.error
							}, 'error');
						}

						$btn.prop('disabled', false);
					}, 500);
				},
				error: function() {
					$progress.removeClass('is-active').hide();
					self.showTabResults($tabContent, {
						message: self.config.strings.error
					}, 'error');
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Handle generate users form submission.
		 *
		 * @param {Event} e Submit event.
		 */
		handleGenerateUsers: function(e) {
			e.preventDefault();

			var self = this;
			var $form = $(e.currentTarget);
			var $btn = $('#apd-generate-users-btn');
			var $progress = $('#apd-users-progress');
			var $results = $('#apd-users-results');

			$form.addClass('is-loading');
			$btn.prop('disabled', true);
			$results.hide();
			$progress.show().addClass('is-active');

			var $progressBar = $progress.find('.apd-progress-bar-fill');
			var $progressText = $progress.find('.apd-progress-text');
			$progressBar.css('width', '50%');
			$progressText.text(this.config.strings.generatingUsers);

			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apd_generate_users',
					nonce: this.config.generateNonce,
					users_count: parseInt($form.find('[name="users_count"]').val(), 10) || 5
				},
				success: function(response) {
					$progressBar.css('width', '100%');
					$progressText.text(self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							$results.html(
								'<p><span class="dashicons dashicons-yes"></span> ' +
								response.data.message + '</p>'
							).removeClass('error').addClass('success').show();

							$('.apd-demo-users .apd-stat-count[data-type="users"]').text(
								self.formatNumber(response.data.count)
							);

							// Show delete button if users exist now.
							if (response.data.count > 0 && !$('#apd-delete-users-btn').length) {
								window.location.reload();
							}
						} else {
							$results.html(
								'<p>' + (response.data.message || self.config.strings.error) + '</p>'
							).removeClass('success').addClass('error').show();
						}

						$form.removeClass('is-loading');
						$btn.prop('disabled', false);
					}, 500);
				},
				error: function() {
					$progress.removeClass('is-active').hide();
					$results.html(
						'<p>' + self.config.strings.error + '</p>'
					).removeClass('success').addClass('error').show();
					$form.removeClass('is-loading');
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Handle delete users button click.
		 *
		 * @param {Event} e Click event.
		 */
		handleDeleteUsers: function(e) {
			e.preventDefault();

			var self = this;

			if (!confirm(this.config.strings.confirmDelete)) {
				return;
			}

			var $btn = $(e.currentTarget);
			var $progress = $('#apd-users-progress');
			var $results = $('#apd-users-results');

			$btn.prop('disabled', true);
			$results.hide();
			$progress.show().addClass('is-active');

			var $progressBar = $progress.find('.apd-progress-bar-fill');
			var $progressText = $progress.find('.apd-progress-text');
			$progressBar.css('width', '50%');
			$progressText.text(this.config.strings.deletingUsers);

			$.ajax({
				url: this.config.ajaxUrl,
				type: 'POST',
				data: {
					action: 'apd_delete_users',
					nonce: this.config.deleteNonce
				},
				success: function(response) {
					$progressBar.css('width', '100%');
					$progressText.text(self.config.strings.success);

					setTimeout(function() {
						$progress.removeClass('is-active').hide();

						if (response.success) {
							$results.html(
								'<p><span class="dashicons dashicons-yes"></span> ' +
								response.data.message + '</p>'
							).removeClass('error').addClass('success').show();

							$('.apd-demo-users .apd-stat-count[data-type="users"]').text('0');

							// Remove delete button and hint.
							$btn.remove();
							$('.apd-delete-users-hint').remove();
						} else {
							$results.html(
								'<p>' + (response.data.message || self.config.strings.error) + '</p>'
							).removeClass('success').addClass('error').show();
							$btn.prop('disabled', false);
						}
					}, 500);
				},
				error: function() {
					$progress.removeClass('is-active').hide();
					$results.html(
						'<p>' + self.config.strings.error + '</p>'
					).removeClass('success').addClass('error').show();
					$btn.prop('disabled', false);
				}
			});
		},

		/**
		 * Update progress for a specific tab.
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {number} percent     Progress percentage.
		 * @param {string} text        Progress text.
		 */
		updateTabProgress: function($tabContent, percent, text) {
			$tabContent.find('.apd-tab-progress .apd-progress-bar-fill').css('width', percent + '%');
			$tabContent.find('.apd-tab-progress .apd-progress-text').text(text);
		},

		/**
		 * Show results for a tab.
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {object} data        Response data.
		 * @param {string} type        'success' or 'error'.
		 */
		showTabResults: function($tabContent, data, type) {
			var $results = $tabContent.find('.apd-tab-results');
			var html = '';

			if (type === 'success' && data.created) {
				html = '<h3><span class="dashicons dashicons-yes"></span> ' +
					(data.message || this.config.strings.success) + '</h3>';
				html += '<ul>';

				var labels = {
					categories: 'Categories created',
					tags: 'Tags created',
					listings: 'Listings created',
					reviews: 'Reviews created',
					inquiries: 'Inquiries created',
					favorites: 'Favorites added'
				};

				for (var key in data.created) {
					if (data.created.hasOwnProperty(key) && data.created[key] > 0) {
						var label = labels[key] || (key.charAt(0).toUpperCase() + key.slice(1) + ' created');
						html += '<li><span class="dashicons dashicons-yes"></span> ' +
							label + ': ' + data.created[key] + '</li>';
					}
				}

				html += '</ul>';
			} else {
				html = '<p>' + (data.message || this.config.strings.error) + '</p>';
			}

			$results.html(html).removeClass('success error').addClass(type).show();
		},

		/**
		 * Show delete results for a tab.
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {object} data        Response data.
		 */
		showDeleteResults: function($tabContent, data) {
			var $results = $tabContent.find('.apd-tab-results');
			var html = '<h3><span class="dashicons dashicons-yes"></span> ' +
				(data.message || 'Demo data deleted.') + '</h3>';

			if (data.deleted) {
				html += '<ul>';

				var labels = {
					categories: 'Categories deleted',
					tags: 'Tags deleted',
					listings: 'Listings deleted',
					reviews: 'Reviews deleted',
					inquiries: 'Inquiries deleted',
					favorites: 'Favorites cleared'
				};

				for (var key in data.deleted) {
					if (data.deleted.hasOwnProperty(key) && data.deleted[key] > 0) {
						var label = labels[key] || (key.charAt(0).toUpperCase() + key.slice(1) + ' deleted');
						html += '<li><span class="dashicons dashicons-yes"></span> ' +
							label + ': ' + data.deleted[key] + '</li>';
					}
				}

				html += '</ul>';
			}

			$results.html(html).removeClass('error').addClass('success').show();
		},

		/**
		 * Update stats table for a specific tab.
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {object} counts      Count data by type.
		 * @param {string} module      Module slug.
		 */
		updateTabStats: function($tabContent, counts, module) {
			if (!counts) return;

			var total = 0;

			for (var type in counts) {
				if (counts.hasOwnProperty(type) && type !== 'users') {
					var dataType = module + '_' + type;
					var $cell = $tabContent.find('.apd-stat-count[data-type="' + dataType + '"]');
					if ($cell.length) {
						$cell.text(this.formatNumber(counts[type]));
					}
					total += counts[type];
				}
			}

			$tabContent.find('.apd-stat-total[data-module="' + module + '"]').text(this.formatNumber(total));
		},

		/**
		 * Update delete section after generation (may need to show the button).
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {object} data        Response data.
		 */
		updateDeleteSection: function($tabContent, data) {
			// If there was no data before and now there is, reload to show delete button.
			var $noData = $tabContent.find('.apd-demo-delete .apd-no-data');
			if ($noData.length && data.created) {
				var hasCreated = false;
				for (var key in data.created) {
					if (data.created.hasOwnProperty(key) && data.created[key] > 0) {
						hasCreated = true;
						break;
					}
				}
				if (hasCreated) {
					window.location.reload();
				}
			}
		},

		/**
		 * Update delete section to show empty state after deletion.
		 *
		 * @param {jQuery} $tabContent Tab content container.
		 * @param {string} module      Module slug.
		 */
		updateDeleteSectionEmpty: function($tabContent, module) {
			var $section = $tabContent.find('.apd-demo-delete');
			$section.find('.apd-warning, .apd-delete-tab-btn').remove();
			$section.append(
				'<p class="apd-no-data">' +
				'<span class="dashicons dashicons-yes-alt"></span> ' +
				'No demo data found.' +
				'</p>'
			);
		},

		/**
		 * Update the delete users button enabled/disabled state.
		 *
		 * @param {boolean} hasModuleData Whether module data exists.
		 */
		updateDeleteUsersButton: function(hasModuleData) {
			var $btn = $('#apd-delete-users-btn');
			if ($btn.length) {
				$btn.prop('disabled', hasModuleData);

				if (hasModuleData) {
					if (!$('.apd-delete-users-hint').length) {
						$btn.after(
							'<span class="description apd-delete-users-hint"> ' +
							'Delete all tab data first before deleting users.</span>'
						);
					}
				} else {
					$('.apd-delete-users-hint').remove();
				}
			}
		},

		/**
		 * Format number with locale separators.
		 *
		 * @param {number} num Number to format.
		 * @return {string} Formatted number.
		 */
		formatNumber: function(num) {
			return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
		}
	};

	// Initialize on document ready.
	$(document).ready(function() {
		APDDemoData.init();
	});

	// Handle browser back/forward for hash changes.
	$(window).on('hashchange', function() {
		var hash = window.location.hash.replace('#', '');
		var tabs = APDDemoData.config.tabs || [];
		if (tabs.indexOf(hash) !== -1) {
			APDDemoData.activateTab(hash);
		}
	});

})(jQuery);
