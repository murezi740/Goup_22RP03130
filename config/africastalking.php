<?php
// AfricasTalking API Configuration
define('AT_API_KEY', 'atsk_b5f1d4990b5f1bf5f9a071cdd2a7f76c10c76e5fa434e432f6adca0ecbbb121944922568'); // Replace with your API key
define('AT_USERNAME', 'sandbox'); // Replace with your username
define('AT_SHORTCODE', '7307'); // Replace with your shortcode

// USSD Configuration
define('USSD_SERVICE_CODE', '*384*8478#'); // Replace with your USSD code

// SMS Configuration
define('SMS_SENDER_ID', 'BIRYO'); // Your sender ID

// AfricasTalking API URL
define('AT_API_URL', 'https://api.africastalking.com/version1');

// SMS sending function using cURL
function sendSMS($phoneNumber, $message) {
    $url = AT_API_URL . '/messaging';
    $data = [
        'username' => AT_USERNAME,
        'to' => $phoneNumber,
        'message' => $message,
        'from' => SMS_SENDER_ID
    ];

    $headers = [
        'Accept: application/json',
        'Content-Type: application/x-www-form-urlencoded',
        'apiKey: ' . AT_API_KEY
    ];

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode == 201) {
        return true;
    } else {
        error_log("SMS sending failed: " . $response);
        return false;
    }
}

// USSD Response function
function sendUSSDResponse($sessionId, $text) {
    header('Content-type: text/plain');
    echo $text;
    exit;
}

// USSD Session handling
function handleUSSDRequest($sessionId, $serviceCode, $phoneNumber, $text) {
    // Store session data in database or session
    $db = getDBConnection();
    $stmt = $db->prepare("INSERT INTO ussd_sessions (session_id, phone_number, menu_level, menu_data) 
                         VALUES (?, ?, ?, ?) 
                         ON DUPLICATE KEY UPDATE 
                         menu_level = VALUES(menu_level), 
                         menu_data = VALUES(menu_data)");
    $stmt->execute([$sessionId, $phoneNumber, 'main', $text]);
    
    return true;
}
