<?php
// Security: Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); // Method Not Allowed
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

// Get the request body
$input = json_decode(file_get_contents('php://input'), true);

// Check if message is provided
if (!isset($input['message']) || empty($input['message'])) {
    http_response_code(400); // Bad Request
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No message provided']);
    exit;
}

// Store API key securely - in a production environment, this would be in a .env file or secure storage
$API_KEY = "eyJhbGciOiJIUzI1NiIsImtpZCI6IlV6SXJWd1h0dnprLVRvdzlLZWstc0M1akptWXBvX1VaVkxUZlpnMDRlOFUiLCJ0eXAiOiJKV1QifQ.eyJzdWIiOiJnb29nbGUtb2F1dGgyfDEwMzY5Mjg3OTQ5NDY4MjU1MDMzMCIsInNjb3BlIjoib3BlbmlkIG9mZmxpbmVfYWNjZXNzIiwiaXNzIjoiYXBpX2tleV9pc3N1ZXIiLCJhdWQiOlsiaHR0cHM6Ly9uZWJpdXMtaW5mZXJlbmNlLmV1LmF1dGgwLmNvbS9hcGkvdjIvIl0sImV4cCI6MTkwMTE4NjcyMiwidXVpZCI6IjI3MzE1MGMzLThlNmEtNDJhMC1hYmFiLTRmODUwZGNiYTVjOSIsIm5hbWUiOiJhcHAiLCJleHBpcmVzX2F0IjoiMjAzMC0wMy0zMVQxMToyNToyMiswMDAwIn0.79leXU14gNv_eSNZGnchFKTYooDDWWPM7u1B9wQxhak";
$apiUrl = 'https://api.studio.nebius.com/v1/chat/completions';

// Prepare the request to the Nebius API
$payload = [
    "model" => "meta-llama/Meta-Llama-3.1-70B-Instruct-fast",
    "max_tokens" => 150,
    "temperature" => 0.6,
    "top_p" => 0.9,
    "extra_body" => [
        "top_k" => 50
    ],
    "messages" => [
        [
            "role" => "system",
            "content" => "You are a helpful health assistant. Provide a concise response listing up to three possible diagnostic options and a brief recommendation of a medication keep the response under 100 words. If the user asks for a specific medication, provide a brief description of its use and side effects."
        ],
        [
            "role" => "user",
            "content" => $input['message']
        ]
    ]
];

// Send request to Nebius API
$ch = curl_init($apiUrl);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    "Content-Type: application/json",
    "Accept: */*",
    "Authorization: Bearer " . $API_KEY
]);

// Execute the request
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$curlError = curl_error($ch);
curl_close($ch);

// Handle response
header('Content-Type: application/json');

if ($curlError) {
    echo json_encode(['error' => 'API request failed: ' . $curlError]);
    exit;
}

if ($httpCode !== 200) {
    echo json_encode(['error' => 'API returned error code: ' . $httpCode]);
    exit;
}

// Process the response
$data = json_decode($response, true);
if (isset($data['choices'][0]['message']['content'])) {
    echo json_encode(['response' => $data['choices'][0]['message']['content']]);
} else {
    echo json_encode(['error' => 'Unexpected API response format']);
}
