/**
 * PostPilot FAQ Accordion Functionality
 * 
 * Lightweight vanilla JavaScript for accordion interactions
 * Supports keyboard navigation and ARIA attributes
 * 
 * @package PostPilot
 * @since 1.0.0
 */

(function() {
    'use strict';

    /**
     * Initialize FAQ accordion on DOM ready
     */
    function initFAQAccordion() {
        const faqContainer = document.querySelector('.nexipilot-faq--accordion');
        
        if (!faqContainer) {
            return;
        }

        const faqItems = faqContainer.querySelectorAll('.nexipilot-faq__item');
        
        faqItems.forEach(function(item, index) {
            const question = item.querySelector('.nexipilot-faq__question');
            const answer = item.querySelector('.nexipilot-faq__answer');
            
            if (!question || !answer) {
                return;
            }

            // Set unique IDs
            const answerId = 'nexipilot-faq-answer-' + index;
            answer.id = answerId;
            
            // Set initial ARIA attributes
            question.setAttribute('aria-expanded', 'false');
            question.setAttribute('aria-controls', answerId);
            answer.setAttribute('aria-hidden', 'true');
            answer.setAttribute('hidden', '');
            
            // Add click event
            question.addEventListener('click', function() {
                toggleAccordion(item, question, answer);
            });
            
            // Add keyboard support
            question.addEventListener('keydown', function(e) {
                // Enter or Space key
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    toggleAccordion(item, question, answer);
                }
            });
        });
    }

    /**
     * Toggle accordion item
     * 
     * @param {HTMLElement} item - The FAQ item container
     * @param {HTMLElement} question - The question button
     * @param {HTMLElement} answer - The answer container
     */
    function toggleAccordion(item, question, answer) {
        const isExpanded = question.getAttribute('aria-expanded') === 'true';
        const faqContainer = item.closest('.nexipilot-faq--accordion');
        
        // Close all other items (accordion behavior - only one open at a time)
        if (!isExpanded) {
            closeAllAccordions(faqContainer);
        }
        
        // Toggle current item
        if (isExpanded) {
            closeAccordion(item, question, answer);
        } else {
            openAccordion(item, question, answer);
        }
    }

    /**
     * Open accordion item
     * 
     * @param {HTMLElement} item - The FAQ item container
     * @param {HTMLElement} question - The question button
     * @param {HTMLElement} answer - The answer container
     */
    function openAccordion(item, question, answer) {
        question.setAttribute('aria-expanded', 'true');
        answer.setAttribute('aria-hidden', 'false');
        answer.removeAttribute('hidden');
        item.setAttribute('data-active', 'true');
    }

    /**
     * Close accordion item
     * 
     * @param {HTMLElement} item - The FAQ item container
     * @param {HTMLElement} question - The question button
     * @param {HTMLElement} answer - The answer container
     */
    function closeAccordion(item, question, answer) {
        question.setAttribute('aria-expanded', 'false');
        answer.setAttribute('aria-hidden', 'true');
        answer.setAttribute('hidden', '');
        item.removeAttribute('data-active');
    }

    /**
     * Close all accordion items
     * 
     * @param {HTMLElement} container - The FAQ container
     */
    function closeAllAccordions(container) {
        const allItems = container.querySelectorAll('.nexipilot-faq__item');
        
        allItems.forEach(function(item) {
            const question = item.querySelector('.nexipilot-faq__question');
            const answer = item.querySelector('.nexipilot-faq__answer');
            
            if (question && answer) {
                closeAccordion(item, question, answer);
            }
        });
    }

    // Initialize on DOM ready
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initFAQAccordion);
    } else {
        initFAQAccordion();
    }

})();
