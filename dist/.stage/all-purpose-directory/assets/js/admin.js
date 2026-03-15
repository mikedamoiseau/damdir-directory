/**
 * All Purpose Directory - Admin Scripts
 *
 * Handles listing type switching in the post editor. When a listing type
 * radio button is changed, fields are dynamically shown/hidden based on
 * the field-to-type mapping output by ListingTypeMetaBox.
 *
 * @package APD
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        initListingTypeSwitch();
    });

    /**
     * Initialize listing type radio switching.
     *
     * Reads the field-to-type JSON mapping from the meta box hidden element
     * and wires up change handlers on listing type radio buttons.
     */
    function initListingTypeSwitch() {
        var $typeRadios = $('input[name="apd_listing_type"]');
        if (!$typeRadios.length) {
            return;
        }

        var $mappingEl = $('#apd-field-type-mapping');
        if (!$mappingEl.length) {
            return;
        }

        var fieldTypes;
        try {
            fieldTypes = JSON.parse($mappingEl.attr('data-field-types') || '{}');
        } catch (e) {
            return;
        }

        $typeRadios.on('change', function() {
            toggleFieldsByType($(this).val(), fieldTypes);
        });

        // Apply initial state from currently checked radio.
        var currentType = $typeRadios.filter(':checked').val();
        if (currentType) {
            toggleFieldsByType(currentType, fieldTypes);
        }
    }

    /**
     * Show/hide fields based on the selected listing type.
     *
     * Handles two mapping formats:
     * - Type-specific: "field_name": "type-slug" or "field_name": ["type1", "type2"]
     * - Hidden by module: "field_name": {"hidden_by": ["module-slug"]}
     *
     * @param {string} selectedType The selected listing type slug.
     * @param {Object} fieldTypes   Field-to-type mapping object.
     */
    function toggleFieldsByType(selectedType, fieldTypes) {
        $.each(fieldTypes, function(fieldName, config) {
            var $field = $('[data-field-name="' + fieldName + '"]');
            if (!$field.length) {
                return;
            }

            var visible;

            if (config && typeof config === 'object' && !Array.isArray(config) && config.hidden_by) {
                // Global field hidden by specific modules.
                visible = config.hidden_by.indexOf(selectedType) === -1;
            } else if (config === null) {
                // Global field, always visible.
                visible = true;
            } else if (Array.isArray(config)) {
                // Field visible for multiple types.
                visible = config.indexOf(selectedType) !== -1;
            } else {
                // Field visible for a single type.
                visible = (config === selectedType);
            }

            $field.toggle(visible);
        });
    }
})(jQuery);
