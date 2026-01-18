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
                                <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                        </span>
                        <div>
                            <h1><?php echo esc_html(get_admin_page_title()); ?></h1>
                            <p class="postpilot-subtitle"><?php esc_html_e('AI-Powered Content Enhancement for WordPress', 'postpilot'); ?></p>
                        </div>
                    </div>
                    <div class="postpilot-header-actions">
                        <?php
                        // Check if AI provider is configured and test connection
                        $ai_manager = new \PostPilot\AI\Manager();
                        $is_connected = false;
                        $status_text = __('API Not Connected', 'postpilot');
                        $status_class = 'postpilot-status-disconnected';
                        
                        if ($ai_manager->is_provider_available()) {
                            // Test actual API connection
                            $test_result = $ai_manager->test_api_connection();
                            
                            if (!is_wp_error($test_result)) {
                                // API key is valid and working
                                $is_connected = true;
                                $status_text = __('API Connected', 'postpilot');
                                $status_class = 'postpilot-status-connected';
                            } else {
                                // API key exists but has an error
                                $error_message = $test_result->get_error_message();
                                
                                if (strpos($error_message, 'quota') !== false) {
                                    // Key is valid but quota exceeded
                                    $status_text = __('Connected - Quota Exceeded', 'postpilot');
                                    $status_class = 'postpilot-status-warning';
                                } elseif (strpos($error_message, 'invalid') !== false || strpos($error_message, 'Incorrect') !== false) {
                                    $status_text = __('Invalid API Key', 'postpilot');
                                } else {
                                    $status_text = __('Connection Failed', 'postpilot');
                                }
                            }
                        }
                        ?>
                        <span class="postpilot-status-badge <?php echo esc_attr($status_class); ?>" id="postpilot-api-status">
                            <span class="status-dot"></span>
                            <?php echo esc_html($status_text); ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Tab Navigation -->
            <div class="postpilot-tabs">
                <button type="button" class="postpilot-tab" data-tab="general">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
                        <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
                        <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <?php esc_html_e('General', 'postpilot'); ?>
                </button>
                <button type="button" class="postpilot-tab" data-tab="features">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                    </svg>
                    <?php esc_html_e('Features', 'postpilot'); ?>
                </button>
                <button type="button" class="postpilot-tab" data-tab="troubleshooting">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none">
                        <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                    </svg>
                    <?php esc_html_e('Troubleshooting', 'postpilot'); ?>
                </button>
            </div>

            <form action="options.php" method="post" class="postpilot-settings-form">
                <?php settings_fields('postpilot_settings'); ?>
                
                <!-- General Tab Content -->
                <div class="postpilot-tab-content" id="general-tab">
                <div class="postpilot-settings-grid">
                    <!-- AI Provider Configuration Card -->
                    <div class="postpilot-card">
                        <div class="postpilot-card-header">
                            <div class="postpilot-card-icon">
                                <svg width="24" height="24" viewBox="0 0 24 24" fill="none">
                                    <path d="M12 2L2 7L12 12L22 7L12 2Z" stroke="currentColor" stroke-width="2"/>
                                    <path d="M2 17L12 22L22 17" stroke="currentColor" stroke-width="2"/>
                                    <path d="M2 12L12 17L22 12" stroke="currentColor" stroke-width="2"/>
                                </svg>
                            </div>
                            <div>
                                <h2><?php esc_html_e('AI Provider Configuration', 'postpilot'); ?></h2>
                                <p><?php esc_html_e('Configure your AI provider and API credentials', 'postpilot'); ?></p>
                            </div>
                        </div>
                        <div class="postpilot-card-body">
                            <div class="postpilot-field-group">
                                <label for="postpilot_ai_provider" class="postpilot-label">
                                    <?php esc_html_e('AI Provider', 'postpilot'); ?>
                                </label>
                                <select name="postpilot_ai_provider" id="postpilot_ai_provider" class="postpilot-select">
                                    <option value="openai" <?php selected(get_option('postpilot_ai_provider', 'openai'), 'openai'); ?>>
                                        OpenAI (ChatGPT)
                                    </option>
                                    <option value="claude" <?php selected(get_option('postpilot_ai_provider'), 'claude'); ?>>
                                        Claude (Anthropic)
                                    </option>
                                    <option value="gemini" <?php selected(get_option('postpilot_ai_provider'), 'gemini'); ?>>
                                        Google Gemini
                                    </option>
                                </select>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Choose your preferred AI provider', 'postpilot'); ?>
                                </p>
                            </div>

                            <div class="postpilot-field-group postpilot-api-key-field" id="openai-api-key-field">
                                <label for="postpilot_openai_api_key" class="postpilot-label">
                                    <?php esc_html_e('OpenAI API Key', 'postpilot'); ?>
                                    <?php if (get_option('postpilot_openai_api_key')): ?>
                                        <span class="postpilot-badge postpilot-badge-success">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
                                    <input 
                                        type="password" 
                                        name="postpilot_openai_api_key" 
                                        id="postpilot_openai_api_key"
                                        class="postpilot-input"
                                        value="<?php echo esc_attr($openai_key_decrypted); ?>"
                                        placeholder="sk-..."
                                    />
                                    <button type="button" class="postpilot-btn-icon" id="toggle-openai-key">
                                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Get your API key from', 'postpilot'); ?> 
                                    <a href="https://platform.openai.com/api-keys" target="_blank" rel="noopener">platform.openai.com/api-keys</a>
                                </p>
                            </div>

                            <div class="postpilot-field-group postpilot-api-key-field" id="claude-api-key-field" style="display: none;">
                                <label for="postpilot_claude_api_key" class="postpilot-label">
                                    <?php esc_html_e('Claude API Key', 'postpilot'); ?>
                                    <?php if (get_option('postpilot_claude_api_key')): ?>
                                        <span class="postpilot-badge postpilot-badge-success">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
                                    <input 
                                        type="password" 
                                        name="postpilot_claude_api_key" 
                                        id="postpilot_claude_api_key"
                                        class="postpilot-input"
                                        value="<?php echo esc_attr($claude_key_decrypted); ?>"
                                        placeholder="sk-ant-..."
                                    />
                                    <button type="button" class="postpilot-btn-icon" id="toggle-claude-key">
                                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Get your API key from', 'postpilot'); ?> 
                                    <a href="https://console.anthropic.com/" target="_blank" rel="noopener">console.anthropic.com</a>
                                </p>
                            </div>

                            <div class="postpilot-field-group postpilot-api-key-field" id="gemini-api-key-field" style="display: none;">
                                <label for="postpilot_gemini_api_key" class="postpilot-label">
                                    <?php esc_html_e('Gemini API Key', 'postpilot'); ?>
                                    <?php if (get_option('postpilot_gemini_api_key')): ?>
                                        <span class="postpilot-badge postpilot-badge-success">
                                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none">
                                                <path d="M20 6L9 17L4 12" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
                                    <input 
                                        type="password" 
                                        name="postpilot_gemini_api_key" 
                                        id="postpilot_gemini_api_key"
                                        class="postpilot-input"
                                        value="<?php echo esc_attr($gemini_key_decrypted); ?>"
                                        placeholder="AIza..."
                                    />
                                    <button type="button" class="postpilot-btn-icon" id="toggle-gemini-key">
                                        <svg class="eye-icon" width="20" height="20" viewBox="0 0 24 24" fill="none">
                                            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z" stroke="currentColor" stroke-width="2"/>
                                            <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
                                        </svg>
                                    </button>
                                </div>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Get your API key from', 'postpilot'); ?> 
                                    <a href="https://aistudio.google.com/app/apikey" target="_blank" rel="noopener">Google AI Studio</a>
                                </p>
                            </div>

                            <!-- Model Selection Fields -->
                            <div class="postpilot-field-group" id="openai-model-field">
                                <label for="postpilot_openai_model" class="postpilot-label">
                                    <?php esc_html_e('OpenAI Model', 'postpilot'); ?>
                                </label>
                                <select name="postpilot_openai_model" id="postpilot_openai_model" class="postpilot-select">
                                    <option value="gpt-4o" <?php selected(get_option('postpilot_openai_model', 'gpt-3.5-turbo'), 'gpt-4o'); ?>>
                                        <?php esc_html_e('GPT-4o (Most Capable)', 'postpilot'); ?>
                                    </option>
                                    <option value="gpt-4o-mini" <?php selected(get_option('postpilot_openai_model', 'gpt-3.5-turbo'), 'gpt-4o-mini'); ?>>
                                        <?php esc_html_e('GPT-4o Mini (Balanced)', 'postpilot'); ?>
                                    </option>
                                    <option value="gpt-4-turbo" <?php selected(get_option('postpilot_openai_model', 'gpt-3.5-turbo'), 'gpt-4-turbo'); ?>>
                                        <?php esc_html_e('GPT-4 Turbo', 'postpilot'); ?>
                                    </option>
                                    <option value="gpt-3.5-turbo" <?php selected(get_option('postpilot_openai_model', 'gpt-3.5-turbo'), 'gpt-3.5-turbo'); ?>>
                                        <?php esc_html_e('GPT-3.5 Turbo (Fastest)', 'postpilot'); ?>
                                    </option>
                                </select>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Select the OpenAI model to use for content generation.', 'postpilot'); ?>
                                </p>
                            </div>

                            <div class="postpilot-field-group" id="claude-model-field" style="display: none;">
                                <label for="postpilot_claude_model" class="postpilot-label">
                                    <?php esc_html_e('Claude Model', 'postpilot'); ?>
                                </label>
                                <select name="postpilot_claude_model" id="postpilot_claude_model" class="postpilot-select">
                                    <option value="claude-3-5-sonnet-20241022" <?php selected(get_option('postpilot_claude_model', 'claude-3-haiku-20240307'), 'claude-3-5-sonnet-20241022'); ?>>
                                        <?php esc_html_e('Claude 3.5 Sonnet (Most Capable)', 'postpilot'); ?>
                                    </option>
                                    <option value="claude-3-opus-20240229" <?php selected(get_option('postpilot_claude_model', 'claude-3-haiku-20240307'), 'claude-3-opus-20240229'); ?>>
                                        <?php esc_html_e('Claude 3 Opus', 'postpilot'); ?>
                                    </option>
                                    <option value="claude-3-haiku-20240307" <?php selected(get_option('postpilot_claude_model', 'claude-3-haiku-20240307'), 'claude-3-haiku-20240307'); ?>>
                                        <?php esc_html_e('Claude 3 Haiku (Fastest)', 'postpilot'); ?>
                                    </option>
                                </select>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Select the Claude model to use for content generation.', 'postpilot'); ?>
                                </p>
                            </div>

                            <div class="postpilot-field-group" id="gemini-model-field" style="display: none;">
                                <label for="postpilot_gemini_model" class="postpilot-label">
                                    <?php esc_html_e('Gemini Model', 'postpilot'); ?>
                                </label>
                                <select name="postpilot_gemini_model" id="postpilot_gemini_model" class="postpilot-select">
                                    <option value="gemini-2.5-flash" <?php selected(get_option('postpilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.5-flash'); ?>>
                                        <?php esc_html_e('Gemini 2.5 Flash (Recommended)', 'postpilot'); ?>
                                    </option>
                                    <option value="gemini-2.5-pro" <?php selected(get_option('postpilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.5-pro'); ?>>
                                        <?php esc_html_e('Gemini 2.5 Pro (Most Capable)', 'postpilot'); ?>
                                    </option>
                                    <option value="gemini-2.0-flash" <?php selected(get_option('postpilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.0-flash'); ?>>
                                        <?php esc_html_e('Gemini 2.0 Flash', 'postpilot'); ?>
                                    </option>
                                    <option value="gemini-2.5-flash-lite" <?php selected(get_option('postpilot_gemini_model', 'gemini-2.5-flash'), 'gemini-2.5-flash-lite'); ?>>
                                        <?php esc_html_e('Gemini 2.5 Flash-Lite (Fastest)', 'postpilot'); ?>
                                    </option>
                                </select>
                                <p class="postpilot-field-description">
                                    <?php esc_html_e('Select the Gemini model to use for content generation.', 'postpilot'); ?>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- End General Tab -->
                
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
                                    <path d="M12 2v20M2 12h20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                    <circle cx="12" cy="12" r="3" stroke="currentColor" stroke-width="2"/>
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
                                                <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                                <path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3" stroke="currentColor" stroke-width="2"/>
                                                <circle cx="12" cy="17" r="1" fill="currentColor"/>
                                            </svg>
                                            <?php esc_html_e('FAQ Generator', 'postpilot'); ?>
                                        </label>
                                        <p class="postpilot-feature-description">
                                            <?php esc_html_e('Automatically generate frequently asked questions based on your content', 'postpilot'); ?>
                                        </p>
                                    </div>
                                    <label class="postpilot-toggle">
                                        <input 
                                            type="checkbox" 
                                            name="postpilot_enable_faq" 
                                            value="1" 
                                            <?php checked(get_option('postpilot_enable_faq'), '1'); ?>
                                        />
                                        <span class="postpilot-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="postpilot-feature-options" id="faq-options">
                                    <div class="postpilot-field-group">
                                        <label for="postpilot_faq_position" class="postpilot-label-small">
                                            <?php esc_html_e('Display Position', 'postpilot'); ?>
                                        </label>
                                        <select name="postpilot_faq_position" id="postpilot_faq_position" class="postpilot-select-small">
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
                                        <select name="postpilot_faq_default_layout" id="postpilot_faq_default_layout" class="postpilot-select-small">
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
                                                <path d="M4 6h16M4 12h16M4 18h16" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
                                            </svg>
                                            <?php esc_html_e('Content Summary', 'postpilot'); ?>
                                        </label>
                                        <p class="postpilot-feature-description">
                                            <?php esc_html_e('Generate concise summaries of your posts for better engagement', 'postpilot'); ?>
                                        </p>
                                    </div>
                                    <label class="postpilot-toggle">
                                        <input 
                                            type="checkbox" 
                                            name="postpilot_enable_summary" 
                                            value="1" 
                                            <?php checked(get_option('postpilot_enable_summary'), '1'); ?>
                                        />
                                        <span class="postpilot-toggle-slider"></span>
                                    </label>
                                </div>
                                <div class="postpilot-feature-options" id="summary-options">
                                    <div class="postpilot-field-group">
                                        <label for="postpilot_summary_position" class="postpilot-label-small">
                                            <?php esc_html_e('Display Position', 'postpilot'); ?>
                                        </label>
                                        <select name="postpilot_summary_position" id="postpilot_summary_position" class="postpilot-select-small">
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
                                                <path d="M10 13a5 5 0 0 0 7.54.54l3-3a5 5 0 0 0-7.07-7.07l-1.72 1.71" stroke="currentColor" stroke-width="2"/>
                                                <path d="M14 11a5 5 0 0 0-7.54-.54l-3 3a5 5 0 0 0 7.07 7.07l1.71-1.71" stroke="currentColor" stroke-width="2"/>
                                            </svg>
                                            <?php esc_html_e('Smart Internal Links', 'postpilot'); ?>
                                        </label>
                                        <p class="postpilot-feature-description">
                                            <?php esc_html_e('Automatically suggest relevant internal links to improve SEO', 'postpilot'); ?>
                                        </p>
                                    </div>
                                    <label class="postpilot-toggle">
                                        <input 
                                            type="checkbox" 
                                            name="postpilot_enable_internal_links" 
                                            value="1" 
                                            <?php checked(get_option('postpilot_enable_internal_links'), '1'); ?>
                                        />
                                        <span class="postpilot-toggle-slider"></span>
                                    </label>
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
                                    <path d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                </svg>
                            </div>
                            <div>
                                <h2><?php esc_html_e('Debug Logging', 'postpilot'); ?></h2>
                                <p><?php esc_html_e('Enable debug logging for troubleshooting API issues', 'postpilot'); ?></p>
                            </div>
                        </div>
                        <div class="postpilot-card-body">
                            <!-- Debug Logging Toggle -->
                            <div class="postpilot-field-group">
                                <label class="postpilot-label">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" style="margin-right: 8px;">
                                        <path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                                    </svg>
                                    <?php esc_html_e('Enable Debug Logging', 'postpilot'); ?>
                                </label>
                                <div class="postpilot-toggle-wrapper">
                                    <label class="postpilot-toggle">
                                        <input 
                                            type="checkbox" 
                                            name="postpilot_enable_debug_logging" 
                                            value="1" 
                                            <?php checked(get_option('postpilot_enable_debug_logging'), '1'); ?>
                                        />
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
                                        <circle cx="12" cy="12" r="10" stroke="currentColor" stroke-width="2"/>
                                        <path d="M12 16v-4M12 8h.01" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
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
        <input type="text" 
               name="postpilot_openai_api_key" 
               id="postpilot_openai_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your OpenAI API key', 'postpilot'); ?>" />
        <?php if ($has_key) : ?>
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
        <input type="text" 
               name="postpilot_claude_api_key" 
               id="postpilot_claude_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your Claude API key', 'postpilot'); ?>" />
        <?php if ($has_key) : ?>
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
            <input type="checkbox" 
                   name="postpilot_enable_internal_links" 
                   value="1" 
                   <?php checked($value, '1'); ?> />
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
        <input type="text" 
               name="postpilot_gemini_api_key" 
               id="postpilot_gemini_api_key" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text" 
               placeholder="<?php echo $has_key ? esc_attr__('API key is saved', 'postpilot') : esc_attr__('Enter your Gemini API key', 'postpilot'); ?>" />
        <?php if ($has_key) : ?>
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
}
