<?php
// ==================================================
// FORM HANDLER — SUPPORTS BOTH MAIN FORM & CHATBOT
// ==================================================
error_reporting(0);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ==================================================
// IP HANDLING FUNCTIONS (FIXED URL FORMATTING)
// ==================================================
function getUserIP(): string {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

function getLocationFromIP(string $ip): string {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
        return 'Localhost';
    }
    
    // ✅ FIXED: Removed extra spaces in URL
    $ch = curl_init("https://ipapi.co/{$ip}/json/");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 6,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'EnquiryForm/1.0'
    ]);
    
    $res = curl_exec($ch);
    curl_close($ch);
    
    $data = json_decode($res, true);
    return ($data && isset($data['city']))
        ? "{$data['city']}, {$data['region']}, {$data['country_name']}"
        : 'Unknown Location';
}

// ==================================================
// SUBMISSION TYPE DETECTION
// ==================================================
$is_chatbot = (!empty($_POST['form_name']) && $_POST['form_name'] === 'Chatbot Inquiry');

if ($is_chatbot) {
    // ======================
    // CHATBOT SUBMISSION FLOW
    // ======================
    $name = trim($_POST['uname'] ?? '');
    $mobile_raw = trim($_POST['umobile'] ?? '');
    $chat_option = trim($_POST['chat_option'] ?? 'Not specified');
    
    // Normalize mobile: Remove non-digits → take last 10 digits (Indian number standard)
    $mobile_clean = preg_replace('/\D/', '', $mobile_raw);
    $mobile = substr($mobile_clean, -10);
    
    // Chatbot-specific validation (returns HTTP error - NO redirect)
    if ($name === '' || strlen($mobile) !== 10 || strlen($mobile_clean) < 10) {
        http_response_code(400);
        echo "Invalid name or mobile number format. Please enter 10-digit Indian number.";
        exit;
    }
    
    // CRITICAL: Project name matching your CRM's expected value for this property
    $project = 'Paradise Sai World Empire - Kharghar'; 
    $email = ''; // Chatbot doesn't collect email
    
    $user_ip = getUserIP();
    $location = getLocationFromIP($user_ip);
    
    // Enhanced remark with chatbot context
    $requirements = "CALLTYPE: Chatbot Inquiry\n" .
                    "Selected Option: $chat_option\n" .
                    "Name: $name\n" .
                    "Mobile (Submitted): $mobile_raw\n" .
                    "Mobile (CRM Format): $mobile\n" .
                    "Project: $project\n" .
                    "Location: $location\n" .
                    "Visitor IP: $user_ip\n" .
                    "Lead Source: Chatbot on Website";
    
} else {
    // ======================
    // MAIN ENQUIRY FORM FLOW (UNCHANGED LOGIC)
    // ======================
    $fname   = trim($_POST['enq_fname'] ?? '');
    $lname   = trim($_POST['enq_lname'] ?? '');
    $name    = trim($fname . ' ' . $lname);
    $email   = trim($_POST['enq_email'] ?? '');
    $mobile  = preg_replace('/\D/', '', $_POST['enq_phone'] ?? '');
    $project = trim($_POST['project'] ?? 'SatyamKharghar');
    
    // Main form validation (uses redirect on failure)
    if ($name === '' || strlen($mobile) !== 10 || $project === '') {
        header("Location: index.html");
        exit;
    }
    
    $user_ip = getUserIP();
    $location = getLocationFromIP($user_ip);
    
    $requirements = "Name: $name\n" .
                    "Email: $email\n" .
                    "Mobile: $mobile\n" .
                    "Project: $project\n" .
                    "Location: $location\n" .
                    "Visitor IP: $user_ip\n" .
                    "Lead Source: Website";
}

// ==================================================
// CRM INTEGRATION (COMMON FOR BOTH PATHS)
// ==================================================
$postdata = [
    'Name'    => $name,
    'Email'   => $email,
    'Mobile'  => $mobile,
    'project' => $project,  // Critical: Matches CRM field name exactly
    'remark'  => $requirements
];

$api_key = "36db0bb612c6408b80637d940351b53c060521043458";
$source  = 1386;
// ✅ FIXED: Removed trailing spaces in webhook URL
$webhook = "https://connector.b2bbricks.com/api/Integration/hook/93313757-28ad-4676-8144-979f6259fd9b";

$url = $webhook . '?' . http_build_query([
    'api_key'      => $api_key,
    'source'       => $source,
    'responsetype' => 'json'
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($postdata),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Optional logging
file_put_contents(
    __DIR__ . '/crm_response.log',
    date('Y-m-d H:i:s') . " | Type: " . ($is_chatbot ? 'CHATBOT' : 'MAIN_FORM') . 
    " | Project: $project | Mobile: $mobile | HTTP: $httpCode\nResponse: $response\n\n",
    FILE_APPEND
);

// ==================================================
// RESPONSE HANDLING (RESPECTS CLIENT TYPE)
// ==================================================
if ($httpCode === 200) {
    // Both paths redirect to thank you page
    // Chatbot JS detects redirect via fetch API (response.redirected = true)
    header("Location: thankyou.html");
    exit;
}

// Error handling differs by client typess
if ($is_chatbot) {
    http_response_code(500);
    echo "CRM submission failed. Please try again later.";
    exit;
} else {
    http_response_code(500);
    echo "Something went wrong. Please try again later.";
    exit;
}
?>