<?php
/**
 * Assets.php
 *
 * Handles admin assets enqueuing.
 *
 * @package PostPilotAI\Admin\Assets
 * @since 1.0.0
 */

namespace PostPilotAI\Admin\Assets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assets Class
 *
 * Handles the enqueuing of admin CSS and JavaScript files.
 *
 * @package PostPilotAI\Admin\Assets
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
        if ('toplevel_page_postpilotai-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_style(
                'postpilotai-admin-style',
                POSTPILOTAI_ADMIN_ASSETS . '/css/admin.css',
                array(),
                POSTPILOTAI_VERSION
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
        if ('toplevel_page_postpilotai-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'sweetalert2',
                POSTPILOTAI_ADMIN_ASSETS . '/js/sweetalert2.js',
                array(),
                '11.0.0',
                true
            );
        }

        // Enqueue settings.js only on PostPilot settings page
        if ('toplevel_page_postpilotai-settings' === $hook_suffix) {
            wp_enqueue_script(
                'postpilotai-settings-script',
                POSTPILOTAI_ADMIN_ASSETS . '/js/settings.js',
                array('jquery', 'sweetalert2'),
                POSTPILOTAI_VERSION,
                true
            );
        }

        // Enqueue faq-metabox.js only on post edit pages
        if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'postpilotai-faq-metabox-script',
                POSTPILOTAI_ADMIN_ASSETS . '/js/faq-metabox.js',
                array('jquery', 'sweetalert2'),
                POSTPILOTAI_VERSION,
                true
            );

            // Localize script for FAQ metabox
            wp_localize_script(
                'postpilotai-faq-metabox-script',
                'postpilotAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('postpilotai_nonce'),
                    'generateFaqNonce' => wp_create_nonce('postpilotai_generate_faq'),
                    'i18n' => array(
                        'confirmRemove' => __('Are you sure you want to remove this FAQ item?', 'postpilot-ai'),
                        'removeButton' => __('Yes, remove it', 'postpilot-ai'),
                        'cancelButton' => __('Cancel', 'postpilot-ai'),
                        'generating' => __('Generating FAQ...', 'postpilot-ai'),
                        'pleaseWait' => __('Please wait while we generate FAQs from your content.', 'postpilot-ai'),
                        'success' => __('Success!', 'postpilot-ai'),
                        'error' => __('Error', 'postpilot-ai'),
                        'quotaExceeded' => __('API Quota Exceeded', 'postpilot-ai'),
                        'quotaMessage' => __('Your OpenAI quota has been exceeded. Would you like to use demo FAQ content instead?', 'postpilot-ai'),
                        'useDemoButton' => __('Yes, use demo FAQ', 'postpilot-ai'),
                        'demoAdded' => __('Demo FAQ Added', 'postpilot-ai'),
                        'demoMessage' => __('Demo FAQ content has been added. You can edit it manually or add credits to your OpenAI account to generate real content.', 'postpilot-ai'),
                        'rateLimitTitle' => __('Rate Limit Reached', 'postpilot-ai'),
                        'errorTitle' => __('Error', 'postpilot-ai'),
                        'okButton' => __('OK', 'postpilot-ai'),
                        'genericError' => __('An error occurred. Please try again.', 'postpilot-ai'),
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
        $faq_enabled = get_post_meta($post->ID, '_postpilotai_faq_enabled', true);
        if ($faq_enabled !== '1') {
            return;
        }

        // Enqueue FAQ CSS
        wp_enqueue_style(
            'postpilotai-faq',
            POSTPILOTAI_URL . 'Frontend/Assets/css/faq.css',
            array(),
            POSTPILOTAI_VERSION
        );

        // Determine layout and enqueue JS if accordion
        $layout = $this->get_faq_layout($post->ID);
        if ($layout === 'accordion') {
            wp_enqueue_script(
                'postpilotai-faq-accordion',
                POSTPILOTAI_URL . 'Frontend/Assets/js/faq-accordion.js',
                array(),
                POSTPILOTAI_VERSION,
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
        $post_layout = get_post_meta($post_id, '_postpilotai_faq_display_style', true);

        // If default or empty, use global setting
        if (empty($post_layout) || $post_layout === 'default') {
            return get_option('postpilotai_faq_default_layout', 'accordion');
        }

        return $post_layout;
    }
}
