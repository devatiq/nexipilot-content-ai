<?php
/**
 * Grok.php
 *
 * xAI Grok provider implementation.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 * @author Md Abul Bashar <hmbashar@gmail.com>
 */

namespace PostPilot\AI;

if (!defined('ABSPATH')) {
    exit; // Exit if accessed directly
}

use PostPilot\Helpers\Logger;

/**
 * Grok Provider Class
 *
 * Implements the ProviderInterface for xAI Grok.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */
class Grok implements ProviderInterface
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
    private $api_endpoint = 'https://api.x.ai/v1/chat/completions';

    /**
     * Model to use
     *
     * @var string
     */
    private $model = 'grok-beta';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $api_key The Grok API key.
     * @param string $model Optional. The model to use.
     */
    public function __construct($api_key, $model = 'grok-beta')
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
            "Generate exactly 4-5 FAQ items about the following content.\n\nIMPORTANT: Your response must be ONLY a valid JSON array starting with [ and ending with ].\nEach item must have \"question\" and \"answer\" keys.\nDo NOT use markdown code blocks.\nDo NOT truncate the response.\n\nContent: %s\n\nRespond with the complete JSON array:",
            wp_strip_all_tags($content)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        Logger::debug('Grok FAQ raw response', array('response' => substr($response, 0, 200)));

        // Parse JSON response
        $faq_data = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Grok FAQ JSON parse error', array(
                'error' => json_last_error_msg(),
                'response' => substr($response, 0, 500)
            ));
            return new \WP_Error(
                'json_parse_error',
                __('Failed to parse FAQ response from Grok.', 'postpilot')
            );
        }

        if (!is_array($faq_data)) {
            Logger::error('Grok FAQ response is not an array', array('type' => gettype($faq_data)));
            return new \WP_Error(
                'invalid_response',
                __('Invalid FAQ response format from Grok.', 'postpilot')
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
            "Write a concise summary (2-3 sentences) of the following content:\n\n%s",
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
        $posts_list = '';
        foreach ($available_posts as $post) {
            $posts_list .= sprintf(
                "- ID: %d, Title: %s\n",
                $post->ID,
                $post->post_title
            );
        }

        $prompt = sprintf(
            "Based on the following content, suggest which of these posts would be good internal links.\n\nContent: %s\n\nAvailable posts:\n%s\n\nRespond with ONLY a JSON array of post IDs that would be relevant internal links. Example: [1, 5, 12]",
            wp_strip_all_tags($content),
            $posts_list
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        $link_ids = json_decode($response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            return new \WP_Error(
                'json_parse_error',
                __('Failed to parse internal links response from Grok.', 'postpilot')
            );
        }

        return $link_ids;
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
                __('Invalid Grok API key.', 'postpilot')
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
                __('Grok API key is not configured.', 'postpilot')
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
            'temperature' => 0.7,
            'max_tokens' => 2048,
        );

        Logger::debug('API Request to Grok', array(
            'endpoint' => $this->api_endpoint,
            'model' => $this->model,
            'prompt_length' => strlen($prompt),
        ));

        $response = wp_remote_post($this->api_endpoint, array(
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $api_key,
            ),
            'body' => wp_json_encode($body),
            'timeout' => 60,
        ));

        if (is_wp_error($response)) {
            Logger::error('Grok API request failed', array(
                'error' => $response->get_error_message(),
            ));
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        Logger::debug('API Response from Grok', array(
            'code' => $response_code,
            'body_length' => strlen($response_body),
        ));

        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message'])
                ? $error_data['error']['message']
                : __('Unknown error from Grok API.', 'postpilot');

            Logger::error('Grok API error response', array(
                'code' => $response_code,
                'message' => $error_message,
                'body' => $response_body,
            ));

            return new \WP_Error(
				'api_error',
				sprintf(
					/* translators: %1$s: Grok API error message */
					__( 'Grok API error: %1$s', 'postpilot' ),
					$error_message
				)
			);
        }

        $data = json_decode($response_body, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Failed to parse Grok response', array(
                'error' => json_last_error_msg(),
                'body' => substr($response_body, 0, 500),
            ));
            return new \WP_Error(
                'json_parse_error',
                __('Failed to parse response from Grok.', 'postpilot')
            );
        }

        if (!isset($data['choices'][0]['message']['content'])) {
            Logger::error('Invalid Grok response structure', array(
                'data' => $data,
            ));
            return new \WP_Error(
                'invalid_response',
                __('Invalid response structure from Grok.', 'postpilot')
            );
        }

        $content = $data['choices'][0]['message']['content'];

        Logger::debug('Grok response extracted', array(
            'content_length' => strlen($content),
            'content_preview' => substr($content, 0, 100),
        ));

        return trim($content);
    }
}
