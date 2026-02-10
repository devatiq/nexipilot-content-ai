<?php
/**
 * Activate.php
 *
 * Handles plugin activation tasks.
 *
 * @package NexiPilot\Inc
 * @since 1.0.0
 */

namespace NexiPilot;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Activate Class
 *
 * Handles all plugin activation tasks including setting default options
 * and checking system requirements.
 *
 * @package NexiPilot\Inc
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
            deactivate_plugins(plugin_basename(NEXIPILOT_FILE));
            wp_die(
                esc_html__('NexiPilot Content AI requires WordPress 5.8 or higher.', 'nexipilot-content-ai'),
                esc_html__('Plugin Activation Error', 'nexipilot-content-ai'),
                array('back_link' => true)
            );
        }

        // Check PHP version
        if (version_compare(PHP_VERSION, '7.4', '<')) {
            deactivate_plugins(plugin_basename(NEXIPILOT_FILE));
            wp_die(
                esc_html__('NexiPilot Content AI requires PHP 7.4 or higher.', 'nexipilot-content-ai'),
                esc_html__('Plugin Activation Error', 'nexipilot-content-ai'),
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
            'nexipilot_ai_provider' => 'openai',
            'nexipilot_openai_api_key' => '',
            'nexipilot_claude_api_key' => '',
            'nexipilot_enable_faq' => '1',
            'nexipilot_enable_summary' => '1',
            'nexipilot_enable_internal_links' => '1',
            'nexipilot_enable_debug_logging' => '0', //  default off
            'nexipilot_faq_position' => 'after_content',
            'nexipilot_faq_default_layout' => 'accordion',
            'nexipilot_summary_position' => 'before_content',
            'nexipilot_version' => defined('NEXIPILOT_VERSION') ? NEXIPILOT_VERSION : '1.0.0',
        );

        foreach ($default_options as $option_name => $option_value) {
            if (false === get_option($option_name, false)) {
                add_option($option_name, $option_value);
            }
        }
    }
}
