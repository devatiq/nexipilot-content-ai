<?php
/**
 * Gemini.php
 *
 * Google Gemini provider implementation.
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
 * Gemini Provider Class
 *
 * Implements the ProviderInterface for Google Gemini.
 *
 * @package PostPilot\AI
 * @since 1.0.0
 */
class Gemini implements ProviderInterface
{
    /**
     * API key
     *
     * @var string
     */
    private $api_key;

    /**
     * API endpoint base
     *
     * @var string
     */
    private $api_endpoint_base = 'https://generativelanguage.googleapis.com/v1/models/';

    /**
     * Model to use
     *
     * @var string
     */
    private $model = 'gemini-2.5-flash';

    /**
     * Constructor
     *
     * @since 1.0.0
     * @param string $api_key The Gemini API key.
     * @param string $model Optional. The model to use.
     */
    public function __construct($api_key, $model = 'gemini-2.5-flash')
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
            'Based on the following content, generate 4-5 frequently asked questions with answers. Return ONLY a valid JSON array with objects containing "question" and "answer" keys, without any markdown formatting or code blocks. Content: %s',
            wp_strip_all_tags($content)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        Logger::debug('Gemini FAQ raw response', array('response' => substr($response, 0, 200)));

        // Strip markdown code blocks if present (Gemini often wraps JSON in ```json ... ```)
        $cleaned_response = $this->strip_markdown_code_blocks($response);

        Logger::debug('Gemini FAQ after stripping markdown', array(
            'cleaned' => substr($cleaned_response, 0, 200),
            'was_stripped' => $cleaned_response !== $response
        ));

        // Parse JSON response
        $faq_data = json_decode($cleaned_response, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            Logger::error('Gemini FAQ JSON parse error', array(
                'error' => json_last_error_msg(),
                'response' => substr($cleaned_response, 0, 500)
            ));

            // If not valid JSON, create a simple structure
            return array(
                array(
                    'question' => __('What is this content about?', 'postpilot'),
                    'answer' => $cleaned_response,
                ),
            );
        }

        Logger::debug('Gemini FAQ parsed successfully', array('count' => count($faq_data)));
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
            'Analyze this content and suggest 3-5 relevant internal links from the available posts. Return ONLY a valid JSON array with objects containing "keyword" and "post_id" keys, without any markdown formatting or code blocks. Content: %s. Available posts: %s',
            wp_strip_all_tags($content),
            implode('; ', $posts_list)
        );

        $response = $this->make_request($prompt);

        if (is_wp_error($response)) {
            return $response;
        }

        // Strip markdown code blocks if present
        $response = $this->strip_markdown_code_blocks($response);

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
                __('Invalid Gemini API key.', 'postpilot')
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
                __('Gemini API key is not configured.', 'postpilot')
            );
        }

        // Build the API endpoint with model
        $endpoint = $this->api_endpoint_base . $this->model . ':generateContent?key=' . $api_key;

        $body = array(
            'contents' => array(
                array(
                    'parts' => array(
                        array(
                            'text' => $prompt,
                        ),
                    ),
                ),
            ),
            'generationConfig' => array(
                'temperature' => 0.7,
                'maxOutputTokens' => 1024,
            ),
        );

        Logger::log_api_request('Gemini', $endpoint, $body);

        $response = wp_remote_post(
            $endpoint,
            array(
                'headers' => array(
                    'Content-Type' => 'application/json',
                ),
                'body' => wp_json_encode($body),
                'timeout' => 30,
            )
        );

        if (is_wp_error($response)) {
            Logger::log_api_response('Gemini', $response->get_error_message(), true);
            return $response;
        }

        $response_code = wp_remote_retrieve_response_code($response);
        $response_body = wp_remote_retrieve_body($response);

        if ($response_code !== 200) {
            $error_data = json_decode($response_body, true);
            $error_message = isset($error_data['error']['message']) ? $error_data['error']['message'] : 'Unknown error';

            Logger::log_api_response('Gemini', $response_body, true);

            // Provide user-friendly error messages for common errors
            if ($response_code === 400) {
                return new \WP_Error(
                    'gemini_bad_request',
                    sprintf(__('Gemini API error: Invalid request format. %s', 'postpilot'), $error_message)
                );
            } elseif ($response_code === 401 || $response_code === 403) {
                return new \WP_Error(
                    'gemini_invalid_key',
                    __('Invalid Gemini API key. Please check your API key in PostPilot settings.', 'postpilot')
                );
            } elseif ($response_code === 429) {
                return new \WP_Error(
                    'gemini_rate_limit',
                    __('Gemini API rate limit exceeded. Please try again later.', 'postpilot')
                );
            } elseif (strpos($error_message, 'quota') !== false || strpos($error_message, 'RESOURCE_EXHAUSTED') !== false) {
                return new \WP_Error(
                    'gemini_quota_exceeded',
                    __('Gemini quota exceeded. Please check your quota at https://aistudio.google.com/app/apikey', 'postpilot')
                );
            } else {
                return new \WP_Error(
                    'gemini_api_error',
                    sprintf(__('Gemini API error (Code: %d): %s', 'postpilot'), $response_code, $error_message)
                );
            }
        }

        $data = json_decode($response_body, true);

        if (isset($data['candidates'][0]['content']['parts'][0]['text'])) {
            $content = $data['candidates'][0]['content']['parts'][0]['text'];
            Logger::log_api_response('Gemini', $content);
            return $content;
        }


        return new \WP_Error(
            'invalid_response',
            __('Invalid response from Gemini API.', 'postpilot')
        );
    }

    /**
     * Strip markdown code blocks from response
     *
     * Gemini often wraps JSON responses in markdown code blocks like ```json ... ```
     * This method removes those wrappers to get clean JSON.
     *
     * @since 1.0.0
     * @param string $response The API response.
     * @return string Cleaned response without markdown code blocks
     */
    private function strip_markdown_code_blocks($response)
    {
        // Remove markdown code blocks (```json ... ``` or ``` ... ```)
        $response = trim($response);

        // Pattern to match code blocks with optional language identifier
        if (preg_match('/^```(?:json)?\s*\n(.*)\n```$/s', $response, $matches)) {
            return trim($matches[1]);
        }

        // If no code block found, return original response
        return $response;
    }
}

