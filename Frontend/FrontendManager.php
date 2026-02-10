<?php
/**
 * FrontendManager.php
 *
 * This file contains the FrontendManager class, which is responsible for handling the
 * initialization and configuration of the NexiPilot Frontend.
 *
 * @package NexiPilot\Frontend
 * @since 1.0.0
 */

namespace NexiPilot\Frontend;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\Frontend\Assets\Assets;

/**
 * Class FrontendManager
 *
 * Handles the initialization and configuration of the NexiPilot Frontend.
 *
 * @package NexiPilot\Frontend
 * @since 1.0.0
 */
class FrontendManager
{
    /**
     * ContentInjector instance
     *
     * @var ContentInjector
     */
    protected $content_injector;

    /**
     * Assets instance
     *
     * @var Assets
     */
    protected $assets;

    /**
     * FrontendManager constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->set_constants();
        $this->initialize();
    }

    /**
     * Set frontend constants
     *
     * @since 1.0.0
     * @return void
     */
    public function set_constants()
    {
        define('NEXIPILOT_FRONTEND_ASSETS', plugin_dir_url(__FILE__) . 'Assets');
    }

    /**
     * Initialize the NexiPilot Frontend
     *
     * @since 1.0.0
     * @return void
     */
    public function initialize()
    {
        $this->content_injector = new ContentInjector();
        $this->assets = new Assets();
    }
}
