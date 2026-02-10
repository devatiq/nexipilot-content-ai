<?php
/**
 * Claude.php
 *
 * Claude (Anthropic) provider implementation.
 *
 * @package NexiPilot\AI
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */

namespace NexiPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use NexiPilot\Helpers\Logger;

/**
 * Claude Provider Class
 *
 * Implements the ProviderInterface for Claude (Anthropic).
 *
 * @package NexiPilot\AI
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */
class Claude implements ProviderInterface
{
    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API endpoint
     *
     * @var string
     */
    private $api_endpoint = 'https://api.anthropic.com/v1/messages';

    /**
     * Model to use
     *
     * @var string
     */
    private $model = 'claude-3-haiku-20240307';

    /**
     * API version
     *
     * @var string
     */
    private $api_version = '2023-06-01';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $api_key The Claude API key.
     * @param string $model Optional. The model to use.
     */
    public function __construct($api_key, $model = 'claude-3-haiku-20240307')
    {
        $this->api_key = $api_key;
        $this->model = $model;
    }

    /**
     * Generate FAQ from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return array|WP_Error Array of FAQ items or WP_Error on failure
     */
    public function generate_faq($content)
    {
        $prompt = sprintf(
            'Based on the following content, generate 4-5 frequently asked questions with answers. Return the response as a JSON array with objects containing "question" and "answer" keys. Content: %s',
            wp_strip_all_tags($content)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parse JSON response
        $faq_data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            // If not valid JSON, create a simple structure
            return array(
                array(
                    'question' => __('What is this content about?', 'nexipilot-content-ai'),
                    'answer' => $response,
                ),
            );
        }

        return $faq_data;
    }

    /**
     * Generate summary from content
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @return string|WP_Error Summary text or WP_Error on failure
     */
    public function generate_summary($content)
    {
        $prompt = sprintf(
            'Create a concise, engaging summary (2-3 sentences) of the following content: %s',
            wp_strip_all_tags($content)
        );

        return $this->make_request($prompt);
    }

    /**
     * Suggest internal links
     *
     * @since 1.0.0
     * @param string $content The post content.
     * @param array  $available_posts Available posts for linking.
     * @return array|WP_Error Array of link suggestions or WP_Error on failure
     */
    public function suggest_internal_links($content, $available_posts)
    {
        $posts_list = array();
        foreach ($available_posts as $post) {
            $posts_list[] = sprintf(
                'ID: %d, Title: %s, URL: %s',
                $post->ID,
                $post->post_title,
                get_permalink($post->ID)
            );
        }

        $prompt = sprintf(
            'Analyze this content and suggest 3-5 relevant internal links from the available posts. Return as JSON array with "keyword" and "post_id" keys. Content: %s. Available posts: %s',
            wp_strip_all_tags($content),
            implode('; ', $posts_list)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // Parse JSON response
        $links_data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return array();
        }

        return $links_data;
    }

    /**
     * Validate API key
     *
     * @since 1.0.0
     * @param string $api_key The API key to validate.
     * @return bool|WP_Error True if valid, WP_Error on failure
     */
    public function validate_api_key($api_key)
    {
        // Make a simple test request
        $test_response = $this->make_request('Hello', $api_key);

        if (is_wp_error($test_response)) {
            return new \WP_Error(
                'invalid_api_key',
                __('Invalid Claude API key.', 'nexipilot-content-ai')
            );
        }

        return true;
    }

    /**
     * Make API request
     *
     * @since 1.0.0
     * @param string      $prompt The prompt to send.
     * @param string|null $custom_api_key Optional custom API key for validation.
     * @return string|WP_Error Response text or WP_Error on failure
     */
    private function make_request($prompt, $custom_api_key = null)
    {
        $api_key = $custom_api_key ?? $this->api_key;

        if (empty($api_key)) {
            return new \WP_Error(
                'missing_api_key',
                __('Claude API key is not configured.', 'nexipilot-content-ai')
            );
        }

        $body = array(
            'model' => $this->model,
            'messages' => array(
                array(
                    'role' => 'user',
                    'content' => $prompt,
                ),
            ),
            'max_tokens' => 1024,
        );

        Logger::log_api_request('Claude', $this->api_endpoint, $body);

        $response = wp_remote_post(
            $this->api_endpoint,
            array(
                'headers' => array(
                    'x-api-key' => $api_key,
                    'anthropic-version' => $this->api_version,
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            Logger::log_api_response('Claude', $response->get_error_message(), true);
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            Logger::log_api_response('Claude', $response_body, true);
            return new \WP_Error(
                'api_error',
                sprintf(
                    /* translators: %d: HTTP response code */
                    __('Claude API error (Code: %d)', 'nexipilot-content-ai'),
                    $response_code
                )
            );
        }

        $data = json_decode($response_body, true);

        if (isset($data['content'][0]['text'])) {
            $content = $data['content'][0]['text'];
            Logger::log_api_response('Claude', $content);
            return $content;
        }

        return new \WP_Error(
            'invalid_response',
            __('Invalid response from Claude API.', 'nexipilot-content-ai')
        );
    }
}
