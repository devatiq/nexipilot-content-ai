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

        // Remove FAQ Item
        $(document).on('click', '.postpilot-remove-faq-item', function(e) {
            e.preventDefault();
            
            if (confirm('Are you sure you want to remove this FAQ item?')) {
                $(this).closest('.postpilot-faq-item').fadeOut(300, function() {
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

        // Generate FAQ via AJAX
        $(document).on('click', '.postpilot-generate-faq', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const spinner = button.siblings('.spinner');
            const postId = button.data('post-id');
            
            // Disable button and show spinner
            button.prop('disabled', true);
            spinner.addClass('is-active');
            
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
                        alert(response.data.message);
                    } else {
                        alert('Error: ' + response.data.message);
                    }
                },
                error: function(xhr, status, error) {
                    alert('AJAX Error: ' + error);
                },
                complete: function() {
                    // Re-enable button and hide spinner
                    button.prop('disabled', false);
                    spinner.removeClass('is-active');
                }
            });
        });
    });

})(jQuery);
