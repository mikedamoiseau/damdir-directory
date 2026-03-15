/**
 * All Purpose Directory - Frontend Scripts
 *
 * Handles AJAX filtering, form submissions, URL state management,
 * and loading states for listing search and filter functionality.
 *
 * @package APD
 */

(function() {
    'use strict';

    /**
     * APD Filter Module
     *
     * Handles all filter-related functionality including AJAX filtering,
     * URL state management, and UI updates.
     */
    const APDFilter = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Cache for DOM elements.
         */
        elements: {
            form: null,
            results: null,
            activeFilters: null,
            loadingIndicator: null,
            resultsCount: null,
        },

        /**
         * Current state.
         */
        state: {
            isLoading: false,
            currentRequest: null,
            debounceTimer: null,
        },

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * Initialize the filter module.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.cacheElements();

            if (!this.elements.form) {
                return;
            }

            this.bindEvents();
            this.handleInitialState();
        },

        /**
         * Cache DOM elements for performance.
         */
        cacheElements: function() {
            this.elements.form = document.querySelector('.apd-search-form');
            this.elements.results = document.querySelector('.apd-listings');
            this.elements.activeFilters = document.querySelector('.apd-active-filters');
            this.elements.resultsCount = document.querySelector('.apd-results-count');
            this.elements.pagination = document.querySelector('.apd-pagination');

            // Create loading indicator if not exists
            if (this.elements.form && !document.querySelector('.apd-loading-indicator')) {
                const indicator = document.createElement('div');
                indicator.className = 'apd-loading-indicator';
                indicator.setAttribute('aria-hidden', 'true');
                var spinner = document.createElement('span');
                spinner.className = 'apd-loading-spinner';
                var loadingText = document.createElement('span');
                loadingText.className = 'apd-loading-text';
                loadingText.textContent = this.config.i18n?.loading || 'Loading...';
                indicator.appendChild(spinner);
                indicator.appendChild(loadingText);
                this.elements.form.appendChild(indicator);
                this.elements.loadingIndicator = indicator;
            }
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            const form = this.elements.form;

            if (!form) {
                return;
            }

            // Form submission
            form.addEventListener('submit', this.handleSubmit.bind(this));

            // Select changes (immediate filter)
            form.querySelectorAll('select').forEach(select => {
                select.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Checkbox changes (immediate filter)
            form.querySelectorAll('input[type="checkbox"]').forEach(checkbox => {
                checkbox.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Range inputs (debounced)
            form.querySelectorAll('input[type="number"]').forEach(input => {
                input.addEventListener('input', this.handleRangeInput.bind(this));
            });

            // Date inputs (immediate filter)
            form.querySelectorAll('input[type="date"]').forEach(input => {
                input.addEventListener('change', this.handleFilterChange.bind(this));
            });

            // Search input (debounced)
            form.querySelectorAll('input[type="search"], input[type="text"]').forEach(input => {
                input.addEventListener('input', this.handleSearchInput.bind(this));
            });

            // Clear filters button
            const clearBtn = form.querySelector('.apd-search-form__clear');
            if (clearBtn) {
                clearBtn.addEventListener('click', this.handleClearFilters.bind(this));
            }

            // Active filter removal
            document.addEventListener('click', (e) => {
                if (e.target.closest('.apd-active-filters__remove')) {
                    e.preventDefault();
                    this.handleRemoveFilter(e.target.closest('.apd-active-filters__remove'));
                }
            });

            // Browser back/forward navigation
            window.addEventListener('popstate', this.handlePopState.bind(this));

            // Pagination links (if using AJAX)
            document.addEventListener('click', (e) => {
                const paginationLink = e.target.closest('.apd-pagination a, .pagination a');
                if (paginationLink && this.elements.form?.dataset.ajax === 'true') {
                    e.preventDefault();
                    this.handlePagination(paginationLink);
                }
            });
        },

        /**
         * Handle initial state from URL.
         */
        handleInitialState: function() {
            // URL state is already applied by PHP on page load
            // This is called in case we need to do any JS-specific initialization
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e - Submit event.
         */
        handleSubmit: function(e) {
            const form = this.elements.form;

            // Check if AJAX is enabled
            if (form.dataset.ajax !== 'true') {
                return; // Allow normal form submission
            }

            e.preventDefault();
            this.performFilter();
        },

        /**
         * Handle filter control changes.
         *
         * @param {Event} e - Change event.
         */
        handleFilterChange: function(e) {
            const form = this.elements.form;

            if (form.dataset.ajax !== 'true') {
                form.submit();
                return;
            }

            this.performFilter();
        },

        /**
         * Handle range input with debounce.
         *
         * @param {Event} e - Input event.
         */
        handleRangeInput: function(e) {
            this.debounce(() => {
                this.handleFilterChange(e);
            }, 500);
        },

        /**
         * Handle search input with debounce.
         *
         * @param {Event} e - Input event.
         */
        handleSearchInput: function(e) {
            this.debounce(() => {
                this.handleFilterChange(e);
            }, 500);
        },

        /**
         * Handle clear filters button.
         *
         * @param {Event} e - Click event.
         */
        handleClearFilters: function(e) {
            const form = this.elements.form;

            if (form.dataset.ajax !== 'true') {
                return; // Allow normal navigation
            }

            e.preventDefault();

            // Reset form
            form.reset();

            // Clear URL parameters and reload
            const baseUrl = this.config.archiveUrl || window.location.pathname;
            this.updateUrl(baseUrl);
            this.performFilter();
        },

        /**
         * Handle removing a single active filter.
         *
         * @param {HTMLElement} removeLink - The remove link element.
         */
        handleRemoveFilter: function(removeLink) {
            const url = removeLink.href;

            if (this.elements.form?.dataset.ajax === 'true') {
                this.updateUrl(url);
                this.updateFormFromUrl();
                this.performFilter();
            } else {
                window.location.href = url;
            }
        },

        /**
         * Handle browser back/forward navigation.
         *
         * @param {PopStateEvent} e - PopState event.
         */
        handlePopState: function(e) {
            if (this.elements.form?.dataset.ajax === 'true') {
                this.updateFormFromUrl();
                this.performFilter(false); // Don't push state again
            }
        },

        /**
         * Handle pagination link clicks.
         *
         * @param {HTMLElement} link - Pagination link element.
         */
        handlePagination: function(link) {
            const url = new URL(link.href);
            let paged = url.searchParams.get('paged');
            if (!paged) {
                const match = url.pathname.match(/\/page\/(\d+)/);
                paged = match ? match[1] : 1;
            }

            this.performFilter(false, parseInt(paged, 10));

            // Scroll to top of results
            if (this.elements.results) {
                this.elements.results.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }
        },

        /**
         * Perform AJAX filter request.
         *
         * @param {boolean} pushState - Whether to push state to history.
         * @param {number} paged - Page number.
         */
        performFilter: function(pushState = true, paged = 1) {
            const form = this.elements.form;

            if (!form || this.state.isLoading) {
                return;
            }

            // Cancel any pending request
            if (this.state.currentRequest) {
                this.state.currentRequest.abort();
            }

            // Show loading state
            this.setLoading(true);

            // Build form data
            const formData = new FormData(form);
            formData.append('action', 'apd_filter_listings');
            formData.append('_apd_nonce', this.config.filterNonce || '');
            formData.append('paged', paged.toString());

            // Pass posts_per_page from the results container so AJAX matches the initial query.
            var postsPerPage = this.elements.results?.dataset.postsPerPage;
            if (postsPerPage) {
                formData.append('posts_per_page', postsPerPage);
            }

            // Create abort controller for this request
            const controller = new AbortController();
            this.state.currentRequest = controller;

            // Build URL for state
            if (pushState) {
                const newUrl = this.buildUrlFromForm(form);
                this.updateUrl(newUrl);
            }

            // Perform AJAX request
            fetch(this.config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
                signal: controller.signal,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.updateResults(data.data);
                } else {
                    this.showError(data.data?.message || this.config.i18n?.error);
                }
            })
            .catch(error => {
                if (error.name !== 'AbortError') {
                    console.error('APD Filter Error:', error);
                    this.showError(this.config.i18n?.error);
                }
            })
            .finally(() => {
                this.setLoading(false);
                this.state.currentRequest = null;
            });
        },

        /**
         * Update the results container with new content.
         *
         * @param {Object} data - Response data.
         */
        updateResults: function(data) {
            // Update listings
            if (this.elements.results && data.html) {
                this.elements.results.innerHTML = data.html;
            }

            // Update results count
            if (this.elements.resultsCount) {
                const count = data.found_posts || 0;
                let countText;

                if (count === 0) {
                    countText = this.config.i18n?.noResults || 'No listings found.';
                } else if (count === 1) {
                    countText = this.config.i18n?.oneResultFound || '1 listing found';
                } else {
                    countText = (this.config.i18n?.resultsFound || '%d listings found').replace('%d', count);
                }

                this.elements.resultsCount.textContent = countText;
            }

            // Update pagination
            this.updatePagination(data.pagination_html);

            // Update active filters display
            this.updateActiveFilters(data.active_filters);

            // Announce results to screen readers via dedicated live region
            this.announceResults(data.found_posts || 0);

            // Trigger custom event for other scripts
            document.dispatchEvent(new CustomEvent('apd:filtered', {
                detail: data,
            }));
        },

        /**
         * Update the pagination display.
         *
         * @param {string} html - Server-rendered pagination HTML.
         */
        updatePagination: function(html) {
            var existing = this.elements.pagination;

            if (html) {
                if (existing) {
                    // Replace existing pagination.
                    existing.outerHTML = html;
                } else {
                    // Insert pagination after results.
                    if (this.elements.results) {
                        this.elements.results.insertAdjacentHTML('afterend', html);
                    }
                }
            } else if (existing) {
                // No pagination needed — remove it.
                existing.remove();
            }

            // Re-cache the pagination element.
            this.elements.pagination = document.querySelector('.apd-pagination');
        },

        /**
         * Update the active filters display.
         *
         * @param {Object} activeFilters - Active filters data.
         */
        updateActiveFilters: function(activeFilters) {
            // For now, we rely on PHP rendering the active filters
            // In a full implementation, we'd rebuild the chips here
        },

        /**
         * Announce filter results to screen readers via a dedicated live region.
         *
         * @param {number} count - Number of results found.
         */
        announceResults: function(count) {
            var liveRegion = document.getElementById('apd-filter-live-region');
            if (!liveRegion) {
                liveRegion = document.createElement('div');
                liveRegion.id = 'apd-filter-live-region';
                liveRegion.setAttribute('role', 'status');
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.className = 'screen-reader-text';
                document.body.appendChild(liveRegion);
            }

            var message;
            if (count === 0) {
                message = this.config.i18n?.noResults || 'No listings found.';
            } else if (count === 1) {
                message = this.config.i18n?.oneResultFound || '1 listing found';
            } else {
                message = (this.config.i18n?.resultsFound || '%d listings found').replace('%d', count);
            }

            // Clear then set to ensure screen readers detect the change
            liveRegion.textContent = '';
            setTimeout(function() {
                liveRegion.textContent = message;
            }, 100);
        },

        /**
         * Set loading state with skeleton placeholders.
         *
         * @param {boolean} isLoading - Whether loading is in progress.
         */
        setLoading: function(isLoading) {
            this.state.isLoading = isLoading;

            const form = this.elements.form;
            const results = this.elements.results;

            if (isLoading) {
                form?.classList.add('apd-search-form--loading');
                results?.classList.add('apd-listings--loading');
                results?.setAttribute('aria-busy', 'true');

                // Insert skeleton placeholders.
                if (results) {
                    this.insertSkeletons(results);
                }

                if (this.elements.loadingIndicator) {
                    this.elements.loadingIndicator.setAttribute('aria-hidden', 'false');
                }
            } else {
                form?.classList.remove('apd-search-form--loading');
                results?.classList.remove('apd-listings--loading');
                results?.setAttribute('aria-busy', 'false');

                // Remove skeleton placeholders.
                this.removeSkeletons();

                if (this.elements.loadingIndicator) {
                    this.elements.loadingIndicator.setAttribute('aria-hidden', 'true');
                }
            }
        },

        /**
         * Insert skeleton placeholder cards into the results area.
         *
         * @param {HTMLElement} container - The results container.
         */
        insertSkeletons: function(container) {
            this.removeSkeletons();

            var overlay = document.createElement('div');
            overlay.className = 'apd-skeleton-overlay';
            overlay.setAttribute('aria-hidden', 'true');

            for (var i = 0; i < 6; i++) {
                var card = document.createElement('div');
                card.className = 'apd-skeleton-card';
                card.innerHTML =
                    '<div class="apd-skeleton apd-skeleton-card__image"></div>' +
                    '<div class="apd-skeleton-card__body">' +
                    '<div class="apd-skeleton apd-skeleton-card__title"></div>' +
                    '<div class="apd-skeleton apd-skeleton-card__text"></div>' +
                    '<div class="apd-skeleton apd-skeleton-card__text apd-skeleton-card__text--short"></div>' +
                    '</div>';
                overlay.appendChild(card);
            }

            container.appendChild(overlay);
        },

        /**
         * Remove skeleton placeholders.
         */
        removeSkeletons: function() {
            var existing = document.querySelector('.apd-skeleton-overlay');
            if (existing) {
                existing.parentNode.removeChild(existing);
            }
        },

        /**
         * Show an error message.
         *
         * @param {string} message - Error message.
         */
        showError: function(message) {
            if (this.elements.results) {
                const errorDiv = document.createElement('div');
                errorDiv.className = 'apd-filter-error';
                errorDiv.setAttribute('role', 'alert');
                const p = document.createElement('p');
                p.textContent = message || 'An error occurred.';
                errorDiv.appendChild(p);
                this.elements.results.textContent = '';
                this.elements.results.appendChild(errorDiv);
            }
        },

        /**
         * Build URL from form data.
         *
         * @param {HTMLFormElement} form - The form element.
         * @returns {string} The URL with query parameters.
         */
        buildUrlFromForm: function(form) {
            const formData = new FormData(form);
            const params = new URLSearchParams();

            for (const [key, value] of formData.entries()) {
                if (value && value.toString().trim() !== '') {
                    // Handle array parameters (checkboxes)
                    if (key.endsWith('[]')) {
                        params.append(key, value.toString());
                    } else {
                        params.set(key, value.toString());
                    }
                }
            }

            const baseUrl = form.action || this.config.archiveUrl || window.location.pathname;
            const queryString = params.toString();

            return queryString ? baseUrl + '?' + queryString : baseUrl;
        },

        /**
         * Update the browser URL.
         *
         * @param {string} url - New URL.
         */
        updateUrl: function(url) {
            if (window.history && window.history.pushState) {
                window.history.pushState({ apd: true }, '', url);
            }
        },

        /**
         * Update form values from current URL.
         */
        updateFormFromUrl: function() {
            const form = this.elements.form;
            if (!form) {
                return;
            }

            const params = new URLSearchParams(window.location.search);

            // Reset form first
            form.reset();

            // Set values from URL
            params.forEach((value, key) => {
                const input = form.querySelector('[name="' + key + '"], [name="' + key + '[]"]');

                if (!input) {
                    return;
                }

                if (input.type === 'checkbox') {
                    // Handle checkbox groups
                    const checkboxes = form.querySelectorAll('[name="' + key + '"]');
                    checkboxes.forEach(cb => {
                        cb.checked = params.getAll(key).includes(cb.value);
                    });
                } else if (input.type === 'select-multiple') {
                    // Handle multi-select
                    const values = params.getAll(key);
                    Array.from(input.options).forEach(opt => {
                        opt.selected = values.includes(opt.value);
                    });
                } else {
                    input.value = value;
                }
            });
        },

        /**
         * Debounce function execution.
         *
         * @param {Function} func - Function to debounce.
         * @param {number} wait - Wait time in ms.
         */
        debounce: function(func, wait) {
            clearTimeout(this.state.debounceTimer);
            this.state.debounceTimer = setTimeout(func, wait);
        },
    };

    /**
     * APD Submission Form Module
     *
     * Handles client-side validation for the listing submission form.
     */
    const APDSubmission = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Cache for DOM elements.
         */
        elements: {
            form: null,
            submitBtn: null,
        },

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * Initialize the submission module.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.elements.form = document.querySelector('.apd-submission-form');

            if (!this.elements.form) {
                return;
            }

            this.elements.submitBtn = this.elements.form.querySelector('.apd-submission-form__submit');

            this.bindEvents();
            this.initImageUpload();
            this.initFieldGroups();
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            const form = this.elements.form;

            // Form submission with validation
            form.addEventListener('submit', this.handleSubmit.bind(this));

            // Real-time validation on blur
            form.querySelectorAll('input, textarea, select').forEach(field => {
                field.addEventListener('blur', this.handleFieldBlur.bind(this));
                field.addEventListener('input', this.handleFieldInput.bind(this));
            });

            // Clear error on focus
            form.querySelectorAll('input, textarea, select').forEach(field => {
                field.addEventListener('focus', this.clearFieldError.bind(this));
            });
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e - Submit event.
         */
        handleSubmit: function(e) {
            // Prevent double submission.
            if (this.submitting) {
                e.preventDefault();
                return;
            }

            // Validate all fields
            const isValid = this.validateForm();

            if (!isValid) {
                e.preventDefault();

                // Scroll to first error
                const firstError = this.elements.form.querySelector('.apd-field--has-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    const input = firstError.querySelector('input, textarea, select');
                    if (input) {
                        input.focus();
                    }
                }

                // Announce error to screen readers
                this.announceErrors();
                return;
            }

            // Mark as submitting and disable the button.
            this.submitting = true;
            var btn = this.elements.submitBtn;
            if (btn) {
                btn.disabled = true;
                btn.dataset.originalText = btn.textContent;
                btn.textContent = btn.dataset.loadingText || this.config.i18n?.submitting || 'Submitting…';
                btn.classList.add('apd-button--loading');
            }
        },

        /**
         * Validate the entire form.
         *
         * @returns {boolean} True if valid.
         */
        validateForm: function() {
            const form = this.elements.form;
            let isValid = true;

            // Find all required fields
            const requiredFields = form.querySelectorAll('[required], .apd-field--required input, .apd-field--required textarea, .apd-field--required select');

            requiredFields.forEach(field => {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            });

            // Custom field validations
            isValid = this.validateCustomFields() && isValid;

            return isValid;
        },

        /**
         * Validate a single field.
         *
         * @param {HTMLElement} field - The field element.
         * @returns {boolean} True if valid.
         */
        validateField: function(field) {
            const fieldWrapper = field.closest('.apd-field');
            if (!fieldWrapper) {
                return true;
            }

            const fieldName = fieldWrapper.dataset.fieldName || field.name;
            let isValid = true;
            let errorMessage = '';

            // Check required
            if (field.hasAttribute('required') || fieldWrapper.classList.contains('apd-field--required')) {
                if (field.type === 'checkbox') {
                    if (!field.checked) {
                        isValid = false;
                        errorMessage = this.config.i18n?.requiredField || 'This field is required.';
                    }
                } else if (field.tagName === 'SELECT' && field.multiple) {
                    if (field.selectedOptions.length === 0) {
                        isValid = false;
                        errorMessage = this.config.i18n?.requiredField || 'This field is required.';
                    }
                } else if (!field.value.trim()) {
                    isValid = false;
                    errorMessage = this.config.i18n?.requiredField || 'This field is required.';
                }
            }

            // Check email format
            if (isValid && field.type === 'email' && field.value.trim()) {
                const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                if (!emailRegex.test(field.value.trim())) {
                    isValid = false;
                    errorMessage = this.config.i18n?.invalidEmail || 'Please enter a valid email address.';
                }
            }

            // Check URL format
            if (isValid && field.type === 'url' && field.value.trim()) {
                try {
                    new URL(field.value.trim());
                } catch (e) {
                    isValid = false;
                    errorMessage = this.config.i18n?.invalidUrl || 'Please enter a valid URL.';
                }
            }

            // Check min/max length
            if (isValid && field.value.trim()) {
                const minLength = field.getAttribute('minlength');
                const maxLength = field.getAttribute('maxlength');

                if (minLength && field.value.length < parseInt(minLength, 10)) {
                    isValid = false;
                    errorMessage = (this.config.i18n?.minLength || 'Minimum %d characters required.').replace('%d', minLength);
                }

                if (maxLength && field.value.length > parseInt(maxLength, 10)) {
                    isValid = false;
                    errorMessage = (this.config.i18n?.maxLength || 'Maximum %d characters allowed.').replace('%d', maxLength);
                }
            }

            // Update field state
            if (isValid) {
                this.clearFieldError({ target: field });
            } else {
                this.setFieldError(fieldWrapper, errorMessage);
            }

            return isValid;
        },

        /**
         * Validate custom field types.
         *
         * @returns {boolean} True if all custom validations pass.
         */
        validateCustomFields: function() {
            let isValid = true;

            // Validate file uploads
            const fileInputs = this.elements.form.querySelectorAll('input[type="file"]');
            fileInputs.forEach(input => {
                if (input.files.length > 0) {
                    const file = input.files[0];
                    const maxSize = 5 * 1024 * 1024; // 5MB default

                    if (file.size > maxSize) {
                        const fieldWrapper = input.closest('.apd-field');
                        if (fieldWrapper) {
                            this.setFieldError(fieldWrapper, this.config.i18n?.fileTooLarge || 'File size exceeds the maximum limit.');
                            isValid = false;
                        }
                    }
                }
            });

            return isValid;
        },

        /**
         * Handle field blur event.
         *
         * @param {Event} e - Blur event.
         */
        handleFieldBlur: function(e) {
            const field = e.target;
            const fieldWrapper = field.closest('.apd-field');

            if (!fieldWrapper) {
                return;
            }

            // Only validate if field has a value or is required
            if (field.value.trim() || field.hasAttribute('required') || fieldWrapper.classList.contains('apd-field--required')) {
                this.validateField(field);
            }
        },

        /**
         * Handle field input event.
         *
         * @param {Event} e - Input event.
         */
        handleFieldInput: function(e) {
            const field = e.target;
            const fieldWrapper = field.closest('.apd-field');

            if (!fieldWrapper || !fieldWrapper.classList.contains('apd-field--has-error')) {
                return;
            }

            // Re-validate if field had an error
            this.validateField(field);
        },

        /**
         * Clear field error state.
         *
         * @param {Event} e - Focus event.
         */
        clearFieldError: function(e) {
            const field = e.target;
            const fieldWrapper = field.closest('.apd-field');

            if (!fieldWrapper) {
                return;
            }

            fieldWrapper.classList.remove('apd-field--has-error');

            // Remove client-side error message (keep server-side ones)
            const clientErrors = fieldWrapper.querySelectorAll('.apd-field__error--client');
            clientErrors.forEach(error => error.remove());

            // If no errors left, remove the error container
            const errorContainer = fieldWrapper.querySelector('.apd-field__errors');
            if (errorContainer && errorContainer.children.length === 0) {
                errorContainer.remove();
            }
        },

        /**
         * Set field error state.
         *
         * @param {HTMLElement} fieldWrapper - The field wrapper element.
         * @param {string} message - Error message.
         */
        setFieldError: function(fieldWrapper, message) {
            fieldWrapper.classList.add('apd-field--has-error');

            // Check if error container exists
            let errorContainer = fieldWrapper.querySelector('.apd-field__errors');
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.className = 'apd-field__errors';
                errorContainer.setAttribute('role', 'alert');
                errorContainer.setAttribute('aria-live', 'polite');

                // Insert after the input container
                const inputContainer = fieldWrapper.querySelector('.apd-field__input');
                if (inputContainer) {
                    inputContainer.after(errorContainer);
                } else {
                    fieldWrapper.appendChild(errorContainer);
                }
            }

            // Remove existing client-side errors
            const existingClientErrors = errorContainer.querySelectorAll('.apd-field__error--client');
            existingClientErrors.forEach(error => error.remove());

            // Add new error
            const errorElement = document.createElement('p');
            errorElement.className = 'apd-field__error apd-field__error--client';
            errorElement.textContent = message;
            errorContainer.appendChild(errorElement);
        },

        /**
         * Announce errors to screen readers.
         */
        announceErrors: function() {
            const errorCount = this.elements.form.querySelectorAll('.apd-field--has-error').length;

            if (errorCount === 0) {
                return;
            }

            // Create or update live region
            let liveRegion = document.getElementById('apd-form-live-region');
            if (!liveRegion) {
                liveRegion = document.createElement('div');
                liveRegion.id = 'apd-form-live-region';
                liveRegion.setAttribute('role', 'status');
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.className = 'screen-reader-text';
                document.body.appendChild(liveRegion);
            }

            const message = errorCount === 1
                ? (this.config.i18n?.oneError || 'Please fix 1 error before submitting.')
                : (this.config.i18n?.multipleErrors || 'Please fix %d errors before submitting.').replace('%d', errorCount);

            liveRegion.textContent = message;
        },

        /**
         * Initialize image upload functionality.
         */
        initImageUpload: function() {
            const imageUploads = this.elements.form.querySelectorAll('.apd-image-upload');

            imageUploads.forEach(upload => {
                const fileInput = upload.querySelector('.apd-image-upload__file');
                const hiddenInput = upload.querySelector('.apd-image-upload__input');
                const preview = upload.querySelector('.apd-image-upload__preview');
                const removeBtn = upload.querySelector('.apd-image-upload__remove');
                const buttonText = upload.querySelector('.apd-image-upload__button-text');

                if (fileInput) {
                    fileInput.addEventListener('change', (e) => {
                        const file = e.target.files[0];
                        if (file) {
                            // Validate file type
                            const validTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
                            if (!validTypes.includes(file.type)) {
                                const fieldWrapper = upload.closest('.apd-field');
                                if (fieldWrapper) {
                                    this.setFieldError(fieldWrapper, this.config.i18n?.invalidImageType || 'Please select a valid image file (JPG, PNG, GIF, or WebP).');
                                }
                                fileInput.value = '';
                                return;
                            }

                            // Set loading state for screen readers
                            upload.setAttribute('aria-busy', 'true');
                            this.announceImageStatus(upload, this.config.i18n?.imageLoading || 'Loading image preview...');

                            // Show preview
                            const reader = new FileReader();
                            reader.onload = (e) => {
                                if (preview) {
                                    var img = document.createElement('img');
                                    img.src = e.target.result;
                                    img.alt = this.config.i18n?.previewAlt || 'Image preview';
                                    img.className = 'apd-image-upload__image';
                                    preview.textContent = '';
                                    preview.appendChild(img);
                                    preview.classList.add('apd-image-upload__preview--visible');
                                }
                                if (buttonText) {
                                    buttonText.textContent = this.config.i18n?.changeImage || 'Change Image';
                                }
                                // Show remove button if it exists
                                if (removeBtn) {
                                    removeBtn.style.display = '';
                                }

                                // Clear the hidden input since we're uploading a new file
                                if (hiddenInput) {
                                    hiddenInput.value = '';
                                }

                                // Clear any previous errors
                                const fieldWrapper = upload.closest('.apd-field');
                                if (fieldWrapper) {
                                    fieldWrapper.classList.add('apd-field--has-image');
                                    this.clearFieldError({ target: fileInput });
                                }

                                // Clear loading state
                                upload.setAttribute('aria-busy', 'false');
                                this.announceImageStatus(upload, this.config.i18n?.imageReady || 'Image preview ready.');
                            };
                            reader.readAsDataURL(file);
                        }
                    });
                }

                if (removeBtn) {
                    removeBtn.addEventListener('click', () => {
                        if (preview) {
                            preview.textContent = '';
                            preview.classList.remove('apd-image-upload__preview--visible');
                        }
                        if (fileInput) {
                            fileInput.value = '';
                        }
                        if (hiddenInput) {
                            hiddenInput.value = '';
                        }
                        if (buttonText) {
                            buttonText.textContent = this.config.i18n?.selectImage || 'Select Image';
                        }
                        removeBtn.style.display = 'none';

                        const fieldWrapper = upload.closest('.apd-field');
                        if (fieldWrapper) {
                            fieldWrapper.classList.remove('apd-field--has-image');
                        }
                    });
                }
            });
        },

        /**
         * Announce image upload status to screen readers.
         *
         * @param {HTMLElement} upload - The image upload container.
         * @param {string} message - The status message.
         */
        announceImageStatus: function(upload, message) {
            var statusEl = upload.querySelector('.apd-image-upload__status');
            if (!statusEl) {
                statusEl = document.createElement('div');
                statusEl.className = 'apd-image-upload__status screen-reader-text';
                statusEl.setAttribute('role', 'status');
                statusEl.setAttribute('aria-live', 'polite');
                upload.appendChild(statusEl);
            }
            statusEl.textContent = message;
        },

        /**
         * Initialize collapsible field groups.
         */
        initFieldGroups: function() {
            const groupToggles = this.elements.form.querySelectorAll('.apd-submission-form__group-toggle');

            groupToggles.forEach(toggle => {
                toggle.addEventListener('click', () => {
                    const expanded = toggle.getAttribute('aria-expanded') === 'true';
                    const groupId = toggle.getAttribute('aria-controls');
                    const groupBody = document.getElementById(groupId);
                    const group = toggle.closest('.apd-submission-form__group');

                    toggle.setAttribute('aria-expanded', !expanded);

                    if (groupBody) {
                        if (expanded) {
                            groupBody.setAttribute('hidden', '');
                        } else {
                            groupBody.removeAttribute('hidden');
                        }
                    }

                    if (group) {
                        group.classList.toggle('apd-submission-form__group--collapsed', expanded);
                    }
                });
            });
        },
    };

    /**
     * APD My Listings Module
     *
     * Handles AJAX actions for the My Listings dashboard tab.
     */
    const APDMyListings = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Cache for DOM elements.
         */
        elements: {
            container: null,
        },

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * Initialize the module.
         * Guards: bails early if already initialized or DOM element not present.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.elements.container = document.querySelector('.apd-my-listings');

            if (!this.elements.container) {
                return;
            }

            this.bindEvents();
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            // Handle action links with confirmation.
            document.addEventListener('click', this.handleActionClick.bind(this));

            // Handle sort select navigation.
            var sortSelect = this.elements.container.querySelector('.apd-my-listings__sort-select');
            if (sortSelect) {
                sortSelect.addEventListener('change', function() {
                    if (this.value) {
                        window.location.href = this.value;
                    }
                });
            }
        },

        /**
         * Handle action link clicks.
         *
         * @param {Event} e - Click event.
         */
        handleActionClick: function(e) {
            const actionLink = e.target.closest('.apd-listing-action[data-confirm]');

            if (!actionLink) {
                return;
            }

            const confirmMessage = actionLink.dataset.confirm;

            if (confirmMessage && !window.confirm(confirmMessage)) {
                e.preventDefault();
                return;
            }

            // Check if this is an AJAX action.
            if (this.elements.container?.dataset.ajax === 'true') {
                e.preventDefault();
                this.handleAjaxAction(actionLink);
            }
        },

        /**
         * Handle AJAX action.
         *
         * @param {HTMLElement} actionLink - The action link element.
         */
        handleAjaxAction: function(actionLink) {
            const url = new URL(actionLink.href);
            const params = url.searchParams;
            const action = params.get('apd_action');
            const listingId = params.get('listing_id');
            const nonce = params.get('_apd_nonce') || this.elements.container?.dataset.nonce;

            if (!action || !listingId) {
                return;
            }

            const row = actionLink.closest('.apd-listing-row');
            if (row) {
                row.style.opacity = '0.5';
                row.style.pointerEvents = 'none';
            }

            let ajaxAction;
            const formData = new FormData();
            formData.append('_apd_nonce', nonce);
            formData.append('listing_id', listingId);

            if (action === 'delete' || action === 'trash') {
                ajaxAction = 'apd_delete_listing';
                formData.append('delete_type', action === 'delete' ? 'permanent' : 'trash');
            } else {
                ajaxAction = 'apd_update_listing_status';
                formData.append('status', action);
            }

            formData.append('action', ajaxAction);

            fetch(this.config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    if (action === 'delete' || action === 'trash') {
                        // Remove the row with animation.
                        if (row) {
                            row.style.transition = 'all 0.3s ease-out';
                            row.style.transform = 'translateX(-100%)';
                            row.style.opacity = '0';
                            setTimeout(() => {
                                row.remove();
                                this.updateCount();
                            }, 300);
                        }
                    } else if (data.data.status_badge) {
                        // Update the status badge.
                        const statusCell = row?.querySelector('.apd-listing-row__status');
                        if (statusCell) {
                            var badgeWrapper = document.createElement('div');
                            badgeWrapper.innerHTML = data.data.status_badge;
                            statusCell.textContent = '';
                            while (badgeWrapper.firstChild) {
                                statusCell.appendChild(badgeWrapper.firstChild);
                            }
                        }
                        if (row) {
                            row.style.opacity = '1';
                            row.style.pointerEvents = 'auto';
                        }
                    }

                    this.showNotice(data.data.message, 'success');
                } else {
                    if (row) {
                        row.style.opacity = '1';
                        row.style.pointerEvents = 'auto';
                    }
                    this.showNotice(data.data?.message || 'An error occurred.', 'error');
                }
            })
            .catch(error => {
                console.error('APD Action Error:', error);
                if (row) {
                    row.style.opacity = '1';
                    row.style.pointerEvents = 'auto';
                }
                this.showNotice(this.config.i18n?.error || 'An error occurred.', 'error');
            });
        },

        /**
         * Update the listing count display.
         */
        updateCount: function() {
            const rows = this.elements.container?.querySelectorAll('.apd-listing-row');
            const countEl = this.elements.container?.querySelector('.apd-my-listings__count');

            if (countEl && rows) {
                countEl.textContent = '(' + rows.length + ')';
            }

            // Show empty state if no rows left.
            if (rows && rows.length === 0) {
                const tableWrapper = this.elements.container?.querySelector('.apd-my-listings__table-wrapper');
                if (tableWrapper) {
                    var emptyDiv = document.createElement('div');
                    emptyDiv.className = 'apd-my-listings-empty';
                    var emptyP = document.createElement('p');
                    emptyP.textContent = this.config.i18n?.noListings || 'No listings found.';
                    emptyDiv.appendChild(emptyP);
                    tableWrapper.textContent = '';
                    tableWrapper.appendChild(emptyDiv);
                }
            }
        },

        /**
         * Show a notice message.
         *
         * @param {string} message - Notice message.
         * @param {string} type - Notice type (success, error).
         */
        showNotice: function(message, type) {
            // Remove existing notices.
            const existingNotice = this.elements.container?.querySelector('.apd-notice');
            if (existingNotice) {
                existingNotice.remove();
            }

            // Create notice element.
            const notice = document.createElement('div');
            notice.className = 'apd-notice apd-notice--' + type;
            notice.setAttribute('role', 'alert');
            const p = document.createElement('p');
            p.textContent = message;
            notice.appendChild(p);

            // Insert at the start of the container.
            this.elements.container?.insertBefore(notice, this.elements.container.firstChild);

            // Auto-remove after 5 seconds.
            setTimeout(() => {
                notice.style.transition = 'opacity 0.3s ease-out';
                notice.style.opacity = '0';
                setTimeout(() => notice.remove(), 300);
            }, 5000);
        },
    };

    /**
     * APD Favorites Module
     *
     * Handles the favorite button toggle functionality.
     */
    const APDFavorites = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * State tracking for pending requests.
         */
        state: {
            pendingRequests: new Map(),
        },

        /**
         * Initialize the favorites module.
         * Guards: bails early if already initialized or no favorite buttons present.
         * Uses event delegation on document for dynamically-added buttons.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            // Check if any favorite buttons exist before binding global listener.
            if (!document.querySelector('.apd-favorite-button')) {
                return;
            }

            this.bindEvents();
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            // Use event delegation for favorite buttons.
            document.addEventListener('click', this.handleFavoriteClick.bind(this));
        },

        /**
         * Handle favorite button click.
         *
         * @param {Event} e - Click event.
         */
        handleFavoriteClick: function(e) {
            const button = e.target.closest('.apd-favorite-button');

            if (!button) {
                return;
            }

            e.preventDefault();
            e.stopPropagation();

            const listingId = button.dataset.listingId;

            // Prevent duplicate requests.
            if (this.state.pendingRequests.has(listingId)) {
                return;
            }

            this.toggleFavorite(button, listingId);
        },

        /**
         * Toggle favorite status via AJAX.
         *
         * @param {HTMLElement} button - The button element.
         * @param {string} listingId - The listing ID.
         */
        toggleFavorite: function(button, listingId) {
            const nonce = button.dataset.nonce || this.config.favoriteNonce;
            const action = this.config.favoriteAction || 'apd_toggle_favorite';

            // Store original state for rollback.
            const originalState = {
                isActive: button.classList.contains('apd-favorite-button--active'),
                ariaPressed: button.getAttribute('aria-pressed'),
                ariaLabel: button.getAttribute('aria-label'),
                count: button.querySelector('.apd-favorite-count')?.dataset.count || '0',
            };

            // Optimistic UI update.
            this.updateButtonState(button, !originalState.isActive);

            // Mark as pending.
            this.state.pendingRequests.set(listingId, true);

            // Add loading state.
            button.classList.add('apd-favorite-button--loading');
            button.disabled = true;

            // Build form data.
            const formData = new FormData();
            formData.append('action', action);
            formData.append('_apd_nonce', nonce);
            formData.append('listing_id', listingId);

            // Perform AJAX request.
            fetch(this.config.ajaxUrl || '/wp-admin/admin-ajax.php', {
                method: 'POST',
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update all buttons for this listing on the page.
                    this.updateAllButtons(listingId, data.data);

                    // Show success message (optional toast/notification).
                    this.showNotification(data.data.message, 'success');
                } else {
                    // Handle errors.
                    if (data.data?.code === 'login_required') {
                        this.handleLoginRequired(data.data.login_url);
                    } else {
                        this.showNotification(data.data?.message || this.config.i18n?.favoriteError, 'error');
                    }

                    // Rollback optimistic update.
                    this.updateButtonState(button, originalState.isActive);
                }
            })
            .catch(error => {
                console.error('APD Favorite Error:', error);

                // Rollback optimistic update.
                this.updateButtonState(button, originalState.isActive);

                this.showNotification(this.config.i18n?.favoriteError || 'An error occurred.', 'error');
            })
            .finally(() => {
                // Remove pending and loading states.
                this.state.pendingRequests.delete(listingId);
                button.classList.remove('apd-favorite-button--loading');
                button.disabled = false;
            });
        },

        /**
         * Update button state.
         *
         * @param {HTMLElement} button - The button element.
         * @param {boolean} isActive - Whether the favorite is active.
         */
        updateButtonState: function(button, isActive) {
            const i18n = this.config.i18n || {};

            if (isActive) {
                button.classList.add('apd-favorite-button--active');
                button.setAttribute('aria-pressed', 'true');
                button.setAttribute('aria-label', i18n.removeFromFavorites || 'Remove from favorites');

                // Update heart icon.
                const iconContainer = button.querySelector('.apd-favorite-icon');
                if (iconContainer) {
                    iconContainer.innerHTML = this.getHeartSvg(true);
                }
            } else {
                button.classList.remove('apd-favorite-button--active');
                button.setAttribute('aria-pressed', 'false');
                button.setAttribute('aria-label', i18n.addToFavorites || 'Add to favorites');

                // Update heart icon.
                const iconContainer = button.querySelector('.apd-favorite-icon');
                if (iconContainer) {
                    iconContainer.innerHTML = this.getHeartSvg(false);
                }
            }
        },

        /**
         * Update all buttons for a listing on the page.
         *
         * @param {string} listingId - The listing ID.
         * @param {Object} data - Response data.
         */
        updateAllButtons: function(listingId, data) {
            const buttons = document.querySelectorAll('.apd-favorite-button[data-listing-id="' + listingId + '"]');

            buttons.forEach(button => {
                this.updateButtonState(button, data.is_favorite);

                // Update count if displayed.
                const countEl = button.querySelector('.apd-favorite-count');
                if (countEl) {
                    countEl.dataset.count = data.count;
                    countEl.textContent = data.count > 0 ? this.formatNumber(data.count) : '';
                }
            });

            // Update favorite summary text if present.
            const summaries = document.querySelectorAll('.apd-favorite-summary');
            summaries.forEach(summary => {
                const nearButton = summary.closest('.apd-single-listing__favorites')?.querySelector('.apd-favorite-button');
                if (nearButton && nearButton.dataset.listingId === listingId) {
                    if (data.count > 0) {
                        const text = data.count === 1
                            ? (this.config.i18n?.personFavorited || '1 person favorited this')
                            : (this.config.i18n?.peopleFavorited || '%d people favorited this').replace('%d', this.formatNumber(data.count));
                        summary.textContent = text;
                        summary.style.display = '';
                    } else {
                        summary.style.display = 'none';
                    }
                }
            });
        },

        /**
         * Get heart SVG HTML.
         *
         * @param {boolean} filled - Whether the heart should be filled.
         * @returns {string} SVG HTML.
         */
        getHeartSvg: function(filled) {
            if (filled) {
                return '<svg class="apd-heart-icon apd-heart-icon--filled" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="currentColor"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
            }
            return '<svg class="apd-heart-icon apd-heart-icon--outline" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" width="24" height="24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>';
        },

        /**
         * Handle login required response.
         *
         * @param {string} loginUrl - The login URL with redirect.
         */
        handleLoginRequired: function(loginUrl) {
            // Check if a custom handler exists.
            if (typeof window.apdHandleFavoriteLoginRequired === 'function') {
                window.apdHandleFavoriteLoginRequired(loginUrl);
                return;
            }

            // Default behavior: show confirmation and redirect.
            const message = this.config.i18n?.loginRequired || 'Please log in to save favorites.';

            if (window.confirm(message + '\n\n' + 'Redirect to login page?')) {
                window.location.href = loginUrl;
            }
        },

        /**
         * Show a notification message.
         *
         * @param {string} message - The message to show.
         * @param {string} type - Message type (success, error).
         */
        showNotification: function(message, type) {
            // Trigger custom event for external notification systems.
            document.dispatchEvent(new CustomEvent('apd:notification', {
                detail: { message, type },
            }));

            // Show visual toast.
            this.showToast(message, type);

            // Announce to screen readers.
            this.announceToScreenReader(message);
        },

        /**
         * Show a toast notification.
         *
         * @param {string} message - The message to show.
         * @param {string} type - Toast type (success, error).
         */
        showToast: function(message, type) {
            if (!message) {
                return;
            }

            // Get or create toast container.
            var container = document.getElementById('apd-toast-container');
            if (!container) {
                container = document.createElement('div');
                container.id = 'apd-toast-container';
                container.className = 'apd-toast-container';
                container.setAttribute('aria-live', 'polite');
                document.body.appendChild(container);
            }

            // Create toast element.
            var toast = document.createElement('div');
            toast.className = 'apd-toast apd-toast--' + (type || 'success');
            toast.setAttribute('role', 'status');

            var icon = type === 'error' ? '\u2717' : '\u2713';
            var iconSpan = document.createElement('span');
            iconSpan.className = 'apd-toast__icon';
            iconSpan.setAttribute('aria-hidden', 'true');
            iconSpan.textContent = icon;
            var msgSpan = document.createElement('span');
            msgSpan.className = 'apd-toast__message';
            msgSpan.textContent = message;
            toast.appendChild(iconSpan);
            toast.appendChild(msgSpan);

            container.appendChild(toast);

            // Trigger enter animation on next frame.
            requestAnimationFrame(function() {
                toast.classList.add('apd-toast--visible');
            });

            // Auto-remove after 3 seconds.
            setTimeout(function() {
                toast.classList.remove('apd-toast--visible');
                toast.classList.add('apd-toast--hiding');
                setTimeout(function() {
                    if (toast.parentNode) {
                        toast.parentNode.removeChild(toast);
                    }
                }, 300);
            }, 3000);
        },

        /**
         * Escape HTML special characters.
         *
         * @param {string} str - String to escape.
         * @returns {string} Escaped string.
         */
        escapeHtml: function(str) {
            var div = document.createElement('div');
            div.appendChild(document.createTextNode(str));
            return div.innerHTML;
        },

        /**
         * Announce message to screen readers.
         *
         * @param {string} message - The message to announce.
         */
        announceToScreenReader: function(message) {
            let liveRegion = document.getElementById('apd-favorite-live-region');

            if (!liveRegion) {
                liveRegion = document.createElement('div');
                liveRegion.id = 'apd-favorite-live-region';
                liveRegion.setAttribute('role', 'status');
                liveRegion.setAttribute('aria-live', 'polite');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.className = 'screen-reader-text';
                document.body.appendChild(liveRegion);
            }

            liveRegion.textContent = message;
        },

        /**
         * Format a number with locale-aware separators.
         *
         * @param {number} num - The number to format.
         * @returns {string} Formatted number.
         */
        formatNumber: function(num) {
            return new Intl.NumberFormat().format(num);
        },
    };

    /**
     * APD Review Form Module
     *
     * Handles interactive star rating and AJAX form submission for reviews.
     */
    const APDReviewForm = {

        /**
         * Configuration from WordPress.
         */
        config: window.apdFrontend || {},

        /**
         * Cache for DOM elements.
         */
        elements: {
            forms: [],
            starInputs: [],
        },

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * Current state.
         */
        state: {
            isSubmitting: false,
        },

        /**
         * Initialize the review form module.
         * Guards: bails early if already initialized or no review forms present.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.cacheElements();

            if (this.elements.forms.length === 0) {
                return;
            }

            this.bindEvents();
        },

        /**
         * Cache DOM elements.
         */
        cacheElements: function() {
            this.elements.forms = document.querySelectorAll('.apd-review-form');
            this.elements.starInputs = document.querySelectorAll('.apd-star-input');
        },

        /**
         * Bind event listeners.
         */
        bindEvents: function() {
            // Star input interactions
            this.elements.starInputs.forEach(input => {
                this.bindStarInputEvents(input);
            });

            // Form submissions
            this.elements.forms.forEach(form => {
                if (form.classList.contains('apd-review-form--ajax')) {
                    form.addEventListener('submit', this.handleFormSubmit.bind(this));
                }
            });
        },

        /**
         * Bind events for a star input element.
         *
         * @param {HTMLElement} container - The star input container.
         */
        bindStarInputEvents: function(container) {
            const stars = container.querySelectorAll('.apd-star-input__star');
            const radios = container.querySelectorAll('.apd-star-input__radio');
            const label = container.querySelector('.apd-star-input__label');

            // Mouse events for visual stars
            stars.forEach((star, index) => {
                star.addEventListener('mouseenter', () => {
                    this.highlightStars(stars, index + 1);
                });

                star.addEventListener('click', () => {
                    this.selectRating(container, index + 1);
                });
            });

            // Mouse leave - restore selected state
            const starsContainer = container.querySelector('.apd-star-input__stars');
            if (starsContainer) {
                starsContainer.addEventListener('mouseleave', () => {
                    const selected = parseInt(container.dataset.selected || '0', 10);
                    this.highlightStars(stars, selected);
                });
            }

            // Radio button changes (for keyboard navigation)
            radios.forEach(radio => {
                radio.addEventListener('change', (e) => {
                    const value = parseInt(e.target.value, 10);
                    this.selectRating(container, value);
                });

                radio.addEventListener('focus', (e) => {
                    const value = parseInt(e.target.value, 10);
                    this.highlightStars(stars, value);
                    this.setStarFocus(stars, value - 1);
                });

                radio.addEventListener('blur', () => {
                    this.clearStarFocus(stars);
                });
            });

            // Keyboard navigation for the stars container
            if (starsContainer) {
                starsContainer.addEventListener('keydown', (e) => {
                    this.handleStarKeydown(e, container, stars, radios);
                });
            }
        },

        /**
         * Handle keyboard navigation for star rating.
         *
         * @param {KeyboardEvent} e - The keyboard event.
         * @param {HTMLElement} container - The star input container.
         * @param {NodeList} stars - The star elements.
         * @param {NodeList} radios - The radio elements.
         */
        handleStarKeydown: function(e, container, stars, radios) {
            const current = parseInt(container.dataset.selected || '0', 10);
            let newValue = current;

            switch (e.key) {
                case 'ArrowRight':
                case 'ArrowUp':
                    e.preventDefault();
                    newValue = Math.min(current + 1, stars.length);
                    break;
                case 'ArrowLeft':
                case 'ArrowDown':
                    e.preventDefault();
                    newValue = Math.max(current - 1, 1);
                    break;
                case 'Home':
                    e.preventDefault();
                    newValue = 1;
                    break;
                case 'End':
                    e.preventDefault();
                    newValue = stars.length;
                    break;
                default:
                    return;
            }

            if (newValue !== current) {
                this.selectRating(container, newValue);
                // Also check the corresponding radio
                if (radios[newValue - 1]) {
                    radios[newValue - 1].checked = true;
                }
            }
        },

        /**
         * Highlight stars up to a certain value.
         *
         * @param {NodeList} stars - The star elements.
         * @param {number} value - Number of stars to highlight.
         */
        highlightStars: function(stars, value) {
            stars.forEach((star, index) => {
                if (index < value) {
                    star.classList.add('apd-star-input__star--active');
                } else {
                    star.classList.remove('apd-star-input__star--active');
                }
            });
        },

        /**
         * Set focus indicator on a specific star.
         *
         * @param {NodeList} stars - The star elements.
         * @param {number} index - The star index to focus.
         */
        setStarFocus: function(stars, index) {
            stars.forEach((star, i) => {
                star.classList.toggle('apd-star-input__star--focused', i === index);
            });
        },

        /**
         * Remove focus indicator from all stars.
         *
         * @param {NodeList} stars - The star elements.
         */
        clearStarFocus: function(stars) {
            stars.forEach(star => {
                star.classList.remove('apd-star-input__star--focused');
            });
        },

        /**
         * Select a rating value.
         *
         * @param {HTMLElement} container - The star input container.
         * @param {number} value - The selected rating.
         */
        selectRating: function(container, value) {
            container.dataset.selected = value;

            const stars = container.querySelectorAll('.apd-star-input__star');
            const radios = container.querySelectorAll('.apd-star-input__radio');
            const label = container.querySelector('.apd-star-input__label');

            // Update visual stars
            this.highlightStars(stars, value);

            // Check the radio button
            if (radios[value - 1]) {
                radios[value - 1].checked = true;
            }

            // Update the label
            if (label) {
                const i18n = this.config.i18n || {};
                const starText = value === 1
                    ? (i18n.starLabel || '%d star').replace('%d', value)
                    : (i18n.starsLabel || '%d stars').replace('%d', value);

                var selectedSpan = document.createElement('span');
                selectedSpan.className = 'apd-star-input__selected-text';
                selectedSpan.textContent = value + ' ' + starText.replace(/^\d+\s*/, '') + ' selected';
                label.textContent = '';
                label.appendChild(selectedSpan);
            }
        },

        /**
         * Handle form submission.
         *
         * @param {Event} e - Submit event.
         */
        handleFormSubmit: function(e) {
            e.preventDefault();

            if (this.state.isSubmitting) {
                return;
            }

            const form = e.target;

            // Validate before submitting
            if (!this.validateForm(form)) {
                return;
            }

            this.submitReview(form);
        },

        /**
         * Validate the review form.
         *
         * @param {HTMLFormElement} form - The form element.
         * @returns {boolean} True if valid.
         */
        validateForm: function(form) {
            const i18n = this.config.i18n || {};
            let isValid = true;

            // Check rating
            const ratingInput = form.querySelector('input[name="rating"]:checked');
            if (!ratingInput) {
                this.showFormMessage(form, i18n.ratingRequired || 'Please select a rating.', 'error');
                isValid = false;
            }

            // Check content
            const contentField = form.querySelector('[name="review_content"]');
            const minLength = parseInt(form.dataset.minContentLength || '10', 10);

            if (contentField && contentField.value.trim().length < minLength) {
                const message = (i18n.reviewTooShort || 'Your review is too short. Please write at least %d characters.')
                    .replace('%d', minLength);
                this.showFormMessage(form, message, 'error');
                isValid = false;
            }

            return isValid;
        },

        /**
         * Submit the review via AJAX.
         *
         * @param {HTMLFormElement} form - The form element.
         */
        submitReview: function(form) {
            const i18n = this.config.i18n || {};

            this.state.isSubmitting = true;
            this.setFormLoading(form, true);
            this.showFormMessage(form, i18n.reviewSubmitting || 'Submitting review...', 'info');

            const formData = new FormData(form);
            formData.append('action', 'apd_submit_review');
            formData.append('nonce', this.config.reviewNonce || this.config.nonce);

            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    this.handleSubmitSuccess(form, data.data);
                } else {
                    this.handleSubmitError(form, data.data);
                }
            })
            .catch(error => {
                console.error('Review submission error:', error);
                this.handleSubmitError(form, {
                    message: i18n.reviewError || 'Failed to submit review. Please try again.',
                });
            })
            .finally(() => {
                this.state.isSubmitting = false;
                this.setFormLoading(form, false);
            });
        },

        /**
         * Handle successful submission.
         *
         * @param {HTMLFormElement} form - The form element.
         * @param {Object} data - Response data.
         */
        handleSubmitSuccess: function(form, data) {
            this.showFormMessage(form, data.message, 'success');

            // If pending approval, show pending message
            if (data.is_pending && !data.is_update) {
                const i18n = this.config.i18n || {};
                this.showFormMessage(form, i18n.reviewPending || data.message, 'success');
            }

            // If this was a new review (not edit), hide the form after success
            if (!data.is_update) {
                setTimeout(() => {
                    const wrapper = form.closest('.apd-review-form__wrapper');
                    if (wrapper) {
                        wrapper.style.display = 'none';
                    }
                }, 3000);
            }

            /**
             * Trigger custom event for extensibility.
             */
            form.dispatchEvent(new CustomEvent('apd:review-submitted', {
                detail: data,
                bubbles: true,
            }));
        },

        /**
         * Handle submission error.
         *
         * @param {HTMLFormElement} form - The form element.
         * @param {Object} data - Error data.
         */
        handleSubmitError: function(form, data) {
            const message = data.message || (this.config.i18n?.reviewError || 'Failed to submit review.');
            this.showFormMessage(form, message, 'error');

            // If multiple errors, show them all
            if (data.errors && Array.isArray(data.errors)) {
                const messageEl = form.querySelector('.apd-review-form__message');
                if (messageEl) {
                    messageEl.textContent = '';
                    messageEl.className = 'apd-review-form__message apd-review-form__message--visible apd-review-form__message--error';
                    data.errors.forEach(function(error) {
                        var p = document.createElement('p');
                        p.textContent = error;
                        messageEl.appendChild(p);
                    });
                }
            }
        },

        /**
         * Show a message in the form.
         *
         * @param {HTMLFormElement} form - The form element.
         * @param {string} message - The message to show.
         * @param {string} type - Message type (success, error, info).
         */
        showFormMessage: function(form, message, type) {
            const messageEl = form.querySelector('.apd-review-form__message');

            if (!messageEl) {
                return;
            }

            messageEl.textContent = message;
            messageEl.className = 'apd-review-form__message apd-review-form__message--visible apd-review-form__message--' + type;
        },

        /**
         * Set form loading state.
         *
         * @param {HTMLFormElement} form - The form element.
         * @param {boolean} isLoading - Whether loading.
         */
        setFormLoading: function(form, isLoading) {
            const submitBtn = form.querySelector('.apd-review-form__submit');

            if (submitBtn) {
                submitBtn.disabled = isLoading;

                if (isLoading) {
                    submitBtn.dataset.originalText = submitBtn.textContent;
                    submitBtn.textContent = this.config.i18n?.reviewSubmitting || 'Submitting...';
                } else if (submitBtn.dataset.originalText) {
                    submitBtn.textContent = submitBtn.dataset.originalText;
                }
            }

            // Add loading class to form
            form.classList.toggle('apd-review-form--loading', isLoading);
        },
    };

    /**
     * APD Profile Module
     *
     * Handles profile form double-submit protection and unsaved changes warning.
     */
    const APDProfile = {

        /**
         * Cache for DOM elements.
         */
        elements: {
            form: null,
            submitBtn: null,
        },

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * State tracking.
         */
        state: {
            isSubmitting: false,
            isDirty: false,
            initialValues: null,
        },

        /**
         * Initialize the profile module.
         * Guards: bails early if already initialized or no profile form present.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.elements.form = document.querySelector('.apd-profile-form');
            if (!this.elements.form) {
                return;
            }

            this.elements.submitBtn = this.elements.form.querySelector('.apd-profile-form__submit');
            if (!this.elements.submitBtn) {
                return;
            }

            // Store initial form values for dirty checking.
            this.initialValues = new FormData(this.elements.form);

            this.elements.form.addEventListener('submit', this.handleSubmit.bind(this));
            this.elements.form.addEventListener('input', this.handleChange.bind(this));
            this.elements.form.addEventListener('change', this.handleChange.bind(this));
            window.addEventListener('beforeunload', this.handleBeforeUnload.bind(this));
        },

        /**
         * Handle form field changes to track dirty state.
         */
        handleChange: function() {
            this.state.isDirty = true;
        },

        /**
         * Handle beforeunload to warn about unsaved changes.
         *
         * @param {BeforeUnloadEvent} e - The beforeunload event.
         */
        handleBeforeUnload: function(e) {
            if (this.state.isDirty && !this.state.isSubmitting) {
                e.preventDefault();
            }
        },

        /**
         * Handle form submission - prevent double submit.
         *
         * @param {Event} e - Submit event.
         */
        handleSubmit: function(e) {
            if (this.state.isSubmitting) {
                e.preventDefault();
                return;
            }

            this.state.isSubmitting = true;
            this.state.isDirty = false;

            var btn = this.elements.submitBtn;
            var submittingText = btn.dataset.submittingText;

            btn.disabled = true;
            btn.setAttribute('aria-disabled', 'true');

            if (submittingText) {
                btn.dataset.originalText = btn.textContent;
                btn.textContent = submittingText;
            }
        },
    };

    /**
     * APD Character Counter
     *
     * Provides live character counts for textareas with minimum length requirements.
     * Automatically finds `.apd-char-counter` elements and binds to the preceding textarea.
     */
    const APDCharCounter = {

        /**
         * Whether the module has been initialized.
         */
        initialized: false,

        /**
         * Initialize all character counters on the page.
         * Guards: bails early if already initialized or no counter elements present.
         */
        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            var counters = document.querySelectorAll('.apd-char-counter');

            if (counters.length === 0) {
                return;
            }

            counters.forEach(this.bindCounter.bind(this));
        },

        /**
         * Bind a single counter element to its textarea.
         *
         * @param {HTMLElement} counter - The counter paragraph element.
         */
        bindCounter: function(counter) {
            var min = parseInt(counter.dataset.min, 10);
            if (!min || min <= 0) {
                return;
            }

            // Find the associated textarea: look for a textarea in the same parent field wrapper.
            var field = counter.closest('.apd-review-form__field, .apd-field');
            if (!field) {
                return;
            }

            var textarea = field.querySelector('textarea');
            if (!textarea) {
                return;
            }

            var currentEl = counter.querySelector('.apd-char-counter__current');
            if (!currentEl) {
                return;
            }

            // Update on input.
            var update = function() {
                var len = textarea.value.length;
                currentEl.textContent = len;

                if (len >= min) {
                    counter.classList.add('apd-char-counter--met');
                    counter.classList.remove('apd-char-counter--unmet');
                } else {
                    counter.classList.remove('apd-char-counter--met');
                    counter.classList.add('apd-char-counter--unmet');
                }
            };

            textarea.addEventListener('input', update);

            // Run once on init to handle pre-filled values.
            update();
        },
    };

    /**
     * APD Contact Form Validation Module
     *
     * Provides client-side validation for the contact form,
     * matching the submission form's validation pattern.
     */
    const APDContactForm = {

        config: window.apdFrontend || {},

        elements: {
            form: null,
        },

        initialized: false,

        init: function() {
            if (this.initialized) return;
            this.initialized = true;

            this.elements.form = document.querySelector('.apd-contact-form');

            if (!this.elements.form) {
                return;
            }

            this.bindEvents();
        },

        bindEvents: function() {
            var form = this.elements.form;

            form.addEventListener('submit', this.handleSubmit.bind(this));

            form.querySelectorAll('input, textarea').forEach(function(field) {
                field.addEventListener('blur', function(e) {
                    this.validateField(e.target);
                }.bind(this));

                field.addEventListener('focus', function(e) {
                    this.clearFieldError(e.target);
                }.bind(this));
            }.bind(this));
        },

        handleSubmit: function(e) {
            e.preventDefault();

            var isValid = this.validateForm();

            if (!isValid) {
                var firstError = this.elements.form.querySelector('.apd-field--has-error');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    var input = firstError.querySelector('input, textarea');
                    if (input) {
                        input.focus();
                    }
                }

                this.announceErrors();
                return;
            }

            this.submitForm();
        },

        submitForm: function() {
            var form = this.elements.form;
            var submitBtn = form.querySelector('.apd-contact-form__submit');

            // Prevent double-submit.
            if (submitBtn) {
                submitBtn.disabled = true;
                submitBtn.dataset.originalText = submitBtn.textContent;
                submitBtn.textContent = this.config.i18n?.submitting || 'Sending...';
            }

            var formData = new FormData(form);

            fetch(this.config.ajaxUrl, {
                method: 'POST',
                body: formData,
                credentials: 'same-origin',
            })
            .then(function(response) { return response.json(); })
            .then(function(data) {
                if (data.success) {
                    this.showMessage(data.data.message, 'success');
                    form.reset();
                } else {
                    this.showMessage(data.data.message || (this.config.i18n?.contactError || 'Failed to send message. Please try again.'), 'error');
                }
            }.bind(this))
            .catch(function() {
                this.showMessage(this.config.i18n?.contactError || 'Failed to send message. Please try again.', 'error');
            }.bind(this))
            .finally(function() {
                if (submitBtn) {
                    submitBtn.disabled = false;
                    submitBtn.textContent = submitBtn.dataset.originalText || submitBtn.textContent;
                }
            });
        },

        showMessage: function(message, type) {
            var form = this.elements.form;
            var messageEl = form.querySelector('.apd-contact-form__message');

            if (!messageEl) {
                messageEl = document.createElement('div');
                messageEl.className = 'apd-contact-form__message';
                messageEl.setAttribute('role', 'status');
                messageEl.setAttribute('aria-live', 'polite');
                form.appendChild(messageEl);
            }

            messageEl.textContent = message;
            messageEl.className = 'apd-contact-form__message apd-contact-form__message--visible apd-contact-form__message--' + type;
        },

        validateForm: function() {
            var form = this.elements.form;
            var isValid = true;

            form.querySelectorAll('[required]').forEach(function(field) {
                if (!this.validateField(field)) {
                    isValid = false;
                }
            }.bind(this));

            return isValid;
        },

        validateField: function(field) {
            var wrapper = field.closest('.apd-field');
            if (!wrapper) {
                return true;
            }

            // Skip honeypot
            if (wrapper.classList.contains('apd-field--hp')) {
                return true;
            }

            var isValid = true;
            var errorMessage = '';

            // Required check
            if (field.hasAttribute('required') && !field.value.trim()) {
                isValid = false;
                errorMessage = this.config.i18n?.requiredField || 'This field is required.';
            }

            // Email format
            if (isValid && field.type === 'email' && field.value.trim()) {
                if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(field.value.trim())) {
                    isValid = false;
                    errorMessage = this.config.i18n?.invalidEmail || 'Please enter a valid email address.';
                }
            }

            // Min length
            if (isValid && field.value.trim()) {
                var minLength = field.getAttribute('minlength');
                if (minLength && field.value.length < parseInt(minLength, 10)) {
                    isValid = false;
                    errorMessage = (this.config.i18n?.minLength || 'Minimum %d characters required.').replace('%d', minLength);
                }
            }

            if (isValid) {
                this.clearFieldError(field);
            } else {
                this.setFieldError(wrapper, errorMessage);
            }

            return isValid;
        },

        setFieldError: function(wrapper, message) {
            wrapper.classList.add('apd-field--has-error');

            var errorContainer = wrapper.querySelector('.apd-field__errors');
            if (!errorContainer) {
                errorContainer = document.createElement('div');
                errorContainer.className = 'apd-field__errors';
                errorContainer.setAttribute('role', 'alert');
                errorContainer.setAttribute('aria-live', 'polite');
                wrapper.appendChild(errorContainer);
            }

            // Remove existing client errors
            var existing = errorContainer.querySelectorAll('.apd-field__error--client');
            existing.forEach(function(el) { el.remove(); });

            var errorEl = document.createElement('p');
            errorEl.className = 'apd-field__error apd-field__error--client';
            errorEl.textContent = message;
            errorContainer.appendChild(errorEl);
        },

        clearFieldError: function(field) {
            var wrapper = field.closest('.apd-field');
            if (!wrapper) {
                return;
            }

            wrapper.classList.remove('apd-field--has-error');

            var clientErrors = wrapper.querySelectorAll('.apd-field__error--client');
            clientErrors.forEach(function(el) { el.remove(); });

            var errorContainer = wrapper.querySelector('.apd-field__errors');
            if (errorContainer && errorContainer.children.length === 0) {
                errorContainer.remove();
            }
        },

        announceErrors: function() {
            var errors = this.elements.form.querySelectorAll('.apd-field--has-error');
            var count = errors.length;

            if (count === 0) {
                return;
            }

            var liveRegion = document.getElementById('apd-contact-live-region');
            if (!liveRegion) {
                liveRegion = document.createElement('div');
                liveRegion.id = 'apd-contact-live-region';
                liveRegion.setAttribute('role', 'status');
                liveRegion.setAttribute('aria-live', 'assertive');
                liveRegion.setAttribute('aria-atomic', 'true');
                liveRegion.className = 'screen-reader-text';
                document.body.appendChild(liveRegion);
            }

            var message = count === 1
                ? (this.config.i18n?.oneError || 'Please fix 1 error before submitting.')
                : (this.config.i18n?.multipleErrors || 'Please fix %d errors before submitting.').replace('%d', count);

            liveRegion.textContent = '';
            setTimeout(function() {
                liveRegion.textContent = message;
            }, 100);
        },
    };

    /**
     * Initialize on DOM ready.
     */
    document.addEventListener('DOMContentLoaded', function() {
        APDFilter.init();
        APDSubmission.init();
        APDContactForm.init();
        APDMyListings.init();
        APDFavorites.init();
        APDReviewForm.init();
        APDProfile.init();
        APDCharCounter.init();
    });

    // Expose to global scope for external access
    window.APDFilter = APDFilter;
    window.APDSubmission = APDSubmission;
    window.APDContactForm = APDContactForm;
    window.APDMyListings = APDMyListings;
    window.APDFavorites = APDFavorites;
    window.APDReviewForm = APDReviewForm;
    window.APDProfile = APDProfile;

})();
