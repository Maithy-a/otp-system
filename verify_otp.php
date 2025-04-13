<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if OTP session exists
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'message' => 'OTP session expired. Please request a new code.']);
        exit;
    }
    header('Location: index.php');
    exit;
}

$expiry_time = 5 * 60; // 5 minutes in seconds
if (time() - $_SESSION['otp_time'] > $expiry_time) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        echo json_encode(['success' => false, 'message' => 'OTP has expired. Please request a new code.']);
        exit;
    }
    session_destroy();
    header('Location: index.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $inputOtp = trim(filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_NUMBER_INT));

    if (isset($_SESSION['otp']) && $inputOtp === $_SESSION['otp']) {
        session_regenerate_id(true); // Prevent session fixation
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        echo json_encode(['success' => true]);
        exit;
    } else {
        echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
        exit;
    }
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/css/tabler.min.css">
    <link rel="stylesheet" href="style.css">
    <!-- iziToast CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/js/tabler.min.js"></script>
    <!-- iziToast JS -->
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>

</head>

<body>
    <div class="verification-container">
        <div class="verification-card">
            <svg class="verification-icon" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none"
                stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                <polyline points="22 4 12 14.01 9 11.01"></polyline>
            </svg>
            <h1 class="verification-title">Verify OTP</h1>
            <p class="verification-subtitle">Enter the 4-digit code sent to your phone</p>

            <?php if (isset($_SESSION['phone'])): ?>
                <p class="phone-info">
                    Code sent to: <?php echo htmlspecialchars($_SESSION['phone']); ?>
                    <button class="resend-button" id="resend-otp">Resend Code</button>
                </p>
            <?php endif; ?>

            <form action="" method="POST" id="otp-form">
                <input class="otp-input" type="text" id="otp" name="otp" pattern="\d{4}" maxlength="4"
                    placeholder="0000" required>

                <button class="verify-button" type="submit">
                    Verify Code
                </button>
            </form>
        </div>
    </div>
    <script src="main.js"></script>
</body>

</html>