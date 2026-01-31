<?php
session_start();

// ❌ Direct access block
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Invalid request method');
}

/* ---------- INPUT ---------- */
$name   = trim($_POST['Name'] ?? '');
$email  = trim($_POST['Email'] ?? '');
$code   = trim($_POST['CountryCode'] ?? '');
$number = preg_replace('/\D/', '', $_POST['Number'] ?? '');

/* ---------- VALIDATION ---------- */
if ($name === '' || $email === '' || $number === '') {
    exit('All fields are required');
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit('Invalid email address');
}

/* ---------- REAL IP ---------- */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    return $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
}
$ip = getUserIP();

/* ---------- LOCATION ---------- */
$location = "Unknown";
$geo = @json_decode(file_get_contents("http://ip-api.com/json/$ip"), true);
if (!empty($geo) && $geo['status'] === 'success') {
    $location = $geo['city'] . ", " . $geo['regionName'] . ", " . $geo['country'];
}

/* ---------- CRM DATA ---------- */
$mobile = $code . $number;

$requirement   = "Visitor IP: $ip | Location: $location";
$interestedIn  = "Mantra Magnus";

/* ---------- API CONFIG ---------- */
$api_key  = "36db0bb612c6408b80637d940351b53c060521043458";
$sourceid = 2744;

$postData = [
    "name"          => $name,
    "mobile"        => $mobile,
    "email"         => $email,
    "requirement"   => $requirement,     // ✅ REQUIREMENTS FIELD
    "interested_in" => $interestedIn      // ✅ INTERESTED IN
];

$urlData = [
    "api_key"      => $api_key,
    "source"       => $sourceid,
    "responsetype" => "json",
    "account"      => ""
];

/* ---------- SEND TO CRM ---------- */
$response = sendToCRM($urlData, $postData);

/* ---------- OUTCOME ---------- */
$outcome = "Unknown";

if ($response) {
    $crm = json_decode($response, true);

    if (isset($crm['message'])) {
        $outcome = $crm['message'];   // Lead Created Successfully
    } elseif (isset($crm['status'])) {
        $outcome = $crm['status'];
    }
}

/* ---------- SAVE OUTCOME ---------- */
$_SESSION['crm_outcome'] = $outcome;

/* ---------- REDIRECT ---------- */
header("Location: thankyou.html");
exit;


/* ---------- FUNCTION ---------- */
function sendToCRM($urlData, $data) {

    $url = "https://connector.b2bbricks.com/api/Integration/hook/7655ffe1-f2d4-4f6c-a8c6-f4e9ef94ab85?"
         . http_build_query($urlData);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);

    $response = curl_exec($ch);
    curl_close($ch);

    return $response;
}
