<?php
ob_start(); // Start output buffering
session_start();
session_regenerate_id(true);

require 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;
use Dotenv\Dotenv;

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    error_log("Environment variables loaded");
} catch (Exception $e) {
    error_log("Dotenv Error: " . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

$username = $_ENV['AFRICASTALKING_USERNAME'] ?? '';
$apiKey = $_ENV['AFRICASTALKING_API_KEY'] ?? '';

if (empty($username) || empty($apiKey)) {
    error_log("Missing Africa's Talking credentials");
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Server configuration error']);
    exit;
}

$AT = new AfricasTalking($username, $apiKey);

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Session expired']);
    exit;
}
$_SESSION['last_activity'] = time();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($csrfToken !== $_SESSION['csrf_token']) {
        error_log("Invalid CSRF token for phone: " . ($_POST['phone'] ?? 'unknown'));
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $phone = filter_input(INPUT_POST, 'phone', FILTER_SANITIZE_STRING);
    $phone = trim($phone);

    // Validate phone number
    if (!preg_match("/^\+254\d{9}$/", $phone)) {
        error_log("Invalid phone number: $phone");
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }

    // Rate limiting
    $maxAttempts = 5; // Maximum 5 attempts per day
    $cooldown = 86400; // 24 hours in seconds

    if (!isset($_SESSION['otp_attempts'][$phone])) {
        $_SESSION['otp_attempts'][$phone] = 0;
        $_SESSION['otp_last_attempt'][$phone] = 0;
    }

    // Check if we've reached the daily limit
    if ($_SESSION['otp_attempts'][$phone] >= $maxAttempts) {
        $timeSinceLastAttempt = time() - $_SESSION['otp_last_attempt'][$phone];
        if ($timeSinceLastAttempt < $cooldown) {
            $hoursLeft = ceil(($cooldown - $timeSinceLastAttempt) / 3600);
            echo json_encode([
                'success' => false, 
                'message' => "You've reached the maximum number of OTP requests for today. Please try again in {$hoursLeft} hours."
            ]);
            exit;
        } else {
            // Reset counter if 24 hours have passed
            $_SESSION['otp_attempts'][$phone] = 0;
        }
    }

    // Check existing OTP
    if (isset($_SESSION['otp_time']) && (time() - $_SESSION['otp_time']) < 120) {
        echo json_encode(['success' => false, 'message' => 'An OTP is already active. Please wait for it to expire.']);
        exit;
    }

    $otp = sprintf("%04d", random_int(1000, 9999));
    $_SESSION['otp'] = $otp;
    $_SESSION['phone'] = $phone;
    $_SESSION['otp_time'] = time();
    $_SESSION['otp_verify_attempts'] = 0;

    // Send OTP via SMS
    $sms = $AT->sms();
    $message = "Your OTP is: $otp. Valid for 2 minutes.";

    try {
        $result = $sms->send([
            'to' => $phone,
            'message' => $message,
        ]);

        if ($result['status'] === 'success' && !empty($result['data']->SMSMessageData->Recipients)) {
            foreach ($result['data']->SMSMessageData->Recipients as $recipient) {
                if ($recipient->status === 'Success') {
                    $_SESSION['otp_attempts'][$phone]++;
                    $_SESSION['otp_last_attempt'][$phone] = time();
                    error_log("OTP sent successfully to $phone");
                    // Force session write
                    session_write_close();
                    echo json_encode(['success' => true, 'redirect' => 'verify_otp.php']);
                    exit;
                }
            }
            error_log("SMS delivery failed for $phone");
            echo json_encode(['success' => false, 'message' => 'SMS delivery failed. Please try again.']);
            exit;
        }
    } catch (Exception $e) {
        error_log("SMS Error for $phone: " . $e->getMessage());
        echo json_encode(['success' => false, 'message' => 'Failed to send OTP. Please try again.']);
        exit;
    }
}

header('Content-Type: application/json');
echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;
?>