<?php
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Method Not Allowed');
}

$PROJECT_NAME = 'Paradise Sai World Empire';

function getUserIP() {
    $keys = ['HTTP_CF_CONNECTING_IP', 'HTTP_X_REAL_IP', 'HTTP_X_FORWARDED_FOR', 'REMOTE_ADDR'];
    foreach ($keys as $key) {
        if (!empty($_SERVER[$key])) {
            $ip = $_SERVER[$key];
            if ($key === 'HTTP_X_FORWARDED_FOR') {
                $ip = trim(explode(',', $ip)[0]);
            }
            if (filter_var($ip, FILTER_VALIDATE_IP)) {
                return $ip;
            }
        }
    }
    return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
}

$user_ip = getUserIP();
file_put_contents(__DIR__ . '/debug.log', "IP: {$user_ip}\n", FILE_APPEND);

if (in_array($user_ip, ['127.0.0.1', '::1', '0.0.0.0']) || 
    !filter_var($user_ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
    $visitor_location = 'Local Test';
} else {
    // ✅ FIXED: NO SPACES
    $url = "https://ipapi.co/{$user_ip}/json/";

    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT      => 'ParadiseEnquiry/1.0'
    ]);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error    = curl_error($ch);
    curl_close($ch);

    file_put_contents(__DIR__ . '/debug.log', "HTTP: {$httpCode}, Error: {$error}, Response: " . substr($response, 0, 200) . "\n", FILE_APPEND);

    if ($httpCode === 200 && $response) {
        $data = json_decode($response, true);
        if (is_array($data)) {
            $city    = $data['city'] ?? '';
            $region  = $data['region'] ?? '';
            $country = $data['country_name'] ?? '';
            $parts   = array_filter([$city, $region, $country]);
            $visitor_location = implode(', ', $parts) ?: 'Location Unknown';
        } else {
            $visitor_location = 'Invalid API Response';
        }
    } else {
        // ✅ FIXED: NO SPACES
        $ch2 = curl_init("https://ipapi.co/{$user_ip}/country_name/");
        curl_setopt_array($ch2, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 5,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        $country = curl_exec($ch2);
        curl_close($ch2);
        $visitor_location = trim($country) ?: 'Location Unavailable';
    }
}

$name   = trim($_POST['uname'] ?? '');
$mobile = preg_replace('/\D/', '', $_POST['umobile'] ?? '');

// Get chatbot fields
$chat_option = trim($_POST['chat_option'] ?? '');
$message = trim($_POST['message'] ?? '');

// ✅ Accept 10–15 digit mobile (with country code)
if ($name === '' || !ctype_digit($mobile) || strlen($mobile) < 10 || strlen($mobile) > 15) {
    exit('Invalid input');
}

// Build remark
$remark = "Project: {$PROJECT_NAME}, Type: General, Location: {$visitor_location}, Visitor IP: {$user_ip}";
if ($chat_option) {
    $remark .= ", Chatbot Option: " . htmlspecialchars($chat_option, ENT_QUOTES, 'UTF-8');
}
if ($message && !in_array($message, [$chat_option, 'Mobile shared via chatbot', ''])) {
    $remark .= ", Message: " . substr(htmlspecialchars($message, ENT_QUOTES, 'UTF-8'), 0, 100);
}

$interested_in = $PROJECT_NAME;

$postdata = [
    'name'          => $name,           // ✅ Real name from chatbot
    'mobile'        => $mobile,         // ✅ With country code
    'remark'        => $remark,
    'interested_in' => $interested_in,
];

// ✅ FIXED: No trailing spaces
$webhook_url = "https://connector.b2bbricks.com/api/Integration/hook/43d25585-78eb-4866-85d8-ef77d6dedb4e";
$api_key  = "36db0bb612c6408b80637d940351b53c060521043458";
$sourceid = 1386;

$url = $webhook_url . '?' . http_build_query([
    'api_key'      => $api_key,
    'source'       => $sourceid,
    'responsetype' => 'json',
    'account'      => 'default'
]);

$ch = curl_init($url);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query($postdata),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 20,
    CURLOPT_SSL_VERIFYPEER => true,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded']
]);

$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error    = curl_error($ch);
curl_close($ch);

file_put_contents(__DIR__ . '/crm_response.log', date('Y-m-d H:i:s') . " | Name: {$name} | Mobile: {$mobile} | Location: {$visitor_location} | HTTP: {$httpCode}" . PHP_EOL, FILE_APPEND);

if ($httpCode >= 200 && $httpCode < 300) {
    header('Location: thankyou.html', true, 303);
} else {
    file_put_contents(__DIR__ . '/crm_error.log', "CRM Error: HTTP {$httpCode}, Response: " . substr($response, 0, 300) . "\n", FILE_APPEND);
    header('Location: error.html', true, 303);
}
exit;