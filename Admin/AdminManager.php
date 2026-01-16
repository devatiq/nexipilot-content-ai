<?php
/**
 * AdminManager.php
 *
 * This file contains the AdminManager class, which is responsible for handling the
 * initialization and configuration of the PostPilot Admin.
 *
 * @package PostPilot\Admin
 * @since 1.0.0
 */

namespace PostPilot\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\Admin\Assets\Assets;

/**
 * Class AdminManager
 *
 * Handles the initialization and configuration of the PostPilot Admin.
 *
 * @package PostPilot\Admin
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
     * AdminManager constructor
     *
     * Initializes the AdminManager by setting constants and initiating configurations.
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
     * Sets the constants for the PostPilot Admin
     *
     * @since 1.0.0
     * @return void
     */
    public function set_constants()
    {
        define('POSTPILOT_ADMIN_ASSETS', plugin_dir_url(__FILE__) . 'Assets');
    }

    /**
     * Initializes the classes used by the PostPilot Admin
     *
     * @since 1.0.0
     * @return void
     */
    public function init()
    {
        $this->settings = new Settings();
        $this->assets = new Assets();
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    public function init_hooks()
    {
        add_action('wp_ajax_postpilot_save_setting', array($this, 'postpilot_save_setting'));
        add_filter('plugin_action_links_' . POSTPILOT_BASENAME, array($this, 'add_plugin_settings_link'));
        add_filter('plugin_row_meta', array($this, 'plugin_row_meta'), 10, 2);
    }

    /**
     * Saves a setting through an AJAX request
     *
     * @since 1.0.0
     * @return void
     */
    public function postpilot_save_setting()
    {
        // Verify nonce for security
        check_ajax_referer('postpilot_nonce', 'nonce');

        // Ensure the user has the proper capability
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => __('Unauthorized', 'postpilot')));
            wp_die();
        }

        // Check if the required fields are set
        if (!isset($_POST['settingName']) || !isset($_POST['value'])) {
            wp_send_json_error(array('message' => __('Missing data', 'postpilot')));
            wp_die();
        }

        // Sanitize the setting name
        $setting_name = sanitize_text_field(wp_unslash($_POST['settingName']));

        // Sanitize value based on setting type
        $value = sanitize_text_field(wp_unslash($_POST['value']));

        // Save the setting in the options table
        if (update_option($setting_name, $value)) {
            wp_send_json_success(array('message' => __('Setting saved.', 'postpilot')));
        } else {
            wp_send_json_error(array('message' => __('Failed to save setting.', 'postpilot')));
        }

        wp_die();
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
            esc_url(admin_url('admin.php?page=postpilot-settings')),
            esc_html__('Settings', 'postpilot')
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
        if (POSTPILOT_BASENAME === $file) {
            $row_meta = array(
                'docs' => '<a href="https://github.com/devatiq/postpilot" target="_blank">' . esc_html__('Documentation', 'postpilot') . '</a>',
                'support' => '<a href="https://github.com/devatiq/postpilot/issues" target="_blank">' . esc_html__('Support', 'postpilot') . '</a>',
            );
            return array_merge($links, $row_meta);
        }
        return (array) $links;
    }
}
