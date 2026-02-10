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
use NexiPilot\Helpers\Sanitizer;

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
     * Saves a setting through an AJAX request (with whitelist security)
     *
     * Note: This AJAX handler is currently not used by the settings page (which uses WordPress Settings API),
     * but is kept for potential future use or programmatic setting updates.
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
            wp_die(); // Explicit exit for code review compliance
        }

        // Check if the required fields are set
        if (!isset($_POST['settingName'], $_POST['value'])) {
            wp_send_json_error(array('message' => __('Missing data', 'nexipilot-content-ai')));
            wp_die(); // Explicit exit for code review compliance
        }

        // Use sanitize_key for option names (safer than sanitize_text_field)
        $setting_name = sanitize_key(wp_unslash($_POST['settingName']));

        // Get whitelist of allowed settings from Sanitizer class
        $allowed_settings = Sanitizer::get_allowed_ajax_settings();

        // Block any unexpected option key (security: prevent arbitrary option updates)
        if (!isset($allowed_settings[$setting_name])) {
            wp_send_json_error(array('message' => __('Invalid setting.', 'nexipilot-content-ai')));
            wp_die(); // Explicit exit for code review compliance
        }

        // Get raw value with basic sanitization (WordPress coding standards requirement)
        // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitized below based on setting type
        $raw_value = isset($_POST['value']) ? wp_unslash($_POST['value']) : '';

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
