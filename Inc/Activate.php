<?php
/**
 * Activate.php
 *
 * Handles plugin activation tasks.
 *
 * @package PostPilotAI\Inc
 * @since 1.0.0
 */

namespace PostPilotAI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Activate Class
 *
 * Handles all plugin activation tasks including setting default options
 * and checking system requirements.
 *
 * @package PostPilotAI\Inc
 * @since 1.0.0
 */
class Activate
{
    /**
     * Plugin activation callback
     *
     * @since 1.0.0
     * @return void
     */
    public static function activate()
    {
        // Check WordPress version
        if (version_compare(get_bloginfo('version'), '5.8', '<')) {
            deactivate_plugins(plugin_basename(POSTPILOTAI_FILE));
            wp_die(
                esc_html__('PostPilot AI requires WordPress 5.8 or higher.', 'postpilot'),
                esc_html__('Plugin Activation Error', 'postpilot'),
                array('back_link' => true)
            );
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(POSTPILOTAI_FILE));
            wp_die(
                esc_html__('PostPilot AI requires PHP 7.4 or higher.', 'postpilot'),
                esc_html__('Plugin Activation Error', 'postpilot'),
                array('back_link' => true)
            );
        }

        // Set default options
        self::set_default_options();

        // Flush rewrite rules
        flush_rewrite_rules();
    }

    /**
     * Set default plugin options
     *
     * @since 1.0.0
     * @return void
     */
    private static function set_default_options(): void
    {

        $default_options = array(
            'postpilotai_ai_provider' => 'openai',
            'postpilotai_openai_api_key' => '',
            'postpilotai_claude_api_key' => '',
            'postpilotai_enable_faq' => '1',
            'postpilotai_enable_summary' => '1',
            'postpilotai_enable_internal_links' => '1',
            'postpilotai_enable_debug_logging' => '0', //  default off
            'postpilotai_faq_position' => 'after_content',
            'postpilotai_faq_default_layout' => 'accordion',
            'postpilotai_summary_position' => 'before_content',
            'postpilotai_version' => defined('POSTPILOTAI_VERSION') ? POSTPILOTAI_VERSION : '1.0.0',
        );

        foreach ($default_options as $option_name => $option_value) {
            if (false === get_option($option_name, false)) {
                add_option($option_name, $option_value);
            }
        }
    }
}
