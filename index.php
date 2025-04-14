<?php
session_start();
session_regenerate_id(true);

require 'vendor/autoload.php';

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

// Generate CSRF token
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Session timeout
if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 1800)) {
    session_unset();
    session_destroy();
    header('Location: index.php');
    exit;
}
$_SESSION['last_activity'] = time();
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
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
            <div class="form-wrapper">
                <form action="send_otp.php" method="POST" id="otp-form">
                    <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input class="phone-input" type="tel" id="phone" name="phone" pattern="^\+254\d{9}$" placeholder="+254700000000" value="+254" required aria-label="Phone number">
                    <button class="verify-button" type="submit" aria-label="Send verification code">Send Verification Code</button>
                </form>
            </div>
        </div>
    </div>
    <script src="main.js"></script>
</body>
</html>