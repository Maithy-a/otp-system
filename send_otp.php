<?php
session_start();
require 'vendor/autoload.php';

use AfricasTalking\SDK\AfricasTalking;
use Dotenv\Dotenv;

error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    $dotenv = Dotenv::createImmutable(__DIR__);
    $dotenv->load();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Error loading environment configuration']);
    exit;
}

$username = $_ENV['AFRICASTALKING_USERNAME'];
$apiKey = $_ENV['AFRICASTALKING_API_KEY'];


if (empty($username) || empty($apiKey)) {
    echo json_encode(['success' => false, 'message' => 'Error: AfricasTalking credentials not found']);
    exit;
}

$AT = new AfricasTalking($username, $apiKey);

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $phone = trim($_POST['phone']);

    // Validate phone number
    if (preg_match("/^\+254\d{9}$/", $phone)) {
        $otp = sprintf("%04d", rand(1000, 9999));
        $_SESSION['otp'] = $otp;
        $_SESSION['phone'] = $phone;
        $_SESSION['otp_time'] = time();

        // Send OTP via SMS
        $sms = $AT->sms();
        $message = "Your OTP is: $otp. Valid for 5 minutes.";

        try {
            $result = $sms->send([
                'to' => $phone,
                'message' => $message,
            ]);

            if ($result['status'] === 'success') {
                $response = $result['data'];
                if ($response->SMSMessageData->Recipients[0]->status === 'Success') {
                    echo json_encode(['success' => true]);
                    exit;
                } else {
                    echo json_encode(['success' => false, 'message' => 'SMS sending failed']);
                    exit;
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to send OTP']);
                exit;
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => 'Error: ' . $e->getMessage()]);
            exit;
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
        exit;
    }
}

echo json_encode(['success' => false, 'message' => 'Invalid request']);
exit;