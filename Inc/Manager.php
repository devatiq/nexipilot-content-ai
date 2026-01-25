<?php
/**
 * Manager.php
 *
 * This file contains the Manager class, which is responsible for handling
 * the initialization of the required configurations and functionalities
 * for the PostPilot plugin.
 *
 * @package PostPilotAI\Inc
 * @since 1.0.0
 */

namespace PostPilotAI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilotAI\Admin\AdminManager;
use PostPilotAI\Frontend\FrontendManager;

/**
 * The manager class for PostPilot
 *
 * This class handles the initialization of the required configurations and functionalities
 * for the PostPilot plugin. It orchestrates the Admin and Frontend components.
 *
 * @package PostPilotAI\Inc
 * @since 1.0.0
 */
class Manager
{
    /**
     * Admin Manager instance
     *
     * @var AdminManager
     */
    protected $admin_manager;

    /**
     * Frontend Manager instance
     *
     * @var FrontendManager
     */
    protected $frontend_manager;

    /**
     * Constructor for the Manager class
     *
     * This method initializes the PostPilot Manager by calling the init method.
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        $this->init();
    }

    /**
     * Initiate the PostPilot Manager
     *
     * This method initializes the Admin and Frontend managers.
     *
     * @since 1.0.0
     * @return void
     */
    public function init()
    {
        $this->admin_manager = new AdminManager();
        $this->frontend_manager = new FrontendManager();
    }
}
