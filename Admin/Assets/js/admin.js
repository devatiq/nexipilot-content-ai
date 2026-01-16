/**
 * PostPilot Admin JavaScript
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
        // MODERN SETTINGS PAGE INTERACTIVITY
        // ========================================

        // Toggle API key fields based on selected provider
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

        // Toggle API key visibility (show/hide password)
        $('#toggle-openai-key, #toggle-claude-key').on('click', function() {
            const input = $(this).siblings('input');
            const type = input.attr('type');
            input.attr('type', type === 'password' ? 'text' : 'password');
        });

        // Show/hide feature options based on toggle state
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

    // ===========================
    // FAQ Meta Box Functionality
    // ===========================

    $(document).ready(function() {
        let faqIndex = $('.postpilot-faq-item').length;

        // Add FAQ Item
        $(document).on('click', '.postpilot-add-faq-item', function(e) {
            e.preventDefault();
            
            const template = $('#postpilot-faq-item-template').html();
            const newItem = template
                .replace(/\{\{INDEX\}\}/g, faqIndex)
                .replace(/\{\{NUMBER\}\}/g, faqIndex + 1);
            
            $('.postpilot-faq-items').append(newItem);
            $('.postpilot-no-faqs').remove();
            faqIndex++;
        });

        // Remove FAQ Item with SweetAlert2
        $(document).on('click', '.postpilot-remove-faq-item', function(e) {
            e.preventDefault();
            
            const faqItem = $(this).closest('.postpilot-faq-item');
            
            Swal.fire({
                title: postpilotAdmin.i18n.confirmRemove,
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: postpilotAdmin.i18n.removeButton,
                cancelButtonText: postpilotAdmin.i18n.cancelButton
            }).then((result) => {
                if (result.isConfirmed) {
                    faqItem.fadeOut(300, function() {
                        $(this).remove();
                        
                        // Show "no FAQs" message if all items removed
                        if ($('.postpilot-faq-item').length === 0) {
                            $('.postpilot-faq-items').html('<p class="postpilot-no-faqs">No FAQs yet. Click "Generate FAQ" to create them automatically.</p>');
                        }
                        
                        // Renumber remaining items
                        $('.postpilot-faq-item').each(function(index) {
                            $(this).find('.postpilot-faq-item-number').text('FAQ #' + (index + 1));
                        });
                    });
                }
            });
        });

        // Generate FAQ via AJAX with SweetAlert2
        $(document).on('click', '.postpilot-generate-faq', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const postId = button.data('post-id');
            
            // First, check API status before showing confirmation
            checkApiStatusAndConfirm(button, postId);
        });

        // Function to check API status and show appropriate confirmation
        function checkApiStatusAndConfirm(button, postId) {
            // Show checking status
            Swal.fire({
                title: 'Checking API status...',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            // Check if API is available by making a test call
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'postpilot_check_api_status',
                    nonce: postpilotAdmin.generateFaqNonce
                },
                success: function(response) {
                    Swal.close();
                    
                    if (response.success && response.data.available) {
                        // API is available - show normal confirmation
                        Swal.fire({
                            title: 'Generate FAQ?',
                            text: 'This will use your AI credits to generate FAQ content based on your post.',
                            icon: 'question',
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: 'Yes, generate FAQ',
                            cancelButtonText: postpilotAdmin.i18n.cancelButton
                        }).then((result) => {
                            if (result.isConfirmed) {
                                generateFaqFromAI(button, postId);
                            }
                        });
                    } else {
                        // API not available or quota exceeded - show demo option
                        const errorMessage = response.data.message || 'AI service is currently unavailable.';
                        
                        Swal.fire({
                            icon: 'warning',
                            title: postpilotAdmin.i18n.quotaExceeded,
                            html: `<p>${errorMessage}</p><p>We can generate demo FAQ content instead. Do you want to continue?</p>`,
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: postpilotAdmin.i18n.useDemoButton,
                            cancelButtonText: postpilotAdmin.i18n.cancelButton
                        }).then((result) => {
                            if (result.isConfirmed) {
                                generateDemoFaq(button, postId);
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    console.error('API Status Check Error:', {
                        status: status,
                        error: error,
                        response: xhr.responseText,
                        statusCode: xhr.status
                    });
                    
                    Swal.close();
                    
                    // On error, assume API unavailable and offer demo
                    Swal.fire({
                        icon: 'warning',
                        title: 'Unable to Check API Status',
                        html: '<p>Could not verify AI service availability.</p><p>Would you like to generate demo FAQ content instead?</p>',
                        showCancelButton: true,
                        confirmButtonColor: '#3085d6',
                        cancelButtonColor: '#d33',
                        confirmButtonText: postpilotAdmin.i18n.useDemoButton,
                        cancelButtonText: postpilotAdmin.i18n.cancelButton
                    }).then((result) => {
                        if (result.isConfirmed) {
                            generateDemoFaq(button, postId);
                        }
                    });
                }
            });
        }

        // Function to generate FAQ from AI
        function generateFaqFromAI(button, postId) {
            Swal.fire({
                title: postpilotAdmin.i18n.generating,
                text: postpilotAdmin.i18n.pleaseWait,
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });
            
            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'postpilot_generate_faq',
                    nonce: postpilotAdmin.generateFaqNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Replace FAQ items with generated ones
                        $('.postpilot-faq-items').html(response.data.html);
                        $('.postpilot-no-faqs').remove();
                        
                        // Update button text
                        button.text('Regenerate FAQ');
                        
                        // Update index
                        faqIndex = response.data.count;
                        
                        // Show success message
                        Swal.fire({
                            icon: 'success',
                            title: postpilotAdmin.i18n.success,
                            text: response.data.message,
                            timer: 3000,
                            showConfirmButton: false
                        });
                    } else {
                        // If generation failed, offer demo as fallback
                        const errorMessage = response.data.message || 'FAQ generation failed';
                        
                        Swal.fire({
                            icon: 'error',
                            title: 'Generation Failed',
                            html: `<p>${errorMessage}</p><p>Would you like to use demo FAQ content instead?</p>`,
                            showCancelButton: true,
                            confirmButtonColor: '#3085d6',
                            cancelButtonColor: '#d33',
                            confirmButtonText: postpilotAdmin.i18n.useDemoButton,
                            cancelButtonText: postpilotAdmin.i18n.cancelButton
                        }).then((result) => {
                            if (result.isConfirmed) {
                                generateDemoFaq(button, postId);
                            }
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: postpilotAdmin.i18n.error,
                        text: 'AJAX Error: ' + error
                    });
                }
            });
        }

        // Function to generate demo FAQ
        function generateDemoFaq(button, postId) {
            Swal.fire({
                title: 'Adding Demo FAQ...',
                text: 'Please wait while we add demo FAQ content.',
                allowOutsideClick: false,
                allowEscapeKey: false,
                didOpen: () => {
                    Swal.showLoading();
                }
            });

            $.ajax({
                url: ajaxurl,
                type: 'POST',
                data: {
                    action: 'postpilot_generate_demo_faq',
                    nonce: postpilotAdmin.generateFaqNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Replace FAQ items with demo ones
                        $('.postpilot-faq-items').html(response.data.html);
                        $('.postpilot-no-faqs').remove();
                        
                        // Update button text
                        button.text('Regenerate FAQ');
                        
                        // Update index
                        faqIndex = response.data.count;
                        
                        // Show success message
                        Swal.fire({
                            icon: 'info',
                            title: postpilotAdmin.i18n.demoAdded,
                            text: postpilotAdmin.i18n.demoMessage,
                            confirmButtonText: 'OK'
                        });
                    } else {
                        Swal.fire({
                            icon: 'error',
                            title: postpilotAdmin.i18n.error,
                            text: response.data.message
                        });
                    }
                },
                error: function(xhr, status, error) {
                    Swal.fire({
                        icon: 'error',
                        title: postpilotAdmin.i18n.error,
                        text: 'AJAX Error: ' + error
                    });
                }
            });
        }
    });

})(jQuery);
