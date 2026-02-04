<?php
/**
 * Plots Properties Lead Handler - Production Version
 * Secure CRM Integration with Comprehensive Validation
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
header('X-Frame-Options: DENY');
header('X-XSS-Protection: 1; mode=block');

// Security hardening
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/lead_errors_' . date('Y-m') . '.log');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_STRICT);

// CORS - MATCHES YOUR DOMAIN FROM HTML <base href>
header('Access-Control-Allow-Origin: https://www.plotsproperties.com');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Rate limiting
$rateLimitKey = 'lead_submission_' . $_SERVER['REMOTE_ADDR'];
$rateLimitMax = 3;
$rateLimitWindow = 300;
$rateLimitFile = __DIR__ . '/logs/rate_limit_' . md5($rateLimitKey) . '.log';

if (file_exists($rateLimitFile)) {
    $lastAttempts = json_decode(file_get_contents($rateLimitFile), true) ?? [];
    $recentAttempts = array_filter($lastAttempts, fn($t) => $t > time() - $rateLimitWindow);
    
    if (count($recentAttempts) >= $rateLimitMax) {
        http_response_code(429);
        echo json_encode([
            'success' => false,
            'message' => 'Too many requests. Please try again later.',
            'retry_after' => $rateLimitWindow
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    
    $recentAttempts[] = time();
    file_put_contents($rateLimitFile, json_encode(array_slice($recentAttempts, -10)));
} else {
    file_put_contents($rateLimitFile, json_encode([time()]));
    chmod($rateLimitFile, 0600);
}

// ======================
// HELPER FUNCTIONS
// ======================

function get_sanitized_input(string $key, string $type = 'string'): mixed {
    $value = $_POST[$key] ?? '';
    $value = trim(strip_tags($value));
    $value = preg_replace('/[\x00-\x1F\x7F]/u', '', $value);
    
    return match($type) {
        'string' => preg_replace('/[^\p{L}\p{N}\s\-_,.()&]/u', '', $value),
        'phone' => preg_replace('/\D/', '', $value),
        'project' => preg_replace('/[^\p{L}\p{N}\s\-_,.&()]/u', '', $value),
        'budget' => preg_replace('/[^\p{N}\s\-₹,L–Cr+]/u', '', $value),
        default => $value
    };
}

function validate_indian_mobile(string $mobile): array {
    $clean = preg_replace('/\D/', '', $mobile);
    
    if (strlen($clean) === 12 && strpos($clean, '91') === 0) {
        $clean = substr($clean, 2);
    } elseif (strlen($clean) === 13 && strpos($clean, '91') === 0) {
        $clean = substr($clean, 3);
    }
    
    if (!preg_match('/^[6-9][0-9]{9}$/', $clean)) {
        return ['valid' => false, 'error' => 'Invalid mobile format. Must be 10-digit Indian number starting with 6-9'];
    }
    
    $testNumbers = ['9999999999', '9876543210', '9123456789', '8881188181'];
    if (in_array($clean, $testNumbers)) {
        return ['valid' => false, 'error' => 'Test numbers not accepted. Please provide your actual mobile number'];
    }
    
    return ['valid' => true, 'number' => $clean];
}

function get_real_ip(): string {
    $check_headers = [
        'HTTP_CF_CONNECTING_IP',
        'HTTP_X_FORWARDED_FOR',
        'HTTP_CLIENT_IP',
        'HTTP_X_REAL_IP',
        'REMOTE_ADDR'
    ];
    
    foreach ($check_headers as $header) {
        if (!empty($_SERVER[$header])) {
            $ips = explode(',', $_SERVER[$header]);
            foreach ($ips as $ip) {
                $ip = trim($ip);
                if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                    return $ip;
                }
            }
        }
    }
    
    $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

function get_location_from_ip(string $ip): string {
    if ($ip === '0.0.0.0' || $ip === '127.0.0.1') return 'Local Network';
    
    $cache_file = __DIR__ . "/logs/location_cache_{$ip}.json";
    $cache_ttl = 86400;
    
    if (file_exists($cache_file) && (time() - filemtime($cache_file) < $cache_ttl)) {
        $cached = json_decode(file_get_contents($cache_file), true);
        if ($cached && !empty($cached['location'])) {
            return $cached['location'];
        }
    }
    
    $geo_url = "http://ip-api.com/json/{$ip}?fields=city,regionName,country,timezone";
    $context = stream_context_create([
        'http' => [
            'timeout' => 4,
            'user_agent' => 'PlotsPropertiesLeadHandler/1.0'
        ]
    ]);
    
    $response = @file_get_contents($geo_url, false, $context);
    $location = 'Unknown Location';
    
    if ($response !== false) {
        $geo = json_decode($response, true);
        if (is_array($geo) && empty($geo['status'] ?? '') && !empty($geo['city'])) {
            $parts = [];
            if (!empty($geo['city'])) $parts[] = $geo['city'];
            if (!empty($geo['regionName'])) $parts[] = $geo['regionName'];
            if (!empty($geo['country']) && $geo['country'] !== 'India') $parts[] = $geo['country'];
            $location = implode(', ', $parts);
            
            file_put_contents($cache_file, json_encode([
                'location' => $location,
                'timestamp' => time()
            ]));
            chmod($cache_file, 0600);
        }
    }
    
    return $location;
}

function mask_sensitive_data(array $data): array {
    if (!empty($data['mobile'])) {
        $data['mobile'] = preg_replace('/(\d{3})\d{4}(\d{3})/', '$1****$2', $data['mobile']);
    }
    if (!empty($data['name'])) {
        $nameParts = explode(' ', $data['name']);
        $maskedName = '';
        foreach ($nameParts as $i => $part) {
            if ($i === 0 && strlen($part) > 2) {
                $maskedName .= substr($part, 0, 1) . str_repeat('*', strlen($part) - 1) . ' ';
            } else {
                $maskedName .= $part . ' ';
            }
        }
        $data['name'] = trim($maskedName);
    }
    return $data;
}

function audit_log(string $context, array $data = []): void {
    $logDir = __DIR__ . '/logs';
    if (!is_dir($logDir)) {
        mkdir($logDir, 0750, true);
    }
    
    $maskedData = mask_sensitive_data($data);
    $logEntry = sprintf(
        "[%s] [%s] IP:%s | Data:%s\n",
        date('Y-m-d H:i:s'),
        $context,
        get_real_ip(),
        json_encode($maskedData, JSON_UNESCAPED_UNICODE)
    );
    
    $logFile = $logDir . '/audit_' . date('Y-m-d') . '.log';
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
    chmod($logFile, 0600);
}

// ======================
// MAIN EXECUTION
// ======================

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method', 405);
    }
    
    $ipAddress = get_real_ip();
    $visitorLocation = get_location_from_ip($ipAddress);
    $submissionTime = date('Y-m-d H:i:s');
    $isChatbot = !empty($_POST['uname']) && !empty($_POST['umobile']);
    
    // ======================
    // CHATBOT SUBMISSION
    // ======================
    if ($isChatbot) {
        $name = get_sanitized_input('uname', 'string');
        $rawMobile = get_sanitized_input('umobile', 'phone');
        $chatOption = get_sanitized_input('chat_option', 'string');
        $formName = get_sanitized_input('form_name', 'string');
        
        if (strlen($name) < 2 || strlen($name) > 100) {
            throw new Exception('Name must be 2-100 characters', 400);
        }
        
        $mobileValidation = validate_indian_mobile($rawMobile);
        if (!$mobileValidation['valid']) {
            throw new Exception($mobileValidation['error'], 400);
        }
        $mobile = $mobileValidation['number'];
        
        if (empty($chatOption)) {
            $chatOption = 'Plots Information Request';
        }
        
        // DYNAMIC FORM NAME MAPPING FOR BETTER CRM TRACKING
        $formNameMap = [
            'Brochure' => 'Chatbot - Brochure Request',
            'PriceList' => 'Chatbot - Price List Request',
            'Floor Plan' => 'Chatbot - Plots Plan Request',
            'Offers' => 'Chatbot - Current Offers Inquiry',
            'WhatsApp' => 'Chatbot - WhatsApp Details Request'
        ];
        $dynamicFormName = $formNameMap[$chatOption] ?? 'Chatbot Inquiry';
        
        $projectClean = 'Plots Properties Portfolio';
        
        $remark = sprintf(
            "Source: Website Chatbot (Riya Assistant) | Location: Pune | Visitor IP: %s | Lead Location: %s | Requested Info: %s | Form Type: %s | Submitted: %s",
            $ipAddress,
            $visitorLocation,
            $chatOption,
            $dynamicFormName,
            $submissionTime
        );
        
        $crmData = [
            'name' => $name,
            'mobile' => '+91' . $mobile,
            'project' => $projectClean,
            'remark' => $remark
        ];
        
        $sourceId = '1386';
        $submissionType = 'chatbot';
    } 
    // ======================
    // FORM SUBMISSION
    // ======================
    else {
        $fullName = get_sanitized_input('full_name', 'string');
        $rawPhone = get_sanitized_input('phone', 'phone');
        $projectFull = get_sanitized_input('location', 'project');
        $unitType = get_sanitized_input('interest', 'string');
        $budget = get_sanitized_input('budget', 'budget');
        
        $errors = [];
        
        if (strlen($fullName) < 2 || strlen($fullName) > 100) {
            $errors[] = 'Full name must be 2-100 characters';
        }
        
        $phoneValidation = validate_indian_mobile($rawPhone);
        if (!$phoneValidation['valid']) {
            $errors[] = $phoneValidation['error'];
        } else {
            $phone = $phoneValidation['number'];
        }
        
        if (empty($projectFull) || $projectFull === 'Choose Project') {
            $errors[] = 'Please select a valid project';
        }
        
        if (empty($unitType) || $unitType === 'Select') {
            $errors[] = 'Please select plot size';
        }
        
        if (!empty($errors)) {
            throw new Exception(implode('; ', $errors), 400);
        }
        
        // EXTRACT AREA FROM PARENTHESES (MATCHES HTML OPTIONS)
        $area = 'Pune';
        if (preg_match('/\(([^)]+)\)/', $projectFull, $matches)) {
            $area = trim($matches[1]);
            $projectClean = trim(str_replace($matches[0], '', $projectFull));
        } else {
            $projectClean = $projectFull;
            $areaKeywords = [
                'Hinjewadi' => 'Hinjewadi, Pune',
                'Kharadi' => 'Kharadi, Pune',
                'Baner' => 'Baner, Pune',
                'Talegaon' => 'Talegaon, Pune'
            ];
            foreach ($areaKeywords as $keyword => $location) {
                if (stripos($projectFull, $keyword) !== false) {
                    $area = $location;
                    break;
                }
            }
        }
        
        $remark = sprintf(
            "Source: Website Form | Location: %s | Visitor IP: %s | Lead Location: %s | Plot Size: %s | Budget: %s | Submitted: %s",
            $area,
            $ipAddress,
            $visitorLocation,
            $unitType,
            $budget ?: 'Not specified',
            $submissionTime
        );
        
        $crmData = [
            'name' => $fullName,
            'mobile' => '+91' . $phone,
            'project' => $projectClean,
            'remark' => $remark
        ];
        
        $sourceId = '1386';
        $submissionType = 'form';
    }
    
    // ======================
    // CRM INTEGRATION (CRITICAL FIX: NO TRAILING SPACES)
    // ======================
    $crmConfig = [
        'endpoint' => 'https://connector.b2bbricks.com/api/Integration/hook/bba7baab-fbac-4355-9402-3eda71abb1a7',
        'api_key' => '36db0bb612c6408b80637d940351b53c060521043458',
        'source' => $sourceId,
        'timeout' => 12
    ];
    
    $queryParams = http_build_query([
        'api_key' => $crmConfig['api_key'],
        'source' => $crmConfig['source'],
        'responsetype' => '',
        'account' => ''
    ]);
    
    $fullUrl = rtrim($crmConfig['endpoint']) . '?' . $queryParams;
    
    audit_log('CRM_SUBMISSION_ATTEMPT', [
        'type' => $submissionType,
        'project' => $crmData['project'],
        'name' => $crmData['name'],
        'mobile' => $crmData['mobile'],
        'source_id' => $sourceId,
        'ip' => $ipAddress
    ]);
    
    $ch = curl_init($fullUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($crmData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=utf-8',
            'User-Agent: PlotsPropertiesLeadHandler/2.0',
            'X-Forwarded-For: ' . $ipAddress
        ],
        CURLOPT_TIMEOUT => $crmConfig['timeout'],
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_MAXREDIRS => 2
    ]);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $curlError = curl_error($ch);
    curl_close($ch);
    
    // Process CRM response
    $crmSuccess = false;
    $crmMessage = 'Lead submitted successfully';
    
    if ($curlError) {
        $crmMessage = "Connection error: {$curlError}";
        audit_log('CRM_CONNECTION_ERROR', [
            'error' => $curlError,
            'url' => $fullUrl,
            'http_code' => $httpCode
        ]);
    } 
    elseif ($httpCode >= 200 && $httpCode < 300) {
        $responseData = json_decode($response, true);
        
        if (is_array($responseData)) {
            if (isset($responseData['success']) && $responseData['success'] === false) {
                $crmMessage = $responseData['message'] ?? 'CRM rejected the lead';
                audit_log('CRM_REJECTED_LEAD', [
                    'response' => $responseData,
                    'project' => $crmData['project']
                ]);
            } else {
                $crmSuccess = true;
            }
        } else {
            $crmSuccess = true;
        }
    } 
    else {
        $crmMessage = "CRM returned HTTP {$httpCode}";
        audit_log('CRM_HTTP_ERROR', [
            'http_code' => $httpCode,
            'response' => substr($response, 0, 500),
            'project' => $crmData['project']
        ]);
    }
    
    audit_log($crmSuccess ? 'CRM_SUBMISSION_SUCCESS' : 'CRM_SUBMISSION_FAILED', [
        'type' => $submissionType,
        'project' => $crmData['project'],
        'http_code' => $httpCode,
        'crm_success' => $crmSuccess,
        'crm_message' => $crmMessage
    ]);
    
    // SUCCESS RESPONSE
    if ($crmSuccess) {
        $maskedNumber = substr($isChatbot ? $mobile : $phone, -4);
        $responseMessage = ($isChatbot) 
            ? "Thank you {$crmData['name']}! We've sent the {$chatOption} details to your WhatsApp. Our plots consultant will contact you shortly at +91******{$maskedNumber}"
            : "Thank you {$crmData['name']}! Our plots consultant will contact you shortly at +91******{$maskedNumber}";
        
        http_response_code(200);
        echo json_encode([
            'success' => true,
            'message' => $responseMessage,
            'submission_id' => bin2hex(random_bytes(8))
        ], JSON_UNESCAPED_UNICODE);
        exit;
    } else {
        throw new Exception($crmMessage, 502);
    }
    
} catch (Exception $e) {
    $errorCode = $e->getCode() ?: 500;
    $errorMessage = $e->getMessage();
    
    error_log(sprintf(
        "[%s] Lead Submission Error [%d]: %s | IP: %s | POST: %s",
        date('Y-m-d H:i:s'),
        $errorCode,
        $errorMessage,
        get_real_ip(),
        json_encode($_POST, JSON_UNESCAPED_UNICODE)
    ));
    
    audit_log('SUBMISSION_FAILED', [
        'error_code' => $errorCode,
        'error_message' => $errorMessage,
        'post_data' => array_keys($_POST)
    ]);
    
    http_response_code($errorCode >= 400 && $errorCode < 500 ? $errorCode : 500);
    
    // CORRECTED WHATSAPP NUMBER (8881188181 - MATCHES HTML)
    $whatsappLink = '<a href="https://wa.me/918881188181" style="color:#25D366;text-decoration:underline">+91 88811 88181</a>';
    
    $userMessage = match(true) {
        $errorCode === 429 => 'Too many requests. Please try again in 5 minutes.',
        $errorCode === 400 => 'Please check your information and try again. ' . $errorMessage,
        $errorCode === 403 => 'Security verification failed. Please refresh the page and try again.',
        $errorCode === 405 => 'Invalid request method',
        default => "We're experiencing technical difficulties. Please contact us directly on WhatsApp: {$whatsappLink}"
    };
    
    echo json_encode([
        'success' => false,
        'message' => $userMessage,
        'error_code' => $errorCode
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
?>