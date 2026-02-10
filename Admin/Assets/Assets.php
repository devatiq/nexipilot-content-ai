<?php
/**
 * Assets.php
 *
 * Handles admin assets enqueuing.
 *
 * @package NexiPilot\Admin\Assets
 * @since 1.0.0
 */

namespace NexiPilot\Admin\Assets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assets Class
 *
 * Handles the enqueuing of admin CSS and JavaScript files.
 *
 * @package NexiPilot\Admin\Assets
 * @since 1.0.0
 */
class Assets
{
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_styles'));
        add_action('admin_enqueue_scripts', array($this, 'admin_enqueue_scripts'));
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_scripts'));
    }

    /**
     * Enqueues CSS styles for the PostPilot Admin
     *
     * @since 1.0.0
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function admin_enqueue_styles($hook_suffix)
    {
        // Load on PostPilot settings page and post edit pages
        if ('toplevel_page_nexipilot-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_style(
                'nexipilot-admin-style',
                NEXIPILOT_ADMIN_ASSETS . '/css/admin.css',
                array(),
                NEXIPILOT_VERSION
            );
        }
    }

    /**
     * Enqueues JavaScript scripts for the PostPilot Admin
     *
     * @since 1.0.0
     * @param string $hook_suffix The current admin page hook suffix.
     * @return void
     */
    public function admin_enqueue_scripts($hook_suffix)
    {
        // Enqueue SweetAlert2 on settings page and post edit pages
        if ('toplevel_page_nexipilot-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'sweetalert2',
                NEXIPILOT_ADMIN_ASSETS . '/js/sweetalert2.js',
                array(),
                '11.0.0',
                true
            );
        }

        // Enqueue settings.js only on PostPilot settings page
        if ('toplevel_page_nexipilot-settings' === $hook_suffix) {
            wp_enqueue_script(
                'nexipilot-settings-script',
                NEXIPILOT_ADMIN_ASSETS . '/js/settings.js',
                array('jquery', 'sweetalert2'),
                NEXIPILOT_VERSION,
                true
            );
        }

        // Enqueue faq-metabox.js only on post edit pages
        if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'nexipilot-faq-metabox-script',
                NEXIPILOT_ADMIN_ASSETS . '/js/faq-metabox.js',
                array('jquery', 'sweetalert2'),
                NEXIPILOT_VERSION,
                true
            );

            // Localize script for FAQ metabox
            wp_localize_script(
                'nexipilot-faq-metabox-script',
                'nexipilotAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('nexipilot_nonce'),
                    'generateFaqNonce' => wp_create_nonce('nexipilot_generate_faq'),
                    'i18n' => array(
                        'confirmRemove' => __('Are you sure you want to remove this FAQ item?', 'nexipilot-content-ai'),
                        'removeButton' => __('Yes, remove it', 'nexipilot-content-ai'),
                        'cancelButton' => __('Cancel', 'nexipilot-content-ai'),
                        'generating' => __('Generating FAQ...', 'nexipilot-content-ai'),
                        'pleaseWait' => __('Please wait while we generate FAQs from your content.', 'nexipilot-content-ai'),
                        'success' => __('Success!', 'nexipilot-content-ai'),
                        'error' => __('Error', 'nexipilot-content-ai'),
                        'quotaExceeded' => __('API Quota Exceeded', 'nexipilot-content-ai'),
                        'quotaMessage' => __('Your OpenAI quota has been exceeded. Would you like to use demo FAQ content instead?', 'nexipilot-content-ai'),
                        'useDemoButton' => __('Yes, use demo FAQ', 'nexipilot-content-ai'),
                        'demoAdded' => __('Demo FAQ Added', 'nexipilot-content-ai'),
                        'demoMessage' => __('Demo FAQ content has been added. You can edit it manually or add credits to your OpenAI account to generate real content.', 'nexipilot-content-ai'),
                        'rateLimitTitle' => __('Rate Limit Reached', 'nexipilot-content-ai'),
                        'errorTitle' => __('Error', 'nexipilot-content-ai'),
                        'okButton' => __('OK', 'nexipilot-content-ai'),
                        'genericError' => __('An error occurred. Please try again.', 'nexipilot-content-ai'),
                    ),
                )
            );
        }
    }

    /**
     * Enqueue frontend scripts and styles
     *
     * @since 1.0.0
     * @return void
     */
    public function frontend_enqueue_scripts()
    {
        // Only on singular posts
        if (!is_singular('post')) {
            return;
        }

        global $post;

        // Check if FAQ is enabled for this post
        $faq_enabled = get_post_meta($post->ID, '_nexipilot_faq_enabled', true);
        if ($faq_enabled !== '1') {
            return;
        }

        // Enqueue FAQ CSS
        wp_enqueue_style(
            'nexipilot-faq',
            NEXIPILOT_URL . 'Frontend/Assets/css/faq.css',
            array(),
            NEXIPILOT_VERSION
        );

        // Determine layout and enqueue JS if accordion
        $layout = $this->get_faq_layout($post->ID);
        if ($layout === 'accordion') {
            wp_enqueue_script(
                'nexipilot-faq-accordion',
                NEXIPILOT_URL . 'Frontend/Assets/js/faq-accordion.js',
                array(),
                NEXIPILOT_VERSION,
                true
            );
        }
    }

    /**
     * Get FAQ layout style for a post
     *
     * @since 1.0.0
     * @param int $post_id The post ID.
     * @return string The layout style (accordion or static).
     */
    private function get_faq_layout($post_id)
    {
        // Get per-post layout setting
        $post_layout = get_post_meta($post_id, '_nexipilot_faq_display_style', true);

        // If default or empty, use global setting
        if (empty($post_layout) || $post_layout === 'default') {
            return get_option('nexipilot_faq_default_layout', 'accordion');
        }

        return $post_layout;
    }
}
