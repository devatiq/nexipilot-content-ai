<?php
/**
 * Test Gemini API Connection
 * 
 * Temporary test script to debug Gemini API issues
 */

// Test API key
$api_key = 'AIzaSyA0XE0iariFD8RCltI4uF0w20Ghz6Bd6Cc';
$model = 'gemini-1.5-flash';

// Build the API endpoint
$endpoint = 'https://generativelanguage.googleapis.com/v1/models/' . $model . ':generateContent?key=' . $api_key;

// Prepare the request body
$body = array(
    'contents' => array(
        array(
            'parts' => array(
                array(
                    'text' => 'Hello, this is a test.',
                ),
            ),
        ),
    ),
    'generationConfig' => array(
        'temperature' => 0.7,
        'maxOutputTokens' => 1024,
    ),
);

echo "Testing Gemini API...\n";
echo "Endpoint: $endpoint\n\n";

// Make the request using cURL
$ch = curl_init($endpoint);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, array(
    'Content-Type: application/json',
));
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($body));
curl_setopt($ch, CURLOPT_TIMEOUT, 30);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

$response = curl_exec($ch);
$http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

echo "HTTP Code: $http_code\n";

if ($error) {
    echo "cURL Error: $error\n";
}

echo "\nResponse:\n";
echo $response . "\n";

// Parse response
$data = json_decode($response, true);
if ($data) {
    echo "\nParsed Response:\n";
    print_r($data);
}
