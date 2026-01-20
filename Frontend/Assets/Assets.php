<?php
/**
 * Assets.php
 *
 * Handles frontend assets enqueuing.
 *
 * @package PostPilot\Frontend\Assets
 * @since 1.0.0
 */

namespace PostPilot\Frontend\Assets;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Assets Class
 *
 * Handles the enqueuing of frontend CSS files.
 *
 * @package PostPilot\Frontend\Assets
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
        add_action('wp_enqueue_scripts', array($this, 'frontend_enqueue_styles'));
    }

    /**
     * Enqueues CSS styles for the PostPilot Frontend
     *
     * @since 1.0.0
     * @return void
     */
    public function frontend_enqueue_styles()
    {
        // Only load on single posts
        if (!is_singular('post')) {
            return;
        }

        wp_enqueue_style(
            'postpilot-frontend-style',
            POSTPILOT_FRONTEND_ASSETS . '/css/frontend.css',
            array(),
            POSTPILOT_VERSION
        );

        // Enqueue external AI sharing styles if feature is enabled
        if (get_option('postpilot_enable_external_ai_sharing', '1') === '1') {
            wp_enqueue_style(
                'postpilot-external-ai-sharing',
                POSTPILOT_FRONTEND_ASSETS . '/css/external-ai-sharing.css',
                array(),
                POSTPILOT_VERSION
            );
        }
    }
}
