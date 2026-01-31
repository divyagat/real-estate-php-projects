<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// === GET IP ===
function getUserIP() {
    foreach (['HTTP_CF_CONNECTING_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'] as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = trim(explode(',', $_SERVER[$key])[0]);
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return '0.0.0.0';
}

// === GET LOCATION (FIXED URL - NO SPACES) ===
function getLocationFromIP($ip) {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
        return 'Localhost';
    }

    // ✅ FIXED: Removed spaces from URL
    $url = "https://ipapi.co/{$ip}/json/";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'LeadCapture/1.0'
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($httpCode === 200 && $res) {
        $data = json_decode($res, true);
        if (!empty($data['city']) && !empty($data['region']) && !empty($data['country_name'])) {
            return "{$data['city']}, {$data['region']}, {$data['country_name']}";
        }
    }
    return 'Unknown Location';
}

// === FORM DATA ===
$name   = trim($_POST['uname'] ?? '');
$mobile_raw = $_POST['umobile'] ?? '';
$mobile_clean = preg_replace('/\D/', '', $mobile_raw); // Remove all non-digits

// Detect submission source
$is_chatbot = isset($_POST['chat_option']) || isset($_POST['form_name']);
$chat_option = $_POST['chat_option'] ?? 'N/A';
$form_name = $_POST['form_name'] ?? 'Website Form';
$message = $_POST['message'] ?? '';

// === VALIDATION (ACCEPT 10-15 DIGITS FOR CHATBOT) ===
if ($name === '' || strlen($mobile_clean) < 10) {
    header("Location: index.html");
    exit;
}

// Normalize mobile: Extract last 10 digits for CRM (Indian number)
$mobile_for_crm = (strlen($mobile_clean) > 10) ? substr($mobile_clean, -10) : $mobile_clean;

// === PROJECT DETECTION ===
$project_indicators = [
    'paradise' => 'Paradise Sai World Empire - Kharghar',
    'godrej' => 'Godrej Eternal Palms - Navi Mumbai',
    'mantra 1' => 'Mantra 1 Residences By Burgundy',
    'mantra codename' => 'Mantra Codename-Paradise (Sus Pune)'
];

$project = 'Paradise Sai World Empire - Kharghar'; // Default
$source_text = strtolower($chat_option . ' ' . $form_name . ' ' . $message);

foreach ($project_indicators as $keyword => $project_name) {
    if (strpos($source_text, $keyword) !== false) {
        $project = $project_name;
        break;
    }
}

// === BHK DETECTION ===
$bhk = 'General';
$check_fields = [$chat_option, $form_name, $message];
foreach ($check_fields as $field) {
    if (preg_match('/\b(2|3|4)\s*BHK\b/i', $field, $m)) {
        $bhk = $m[1] . ' BHK';
        break;
    }
}

// === LOCATION ===
$user_ip = getUserIP();
$location = getLocationFromIP($user_ip);

// === REMARK (ENHANCED WITH CHATBOT INFO) ===
$submission_type = $is_chatbot ? 'Chatbot' : 'Website Form';

$remark = "Name: $name
Mobile: $mobile_raw
Mobile (Clean): $mobile_clean
Project: $project
Unit Type: $bhk
Submission Type: $submission_type
Chat Option: $chat_option
Form Name: $form_name
Location: $location
Visitor IP: $user_ip
Timestamp: " . date('Y-m-d H:i:s');

// === DUPLICATE PREVENTION (15 seconds) ===
$cacheKey = 'lead_' . md5($mobile_clean);
$cacheFile = sys_get_temp_dir() . '/' . $cacheKey;
if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < 15) {
    // Likely a double-click — skip CRM
    header("Location: thankyou.html");
    exit;
}
touch($cacheFile); // Update timestamp

// === CRM PAYLOAD ===
$payload = [
    'name'    => $name,
    'mobile'  => $mobile_for_crm, // 10-digit for CRM
    'project' => $project,
    'remark'  => $remark
];

// ✅ FIXED: Removed trailing spaces from webhook URL
$webhookUrl = 'https://connector.b2bbricks.com/api/Integration/hook/002fe3ee-ce3c-49cf-848e-42cdcf2160c5';

$ch = curl_init($webhookUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

// Log error if needed (optional)
if ($httpCode !== 200) {
    error_log("CRM Webhook Failed: HTTP $httpCode, Response: $response");
}

// ALWAYS redirect to maintain user experience
header("Location: thankyou.html");
exit;
?>