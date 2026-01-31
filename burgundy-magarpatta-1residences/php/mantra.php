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

// ✅ Detect chatbot submission
$isChatbot = isset($_POST['is_chatbot']) && $_POST['is_chatbot'] === '1';

// Sanitize inputs
$name = trim($_POST["name"] ?? '');
$email = trim($_POST["email"] ?? '');
$country = $isChatbot ? '91' : trim($_POST["country"] ?? '91'); // Force 91 for chatbot
$mobile = trim($_POST["mobile"] ?? '');
$project = trim($_POST["project"] ?? 'Hornbill Heights');
$message = trim($_POST["message"] ?? 'Website Inquiry');
$locations = trim($_POST["locations"] ?? 'Loni Kalbhor');
$leadLocation = trim($_POST["leadLocation"] ?? 'Riverview City');
$visitor_ip = trim($_POST["visitor_ip"] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$chatOption = trim($_POST["chat_option"] ?? '');

// ✅ Validation - REQUIRED FIELDS (name + mobile)
if (empty($name) || empty($mobile)) {
    error_log("Submission failed: Missing name or mobile - IP: " . $_SERVER['REMOTE_ADDR']);
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    exit;
}

// ✅ Mobile validation (10-digit Indian number)
if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    error_log("Submission failed: Invalid mobile format '$mobile' - IP: " . $_SERVER['REMOTE_ADDR']);
    echo "<script>alert('Invalid mobile number. Please enter 10 digits starting with 6-9.');</script>";
    exit;
}

// ✅ Build remark with chatbot identification
if ($isChatbot) {
    // 🤖 CHATBOT: Skip email, add chat option to remark
    $userAction = !empty($chatOption) ? "User selected: $chatOption" : "Chatbot interaction";
    $remark = "[CHATBOT] $userAction | Location: $locations, Visitor IP: $visitor_ip, Lead Location: $leadLocation, Form: Chatbot";
} else {
    // 🌐 REGULAR FORM: Include email if valid
    $remark = "$message, Location: $locations, Visitor IP: $visitor_ip, Lead Location: $leadLocation, Form: Website Lead";
}

// CRM Configuration
$sourceid = 2744;
$api_key = "36db0bb612c6408b80637d940351b53c060521043458";

// ✅ Build payload
$postdata = [
    "name" => $name,
    "mobile" => '+' . $country . $mobile,
    "project" => $project,
    "remark" => $remark
];

// ✅ EMAIL HANDLING: ONLY for regular forms (SKIP for chatbot)
if (!$isChatbot && !empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $postdata["email"] = $email;
}

$urldata = [
    "api_key" => $api_key,
    "source" => $sourceid,
    "responsetype" => $_POST["type"] ?? '',
    "account" => $_POST["account"] ?? ''
];

// Send to CRM
$result = httpPost($urldata, $postdata);

// ✅ Debug logging
error_log("CRM Submission - Name: $name, Mobile: {$postdata['mobile']}, Chatbot: " . ($isChatbot ? 'YES' : 'NO') . ", Email: " . ($isChatbot ? 'SKIPPED' : ($postdata['email'] ?? 'NONE')));

// Redirect
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
    curl_setopt($curl, CURLOPT_TIMEOUT, 15);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
    
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    
    if ($response === false) {
        error_log("cURL Error: " . curl_error($curl) . " - URL: $url");
    } elseif ($http_code >= 400) {
        error_log("CRM HTTP Error $http_code: $response");
    }
    
    curl_close($curl);
    return $response;
}
?>