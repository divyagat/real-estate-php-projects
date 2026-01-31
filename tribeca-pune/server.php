<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// --- Get real IP ---
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

// --- Get visitor location from IP ---
function getVisitorLocation($ip) {
    // Skip API call for local/test IPs
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
        return 'Localhost';
    }

    // ✅ Fixed: No extra spaces in URL
    $url = "https://ipapi.co/{$ip}/json/";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'RiverviewLead/1.0'
    ]);
    $res = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    // Only decode if response is valid JSON and HTTP 200
    if ($httpCode === 200 && $res !== false) {
        $data = json_decode($res, true);
        if (is_array($data) && isset($data['city']) && !isset($data['error'])) {
            return "{$data['city']}, {$data['region']}, {$data['country_name']}";
        }
    }

    return 'Unknown';
}

// --- Validate form ---
$name  = trim($_POST['uname'] ?? '');
$mobile = preg_replace('/\D/', '', $_POST['umobile'] ?? '');

if ($name === '' || strlen($mobile) !== 10) {
    header("Location: index.html");
    exit;
}

// --- Project values ---
$project_name    = "Tribeca Lulla Nagar";
$project_address = "Lulla Nagar, Pune";

// --- Visitor info ---
$user_ip       = getUserIP();
$lead_location = getVisitorLocation($user_ip);

// --- Chatbot fields ---
$chat_option = trim($_POST['chat_option'] ?? 'Not specified');
$message     = trim($_POST['message'] ?? 'Not specified');
$form_name   = trim($_POST['form_name'] ?? 'Chatbot Inquiry');

// --- Build remark WITHOUT "Project: ..." as requested ---
$remark = "Location: {$project_address}, "
        . "Visitor IP: {$user_ip}, Lead Location: {$lead_location}, "
        . "Chat Option: {$chat_option}, Message: {$message}, Form: {$form_name}";

// --- Payload ---
$payload = [
    'name'    => $name,
    'mobile'  => $mobile,
    'email'   => '',
    'project' => $project_name,  // CRM still gets project name
    'remark'  => $remark
];

// ✅ Fixed: Remove trailing spaces in webhook URL
$webhook_url = "https://connector.b2bbricks.com/api/Integration/hook/7981d4b5-f612-419d-b115-4e018dfa1ea6";

$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload, JSON_UNESCAPED_SLASHES),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15,
    CURLOPT_SSL_VERIFYPEER => true
]);

$response = curl_exec($ch);
curl_close($ch);

// Optional: Log error during development (remove in production)
// if ($response === false) { error_log("CRM webhook failed"); }

header("Location: thankyou.html");
exit;