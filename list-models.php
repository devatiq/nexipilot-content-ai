<?php
/**
 * List available Gemini models
 */

$api_key = 'AIzaSyA0XE0iariFD8RCltI4uF0w20Ghz6Bd6Cc';

// List models endpoint
$endpoint = 'https://generativelanguage.googleapis.com/v1/models?key=' . $api_key;

echo "Listing available Gemini models...\n";
echo "Endpoint: $endpoint\n\n";

$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

echo "HTTP Code: $http_code\n\n";
echo "Response:\n";
echo $response . "\n";

// Parse and display models
$data = json_decode($response, true);
if (isset($data['models'])) {
    echo "\nAvailable Models:\n";
    echo "================\n";
    foreach ($data['models'] as $model) {
        echo "Name: " . $model['name'] . "\n";
        if (isset($model['supportedGenerationMethods'])) {
            echo "Supported methods: " . implode(', ', $model['supportedGenerationMethods']) . "\n";
        }
        echo "---\n";
    }
}
