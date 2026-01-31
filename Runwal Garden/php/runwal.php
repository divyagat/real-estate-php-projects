<?php

if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit;
}

// 🔒 ANTI-SPAM: Honeypot check
if (!empty($_POST['robot_check'])) {
    error_log("Spam blocked: honeypot filled - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    exit;
}

// 🔒 Block ultra-fast submissions (< 2 seconds)
$timestamp = $_POST['timestamp'] ?? 0;
if ((time() - (int)$timestamp) < 2) {
    error_log("Spam blocked: too fast - IP: " . ($_SERVER['REMOTE_ADDR'] ?? 'unknown'));
    http_response_code(400);
    exit;
}

// Sanitize inputs
$name = trim($_POST["name"] ?? '');
$email = trim($_POST["email"] ?? '');
$country = trim($_POST["country"] ?? 'India');
$mobile = trim($_POST["mobile"] ?? '');
$project = trim($_POST["project"] ?? '');
$locations = trim($_POST["locations"] ?? '');
$leadLocation = trim($_POST["leadLocation"] ?? '');
$visitor_ip = trim($_POST["visitor_ip"] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

// Validation
if (empty($name) || empty($mobile)) {
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    exit;
}

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    echo "<script>alert('Invalid mobile number.'); window.history.back();</script>";
    exit;
}

// Build remark
$message = "Enquiry from Runwal Garden City website";
$remark = "$message, Location: $locations, Visitor IP: $visitor_ip, Lead Location: $leadLocation";

// CRM config
$sourceid = 1595;
$api_key = "36db0bb612c6408b80637d940351b53c060521043458";

// Payload
$postdata = [
    "name" => $name,
    "mobile" => ($country === 'India' ? '+91' : '') . $mobile,
    "email" => $email,
    "remark" => $remark,
    "project" => $project
];

$urldata = [
    "api_key" => $api_key,
    "source" => $sourceid,
    "responsetype" => $_POST["type"] ?? '',
    "account" => $_POST["account"] ?? ''
];

// Send to CRM
$result = httpPost($urldata, $postdata);
header("Location: ../thankyou.html");
exit;

function httpPost($urldata, $data) {
    $baseurl = "https://connector.b2bbricks.com/api/Integration/postResponse";
    $url = rtrim($baseurl) . "?" . http_build_query($urldata);

    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    return curl_exec($curl);
}
?>
