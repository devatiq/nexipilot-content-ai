/**
 * PostPilot Settings Page JavaScript
 * 
 * Handles all functionality for the settings page including:
 * - Tab navigation with localStorage persistence
 * - API provider field toggling
 * - API key visibility toggles
 * - Feature option toggles
 * - Settings saved notifications
 * 
 * @package PostPilot
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */

(function ($) {
    'use strict';

    $(document).ready(function () {
        // ========================================
        // SETTINGS SAVED NOTIFICATION
        // ========================================

        // Check if settings were just saved
        if ($('.postpilot-settings-wrap').data('settings-saved')) {
            Swal.fire({
                position: 'top-end',
                icon: 'success',
                title: 'Settings Saved Successfully!',
                showConfirmButton: false,
                timer: 2000,
                toast: true,
                background: '#fff',
                iconColor: '#10b981',
                customClass: {
                    popup: 'postpilot-toast'
                }
            });
        }

        // Check for validation errors
        const validationError = $('.postpilot-settings-wrap').data('validation-error');
        if (validationError) {
            Swal.fire({
                icon: 'error',
                title: 'Integration Error',
                text: validationError,
                confirmButtonText: 'OK',
                confirmButtonColor: '#ef4444'
            });
        }

        // ========================================
        // TAB NAVIGATION WITH PERSISTENCE
        // ========================================

        // Tab switching function
        function switchTab(tabName) {
            // Remove active class from all tabs and tab contents
            $('.postpilot-tab').removeClass('active');
            $('.postpilot-tab-content').removeClass('active');

            // Add active class to clicked tab and corresponding content
            $('.postpilot-tab[data-tab="' + tabName + '"]').addClass('active');
            $('#' + tabName + '-tab').addClass('active');

            // Save active tab to localStorage
            localStorage.setItem('postpilot_active_tab', tabName);
        }

        // Restore last active tab on page load
        const lastActiveTab = localStorage.getItem('postpilot_active_tab') || 'general';
        switchTab(lastActiveTab);

        // Handle tab clicks
        $('.postpilot-tab').on('click', function () {
            const tabName = $(this).data('tab');
            switchTab(tabName);
        });

        // ========================================
        // API PROVIDER FIELD TOGGLING
        // ========================================

        const providerSelect = $('#postpilot_ai_provider');
        const openaiField = $('#openai-api-key-field');
        const claudeField = $('#claude-api-key-field');
        const geminiField = $('#gemini-api-key-field');
        const openaiModelField = $('#openai-model-field');
        const claudeModelField = $('#claude-model-field');
        const geminiModelField = $('#gemini-model-field');

        function toggleApiKeyFields() {
            const selectedProvider = providerSelect.val();

            if (selectedProvider === 'openai') {
                openaiField.show();
                claudeField.hide();
                geminiField.hide();
                openaiModelField.show();
                claudeModelField.hide();
                geminiModelField.hide();
            } else if (selectedProvider === 'claude') {
                openaiField.hide();
                claudeField.show();
                geminiField.hide();
                openaiModelField.hide();
                claudeModelField.show();
                geminiModelField.hide();
            } else if (selectedProvider === 'gemini') {
                openaiField.hide();
                claudeField.hide();
                geminiField.show();
                openaiModelField.hide();
                claudeModelField.hide();
                geminiModelField.show();
            }
        }

        // Initial toggle
        if (providerSelect.length) {
            toggleApiKeyFields();
            providerSelect.on('change', toggleApiKeyFields);
        }

        // ========================================
        // API KEY VISIBILITY TOGGLE
        // ========================================

        $('#toggle-openai-key, #toggle-claude-key, #toggle-gemini-key').on('click', function () {
            const input = $(this).siblings('input');
            const type = input.attr('type');
            input.attr('type', type === 'password' ? 'text' : 'password');
        });

        // ========================================
        // FEATURE OPTIONS TOGGLE
        // ========================================

        function toggleFeatureOptions() {
            // FAQ options
            const faqEnabled = $('input[name="postpilot_enable_faq"]').is(':checked');
            if (faqEnabled) {
                $('#faq-options').addClass('active');
            } else {
                $('#faq-options').removeClass('active');
            }

            // Summary options
            const summaryEnabled = $('input[name="postpilot_enable_summary"]').is(':checked');
            if (summaryEnabled) {
                $('#summary-options').addClass('active');
            } else {
                $('#summary-options').removeClass('active');
            }

            // Internal Links options
            const linksEnabled = $('input[name="postpilot_enable_internal_links"]').is(':checked');
            if (linksEnabled) {
                $('#links-options').addClass('active');
            } else {
                $('#links-options').removeClass('active');
            }

            // External AI Sharing options
            const externalAiEnabled = $('input[name="postpilot_enable_external_ai_sharing"]').is(':checked');
            if (externalAiEnabled) {
                $('#external-ai-sharing-options').addClass('active');
            } else {
                $('#external-ai-sharing-options').removeClass('active');
            }
        }

        // Initialize on page load
        toggleFeatureOptions();

        // Update on toggle change
        $('input[name="postpilot_enable_faq"], input[name="postpilot_enable_summary"], input[name="postpilot_enable_internal_links"], input[name="postpilot_enable_external_ai_sharing"]').on('change', function () {
            toggleFeatureOptions();
        });

        // ========================================
        // FEATURE PROVIDER MODEL DISPLAY UPDATE
        // ========================================

        // Update model display when feature provider changes
        function updateFeatureModelDisplay(featureName, provider) {
            const modelDisplayId = '#' + featureName + '-model-display';
            const modelBadge = $(modelDisplayId + ' .postpilot-badge');

            // Get the model for the selected provider
            let modelValue = '';
            if (provider === 'openai') {
                modelValue = $('#postpilot_openai_model_providers').val() || $('#postpilot_openai_model').val() || 'Not configured';
            } else if (provider === 'claude') {
                modelValue = $('#postpilot_claude_model_providers').val() || $('#postpilot_claude_model').val() || 'Not configured';
            } else if (provider === 'gemini') {
                modelValue = $('#postpilot_gemini_model_providers').val() || $('#postpilot_gemini_model').val() || 'Not configured';
            }

            modelBadge.text(modelValue);
        }

        // Handle FAQ provider change
        $('#postpilot_faq_provider').on('change', function () {
            updateFeatureModelDisplay('faq', $(this).val());
        });

        // Handle Summary provider change
        $('#postpilot_summary_provider').on('change', function () {
            updateFeatureModelDisplay('summary', $(this).val());
        });

        // Handle Internal Links provider change
        $('#postpilot_internal_links_provider').on('change', function () {
            updateFeatureModelDisplay('links', $(this).val());
        });

        // ========================================
        // PASSWORD VISIBILITY TOGGLE (ALL FIELDS)
        // ========================================

        // Handle all toggle-password buttons
        $('.toggle-password').on('click', function () {
            const input = $(this).siblings('input');
            const type = input.attr('type');
            input.attr('type', type === 'password' ? 'text' : 'password');
        });
    });

})(jQuery);
