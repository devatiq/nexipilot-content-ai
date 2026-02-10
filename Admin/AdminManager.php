<?php
/**
 * AdminManager.php
 *
 * This file contains the AdminManager class, which is responsible for handling the
 * initialization and configuration of the NexiPilot Admin.
 *
 * @package NexiPilot\Admin
 * @since 1.0.0
 */

namespace NexiPilot\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\Admin\Assets\Assets;

/**
 * Class AdminManager
 *
 * Handles the initialization and configuration of the NexiPilot Admin.
 *
 * @package NexiPilot\Admin
 * @since 1.0.0
 */
class AdminManager
{
    /**
     * Settings instance
     *
     * @var Settings
     */
    protected $settings;

    /**
     * Assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * FAQ Meta Box instance
     *
     * @var MetaBox\FAQMetaBox
     */
    protected $faq_metabox;

    /**
     * AdminManager constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->set_constants();
        $this->init();
        $this->init_hooks();
    }

    /**
     * Set admin constants
     *
     * @since 1.0.0
     * @return void
     */
    public function set_constants()
    {
        define('NEXIPILOT_ADMIN_ASSETS', plugin_dir_url(__FILE__) . 'Assets');
    }

    /**
     * Initialize the NexiPilot Admin
     *
     * @since 1.0.0
     * @return void
     */
    public function init()
    {
        $this->settings = new Settings();
        $this->assets = new Assets();
        $this->faq_metabox = new MetaBox\FAQMetaBox();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    public function init_hooks()
    {
        add_action('wp_ajax_nexipilot_save_setting', array($this, 'nexipilot_save_setting'));
        add_filter('plugin_action_links_' . NEXIPILOT_BASENAME, array($this, 'add_plugin_settings_link'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    /**
     * Get allowed AJAX settings with their sanitization callbacks
     *
     * @since 1.0.0
     * @return array Map of setting names to sanitization callbacks
     */
    private function get_allowed_ajax_settings()
    {
        return array(
            // AI Provider Settings
            'nexipilot_ai_provider' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_ai_provider'),
            'nexipilot_faq_provider' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_ai_provider'),
            'nexipilot_summary_provider' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_ai_provider'),
            'nexipilot_internal_links_provider' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_ai_provider'),

            // Model Settings
            'nexipilot_openai_model' => 'sanitize_text_field',
            'nexipilot_claude_model' => 'sanitize_text_field',
            'nexipilot_gemini_model' => 'sanitize_text_field',
            'nexipilot_grok_model' => 'sanitize_text_field',

            // Feature Enable/Disable
            'nexipilot_enable_faq' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_enable_summary' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_enable_internal_links' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_enable_debug_logging' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_enable_external_ai_sharing' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),

            // Position Settings
            'nexipilot_faq_position' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_position'),
            'nexipilot_summary_position' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_position'),
            'nexipilot_external_ai_position' => 'sanitize_text_field',
            'nexipilot_faq_default_layout' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_faq_layout'),

            // External AI Sharing Providers
            'nexipilot_external_ai_chatgpt' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_external_ai_claude' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_external_ai_perplexity' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_external_ai_grok' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_external_ai_copilot' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),
            'nexipilot_external_ai_google' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_checkbox'),

            // External AI Heading
            'nexipilot_external_ai_heading' => 'sanitize_text_field',

            // API Keys (encrypted storage)
            'nexipilot_openai_api_key' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_api_key'),
            'nexipilot_claude_api_key' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_api_key'),
            'nexipilot_gemini_api_key' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_api_key'),
            'nexipilot_grok_api_key' => array(\NexiPilot\Helpers\Sanitizer::class, 'sanitize_api_key'),
        );
    }

    /**
     * Saves a setting through an AJAX request (with whitelist security)
     *
     * @since 1.0.0
     * @return void
     */
    public function nexipilot_save_setting()
    {
        // Verify nonce for security
        check_ajax_referer('nexipilot_nonce', 'nonce');

        // Ensure the user has the proper capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'nexipilot-content-ai')));
        }

        // Check if the required fields are set
        if (!isset($_POST['settingName'], $_POST['value'])) {
            wp_send_json_error(array('message' => __('Missing data', 'nexipilot-content-ai')));
        }

        // Use sanitize_key for option names (safer than sanitize_text_field)
        $setting_name = sanitize_key(wp_unslash($_POST['settingName']));

        // Get whitelist of allowed settings
        $allowed_settings = $this->get_allowed_ajax_settings();

        // Block any unexpected option key (security: prevent arbitrary option updates)
        if (!isset($allowed_settings[$setting_name])) {
            wp_send_json_error(array('message' => __('Invalid setting.', 'nexipilot-content-ai')));
        }

        // Get raw value
        $raw_value = wp_unslash($_POST['value']);

        // Sanitize value using the callback defined for this setting
        $sanitize_callback = $allowed_settings[$setting_name];

        if (is_callable($sanitize_callback)) {
            $value = call_user_func($sanitize_callback, $raw_value);
        } else {
            // Fallback to sanitize_text_field if callback is not callable
            $value = sanitize_text_field($raw_value);
        }

        // Save the setting in the options table
        $updated = update_option($setting_name, $value);

        if ($updated) {
            wp_send_json_success(array('message' => __('Setting saved.', 'nexipilot-content-ai')));
        }

        wp_send_json_error(array('message' => __('Failed to save setting.', 'nexipilot-content-ai')));
    }

    /**
     * Add custom links to the plugin actions in the Plugins list
     *
     * @since 1.0.0
     * @param array $links Existing plugin action links.
     * @return array Modified plugin action links.
     */
    public function add_plugin_settings_link($links)
    {
        $settings_link = sprintf(
            '<a href="%s">%s</a>',
            esc_url(admin_url('admin.php?page=nexipilot-settings')),
            esc_html__('Settings', 'nexipilot-content-ai')
        );

        // Prepend the settings link
        array_unshift($links, $settings_link);

        return $links;
    }

    /**
     * Add custom meta links to plugin row
     *
     * @since 1.0.0
     * @param array  $links Existing plugin row meta.
     * @param string $file Plugin file path.
     * @return array Modified plugin row meta.
     */
    public function plugin_row_meta($links, $file)
    {
        if (NEXIPILOT_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="https://github.com/devatiq/nexipilot-content-ai" target="_blank">' . esc_html__('Documentation', 'nexipilot-content-ai') . '</a>',
                'support' => '<a href="https://github.com/devatiq/nexipilot-content-ai/issues" target="_blank">' . esc_html__('Support', 'nexipilot-content-ai') . '</a>',
            );
            return array_merge($links, $row_meta);
        }
        return (array) $links;
    }
}
