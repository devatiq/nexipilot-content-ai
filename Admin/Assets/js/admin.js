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
            
            // Show loading alert
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
                        // Check if it's a quota exceeded error
                        const errorMessage = response.data.message || '';
                        const isQuotaError = errorMessage.toLowerCase().includes('quota') || 
                                           errorMessage.toLowerCase().includes('exceeded') ||
                                           errorMessage.toLowerCase().includes('insufficient');
                        
                        if (isQuotaError) {
                            // Show confirmation dialog for demo FAQ
                            Swal.fire({
                                icon: 'warning',
                                title: postpilotAdmin.i18n.quotaExceeded,
                                text: postpilotAdmin.i18n.quotaMessage,
                                showCancelButton: true,
                                confirmButtonColor: '#3085d6',
                                cancelButtonColor: '#d33',
                                confirmButtonText: postpilotAdmin.i18n.useDemoButton,
                                cancelButtonText: postpilotAdmin.i18n.cancelButton
                            }).then((result) => {
                                if (result.isConfirmed) {
                                    // Generate demo FAQ
                                    generateDemoFaq(button, postId);
                                }
                            });
                        } else {
                            // Show regular error
                            Swal.fire({
                                icon: 'error',
                                title: postpilotAdmin.i18n.error,
                                text: errorMessage
                            });
                        }
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
        });

        // Function to generate demo FAQ
        function generateDemoFaq(button, postId) {
            Swal.fire({
                title: postpilotAdmin.i18n.generating,
                text: 'Adding demo FAQ content...',
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
