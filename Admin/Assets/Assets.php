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
        // Load on PostPilot settings page and post edit pages
        if ('toplevel_page_postpilot-settings' === $hook_suffix || 'post.php' === $hook_suffix || 'post-new.php' === $hook_suffix) {
            wp_enqueue_script(
                'postpilot-admin-script',
                POSTPILOT_ADMIN_ASSETS . '/js/admin.js',
                array('jquery'),
                POSTPILOT_VERSION,
                true
            );

            wp_localize_script(
                'postpilot-admin-script',
                'postpilotAdmin',
                array(
                    'ajaxurl' => admin_url('admin-ajax.php'),
                    'nonce' => wp_create_nonce('postpilot_nonce'),
                    'generateFaqNonce' => wp_create_nonce('postpilot_generate_faq'),
                )
            );
        }
    }
}
