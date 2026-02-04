<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

// ---- Get real IP ----
function chatbot_getUserIP() {
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

// ---- Get visitor location ----
function chatbot_getLocation($ip) {
    if (in_array($ip, ['127.0.0.1', '::1', '0.0.0.0'], true)) {
        return 'Localhost';
    }

    $url = "https://ipapi.co/{$ip}/json/";
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 5,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'Chatbot/1.0'
    ]);

    $res = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($code === 200 && $res) {
        $data = json_decode($res, true);
        if (is_array($data) && !isset($data['error']) && isset($data['city'])) {
            return "{$data['city']}, {$data['region']}, {$data['country_name']}";
        }
    }

    return 'Unknown';
}

// ---- Build remark based on source (Chatbot vs Form) ----
function chatbot_buildRemark($projectAddress) {
    $ip       = chatbot_getUserIP();
    $location = chatbot_getLocation($ip);

    // Check if it's from Chatbot or Form
    $isChatbot = isset($_POST['form_name']) && $_POST['form_name'] === 'Chatbot Inquiry';
    
    if ($isChatbot) {
        // === CHATBOT SUBMISSION ===
        $chat_option = trim($_POST['chat_option'] ?? 'Not specified');
        $message     = trim($_POST['message'] ?? 'Not specified');
        
        return "Source: Chatbot (Riya Assistant)\n"
             . "Project: {$projectAddress}\n"
             . "Visitor IP: {$ip}\n"
             . "Lead Location: {$location}\n"
             . "Requested Info: {$chat_option}\n"
             . "Message: {$message}\n"
             . "Form Type: Chatbot";
    } else {
        // === REGULAR FORM SUBMISSION ===
        $form_source = trim($_POST['form_source'] ?? 'Website Form');
        $message     = trim($_POST['message'] ?? 'Not specified');
        
        return "Source: Website Form\n"
             . "Project: {$projectAddress}\n"
             . "Visitor IP: {$ip}\n"
             . "Lead Location: {$location}\n"
             . "Form Name: {$form_source}\n"
             . "Message: {$message}";
    }
}

// === Extract & sanitize form data ===
$name = trim($_POST['uname'] ?? '');
$mobile = preg_replace('/\D/', '', $_POST['umobile'] ?? '');

// === Validation ===
if ($name === '' || strlen($mobile) !== 10) {
    header("Location: index.html");
    exit;
}

// === Prepare data ===
$project = 'NeoLiv Khopoli Plots';
$remark = chatbot_buildRemark($project);

// === Webhook payload ===
$payload = [
    'name'    => $name,
    'mobile'  => $mobile,
    'project' => $project,
    'remark'  => $remark
];

// === Send to CRM ===
$webhook_url = "https://connector.b2bbricks.com/api/Integration/hook/bba7baab-fbac-4355-9402-3eda71abb1a7";
$ch = curl_init($webhook_url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
    CURLOPT_TIMEOUT        => 15
]);
curl_exec($ch);
curl_close($ch);

// === Redirect after success ===
header("Location: thankyou.html");
exit;
?>