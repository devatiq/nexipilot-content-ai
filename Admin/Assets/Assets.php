<?php
/**
 * Assets.php
 *
 * Handles admin assets enqueuing.
 *
 * @package PostPilot\Admin\Assets
 * @since 1.0.0
 */

namespace PostPilot\Admin\Assets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assets Class
 *
 * Handles the enqueuing of admin CSS and JavaScript files.
 *
 * @package PostPilot\Admin\Assets
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
        if ('toplevel_page_postpilot-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_style(
                'postpilot-admin-style',
                POSTPILOT_ADMIN_ASSETS . '/css/admin.css',
                array(),
                POSTPILOT_VERSION
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
        if ('toplevel_page_postpilot-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'sweetalert2',
                POSTPILOT_ADMIN_ASSETS . '/js/sweetalert2.js',
                array(),
                '11.0.0',
                true
            );
        }

        // Enqueue settings.js only on PostPilot settings page
        if ('toplevel_page_postpilot-settings' === $hook_suffix) {
            wp_enqueue_script(
                'postpilot-settings-script',
                POSTPILOT_ADMIN_ASSETS . '/js/settings.js',
                array('jquery', 'sweetalert2'),
                POSTPILOT_VERSION,
                true
            );
        }

        // Enqueue faq-metabox.js only on post edit pages
        if ('post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'postpilot-faq-metabox-script',
                POSTPILOT_ADMIN_ASSETS . '/js/faq-metabox.js',
                array('jquery', 'sweetalert2'),
                POSTPILOT_VERSION,
                true
            );

            // Localize script for FAQ metabox
            wp_localize_script(
                'postpilot-faq-metabox-script',
                'postpilotAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('postpilot_nonce'),
                    'generateFaqNonce' => wp_create_nonce('postpilot_generate_faq'),
                    'i18n' => array(
                        'confirmRemove' => __('Are you sure you want to remove this FAQ item?', 'postpilot'),
                        'removeButton' => __('Yes, remove it', 'postpilot'),
                        'cancelButton' => __('Cancel', 'postpilot'),
                        'generating' => __('Generating FAQ...', 'postpilot'),
                        'pleaseWait' => __('Please wait while we generate FAQs from your content.', 'postpilot'),
                        'success' => __('Success!', 'postpilot'),
                        'error' => __('Error', 'postpilot'),
                        'quotaExceeded' => __('API Quota Exceeded', 'postpilot'),
                        'quotaMessage' => __('Your OpenAI quota has been exceeded. Would you like to use demo FAQ content instead?', 'postpilot'),
                        'useDemoButton' => __('Yes, use demo FAQ', 'postpilot'),
                        'demoAdded' => __('Demo FAQ Added', 'postpilot'),
                        'demoMessage' => __('Demo FAQ content has been added. You can edit it manually or add credits to your OpenAI account to generate real content.', 'postpilot'),
                        'rateLimitTitle' => __('Rate Limit Reached', 'postpilot'),
                        'errorTitle' => __('Error', 'postpilot'),
                        'okButton' => __('OK', 'postpilot'),
                        'genericError' => __('An error occurred. Please try again.', 'postpilot'),
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
        $faq_enabled = get_post_meta($post->ID, '_postpilot_faq_enabled', true);
        if ($faq_enabled !== '1') {
            return;
        }

        // Enqueue FAQ CSS
        wp_enqueue_style(
            'postpilot-faq',
            POSTPILOT_URL . 'Frontend/Assets/css/faq.css',
            array(),
            POSTPILOT_VERSION
        );

        // Determine layout and enqueue JS if accordion
        $layout = $this->get_faq_layout($post->ID);
        if ($layout === 'accordion') {
            wp_enqueue_script(
                'postpilot-faq-accordion',
                POSTPILOT_URL . 'Frontend/Assets/js/faq-accordion.js',
                array(),
                POSTPILOT_VERSION,
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
        $post_layout = get_post_meta($post_id, '_postpilot_faq_display_style', true);

        // If default or empty, use global setting
        if (empty($post_layout) || $post_layout === 'default') {
            return get_option('postpilot_faq_default_layout', 'accordion');
        }

        return $post_layout;
    }
}
