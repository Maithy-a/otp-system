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
    <title>Request OTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/css/tabler.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/js/tabler.min.js"></script>
</head>

<body>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Phone Verification</h3>
                </div>
                <div class="card-body">
                    <form action="" method="POST">
                        <div class="mb-3">
                            <label class="form-label" for="phone">Phone Number</label>
                            <input class="form-control form-control-md" type="tel" id="phone" name="phone"
                                pattern="^\+254\d{9}$" placeholder="Enter your phone number" required>
                            <div class="form-text text-muted">
                                Format: +254XXXXXXXXX
                            </div>
                        </div>
                        <div class="form-footer">
                            <button class="btn btn-primary w-100" type="submit">
                                Request otp
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</body>

</html>