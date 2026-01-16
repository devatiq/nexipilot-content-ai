/**
 * PostPilot Admin JavaScript
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        // Toggle API key fields based on selected provider
        const providerSelect = $('#postpilot_ai_provider');
        const openaiField = $('#postpilot_openai_api_key').closest('tr');
        const claudeField = $('#postpilot_claude_api_key').closest('tr');

        function toggleApiKeyFields() {
            const selectedProvider = providerSelect.val();
            
            if (selectedProvider === 'openai') {
                openaiField.show();
                claudeField.hide();
            } else if (selectedProvider === 'claude') {
                openaiField.hide();
                claudeField.show();
            }
        }

        // Initial toggle
        if (providerSelect.length) {
            toggleApiKeyFields();
            
            // Toggle on change
            providerSelect.on('change', toggleApiKeyFields);
        }

        // Toggle position fields based on feature enablement
        const faqCheckbox = $('input[name="postpilot_enable_faq"]');
        const faqPositionField = $('#postpilot_faq_position').closest('tr');
        
        const summaryCheckbox = $('input[name="postpilot_enable_summary"]');
        const summaryPositionField = $('#postpilot_summary_position').closest('tr');

        function togglePositionFields() {
            if (faqCheckbox.is(':checked')) {
                faqPositionField.show();
            } else {
                faqPositionField.hide();
            }

            if (summaryCheckbox.is(':checked')) {
                summaryPositionField.show();
            } else {
                summaryPositionField.hide();
            }
        }

        // Initial toggle
        if (faqCheckbox.length && summaryCheckbox.length) {
            togglePositionFields();
            
            // Toggle on change
            faqCheckbox.on('change', togglePositionFields);
            summaryCheckbox.on('change', togglePositionFields);
        }
    });

})(jQuery);
