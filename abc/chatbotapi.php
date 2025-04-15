<?php
header('Content-Type: application/json');

// Get the POST data
$data = json_decode(file_get_contents('php://input'), true);
$userMessage = $data['message'] ?? '';

if (empty($userMessage)) {
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Your Nebius API key should be stored securely 
// (environment variables or a secure configuration file)
$apiKey = getenv('eyJhbGciOiJIUzI1NiIsImtpZCI6IlV6SXJWd1h0dnprLVRvdzlLZWstc0M1akptWXBvX1VaVkxUZlpnMDRlOFUiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiJnb29nbGUtb2F1dGgyfDEwMzY5Mjg3OTQ5NDY4MjU1MDMzMCIsInNjb3BlIjoib3BlbmlkIG9mZmxpbmVfYWNjZXNzIiwiaXNzIjoiYXBpX2tleV9pc3N1ZXIiLCJhdWQiOlsiaHR0cHM6Ly9uZWJpdXMtaW5mZXJlbmNlLmV1LmF1dGgwLmNvbS9hcGkvdjIvIl0sImV4cCI6MTkwMjM5NTQxOCwidXVpZCI6IjEyNzM1NGJjLTlhNTctNDM1MC04ZGYzLWExMGI1MzhlNTEwNSIsIm5hbWUiOiJmaXRidWRkeSIsImV4cGlyZXNfYXQiOiIyMDMwLTA0LTE0VDExOjEwOjE4KzAwMDAifQ.mMdmwfTZxZk7YouGvqH4trt_41BF0QowYBZT6plAOBQ');

if (empty($apiKey)) {
    // For development, you can hardcode it here (not recommended for production)
    // $apiKey = 'your-api-key';
    
    // Return an error if no API key is available
    echo json_encode(['error' => 'API key not configured']);
    exit;
}

// Set up the API request
$url = 'https://api.studio.nebius.com/v1/chat/completions';

$messages = [
    ['role' => 'system', 'content' => 'You are a helpful nutrition and fitness assistant that provides information about healthy eating, calorie counts, and exercise recommendations.'],
    ['role' => 'user', 'content' => $userMessage]
];

$requestData = [
    'model' => 'meta-llama/Meta-Llama-3.1-70B-Instruct-fast',
    'max_tokens' => 512,
    'temperature' => 0.6,
    'top_p' => 0.9,
    'extra_body' => [
        'top_k' => 50
    ],
    'messages' => $messages
];

// Initialize cURL
$ch = curl_init($url);

// Set cURL options
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Authorization: Bearer ' . $apiKey
]);

// Execute cURL request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Check for cURL errors
if (curl_errno($ch)) {
    echo json_encode(['error' => 'Request error: ' . curl_error($ch)]);
    curl_close($ch);
    exit;
}
curl_close($ch);

// Process response
if ($httpCode === 200) {
    $responseData = json_decode($response, true);
    $botResponse = $responseData['choices'][0]['message']['content'] ?? 'Sorry, I couldn\'t generate a response.';
    echo json_encode(['response' => $botResponse]);
} else {
    echo json_encode(['error' => 'API request failed with status code: ' . $httpCode . '. Response: ' . $response]);
}
?>
