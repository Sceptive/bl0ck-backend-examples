<?php
// form_handler.php
header('Content-Type: application/json');

$api_token = getenv('API_TOKEN') ?: 'YOUR_API_TOKEN';
$api_base = 'https://api.bl0ck.sceptive.com';

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Log function to record API responses
function log_api_response($endpoint, $request_data, $response, $http_code) {
    $log_entry = [
        'timestamp' => date('Y-m-d H:i:s'),
        'endpoint' => $endpoint,
        'request' => $request_data,
        'response' => $response,
        'http_code' => $http_code
    ];
    
    file_put_contents('api_logs.json', json_encode($log_entry, JSON_PRETTY_PRINT) . "\n", FILE_APPEND);
    return $log_entry;
}

// Get form data
$form_data = $_POST;
$fingerprint = $_POST['fingerprint'] ?? '';

// Log the incoming request
log_api_response('form_submission', $form_data, 'incoming_request', 0);

// Validate form (example validation)
$is_valid = validate_form($form_data);

if (!$is_valid) {
    // Report suspicious fingerprint
    $report_result = report_fingerprint($api_base, $api_token, $fingerprint, [
        'ip' => $_SERVER['REMOTE_ADDR'],
        'reason' => 'Invalid form submission',
        'form_data' => $form_data
    ]);
    
    // Create response with detailed API information
    $response_data = [
        'status' => 'error',
        'message' => 'Invalid form data',
        'fingerprint' => $fingerprint,
        'api_responses' => [
            'report' => $report_result
        ]
    ];
    
    http_response_code(400);
    echo json_encode($response_data, JSON_PRETTY_PRINT);
    exit;
}

// Check fingerprint before processing
$fp_status = check_fingerprint($api_base, $api_token, $fingerprint);

// Create response with fingerprint check details
$response_data = [
    'status' => 'processing',
    'fingerprint' => $fingerprint,
    'fingerprint_check' => $fp_status
];

if ($fp_status['http_code'] != 200) {
    $response_data['status'] = 'error';
    http_response_code(401);
    echo json_encode($response_data, JSON_PRETTY_PRINT);
    exit;
}

if ($fp_status['recommended_action'] === 'block') {
    // Known suspicious fingerprint - reject
    $response_data['status'] = 'error';
    $response_data['message'] = 'Suspicious activity detected';
    
    http_response_code(403);
    echo json_encode($response_data, JSON_PRETTY_PRINT);
    exit;
}

// Process valid form
$process_result = process_form($form_data);
$response_data['status'] = 'success';
$response_data['message'] = 'Form submitted successfully';
$response_data['processing_result'] = $process_result;

echo json_encode($response_data, JSON_PRETTY_PRINT);

function validate_form($data) {
    // Example validation - check required fields
    $required = ['name', 'email', 'fingerprint'];
    foreach ($required as $field) {
        if (empty($data[$field])) {
            return false;
        }
    }
    
    // Validate email format
    if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
        return false;
    }
    
    return true;
}

function check_fingerprint($api_base, $token, $fp) {
    if (strlen($fp) !== 32 || !preg_match('/^[a-f0-9]+$/', $fp)) {
        $result = ['status' => 'invalid'];
        log_api_response('fingerprint_query', ['fingerprint' => $fp], $result, 400);
        return $result;
    }
    
    $url = "$api_base/bfp/query/$fp";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => ["x-api-token: $token"],
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_VERBOSE => true // Enable verbose output for debugging
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    $response_data = json_decode($response, true) ?? ['raw_response' => $response];
    
    // Log the API call and response
    $log_data = [
        'url' => $url,
        'fingerprint' => $fp,
        'curl_info' => $curl_info,
        'response' => $response_data
    ];
    
    log_api_response('fingerprint_query', ['fingerprint' => $fp], $response_data, $http_code);
    
    if ($http_code === 200) {
        return array_merge(['status' => 'success', 'http_code' => $http_code], $response_data);
    }
    
    return ['status' => 'error', 'http_code' => $http_code, 'response' => $response_data];
}

function report_fingerprint($api_base, $token, $fp, $details) {
    if (strlen($fp) !== 32 || !preg_match('/^[a-f0-9]+$/', $fp)) {
        $result = ['status' => 'invalid_fingerprint'];
        log_api_response('fingerprint_report', ['fingerprint' => $fp, 'details' => $details], $result, 400);
        return $result;
    }
    
    $url = "$api_base/bfp/report/$fp";
    $post_data = json_encode(['details' => $details]);
    
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_HTTPHEADER => [
            "x-api-token: $token",
            'Content-Type: application/json'
        ],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $post_data,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_VERBOSE => true // Enable verbose output for debugging
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curl_info = curl_getinfo($ch);
    curl_close($ch);
    
    $response_data = json_decode($response, true) ?? ['raw_response' => $response];
    
    // Log the API call and response
    $log_data = [
        'url' => $url,
        'fingerprint' => $fp,
        'details' => $details,
        'curl_info' => $curl_info,
        'response' => $response_data
    ];
    
    log_api_response('fingerprint_report', ['fingerprint' => $fp, 'details' => $details], $response_data, $http_code);
    
    if ($http_code === 200) {
        return array_merge(['status' => 'success', 'http_code' => $http_code], $response_data);
    }
    
    return ['status' => 'error', 'http_code' => $http_code, 'response' => $response_data];
}

function process_form($data) {
    // Save to database or perform other actions
    // This is just a placeholder
    $result = [
        'saved' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => $data
    ];
    
    file_put_contents('submissions.log', 
        date('Y-m-d H:i:s') . ' - ' . json_encode($data) . "\n", 
        FILE_APPEND
    );
    
    return $result;
}
?>