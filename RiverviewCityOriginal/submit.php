<?php
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    http_response_code(405);
    exit(json_encode(['status' => 'error', 'message' => 'Method Not Allowed']));
}

// 🚨 SET TO true FOR LOCAL TESTING, false ON LIVE SERVER
$DEBUG = false;

/* ===============================
   GET USER IP (SAFE)
================================ */
function getUserIP() {
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) return $_SERVER['HTTP_CF_CONNECTING_IP'];
    if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) return trim(explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0]);
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) return $_SERVER['HTTP_CLIENT_IP'];
    return $_SERVER['REMOTE_ADDR'] ?? 'unknown';
}

$user_ip = getUserIP();

/* ===============================
   FETCH LOCATION (SAFE CURL)
================================ */
function getLocationFromIP($ip) {
    if ($ip === 'unknown' || $ip === '127.0.0.1') {
        return 'Localhost';
    }

    $ch = curl_init("https://ipapi.co/{$ip}/json/");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 3);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

    $result = curl_exec($ch);
    curl_close($ch);

    if (!$result) return 'Unknown Location';

    $data = json_decode($result, true);
    if (!is_array($data)) return 'Unknown Location';

    return trim(
        ($data['city'] ?? '') . ', ' .
        ($data['region'] ?? '') . ', ' .
        ($data['country_name'] ?? '')
    );
}

$user_location = getLocationFromIP($user_ip);

/* ===============================
   FORM TYPE HANDLING (UNCHANGED)
================================ */
$formtype = $_POST['formtype'] ?? '';

switch ($formtype) {

    case 'popup_form':
        $name = trim($_POST["modal_name"] ?? '');
        $email = trim($_POST["modal_email"] ?? '');
        $phone = preg_replace('/\D/', '', $_POST["modal_phone"] ?? '');
        $project = 'Auto Popup Form';
        $remark = "Form: Homepage Auto Popup";
        break;

    case 'enquiry_form':
        $name = trim($_POST["enq_fname"] ?? '') . ' ' . trim($_POST["enq_lname"] ?? '');
        $email = trim($_POST["enq_email"] ?? '');
        $phone = preg_replace('/\D/', '', $_POST["enq_phone"] ?? '');
        $project = $_POST["formproject"] ?? 'Riverview City';
        $remark = "City: " . ($_POST["enq_city"] ?? '') . " | Message: " . ($_POST["enq_message"] ?? '');
        break;

    case 'brochure_form':
        $name = trim($_POST["bro_fname"] ?? '') . ' ' . trim($_POST["bro_lname"] ?? '');
        $email = trim($_POST["bro_email"] ?? '');
        $phone = preg_replace('/\D/', '', $_POST["bro_phone"] ?? '');
        $project = 'Brochure Request';
        $remark = "Form: Brochure Download";
        break;

    case 'bookvisit_form':
        $name = trim($_POST["bsv_name"] ?? '');
        $email = trim($_POST["bsv_email"] ?? '');
        $phone = preg_replace('/\D/', '', $_POST["bsv_phone"] ?? '');
        $project = 'Site Visit Booking';
        $remark = "Visit Time: " . ($_POST["bsv_datetime"] ?? '') . " | Message: " . ($_POST["bsv_message"] ?? '');
        break;

    case 'footer_form':
        $name = trim($_POST["footer_name"] ?? '');
        $email = trim($_POST["footer_email"] ?? '');
        $phone = preg_replace('/\D/', '', $_POST["footer_phone"] ?? '');
        $project = 'Footer Enquiry';
        $remark = "Message: " . ($_POST["footer_message"] ?? '');
        break;

    default:
        exit(json_encode(['status' => 'error', 'message' => 'Invalid form.']));
}

/* ===============================
   VALIDATION (UNCHANGED)
================================ */
if (empty($name) || empty($email) || empty($phone)) {
    exit(json_encode(['status' => 'error', 'message' => 'All fields are required.']));
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    exit(json_encode(['status' => 'error', 'message' => 'Invalid email.']));
}

if (strlen($phone) !== 10 || !ctype_digit($phone)) {
    exit(json_encode(['status' => 'error', 'message' => 'Please enter a valid 10-digit mobile number.']));
}

/* ===============================
   ADD LOCATION TO REMARK (ONLY CHANGE)
================================ */
$remark .= " | IP: {$user_ip} | Location: {$user_location}";

/* ===============================
   DEBUG MODE
================================ */
if ($DEBUG) {
    exit(json_encode([
        'status' => 'success',
        'message' => 'DEBUG MODE',
        'data' => compact('name', 'email', 'phone', 'project', 'remark')
    ]));
}

/* ===============================
   SEND TO B2BBricks (UNCHANGED)
================================ */
$api_key = "36db0bb612c6408b80637d940351b53c060521043458";
$sourceid = 1386;

$postdata = [
    "name" => $name,
    "mobile" => $phone,
    "email" => $email,
    "remark" => $remark,
    "project" => $project
];

$urldata = [
    "api_key" => $api_key,
    "source" => $sourceid,
    "responsetype" => "json",
    "account" => "default"
];

$baseurl = "https://connector.b2bbricks.com/api/Integration/hook/448f14c5-6ed0-4c02-8c59-e4167e74b276";
$url = rtrim($baseurl) . "?" . http_build_query($urldata);

$curl = curl_init($url);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($postdata));
curl_setopt($curl, CURLOPT_HTTPHEADER, ['Content-Type: application/json']);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_TIMEOUT, 20);
curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);

$response = curl_exec($curl);
$err = curl_error($curl);
curl_close($curl);

if ($err) {
    exit(json_encode(['status' => 'error', 'message' => 'Submission failed.']));
}

exit(json_encode(['status' => 'success', 'message' => 'Thank you! We’ll contact you shortly.']));
?>
