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

(function($) {
    'use strict';

    $(document).ready(function() {
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
        $('.postpilot-tab').on('click', function() {
            const tabName = $(this).data('tab');
            switchTab(tabName);
        });

        // ========================================
        // API PROVIDER FIELD TOGGLING
        // ========================================

        const providerSelect = $('#postpilot_ai_provider');
        const openaiField = $('#openai-api-key-field');
        const claudeField = $('#claude-api-key-field');

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
            providerSelect.on('change', toggleApiKeyFields);
        }

        // ========================================
        // API KEY VISIBILITY TOGGLE
        // ========================================

        $('#toggle-openai-key, #toggle-claude-key').on('click', function() {
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
        }

        // Initialize on page load
        toggleFeatureOptions();

        // Update on toggle change
        $('input[name="postpilot_enable_faq"], input[name="postpilot_enable_summary"]').on('change', function() {
            toggleFeatureOptions();
        });
    });

})(jQuery);
