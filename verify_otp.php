<?php
ob_start(); // Start output buffering
session_start();
session_regenerate_id(true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

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

// Check if OTP session exists
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode(['success' => false, 'message' => 'OTP session expired. Please request a new code.']);
        exit;
    }
    error_log("No OTP session for phone: " . ($_SESSION['phone'] ?? 'unknown'));
    header('Location: index.php');
    exit;
}

$expiry_time = 2 * 60; // 2 minutes
if (time() - $_SESSION['otp_time'] > $expiry_time) {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'message' => 'OTP has expired! Please request a new code.',
            'expired' => true
        ]);
        exit;
    }
    // For GET requests, we'll handle the UI in the HTML
    error_log("OTP expired for phone: " . ($_SESSION['phone'] ?? 'unknown'));
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');

    $csrfToken = filter_input(INPUT_POST, 'csrf_token', FILTER_SANITIZE_STRING);
    if ($csrfToken !== $_SESSION['csrf_token']) {
        error_log("Invalid CSRF token for OTP verification");
        echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
        exit;
    }

    $inputOtp = trim(filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_NUMBER_INT));

    if (!preg_match('/^\d{4}$/', $inputOtp)) {
        echo json_encode(['success' => false, 'message' => 'OTP must be a 4-digit number']);
        exit;
    }

    // OTP verification attempt limit
    if (!isset($_SESSION['otp_verify_attempts'])) {
        $_SESSION['otp_verify_attempts'] = 0;
    }
    if ($_SESSION['otp_verify_attempts'] >= 5) {
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            header('Content-Type: application/json');
            echo json_encode([
                'success' => false,
                'message' => 'Too many attempts. Please request a new OTP.',
                'maxAttemptsReached' => true
            ]);
            exit;
        }
        // For GET requests, we'll handle the UI in the HTML
    }

    if ($inputOtp === $_SESSION['otp']) {
        $_SESSION['otp_verify_attempts'] = 0;
        session_regenerate_id(true);
        unset($_SESSION['otp'], $_SESSION['otp_time']);
        error_log("OTP verified successfully for phone: " . $_SESSION['phone']);
        echo json_encode(['success' => true]);
        exit;
    }

    $_SESSION['otp_verify_attempts']++;
    error_log("Failed OTP attempt for phone: " . $_SESSION['phone']);
    echo json_encode(['success' => false, 'message' => 'Invalid OTP. Please try again.']);
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verify OTP</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
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
                    Code sent to: <span
                        class="phone-number"><?php echo htmlspecialchars($_SESSION['phone'], ENT_QUOTES, 'UTF-8'); ?></span>
                    <button class="resend-button" id="resend-otp" type="button" aria-label="Resend OTP code">
                        <br>Resend
                        Code</button>
                </p>
            <?php endif; ?>
            <div class="form-wrapper">
                <form action="verify_otp.php" method="POST" id="otp-form">
                    <input type="hidden" name="csrf_token"
                        value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                    <input class="otp-input" type="text" id="otp" name="otp" pattern="\d{4}" maxlength="4"
                        placeholder="0000" required aria-label="4-digit OTP code">
                    <button class="verify-button" type="submit" aria-label="Verify OTP">Verify OTP</button>
                    <a href="index.php" class="change-number" aria-label="Change phone number">Change Number</a>
                </form>
            </div>
        </div>
    </div>
    <script src="main.js"></script>
    <script>
        const expiryTime = <?php echo $_SESSION['otp_time'] + 60; ?>;
        const subtitle = document.querySelector(".verification-subtitle");
        const updateCountdown = () => {
            const secondsLeft = Math.max(0, expiryTime - Math.floor(Date.now() / 1000));
            if (secondsLeft > 0) {
                subtitle.textContent = `Enter the 4-digit code sent to your phone (expires in ${secondsLeft}s)`;
                setTimeout(updateCountdown, 1000);
            } else {
                subtitle.textContent = "OTP has expired. Please request a new code.";
            }
        };
        updateCountdown();
    </script>
</body>

</html>