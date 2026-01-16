<?php
/**
 * Plugin Name: PostPilot AI
 * Plugin URI: https://github.com/devatiq/postpilot
 * Description: AI-powered WordPress plugin that generates FAQs, content summaries, and smart internal links for your posts.
 * Version: 1.0.0
 * Author: Nexiby LLC
 * Author URI: https://nexiby.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: postpilot
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
final class PostPilot
{
    /**
     * Plugin instance
     *
     * @var PostPilot|null
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
     * @return PostPilot
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
        define('POSTPILOT_VERSION', '1.0.0');
        define('POSTPILOT_PATH', plugin_dir_path(__FILE__));
        define('POSTPILOT_URL', plugin_dir_url(__FILE__));
        define('POSTPILOT_FILE', __FILE__);
        define('POSTPILOT_BASENAME', plugin_basename(__FILE__));
        define('POSTPILOT_NAME', 'PostPilot AI');
    }

    /**
     * Include required files
     *
     * @since 1.0.0
     * @return void
     */
    private function include_files()
    {
        if (file_exists(POSTPILOT_PATH . 'vendor/autoload.php')) {
            require_once POSTPILOT_PATH . 'vendor/autoload.php';
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
        add_action('init', [$this, 'register_textdomain']);
        register_activation_hook(POSTPILOT_FILE, [$this, 'activate']);
        register_deactivation_hook(POSTPILOT_FILE, [$this, 'deactivate']);
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
        if (class_exists('PostPilot\Manager')) {
            new PostPilot\Manager();
        }
    }

    /**
     * Register text domain for translations
     *
     * @since 1.0.0
     * @return void
     */
    public function register_textdomain()
    {
        load_plugin_textdomain(
            'postpilot',
            false,
            dirname(plugin_basename(__FILE__)) . '/languages'
        );
    }

    /**
     * Plugin activation callback
     *
     * @since 1.0.0
     * @return void
     */
    public function activate()
    {
        if (class_exists('PostPilot\Activate')) {
            PostPilot\Activate::activate();
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
        if (class_exists('PostPilot\Deactivate')) {
            PostPilot\Deactivate::deactivate();
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
    private function __wakeup()
    {
    }
}

/**
 * Bootstrap the plugin
 *
 * @since 1.0.0
 * @return PostPilot
 */
function postpilot()
{
    return PostPilot::get_instance();
}

// Initialize the plugin
postpilot();
