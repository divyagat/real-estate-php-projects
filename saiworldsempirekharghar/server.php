<?php
// ==================================================
// server.php — Fixed for Chatbot + CRM Integration
// ==================================================

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// === GET IP ===
function getUserIP() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = explode(',', $_SERVER[$key])[0];
            if (filter_var($ip, FILTER_VALIDATE_IP)) return $ip;
        }
    }
    return '0.0.0.0';
}

// === GET LOCATION (FIXED URL) ===
function getLocationFromIP($ip) {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) return 'Localhost';
    $ch = curl_init("https://ipapi.co/{$ip}/json/"); // REMOVED EXTRA SPACES
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true
    ]);
    $res = curl_exec($ch);
    curl_close($ch);
    $data = json_decode($res, true);
    return ($data && isset($data['city']))
        ? "{$data['city']}, {$data['region']}, {$data['country_name']}"
        : 'Unknown Location';
}

$user_ip = getUserIP();
$location = getLocationFromIP($user_ip);

// === FORM DATA (CHATBOT-COMPATIBLE) ===
$name  = trim($_POST['uname'] ?? '');
$mobile = preg_replace('/\D/', '', $_POST['umobile'] ?? '');
// CRITICAL FIX: Use chat_option from chatbot OR fallback to form_source
$form_source = trim($_POST['chat_option'] ?? $_POST['form_source'] ?? 'Chatbot Inquiry');

// === VALIDATION (FIXED FOR COUNTRY CODE) ===
if ($name === '' || strlen($mobile) < 10 || strlen($mobile) > 15) {
    // Chatbot JS handles retry on failure - redirect preserves flow
    header("Location: index.html");
    exit;
}

// === DETECT BHK FROM CHAT OPTION ===
$bhk = 'General';
if (preg_match('/\b(2|3|4)\s*BHK\b/i', $form_source, $m)) {
    $bhk = $m[1] . ' BHK';
} elseif (preg_match('/\b(2|3|4)\s*BHK\b/i', $_POST['message'] ?? '', $m)) {
    $bhk = $m[1] . ' BHK'; // Secondary check
}

$project = 'Paradise Sai World Empire - Kharghar';

// === REMARK (ENHANCED FOR CHATBOT) ===
$requirements = "Name: $name
Mobile: $mobile
Project: $project
Unit Type: $bhk
Inquiry Source: $form_source
Chat Option: " . ($_POST['chat_option'] ?? 'N/A') . "
Location: $location
Visitor IP: $user_ip
Form Name: " . ($_POST['form_name'] ?? 'Chatbot');

// === CRM PAYLOAD ===
$postdata = [
    'name'    => $name,
    'mobile'  => $mobile, // Full international format (10-15 digits)
    'email'   => '',
    'project' => $project,
    'remark'  => $requirements
];

// === SEND TO CRM (FIXED URL) ===
$api_key  = "36db0bb612c6408b80637d940351b53c060521043458";
$sourceid = 2739;

// CRITICAL FIX: Removed spaces in query string
$url = "https://connector.b2bbricks.com/api/Integration/postResponse?" . http_build_query([
    'api_key' => $api_key,
    'source'  => $sourceid,
    'responsetype' => 'json'
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => json_encode($postdata),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT => 15,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// ALWAYS redirect to maintain chatbot JS flow (success/failure handled client-side)
header("Location: thankyou.html");
exit;
?>