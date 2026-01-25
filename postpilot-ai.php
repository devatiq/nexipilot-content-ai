<?php
/**
 * Plugin Name: PostPilot AI
 * Plugin URI: https://github.com/devatiq/postpilot-ai
 * Description: AI-powered WordPress plugin that generates FAQs, content summaries, and smart internal links for your posts.
 * Version: 1.0.0
 * Author: Nexiby LLC
 * Author URI: https://nexiby.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: postpilot-ai
 * Domain Path: /languages
 * Requires at least: 5.8
 * Requires PHP: 7.4
 *
 * @package PostPilot
 */

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

/**
 * Main PostPilot Class
 *
 * This is the main singleton class that handles the initialization
 * of the PostPilot AI plugin.
 *
 * @since 1.0.0
 */
final class PostPilotAI
{
    /**
     * Plugin instance
     *
     * @var PostPilotAI|null
     */
    private static $instance = null;

    /**
     * Private constructor to prevent direct instantiation
     *
     * @since 1.0.0
     */
    private function __construct()
    {
        $this->define_constants();
        $this->include_files();
        $this->init_hooks();
    }

    /**
     * Get singleton instance
     *
     * @since 1.0.0
     * @return PostPilotAI
     */
    public static function get_instance()
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Define plugin constants
     *
     * @since 1.0.0
     * @return void
     */
    private function define_constants()
    {
        define('POSTPILOTAI_VERSION', '1.0.0');
        define('POSTPILOTAI_PATH', plugin_dir_path(__FILE__));
        define('POSTPILOTAI_URL', plugin_dir_url(__FILE__));
        define('POSTPILOTAI_FILE', __FILE__);
        define('POSTPILOTAI_BASENAME', plugin_basename(__FILE__));
        define('POSTPILOTAI_NAME', 'PostPilot AI');
    }

    /**
     * Include required files
     *
     * @since 1.0.0
     * @return void
     */
    private function include_files()
    {
        if (file_exists(POSTPILOTAI_PATH . 'vendor/autoload.php')) {
            require_once POSTPILOTAI_PATH . 'vendor/autoload.php';
        }
    }

    /**
     * Initialize WordPress hooks
     *
     * @since 1.0.0
     * @return void
     */
    private function init_hooks()
    {
        add_action('plugins_loaded', [$this, 'plugin_loaded']);
        register_activation_hook(POSTPILOTAI_FILE, [$this, 'activate']);
        register_deactivation_hook(POSTPILOTAI_FILE, [$this, 'deactivate']);
    }

    /**
     * Plugin loaded callback
     *
     * @since 1.0.0
     * @return void
     */
    public function plugin_loaded()
    {
        // Initialize the main manager after all plugins are loaded
        if (class_exists('PostPilotAI\Manager')) {
            new PostPilotAI\Manager();
        }
    }


    /**
     * Plugin activation callback
     *
     * @since 1.0.0
     * @return void
     */
    public function activate()
    {
        if (class_exists('PostPilotAI\Activate')) {
            PostPilotAI\Activate::activate();
        }
    }

    /**
     * Plugin deactivation callback
     *
     * @since 1.0.0
     * @return void
     */
    public function deactivate()
    {
        if (class_exists('PostPilotAI\Deactivate')) {
            PostPilotAI\Deactivate::deactivate();
        }
    }

    /**
     * Prevent cloning of the instance
     *
     * @since 1.0.0
     * @return void
     */
    private function __clone()
    {
    }

    /**
     * Prevent unserializing of the instance
     *
     * @since 1.0.0
     * @return void
     */
    public function __wakeup()
    {
    }
}

/**
 * Bootstrap the plugin
 *
 * @since 1.0.0
 * @return PostPilotAI
 */
function postpilotai()
{
    return PostPilotAI::get_instance();
}

// Initialize the plugin
postpilotai();