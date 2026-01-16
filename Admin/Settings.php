<?php
/**
 * Settings.php
 *
 * WordPress Settings API implementation for PostPilot.
 *
 * @package PostPilot\Admin
 * @since 1.0.0
 */

namespace PostPilot\Admin;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\Helpers\Sanitizer;
use PostPilot\AI\Manager as AIManager;

/**
 * Settings Class
 *
 * Handles all plugin settings using WordPress Settings API.
 *
 * @package PostPilot\Admin
 * @since 1.0.0
 */
class Settings
{
    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
    }

    /**
     * Add admin menu
     *
     * @since 1.0.0
     * @return void
     */
    public function add_admin_menu()
    {
        add_menu_page(
            __('PostPilot AI', 'postpilot'),
            __('PostPilot AI', 'postpilot'),
            'manage_options',
            'postpilot-settings',
            array($this, 'render_settings_page'),
            'dashicons-superhero-alt',
            30
        );
    }

    /**
     * Register settings
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings()
    {
        // Register AI Provider Settings
        register_setting(
            'postpilot_ai_settings',
            'postpilot_ai_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'postpilot_ai_settings',
            'postpilot_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'postpilot_ai_settings',
            'postpilot_claude_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        // Register Feature Settings
        register_setting(
            'postpilot_feature_settings',
            'postpilot_enable_faq',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_feature_settings',
            'postpilot_enable_summary',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_feature_settings',
            'postpilot_enable_internal_links',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_feature_settings',
            'postpilot_faq_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'after_content',
            )
        );

        register_setting(
            'postpilot_feature_settings',
            'postpilot_summary_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'before_content',
            )
        );

        // Add settings sections
        add_settings_section(
            'postpilot_ai_section',
            __('AI Provider Configuration', 'postpilot'),
            array($this, 'render_ai_section_description'),
            'postpilot-settings'
        );

        add_settings_section(
            'postpilot_features_section',
            __('Feature Settings', 'postpilot'),
            array($this, 'render_features_section_description'),
            'postpilot-settings'
        );

        // Add settings fields - AI Provider
        add_settings_field(
            'postpilot_ai_provider',
            __('AI Provider', 'postpilot'),
            array($this, 'render_ai_provider_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        add_settings_field(
            'postpilot_openai_api_key',
            __('OpenAI API Key', 'postpilot'),
            array($this, 'render_openai_api_key_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        add_settings_field(
            'postpilot_claude_api_key',
            __('Claude API Key', 'postpilot'),
            array($this, 'render_claude_api_key_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        // Add settings fields - Features
        add_settings_field(
            'postpilot_enable_faq',
            __('Enable FAQ Generator', 'postpilot'),
            array($this, 'render_enable_faq_field'),
            'postpilot-settings',
            'postpilot_features_section'
        );

        add_settings_field(
            'postpilot_faq_position',
            __('FAQ Position', 'postpilot'),
            array($this, 'render_faq_position_field'),
            'postpilot-settings',
            'postpilot_features_section'
        );

        add_settings_field(
            'postpilot_enable_summary',
            __('Enable Content Summary', 'postpilot'),
            array($this, 'render_enable_summary_field'),
            'postpilot-settings',
            'postpilot_features_section'
        );

        add_settings_field(
            'postpilot_summary_position',
            __('Summary Position', 'postpilot'),
            array($this, 'render_summary_position_field'),
            'postpilot-settings',
            'postpilot_features_section'
        );

        add_settings_field(
            'postpilot_enable_internal_links',
            __('Enable Smart Internal Links', 'postpilot'),
            array($this, 'render_enable_internal_links_field'),
            'postpilot-settings',
            'postpilot_features_section'
        );
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        // Handle settings saved message
        if (isset($_GET['settings-updated'])) {
            add_settings_error(
                'postpilot_messages',
                'postpilot_message',
                __('Settings Saved', 'postpilot'),
                'updated'
            );
        }

        settings_errors('postpilot_messages');
        ?>
        <div class="wrap postpilot-settings-wrap">
            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
            
            <div class="postpilot-settings-header">
                <p><?php esc_html_e('Configure AI-powered features for your WordPress posts.', 'postpilot'); ?></p>
            </div>

            <form action="options.php" method="post">
                <?php
                settings_fields('postpilot_ai_settings');
                settings_fields('postpilot_feature_settings');
                do_settings_sections('postpilot-settings');
                submit_button(__('Save Settings', 'postpilot'));
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render AI section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_ai_section_description()
    {
        echo '<p>' . esc_html__('Configure your AI provider and API credentials.', 'postpilot') . '</p>';
    }

    /**
     * Render features section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_features_section_description()
    {
        echo '<p>' . esc_html__('Enable or disable AI-powered features and configure their display.', 'postpilot') . '</p>';
    }

    /**
     * Render AI provider field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_ai_provider_field()
    {
        $value = get_option('postpilot_ai_provider', 'openai');
        ?>
        <select name="postpilot_ai_provider" id="postpilot_ai_provider">
            <option value="openai" <?php selected($value, 'openai'); ?>>
                <?php esc_html_e('OpenAI (ChatGPT)', 'postpilot'); ?>
            </option>
            <option value="claude" <?php selected($value, 'claude'); ?>>
                <?php esc_html_e('Claude (Anthropic)', 'postpilot'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select your preferred AI provider.', 'postpilot'); ?>
        </p>
        <?php
    }

    /**
     * Render OpenAI API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_openai_api_key_field()
    {
        $value = get_option('postpilot_openai_api_key', '');
        ?>
        <input type="password" 
               name="postpilot_openai_api_key" 
               id="postpilot_openai_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php
            printf(
                /* translators: %s: OpenAI API URL */
                esc_html__('Get your API key from %s', 'postpilot'),
                '<a href="https://platform.openai.com/api-keys" target="_blank">OpenAI</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render Claude API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_claude_api_key_field()
    {
        $value = get_option('postpilot_claude_api_key', '');
        ?>
        <input type="password" 
               name="postpilot_claude_api_key" 
               id="postpilot_claude_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" />
        <p class="description">
            <?php
            printf(
                /* translators: %s: Anthropic API URL */
                esc_html__('Get your API key from %s', 'postpilot'),
                '<a href="https://console.anthropic.com/" target="_blank">Anthropic</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render enable FAQ field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_faq_field()
    {
        $value = get_option('postpilot_enable_faq', '1');
        ?>
        <label>
            <input type="checkbox" 
                   name="postpilot_enable_faq" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-generated FAQ section', 'postpilot'); ?>
        </label>
        <?php
    }

    /**
     * Render FAQ position field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_faq_position_field()
    {
        $value = get_option('postpilot_faq_position', 'after_content');
        ?>
        <select name="postpilot_faq_position" id="postpilot_faq_position">
            <option value="before_content" <?php selected($value, 'before_content'); ?>>
                <?php esc_html_e('Before Content', 'postpilot'); ?>
            </option>
            <option value="after_content" <?php selected($value, 'after_content'); ?>>
                <?php esc_html_e('After Content', 'postpilot'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render enable summary field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_summary_field()
    {
        $value = get_option('postpilot_enable_summary', '1');
        ?>
        <label>
            <input type="checkbox" 
                   name="postpilot_enable_summary" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-generated content summary', 'postpilot'); ?>
        </label>
        <?php
    }

    /**
     * Render summary position field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_summary_position_field()
    {
        $value = get_option('postpilot_summary_position', 'before_content');
        ?>
        <select name="postpilot_summary_position" id="postpilot_summary_position">
            <option value="before_content" <?php selected($value, 'before_content'); ?>>
                <?php esc_html_e('Before Content', 'postpilot'); ?>
            </option>
            <option value="after_content" <?php selected($value, 'after_content'); ?>>
                <?php esc_html_e('After Content', 'postpilot'); ?>
            </option>
        </select>
        <?php
    }

    /**
     * Render enable internal links field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_enable_internal_links_field()
    {
        $value = get_option('postpilot_enable_internal_links', '1');
        ?>
        <label>
            <input type="checkbox" 
                   name="postpilot_enable_internal_links" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-powered smart internal linking', 'postpilot'); ?>
        </label>
        <?php
    }
}
