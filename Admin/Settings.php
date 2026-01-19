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

        // Add validation hooks for API keys
        add_action('update_option_postpilot_openai_api_key', array($this, 'validate_openai_key'), 10, 2);
        add_action('update_option_postpilot_claude_api_key', array($this, 'validate_claude_key'), 10, 2);
        add_action('update_option_postpilot_gemini_api_key', array($this, 'validate_gemini_key'), 10, 2);
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
            'postpilot_settings',
            'postpilot_ai_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_openai_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_claude_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_gemini_api_key',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_api_key'),
                'default' => '',
            )
        );

        // Register Model Selection Settings
        register_setting(
            'postpilot_settings',
            'postpilot_openai_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gpt-3.5-turbo',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_claude_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'claude-3-haiku-20240307',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_gemini_model',
            array(
                'type' => 'string',
                'sanitize_callback' => 'sanitize_text_field',
                'default' => 'gemini-2.5-flash',
            )
        );

        // Register Per-Feature Provider Settings (v2.0.0)
        register_setting(
            'postpilot_settings',
            'postpilot_faq_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_summary_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_internal_links_provider',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_ai_provider'),
                'default' => 'openai',
            )
        );

        // Register Feature Settings
        register_setting(
            'postpilot_settings',
            'postpilot_enable_faq',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_enable_summary',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_enable_internal_links',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '1',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_faq_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'after_content',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_faq_default_layout',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_faq_layout'),
                'default' => 'accordion',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_summary_position',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_position'),
                'default' => 'before_content',
            )
        );

        register_setting(
            'postpilot_settings',
            'postpilot_enable_debug_logging',
            array(
                'type' => 'string',
                'sanitize_callback' => array(Sanitizer::class, 'sanitize_checkbox'),
                'default' => '',
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

        add_settings_field(
            'postpilot_gemini_api_key',
            __('Gemini API Key', 'postpilot'),
            array($this, 'render_gemini_api_key_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        // Add Model Selection Fields
        add_settings_field(
            'postpilot_openai_model',
            __('OpenAI Model', 'postpilot'),
            array($this, 'render_openai_model_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        add_settings_field(
            'postpilot_claude_model',
            __('Claude Model', 'postpilot'),
            array($this, 'render_claude_model_field'),
            'postpilot-settings',
            'postpilot_ai_section'
        );

        add_settings_field(
            'postpilot_gemini_model',
            __('Gemini Model', 'postpilot'),
            array($this, 'render_gemini_model_field'),
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
            'postpilot_faq_default_layout',
            __('Default FAQ Display Style', 'postpilot'),
            array($this, 'render_faq_default_layout_field'),
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
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Check if settings were updated (for SweetAlert2 notification)
        $settings_updated = isset($_GET['settings-updated']) && $_GET['settings-updated'] === 'true';
        ?>
        <div class="wrap postpilot-settings-wrap" <?php echo $settings_updated ? 'data-settings-saved="true"' : ''; ?>>
            <!-- Header Section -->
            <div class="postpilot-header">
                <div class="postpilot-header-content">
                    <div class="postpilot-header-title">
                        <span class="postpilot-icon">
                            <svg width="32" height="32" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"
                                    stroke-linecap="round" stroke-linejoin="round" />
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                    stroke-linejoin="round" />
                            </svg>
                        </span>
                        <div>
                            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                            <p class="postpilot-subtitle">
                                <?php esc_html_e('AI-Powered Content Enhancement for WordPress', 'postpilot'); ?>
                            </p>
                        </div>
                    </div>
                    <div class="postpilot-header-actions">
                        <?php
                        // Count configured providers and build tooltip
                        $providers = array(
                            'openai' => 'OpenAI',
                            'claude' => 'Claude',
                            'gemini' => 'Gemini'
                        );
                        $configured = array();
                        $not_configured = array();

                        foreach ($providers as $key => $name) {
                            $status = $this->get_provider_status($key);
                            // Only count 'connected' (verified) as configured, not 'saved' (unverified)
                            if ($status['status'] === 'connected') {
                                $configured[] = $name . ' ' . $status['icon'];
                            } else {
                                $not_configured[] = $name;
                            }
                        }

                        $configured_count = count($configured);
                        $total_providers = count($providers);

                        // Build tooltip
                        $tooltip_parts = array();
                        if (!empty($configured)) {
                            $tooltip_parts[] = __('Connected: ', 'postpilot') . implode(', ', $configured);
                        }
                        if (!empty($not_configured)) {
                            $tooltip_parts[] = __('Not configured: ', 'postpilot') . implode(', ', $not_configured);
                        }
                        $tooltip = implode("\n", $tooltip_parts);

                        // Determine badge text and class
                        if ($configured_count === 0) {
                            $status_text = __('Not Connected', 'postpilot');
                            $status_class = 'postpilot-status-disconnected';
                        } elseif ($configured_count === $total_providers) {
                            $status_text = sprintf(__('%d of %d Connected', 'postpilot'), $configured_count, $total_providers);
                            $status_class = 'postpilot-status-connected';
                        } else {
                            $status_text = sprintf(__('%d of %d Connected', 'postpilot'), $configured_count, $total_providers);
                            $status_class = 'postpilot-status-partial';
                        }
                        ?>
                        <span class="postpilot-status-badge <?php echo esc_attr($status_class); ?>" id="postpilot-api-status"
                            title="<?php echo esc_attr($tooltip); ?>">
                            <span class="status-dot"></span>
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="postpilot-tabs">
                <button type="button" class="postpilot-tab" data-tab="features">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                    </svg>
                    <?php esc_html_e('Features', 'postpilot'); ?>
                </button>
                <button type="button" class="postpilot-tab" data-tab="ai-providers">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                            stroke-linejoin="round" />
                    </svg>
                    <?php esc_html_e('AI Providers', 'postpilot'); ?>
                </button>
                <button type="button" class="postpilot-tab" data-tab="troubleshooting">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2"
                            stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <?php esc_html_e('Troubleshooting', 'postpilot'); ?>
                </button>
            </div>

            <form action="options.php" method="post" class="postpilot-settings-form">
                <?php settings_fields('postpilot_settings'); ?>

                <!-- AI Providers Tab Content -->
                <div class="postpilot-tab-content" id="ai-providers-tab">
                    <div class="postpilot-settings-grid">
                        <!-- OpenAI Provider Card -->
                        <div class="postpilot-card postpilot-provider-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('OpenAI (ChatGPT)', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Configure OpenAI API credentials and model selection', 'postpilot'); ?>
                                    </p>
                                </div>
                                <?php
                                $openai_status = $this->get_provider_status('openai');
                                ?>
                                <span class="postpilot-badge <?php echo esc_attr($openai_status['class']); ?>"
                                    title="<?php echo esc_attr($openai_status['tooltip']); ?>">
                                    <?php echo esc_html($openai_status['icon'] . ' ' . $openai_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilot-card-body">
                                <!-- API Key Field -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_openai_api_key_providers" class="postpilot-label">
                                        <?php esc_html_e('API Key', 'postpilot'); ?>
                                        <?php if (get_option('postpilot_openai_api_key')): ?>
                                            <span class="postpilot-badge postpilot-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'postpilot'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilot-input-group">
                                        <?php
                                        $openai_key = get_option('postpilot_openai_api_key');
                                        $openai_key_decrypted = !empty($openai_key) ? \PostPilot\Helpers\Encryption::decrypt($openai_key) : '';
                                        ?>
                                        <input type="password" name="postpilot_openai_api_key"
                                            id="postpilot_openai_api_key_providers" class="postpilot-input"
                                            value="<?php echo esc_attr($openai_key_decrypted); ?>" placeholder="sk-..." />
                                        <button type="button" class="postpilot-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Get your API key from', 'postpilot'); ?>
                                        <a href="https://platform.openai.com/api-keys" target="_blank"
                                            rel="noopener">platform.openai.com/api-keys</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_openai_model_providers" class="postpilot-label">
                                        <?php esc_html_e('Model', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_openai_model" id="postpilot_openai_model_providers"
                                        class="postpilot-select">
                                        <option value="gpt-4o" <?php selected(get_option('postpilot_openai_model', 'gpt-4o'), 'gpt-4o'); ?>>
                                            GPT-4o
                                        </option>
                                        <option value="gpt-4-turbo" <?php selected(get_option('postpilot_openai_model'), 'gpt-4-turbo'); ?>>
                                            GPT-4 Turbo
                                        </option>
                                        <option value="gpt-3.5-turbo" <?php selected(get_option('postpilot_openai_model'), 'gpt-3.5-turbo'); ?>>
                                            GPT-3.5 Turbo
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use OpenAI', 'postpilot'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Claude Provider Card -->
                        <div class="postpilot-card postpilot-provider-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Anthropic Claude', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Configure Claude API credentials and model selection', 'postpilot'); ?>
                                    </p>
                                </div>
                                <?php
                                $claude_status = $this->get_provider_status('claude');
                                ?>
                                <span class="postpilot-badge <?php echo esc_attr($claude_status['class']); ?>"
                                    title="<?php echo esc_attr($claude_status['tooltip']); ?>">
                                    <?php echo esc_html($claude_status['icon'] . ' ' . $claude_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilot-card-body">
                                <!-- API Key Field -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_claude_api_key_providers" class="postpilot-label">
                                        <?php esc_html_e('API Key', 'postpilot'); ?>
                                        <?php if (get_option('postpilot_claude_api_key')): ?>
                                            <span class="postpilot-badge postpilot-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'postpilot'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilot-input-group">
                                        <?php
                                        $claude_key = get_option('postpilot_claude_api_key');
                                        $claude_key_decrypted = !empty($claude_key) ? \PostPilot\Helpers\Encryption::decrypt($claude_key) : '';
                                        ?>
                                        <input type="password" name="postpilot_claude_api_key"
                                            id="postpilot_claude_api_key_providers" class="postpilot-input"
                                            value="<?php echo esc_attr($claude_key_decrypted); ?>" placeholder="sk-ant-..." />
                                        <button type="button" class="postpilot-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Get your API key from', 'postpilot'); ?>
                                        <a href="https://console.anthropic.com" target="_blank"
                                            rel="noopener">console.anthropic.com</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_claude_model_providers" class="postpilot-label">
                                        <?php esc_html_e('Model', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_claude_model" id="postpilot_claude_model_providers"
                                        class="postpilot-select">
                                        <option value="claude-3-5-sonnet-20241022" <?php selected(get_option('postpilot_claude_model', 'claude-3-5-sonnet-20241022'), 'claude-3-5-sonnet-20241022'); ?>>
                                            Claude 3.5 Sonnet
                                        </option>
                                        <option value="claude-3-opus-20240229" <?php selected(get_option('postpilot_claude_model'), 'claude-3-opus-20240229'); ?>>
                                            Claude 3 Opus
                                        </option>
                                        <option value="claude-3-haiku-20240307" <?php selected(get_option('postpilot_claude_model'), 'claude-3-haiku-20240307'); ?>>
                                            Claude 3 Haiku
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use Claude', 'postpilot'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Gemini Provider Card -->
                        <div class="postpilot-card postpilot-provider-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M13 2L3 14h9l-1 8 10-12h-9l1-8z" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Google Gemini', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Configure Gemini API credentials and model selection', 'postpilot'); ?>
                                    </p>
                                </div>
                                <?php
                                $gemini_status = $this->get_provider_status('gemini');
                                ?>
                                <span class="postpilot-badge <?php echo esc_attr($gemini_status['class']); ?>"
                                    title="<?php echo esc_attr($gemini_status['tooltip']); ?>">
                                    <?php echo esc_html($gemini_status['icon'] . ' ' . $gemini_status['text']); ?>
                                </span>
                            </div>
                            <div class="postpilot-card-body">
                                <!-- API Key Field -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_gemini_api_key_providers" class="postpilot-label">
                                        <?php esc_html_e('API Key', 'postpilot'); ?>
                                        <?php if (get_option('postpilot_gemini_api_key')): ?>
                                            <span class="postpilot-badge postpilot-badge-success">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                    <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Saved', 'postpilot'); ?>
                                            </span>
                                        <?php endif; ?>
                                    </label>
                                    <div class="postpilot-input-group">
                                        <?php
                                        $gemini_key = get_option('postpilot_gemini_api_key');
                                        $gemini_key_decrypted = !empty($gemini_key) ? \PostPilot\Helpers\Encryption::decrypt($gemini_key) : '';
                                        ?>
                                        <input type="password" name="postpilot_gemini_api_key"
                                            id="postpilot_gemini_api_key_providers" class="postpilot-input"
                                            value="<?php echo esc_attr($gemini_key_decrypted); ?>" placeholder="AIza..." />
                                        <button type="button" class="postpilot-btn-icon toggle-password">
                                            <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor"
                                                    stroke-width="2" />
                                                <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                            </svg>
                                        </button>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Get your API key from', 'postpilot'); ?>
                                        <a href="https://aistudio.google.com/app/apikey" target="_blank"
                                            rel="noopener">aistudio.google.com/app/apikey</a>
                                    </p>
                                </div>

                                <!-- Model Selection -->
                                <div class="postpilot-field-group">
                                    <label for="postpilot_gemini_model_providers" class="postpilot-label">
                                        <?php esc_html_e('Model', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_gemini_model" id="postpilot_gemini_model_providers"
                                        class="postpilot-select">
                                        <option value="gemini-2.5-flash" <?php selected(get_option('postpilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.5-flash'); ?>>
                                            Gemini 2.5 Flash (Recommended)
                                        </option>
                                        <option value="gemini-2.5-pro" <?php selected(get_option('postpilot_gemini_model'), 'gemini-2.5-pro'); ?>>
                                            Gemini 2.5 Pro (Most Capable)
                                        </option>
                                        <option value="gemini-2.0-flash" <?php selected(get_option('postpilot_gemini_model'), 'gemini-2.0-flash'); ?>>
                                            Gemini 2.0 Flash
                                        </option>
                                        <option value="gemini-2.5-flash-lite" <?php selected(get_option('postpilot_gemini_model'), 'gemini-2.5-flash-lite'); ?>>
                                            Gemini 2.5 Flash-Lite (Fastest)
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <strong><?php esc_html_e('This model will be used for ALL features that use Gemini', 'postpilot'); ?></strong>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="postpilot-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'postpilot'), 'primary postpilot-btn-primary', 'submit', false); ?>
                    </div>
                </div>

                <!-- Feature AI Settings Tab Content -->
                <div class="postpilot-tab-content" id="feature-settings-tab">
                    <div class="postpilot-settings-grid">
                        <!-- FAQ Generator Card -->
                        <div class="postpilot-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                        <path d="M9.09 9a3 3 0 015.83 1c0 2-3 3-3 3m.08 4h.01" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('FAQ Generator', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Select which AI provider to use for FAQ generation', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="postpilot-card-body">
                                <div class="postpilot-field-group">
                                    <label for="postpilot_faq_provider" class="postpilot-label">
                                        <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_faq_provider" id="postpilot_faq_provider" class="postpilot-select">
                                        <option value="openai" <?php selected(get_option('postpilot_faq_provider', 'openai'), 'openai'); ?>>
                                            OpenAI (ChatGPT)
                                        </option>
                                        <option value="claude" <?php selected(get_option('postpilot_faq_provider'), 'claude'); ?>>
                                            Claude (Anthropic)
                                        </option>
                                        <option value="gemini" <?php selected(get_option('postpilot_faq_provider'), 'gemini'); ?>>
                                            Google Gemini
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Choose which AI provider to use for generating FAQs', 'postpilot'); ?>
                                    </p>
                                </div>

                                <div class="postpilot-field-group">
                                    <label class="postpilot-label">
                                        <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                    </label>
                                    <div class="postpilot-readonly-field" id="faq-model-display">
                                        <span class="postpilot-badge postpilot-badge-info">
                                            <?php
                                            $faq_provider = get_option('postpilot_faq_provider', 'openai');
                                            $faq_model = get_option("postpilot_{$faq_provider}_model", '');
                                            echo esc_html($faq_model ?: __('Not configured', 'postpilot'));
                                            ?>
                                        </span>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Content Summary Card -->
                        <div class="postpilot-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M4 6h16M4 12h16M4 18h7" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Content Summary', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Select which AI provider to use for content summaries', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="postpilot-card-body">
                                <div class="postpilot-field-group">
                                    <label for="postpilot_summary_provider" class="postpilot-label">
                                        <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_summary_provider" id="postpilot_summary_provider"
                                        class="postpilot-select">
                                        <option value="openai" <?php selected(get_option('postpilot_summary_provider', 'openai'), 'openai'); ?>>
                                            OpenAI (ChatGPT)
                                        </option>
                                        <option value="claude" <?php selected(get_option('postpilot_summary_provider'), 'claude'); ?>>
                                            Claude (Anthropic)
                                        </option>
                                        <option value="gemini" <?php selected(get_option('postpilot_summary_provider'), 'gemini'); ?>>
                                            Google Gemini
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Choose which AI provider to use for generating content summaries', 'postpilot'); ?>
                                    </p>
                                </div>

                                <div class="postpilot-field-group">
                                    <label class="postpilot-label">
                                        <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                    </label>
                                    <div class="postpilot-readonly-field" id="summary-model-display">
                                        <span class="postpilot-badge postpilot-badge-info">
                                            <?php
                                            $summary_provider = get_option('postpilot_summary_provider', 'openai');
                                            $summary_model = get_option("postpilot_{$summary_provider}_model", '');
                                            echo esc_html($summary_model ?: __('Not configured', 'postpilot'));
                                            ?>
                                        </span>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>

                        <!-- Smart Internal Links Card -->
                        <div class="postpilot-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M10 13a5 5 0 007.54.54l3-3a5 5 0 00-7.07-7.07l-1.72 1.71" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" />
                                        <path d="M14 11a5 5 0 00-7.54-.54l-3 3a5 5 0 007.07 7.07l1.71-1.71"
                                            stroke="currentColor" stroke-width="2" stroke-linecap="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Smart Internal Links', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Select which AI provider to use for internal link suggestions', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="postpilot-card-body">
                                <div class="postpilot-field-group">
                                    <label for="postpilot_internal_links_provider" class="postpilot-label">
                                        <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                    </label>
                                    <select name="postpilot_internal_links_provider" id="postpilot_internal_links_provider"
                                        class="postpilot-select">
                                        <option value="openai" <?php selected(get_option('postpilot_internal_links_provider', 'openai'), 'openai'); ?>>
                                            OpenAI (ChatGPT)
                                        </option>
                                        <option value="claude" <?php selected(get_option('postpilot_internal_links_provider'), 'claude'); ?>>
                                            Claude (Anthropic)
                                        </option>
                                        <option value="gemini" <?php selected(get_option('postpilot_internal_links_provider'), 'gemini'); ?>>
                                            Google Gemini
                                        </option>
                                    </select>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Choose which AI provider to use for suggesting internal links', 'postpilot'); ?>
                                    </p>
                                </div>

                                <div class="postpilot-field-group">
                                    <label class="postpilot-label">
                                        <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                    </label>
                                    <div class="postpilot-readonly-field" id="links-model-display">
                                        <span class="postpilot-badge postpilot-badge-info">
                                            <?php
                                            $links_provider = get_option('postpilot_internal_links_provider', 'openai');
                                            $links_model = get_option("postpilot_{$links_provider}_model", '');
                                            echo esc_html($links_model ?: __('Not configured', 'postpilot'));
                                            ?>
                                        </span>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="postpilot-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'postpilot'), 'primary postpilot-btn-primary', 'submit', false); ?>
                    </div>
                </div>

                <!-- Features Tab Content -->
                <div class="postpilot-tab-content" id="features-tab">
                    <div class="postpilot-settings-grid">
                        <!-- Feature Settings Card -->
                        <div class="postpilot-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2"
                                            stroke-linecap="round" />
                                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Feature Settings', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Enable or disable AI-powered features', 'postpilot'); ?></p>
                                </div>
                            </div>
                            <div class="postpilot-card-body">
                                <!-- FAQ Generator -->
                                <div class="postpilot-feature-item">
                                    <div class="postpilot-feature-header">
                                        <div class="postpilot-feature-info">
                                            <label class="postpilot-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                                    <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor"
                                                        stroke-width="2" />
                                                    <circle cx="12" cy="17" r="1" fill="currentColor" />
                                                </svg>
                                                <?php esc_html_e('FAQ Generator', 'postpilot'); ?>
                                            </label>
                                            <p class="postpilot-feature-description">
                                                <?php esc_html_e('Automatically generate frequently asked questions based on your content', 'postpilot'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilot-toggle">
                                            <input type="checkbox" name="postpilot_enable_faq" value="1" <?php checked(get_option('postpilot_enable_faq'), '1'); ?> />
                                            <span class="postpilot-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="postpilot-feature-options" id="faq-options">
                                        <!-- AI Provider Selection -->
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_faq_provider" class="postpilot-label-small">
                                                <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_faq_provider" id="postpilot_faq_provider"
                                                class="postpilot-select-small">
                                                <option value="openai" <?php selected(get_option('postpilot_faq_provider', 'openai'), 'openai'); ?>>
                                                    OpenAI (ChatGPT)
                                                </option>
                                                <option value="claude" <?php selected(get_option('postpilot_faq_provider'), 'claude'); ?>>
                                                    Claude (Anthropic)
                                                </option>
                                                <option value="gemini" <?php selected(get_option('postpilot_faq_provider'), 'gemini'); ?>>
                                                    Google Gemini
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilot-field-group" id="faq-model-display">
                                            <label class="postpilot-label-small">
                                                <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                            </label>
                                            <span class="postpilot-badge postpilot-badge-info">
                                                <?php
                                                $faq_provider = get_option('postpilot_faq_provider', 'openai');
                                                $faq_model = get_option("postpilot_{$faq_provider}_model", '');
                                                echo esc_html($faq_model ?: __('Not configured', 'postpilot'));
                                                ?>
                                            </span>
                                            <p class="postpilot-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                            </p>
                                        </div>
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_faq_position" class="postpilot-label-small">
                                                <?php esc_html_e('Display Position', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_faq_position" id="postpilot_faq_position"
                                                class="postpilot-select-small">
                                                <option value="after_content" <?php selected(get_option('postpilot_faq_position', 'after_content'), 'after_content'); ?>>
                                                    <?php esc_html_e('After Content', 'postpilot'); ?>
                                                </option>
                                                <option value="before_content" <?php selected(get_option('postpilot_faq_position'), 'before_content'); ?>>
                                                    <?php esc_html_e('Before Content', 'postpilot'); ?>
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_faq_default_layout" class="postpilot-label-small">
                                                <?php esc_html_e('Default Display Style', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_faq_default_layout" id="postpilot_faq_default_layout"
                                                class="postpilot-select-small">
                                                <option value="accordion" <?php selected(get_option('postpilot_faq_default_layout', 'accordion'), 'accordion'); ?>>
                                                    <?php esc_html_e('Accordion', 'postpilot'); ?>
                                                </option>
                                                <option value="static" <?php selected(get_option('postpilot_faq_default_layout', 'accordion'), 'static'); ?>>
                                                    <?php esc_html_e('Static', 'postpilot'); ?>
                                                </option>
                                            </select>
                                            <p class="description" style="margin-top: 5px; font-size: 12px;">
                                                <?php esc_html_e('Can be overridden per post', 'postpilot'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Content Summary -->
                                <div class="postpilot-feature-item">
                                    <div class="postpilot-feature-header">
                                        <div class="postpilot-feature-info">
                                            <label class="postpilot-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2"
                                                        stroke-linecap="round" />
                                                </svg>
                                                <?php esc_html_e('Content Summary', 'postpilot'); ?>
                                            </label>
                                            <p class="postpilot-feature-description">
                                                <?php esc_html_e('Generate concise summaries of your posts for better engagement', 'postpilot'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilot-toggle">
                                            <input type="checkbox" name="postpilot_enable_summary" value="1" <?php checked(get_option('postpilot_enable_summary'), '1'); ?> />
                                            <span class="postpilot-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <div class="postpilot-feature-options" id="summary-options">
                                        <!-- AI Provider Selection -->
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_summary_provider" class="postpilot-label-small">
                                                <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_summary_provider" id="postpilot_summary_provider"
                                                class="postpilot-select-small">
                                                <option value="openai" <?php selected(get_option('postpilot_summary_provider', 'openai'), 'openai'); ?>>
                                                    OpenAI (ChatGPT)
                                                </option>
                                                <option value="claude" <?php selected(get_option('postpilot_summary_provider'), 'claude'); ?>>
                                                    Claude (Anthropic)
                                                </option>
                                                <option value="gemini" <?php selected(get_option('postpilot_summary_provider'), 'gemini'); ?>>
                                                    Google Gemini
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilot-field-group" id="summary-model-display">
                                            <label class="postpilot-label-small">
                                                <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                            </label>
                                            <span class="postpilot-badge postpilot-badge-info">
                                                <?php
                                                $summary_provider = get_option('postpilot_summary_provider', 'openai');
                                                $summary_model = get_option("postpilot_{$summary_provider}_model", '');
                                                echo esc_html($summary_model ?: __('Not configured', 'postpilot'));
                                                ?>
                                            </span>
                                            <p class="postpilot-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                            </p>
                                        </div>
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_summary_position" class="postpilot-label-small">
                                                <?php esc_html_e('Display Position', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_summary_position" id="postpilot_summary_position"
                                                class="postpilot-select-small">
                                                <option value="before_content" <?php selected(get_option('postpilot_summary_position', 'before_content'), 'before_content'); ?>>
                                                    <?php esc_html_e('Before Content', 'postpilot'); ?>
                                                </option>
                                                <option value="after_content" <?php selected(get_option('postpilot_summary_position'), 'after_content'); ?>>
                                                    <?php esc_html_e('After Content', 'postpilot'); ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>
                                </div>

                                <!-- Smart Internal Links -->
                                <div class="postpilot-feature-item">
                                    <div class="postpilot-feature-header">
                                        <div class="postpilot-feature-info">
                                            <label class="postpilot-feature-title">
                                                <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                                                    <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71"
                                                        stroke="currentColor" stroke-width="2" />
                                                    <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71"
                                                        stroke="currentColor" stroke-width="2" />
                                                </svg>
                                                <?php esc_html_e('Smart Internal Links', 'postpilot'); ?>
                                            </label>
                                            <p class="postpilot-feature-description">
                                                <?php esc_html_e('Automatically suggest relevant internal links to improve SEO', 'postpilot'); ?>
                                            </p>
                                        </div>
                                        <label class="postpilot-toggle">
                                            <input type="checkbox" name="postpilot_enable_internal_links" value="1" <?php checked(get_option('postpilot_enable_internal_links'), '1'); ?> />
                                            <span class="postpilot-toggle-slider"></span>
                                        </label>
                                    </div>
                                    <!-- AI Provider Selection for Internal Links -->
                                    <div class="postpilot-feature-options" id="links-options" style="margin-top: 15px;">
                                        <div class="postpilot-field-group">
                                            <label for="postpilot_internal_links_provider" class="postpilot-label-small">
                                                <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                            </label>
                                            <select name="postpilot_internal_links_provider"
                                                id="postpilot_internal_links_provider" class="postpilot-select-small">
                                                <option value="openai" <?php selected(get_option('postpilot_internal_links_provider', 'openai'), 'openai'); ?>>
                                                    OpenAI (ChatGPT)
                                                </option>
                                                <option value="claude" <?php selected(get_option('postpilot_internal_links_provider'), 'claude'); ?>>
                                                    Claude (Anthropic)
                                                </option>
                                                <option value="gemini" <?php selected(get_option('postpilot_internal_links_provider'), 'gemini'); ?>>
                                                    Google Gemini
                                                </option>
                                            </select>
                                        </div>
                                        <div class="postpilot-field-group" id="links-model-display">
                                            <label class="postpilot-label-small">
                                                <?php esc_html_e('Model in Use', 'postpilot'); ?>
                                            </label>
                                            <span class="postpilot-badge postpilot-badge-info">
                                                <?php
                                                $links_provider = get_option('postpilot_internal_links_provider', 'openai');
                                                $links_model = get_option("postpilot_{$links_provider}_model", '');
                                                echo esc_html($links_model ?: __('Not configured', 'postpilot'));
                                                ?>
                                            </span>
                                            <p class="postpilot-field-description">
                                                <?php esc_html_e('Model is configured in the AI Providers tab', 'postpilot'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Features Tab -->

                    <div class="postpilot-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'postpilot'), 'primary postpilot-btn-primary', 'submit', false); ?>
                    </div>
                </div>

                <!-- Troubleshooting Tab Content -->
                <div class="postpilot-tab-content" id="troubleshooting-tab">
                    <div class="postpilot-settings-grid">
                        <!-- Debug Logging Card -->
                        <div class="postpilot-card">
                            <div class="postpilot-card-header">
                                <div class="postpilot-card-icon">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor"
                                            stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                    </svg>
                                </div>
                                <div>
                                    <h2><?php esc_html_e('Debug Logging', 'postpilot'); ?></h2>
                                    <p><?php esc_html_e('Enable debug logging for troubleshooting API issues', 'postpilot'); ?>
                                    </p>
                                </div>
                            </div>
                            <div class="postpilot-card-body">
                                <!-- Debug Logging Toggle -->
                                <div class="postpilot-field-group">
                                    <label class="postpilot-label">
                                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;">
                                            <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor"
                                                stroke-width="2" stroke-linecap="round" stroke-linejoin="round" />
                                        </svg>
                                        <?php esc_html_e('Enable Debug Logging', 'postpilot'); ?>
                                    </label>
                                    <div class="postpilot-toggle-wrapper">
                                        <label class="postpilot-toggle">
                                            <input type="checkbox" name="postpilot_enable_debug_logging" value="1" <?php checked(get_option('postpilot_enable_debug_logging'), '1'); ?> />
                                            <span class="postpilot-toggle-slider"></span>
                                        </label>
                                        <span class="postpilot-toggle-label">
                                            <?php esc_html_e('Log all API requests and responses to debug.log', 'postpilot'); ?>
                                        </span>
                                    </div>
                                    <p class="postpilot-field-description">
                                        <?php esc_html_e('When enabled, all API requests and responses will be logged to debug.log. This is useful for troubleshooting API connection issues. Keep this disabled in production environments to avoid filling up your debug log.', 'postpilot'); ?>
                                    </p>

                                    <div class="postpilot-info-box" style="margin-top: 16px;">
                                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" style="flex-shrink: 0;">
                                            <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2" />
                                            <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2"
                                                stroke-linecap="round" />
                                        </svg>
                                        <div>
                                            <strong><?php esc_html_e('Debug Log Location:', 'postpilot'); ?></strong>
                                            <p style="margin: 4px 0 0 0; font-size: 13px; opacity: 0.8;">
                                                <?php echo esc_html(WP_CONTENT_DIR . '/debug.log'); ?>
                                            </p>
                                            <p style="margin: 8px 0 0 0; font-size: 13px; opacity: 0.8;">
                                                <?php esc_html_e('Make sure WP_DEBUG and WP_DEBUG_LOG are enabled in wp-config.php for debug logging to work.', 'postpilot'); ?>
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!-- End Troubleshooting Tab -->

                    <div class="postpilot-submit-wrapper">
                        <?php submit_button(__('Save Settings', 'postpilot'), 'primary postpilot-btn-primary', 'submit', false); ?>
                    </div>
                </div>

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
        $has_key = !empty($value);
        ?>
        <input type="text" name="postpilot_openai_api_key" id="postpilot_openai_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your OpenAI API key', 'postpilot'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
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
        $has_key = !empty($value);
        ?>
        <input type="text" name="postpilot_claude_api_key" id="postpilot_claude_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your Claude API key', 'postpilot'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
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
            <input type="checkbox" name="postpilot_enable_faq" value="1" <?php checked($value, '1'); ?> />
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
            <input type="checkbox" name="postpilot_enable_summary" value="1" <?php checked($value, '1'); ?> />
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
     * Render FAQ default layout field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_faq_default_layout_field()
    {
        $value = get_option('postpilot_faq_default_layout', 'accordion');
        ?>
        <select name="postpilot_faq_default_layout" id="postpilot_faq_default_layout">
            <option value="accordion" <?php selected($value, 'accordion'); ?>>
                <?php esc_html_e('Accordion', 'postpilot'); ?>
            </option>
            <option value="static" <?php selected($value, 'static'); ?>>
                <?php esc_html_e('Static', 'postpilot'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Choose the default layout for FAQ display. This can be overridden per post.', 'postpilot'); ?>
        </p>
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
            <input type="checkbox" name="postpilot_enable_internal_links" value="1" <?php checked($value, '1'); ?> />
            <?php esc_html_e('Enable AI-powered smart internal linking', 'postpilot'); ?>
        </label>
        <?php
    }

    /**
     * Render Gemini API key field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_gemini_api_key_field()
    {
        $value = get_option('postpilot_gemini_api_key', '');
        $has_key = !empty($value);
        ?>
        <input type="text" name="postpilot_gemini_api_key" id="postpilot_gemini_api_key" value="<?php echo esc_attr($value); ?>"
            class="regular-text"
            placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your Gemini API key', 'postpilot'); ?>" />
        <?php if ($has_key): ?>
            <span class="dashicons dashicons-yes-alt" style="color: #46b450; margin-left: 5px;"></span>
        <?php endif; ?>
        <p class="description">
            <?php
            printf(
                /* translators: %s: Google AI Studio URL */
                esc_html__('Get your API key from %s', 'postpilot'),
                '<a href="https://aistudio.google.com/app/apikey" target="_blank">Google AI Studio</a>'
            );
            ?>
        </p>
        <?php
    }

    /**
     * Render OpenAI model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_openai_model_field()
    {
        $value = get_option('postpilot_openai_model', 'gpt-3.5-turbo');
        ?>
        <select name="postpilot_openai_model" id="postpilot_openai_model">
            <option value="gpt-4o" <?php selected($value, 'gpt-4o'); ?>>
                <?php esc_html_e('GPT-4o (Most Capable)', 'postpilot'); ?>
            </option>
            <option value="gpt-4o-mini" <?php selected($value, 'gpt-4o-mini'); ?>>
                <?php esc_html_e('GPT-4o Mini (Balanced)', 'postpilot'); ?>
            </option>
            <option value="gpt-4-turbo" <?php selected($value, 'gpt-4-turbo'); ?>>
                <?php esc_html_e('GPT-4 Turbo', 'postpilot'); ?>
            </option>
            <option value="gpt-3.5-turbo" <?php selected($value, 'gpt-3.5-turbo'); ?>>
                <?php esc_html_e('GPT-3.5 Turbo (Fastest)', 'postpilot'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the OpenAI model to use for content generation.', 'postpilot'); ?>
        </p>
        <?php
    }

    /**
     * Render Claude model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_claude_model_field()
    {
        $value = get_option('postpilot_claude_model', 'claude-3-haiku-20240307');
        ?>
        <select name="postpilot_claude_model" id="postpilot_claude_model">
            <option value="claude-3-5-sonnet-20241022" <?php selected($value, 'claude-3-5-sonnet-20241022'); ?>>
                <?php esc_html_e('Claude 3.5 Sonnet (Most Capable)', 'postpilot'); ?>
            </option>
            <option value="claude-3-opus-20240229" <?php selected($value, 'claude-3-opus-20240229'); ?>>
                <?php esc_html_e('Claude 3 Opus', 'postpilot'); ?>
            </option>
            <option value="claude-3-haiku-20240307" <?php selected($value, 'claude-3-haiku-20240307'); ?>>
                <?php esc_html_e('Claude 3 Haiku (Fastest)', 'postpilot'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the Claude model to use for content generation.', 'postpilot'); ?>
        </p>
        <?php
    }

    /**
     * Render Gemini model field
     *
     * @since 1.0.0
     * @return void
     */
    public function render_gemini_model_field()
    {
        $value = get_option('postpilot_gemini_model', 'gemini-1.5-flash');
        ?>
        <select name="postpilot_gemini_model" id="postpilot_gemini_model">
            <option value="gemini-1.5-pro" <?php selected($value, 'gemini-1.5-pro'); ?>>
                <?php esc_html_e('Gemini 1.5 Pro (Most Capable)', 'postpilot'); ?>
            </option>
            <option value="gemini-1.5-flash" <?php selected($value, 'gemini-1.5-flash'); ?>>
                <?php esc_html_e('Gemini 1.5 Flash (Balanced)', 'postpilot'); ?>
            </option>
            <option value="gemini-1.0-pro" <?php selected($value, 'gemini-1.0-pro'); ?>>
                <?php esc_html_e('Gemini 1.0 Pro', 'postpilot'); ?>
            </option>
        </select>
        <p class="description">
            <?php esc_html_e('Select the Gemini model to use for content generation.', 'postpilot'); ?>
        </p>
        <?php
    }

    /**
     * Get provider connection status
     *
     * Returns status information for a given AI provider with 4 states:
     * - missing: No API key configured (neutral, gray)
     * - saved: API key exists but not validated (yellow)
     * - invalid: API key validation failed (red)
     * - connected: API key validated successfully (green)
     *
     * @since 1.0.0
     * @param string $provider_key Provider key (openai, claude, gemini)
     * @return array Status array with icon, text, class, and tooltip
     */
    private function get_provider_status($provider_key)
    {
        $api_key = get_option("postpilot_{$provider_key}_api_key");

        // State 1: Missing Key (gray, neutral)
        if (empty($api_key)) {
            return array(
                'status' => 'missing',
                'icon' => '⚪',
                'text' => __('Missing Key', 'postpilot'),
                'class' => 'postpilot-status-gray',
                'tooltip' => __('No API key configured. Add one if you plan to use this provider.', 'postpilot')
            );
        }

        // Check if we have a validation result stored
        $validation_result = get_transient("postpilot_{$provider_key}_validation");

        // State 2: Key Saved (Not Verified) - yellow
        if ($validation_result === false) {
            return array(
                'status' => 'saved',
                'icon' => '🟡',
                'text' => __('Key Saved', 'postpilot'),
                'class' => 'postpilot-status-yellow',
                'tooltip' => __('API key saved but not verified. Save settings to validate.', 'postpilot')
            );
        }

        // State 3: Invalid Key - red
        if (is_wp_error($validation_result)) {
            return array(
                'status' => 'invalid',
                'icon' => '❌',
                'text' => __('Invalid Key', 'postpilot'),
                'class' => 'postpilot-status-red',
                'tooltip' => $validation_result->get_error_message()
            );
        }

        // State 4: Connected - green
        return array(
            'status' => 'connected',
            'icon' => '✅',
            'text' => __('Connected', 'postpilot'),
            'class' => 'postpilot-status-green',
            'tooltip' => __('API key verified and working', 'postpilot')
        );
    }

    /**
     * Validate OpenAI API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_openai_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('postpilot_openai_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilot\Helpers\Encryption::decrypt($new_value);

        $openai = new \PostPilot\AI\OpenAI($decrypted_key);
        $result = $openai->validate_api_key($decrypted_key);

        set_transient('postpilot_openai_validation', $result, WEEK_IN_SECONDS);
    }

    /**
     * Validate Claude API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_claude_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('postpilot_claude_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilot\Helpers\Encryption::decrypt($new_value);

        $claude = new \PostPilot\AI\Claude($decrypted_key);
        $result = $claude->validate_api_key($decrypted_key);

        set_transient('postpilot_claude_validation', $result, WEEK_IN_SECONDS);
    }

    /**
     * Validate Gemini API key
     *
     * @since 1.0.0
     * @param mixed $old_value Old API key value
     * @param mixed $new_value New API key value
     * @return void
     */
    public function validate_gemini_key($old_value, $new_value)
    {
        if (empty($new_value)) {
            delete_transient('postpilot_gemini_validation');
            return;
        }

        // Only validate if key changed
        if ($old_value === $new_value) {
            return;
        }

        // Decrypt the API key before validation (keys are stored encrypted)
        $decrypted_key = \PostPilot\Helpers\Encryption::decrypt($new_value);

        $gemini = new \PostPilot\AI\Gemini($decrypted_key);
        $result = $gemini->validate_api_key($decrypted_key);

        set_transient('postpilot_gemini_validation', $result, WEEK_IN_SECONDS);
    }
}
