<?php
require 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;
use Dotenv\Dotenv;

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Start logging
error_log("Script started");

// Load environment variables
try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
    error_log("Environment variables loaded");
} catch (Exception $e) {
    error_log("Error loading .env file: " . $e->getMessage());
    die("Error loading environment configuration");
}

session_start();

$username = $_ENV['AFRICASTALKING_USERNAME'];
$apiKey = $_ENV['AFRICASTALKING_API_KEY'];

error_log("Username exists: " . (!empty($username) ? 'yes' : 'no'));
error_log("API Key exists: " . (!empty($apiKey) ? 'yes' : 'no'));

// Verify credentials are loaded
if (empty($username) || empty($apiKey)) {
    error_log("Missing AfricasTalking credentials");
    die("Error: AfricasTalking credentials not found in .env file");
}

$AT = new AfricasTalking($username, $apiKey);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    error_log("Form submitted");
    error_log("POST data: " . print_r($_POST, true));

    $phone = trim($_POST['phone']);
    error_log("Phone number received: " . $phone);

    // Validate phone number
    if (preg_match("/^\+254\d{9}$/", $phone)) {
        error_log("Phone number validation passed");

        $otp = sprintf("%04d", rand(1000, 9999));
        $_SESSION['otp'] = $otp;
        $_SESSION['phone'] = $phone;
        $_SESSION['otp_time'] = time();

        error_log("Generated OTP: " . $otp);

        // Send OTP via SMS
        $sms = $AT->sms();
        $message = "Your OTP is: $otp. Valid for 5 minutes.";

        try {
            error_log("Attempting to send SMS");
            $result = $sms->send([
                'to' => $phone,
                'message' => $message,
            ]);

            error_log("SMS API Response: " . print_r($result, true));

            // Detailed response checking
            if ($result['status'] === 'success') {
                $response = $result['data'];
                if ($response->SMSMessageData->Recipients[0]->status === 'Success') {
                    error_log("SMS sent successfully");
                    header('Location: verify_otp.php');
                    exit;
                } else {
                    $error = "SMS sending failed: " . $response->SMSMessageData->Recipients[0]->status;
                    error_log("SMS sending failed: " . $error);
                    echo "<div class='error'>$error</div>";
                }
            } else {
                error_log("Failed to send SMS");
                echo "<div class='error'>Failed to send OTP. Please try again.</div>";
            }
        } catch (Exception $e) {
            $error = "Error: " . htmlspecialchars($e->getMessage());
            error_log("Exception while sending SMS: " . $e->getMessage());
            echo "<div class='error'>$error</div>";
        }
    } else {
        error_log("Phone number validation failed");
        echo "<div class='error'>Invalid phone number format. Please use format: +254XXXXXXXXX</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="Secure OTP Verification System">
    <meta name="keywords" content="OTP, verification, security, authentication">
    <title>OTP Verification System</title>
    <link rel="icon" href="/favicon.ico" type="image/x-icon">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/css/tabler.min.css">
    <link rel="stylesheet" href="style.css">
   
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/js/tabler.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>

</head>

<body>
    <div class="verification-container">
        <div class="verification-card">
            <svg class="verification-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h1 class="verification-title">Phone Verification</h1>
            <p class="verification-subtitle">Enter your phone number to receive a verification code</p>
            
            <form action="" method="POST" id="otp-form">
                <input class="phone-input" type="tel" id="phone" name="phone"
                    pattern="^\+254\d{9}$" placeholder="+254700000000" required>
                
                <button class="verify-button" type="submit" id="send-otp-btn">
                    Send Verification Code
                </button>
            </form>
        </div>
    </div>
<script src="main.js"></script>
</body>

</html>