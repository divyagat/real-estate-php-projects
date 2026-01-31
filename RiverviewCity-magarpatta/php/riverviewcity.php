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
$mobile = trim($_POST["mobile"] ?? '');
$project = trim($_POST["project"] ?? 'Riverview City');
$locations = trim($_POST["locations"] ?? 'Loni Kalbhor');
$leadLocation = trim($_POST["leadLocation"] ?? 'Riverview City');
$visitor_ip = trim($_POST["visitor_ip"] ?? $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');
$chatOption = trim($_POST["chat_option"] ?? '');

// ✅ Detect chatbot submission
$isChatbot = isset($_POST['is_chatbot']) && $_POST['is_chatbot'] === '1';

// Validation
if (empty($name) || empty($mobile)) {
    echo "<script>alert('Please fill all required fields.'); window.history.back();</script>";
    exit;
}

if (!preg_match('/^[6-9][0-9]{9}$/', $mobile)) {
    echo "<script>alert('Invalid mobile number.'); window.history.back();</script>";
    exit;
}

// ✅ Build remark with chatbot indicator and chat option
$inquiry = "Enquiry for Hornbill Heights at Riverview City";
$formSource = $isChatbot ? "Chatbot" : "Website Lead";

// Add chat option to remark if available
$chatInfo = $isChatbot && !empty($chatOption) ? " | Chat Option: $chatOption" : "";

$remark = "Location: $locations, Visitor IP: $visitor_ip, Lead Location: $leadLocation, Inquiry: $inquiry, Form: $formSource$chatInfo";

// CRM config
$sourceid = 1386;
$api_key = "36db0bb612c6408b80637d940351b53c060521043458";

// ✅ Build payload - SKIP email for chatbot
$postdata = [
    "name" => $name,
    "mobile" => '+91' . $mobile,
    "remark" => $remark,
    "project" => $project
];

// Only add email if it's NOT a chatbot submission
if (!$isChatbot && !empty($email)) {
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
    $response = curl_exec($curl);
    
    // Debug: Log CRM response for troubleshooting
    error_log("CRM Response: " . $response);
    
    return $response;
}
?>