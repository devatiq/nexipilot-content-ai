/**
 * PostPilot FAQ Meta Box JavaScript
 * 
 * Handles all functionality for the FAQ meta box in the post editor including:
 * - Adding/removing FAQ items
 * - Generating FAQs via AI
 * - Generating demo FAQs
 * - API status checking
 * - SweetAlert2 confirmations
 * 
 * @package PostPilot
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar1@gmail.com>
 */

(function($) {
    'use strict';

    $(document).ready(function() {
        let faqIndex = $('.postpilotai-faq-item').length;

        // ========================================
        // ADD FAQ ITEM
        // ========================================

        $(document).on('click', '.postpilotai-add-faq-item', function(e) {
            e.preventDefault();
            
            const template = $('#postpilotai-faq-item-template').html();
            const newItem = template
                .replace(/\{\{INDEX\}\}/g, faqIndex)
                .replace(/\{\{NUMBER\}\}/g, faqIndex + 1);
            
            $('.postpilotai-faq-items').append(newItem);
            $('.postpilotai-no-faqs').remove();
            faqIndex++;
        });

        // ========================================
        // REMOVE FAQ ITEM
        // ========================================

        $(document).on('click', '.postpilotai-remove-faq-item', function(e) {
            e.preventDefault();
            
            const faqItem = $(this).closest('.postpilotai-faq-item');
            
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
                        if ($('.postpilotai-faq-item').length === 0) {
                            $('.postpilotai-faq-items').html('<p class="postpilotai-no-faqs">No FAQs yet. Click "Generate FAQ" to create them automatically.</p>');
                        }
                        
                        // Renumber remaining items
                        $('.postpilotai-faq-item').each(function(index) {
                            $(this).find('.postpilotai-faq-item-number').text('FAQ #' + (index + 1));
                        });
                    });
                }
            });
        });

        // ========================================
        // GENERATE FAQ VIA AJAX
        // ========================================

        $(document).on('click', '.postpilotai-generate-faq', function(e) {
            e.preventDefault();
            
            const button = $(this);
            const postId = button.data('post-id');
            
            // First, check API status before showing confirmation
            checkApiStatusAndConfirm(button, postId);
        });

        // ========================================
        // CHECK API STATUS AND CONFIRM
        // ========================================

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
                    action: 'postpilotai_check_api_status',
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

        // ========================================
        // GENERATE FAQ FROM AI
        // ========================================

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
                    action: 'postpilotai_generate_faq',
                    nonce: postpilotAdmin.generateFaqNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Replace FAQ items with generated ones
                        $('.postpilotai-faq-items').html(response.data.html);
                        $('.postpilotai-no-faqs').remove();
                        
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
                        // Check if it's a rate limit error
                        if (response.data && response.data.rate_limited) {
                            Swal.fire({
                                icon: 'warning',
                                title: postpilotAdmin.i18n.rateLimitTitle || 'Rate Limit Reached',
                                text: response.data.message,
                                confirmButtonText: postpilotAdmin.i18n.okButton || 'OK',
                                customClass: {
                                    confirmButton: 'swal2-confirm swal2-styled'
                                }
                            });
                            return; // Don't offer demo FAQ for rate limits
                        }
                        
                        // If generation failed (not rate limited), offer demo as fallback
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

        // ========================================
        // GENERATE DEMO FAQ
        // ========================================

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
                    action: 'postpilotai_generate_demo_faq',
                    nonce: postpilotAdmin.generateFaqNonce,
                    post_id: postId
                },
                success: function(response) {
                    if (response.success) {
                        // Replace FAQ items with demo ones
                        $('.postpilotai-faq-items').html(response.data.html);
                        $('.postpilotai-no-faqs').remove();
                        
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
