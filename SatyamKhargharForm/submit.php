<?php
// ==================================================
// SERVER.PHP — CLEAN & PRODUCTION READY
// ==================================================

error_reporting(0);

// ==================================================
// ALLOW ONLY POST
// ==================================================
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ==================================================
// GET USER IP
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

$user_ip = getUserIP();

// ==================================================
// GET LOCATION FROM IP
// ==================================================
function getLocationFromIP(string $ip): string {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'])) {
        return 'Localhost';
    }

    // ✅ Fixed: Removed extra spaces in URL
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

$location = getLocationFromIP($user_ip);

// ==================================================
// FORM DATA
// ==================================================
$name    = trim($_POST['name'] ?? '');
$email   = trim($_POST['email'] ?? '');
$mobile  = preg_replace('/\D/', '', $_POST['phone'] ?? '');
$project = trim($_POST['project'] ?? '');          // ✅ Project name from dropdown
$bhk     = trim($_POST['interested_in'] ?? '');   // e.g., "2 BHK"
$budget  = trim($_POST['budget'] ?? '');

// ==================================================
// BASIC VALIDATION
// ==================================================
if ($name === '' || strlen($mobile) !== 10 || $project === '') {
    header("Location: index.html");
    exit;
}

// ==================================================
// REQUIREMENTS (TEXT ONLY) — NOW INCLUDES PROJECT NAME CLEARLY
// ==================================================
$requirements = "Name: $name
Email: $email
Mobile: $mobile
Project: $project
Unit Type: $bhk
Budget: $budget
IP: $user_ip
Location: $location";

// ==================================================
// CRM PAYLOAD
// ==================================================
$postdata = [
    'Name'         => $name,
    'Email'        => $email,
    'Mobile'       => $mobile,

    // 🔥 ONLY PROJECT NAME GOES HERE — CORRECT
     'project' => $project, 

    'remark'  => $requirements
];

// ==================================================
// CRM CONFIG
// ==================================================
$api_key = "36db0bb612c6408b80637d940351b53c060521043458";
$source  = 1386;

// ✅ Fixed: Removed trailing space in webhook URL
$webhook = "https://connector.b2bbricks.com/api/Integration/hook/93313757-28ad-4676-8144-979f6259fd9b";

$url = $webhook . '?' . http_build_query([
    'api_key'      => $api_key,
    'source'       => $source,
    'responsetype' => 'json'
]);

// ==================================================
// SEND TO CRM
// ==================================================
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

// ==================================================
// LOG CRM RESPONSE (SAFE)
// ==================================================
file_put_contents(
    __DIR__ . '/crm_response.log',
    date('Y-m-d H:i:s') . "\nHTTP Code: $httpCode\nResponse: $response\n\n",
    FILE_APPEND
);

// ==================================================
// REDIRECT BASED ON RESPONSE
// ==================================================
if ($httpCode === 200) {
    header("Location: thankyou.html");
    exit;
}

// ==================================================
// FALLBACK ERROR
// ==================================================
http_response_code(500);
echo "Something went wrong. Please try again later.";
exit;