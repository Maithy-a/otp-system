<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if OTP session exists
if (!isset($_SESSION['otp']) || !isset($_SESSION['otp_time'])) {
    header('Location: index.php');
    exit;
}

// Check if OTP has expired (5 minutes)
$expiry_time = 5 * 60; // 5 minutes in seconds
if (time() - $_SESSION['otp_time'] > $expiry_time) {
    session_destroy();
    header('Location: index.php');
    exit;
}

$verification_success = false;
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $inputOtp = trim(filter_input(INPUT_POST, 'otp', FILTER_SANITIZE_NUMBER_INT));

    if (isset($_SESSION['otp']) && $inputOtp === $_SESSION['otp']) {
        session_regenerate_id(true); // Prevent session fixation
        unset($_SESSION['otp']);
        unset($_SESSION['otp_time']);
        $verification_success = true;
    } else {
        $error = "Invalid OTP. Please try again.";
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
    <script src="https://cdn.jsdelivr.net/npm/@tabler/core@1.0.0-beta24/dist/js/tabler.min.js"></script>
</head>

<body>
    <div class="page page-center">
        <div class="container container-tight py-4">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title mb-0">Verify OTP</h3>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['phone'])): ?>
                        <div class="alert alert-success mb-3">
                            OTP sent to: <?php echo htmlspecialchars($_SESSION['phone']); ?>
                        </div>
                    <?php endif; ?>

                    <?php if (isset($error)): ?>
                        <div class="alert alert-danger mb-3">
                            <?php echo htmlspecialchars($error); ?>
                        </div>
                    <?php endif; ?>

                    <form action="" method="POST" id="otp-form">
                        <div class="mb-3">
                            <label class="form-label" for="otp">Enter the 4-digit OTP</label>
                            <div id="otp" class="inputs d-flex flex-row justify-content-center mt-2">
                                <input class="m-2 text-center form-control rounded" type="text" maxlength="1"
                                    name="otp[]" required>
                                <input class="m-2 text-center form-control rounded" type="text" maxlength="1"
                                    name="otp[]" required>
                                <input class="m-2 text-center form-control rounded" type="text" maxlength="1"
                                    name="otp[]" required>
                                <input class="m-2 text-center form-control rounded" type="text" maxlength="1"
                                    name="otp[]" required>
                            </div>
                            <input type="hidden" id="finalOtp" name="otp">
                            <div class="form-text text-muted">
                                Please enter the 4-digit code sent to your phone
                            </div>
                        </div>
                        <div class="form-footer">
                            <button class="btn btn-primary w-100" type="submit">
                                Verify OTP
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener("DOMContentLoaded", function () {
            const otpInputs = document.querySelectorAll('#otp input');
            const finalOtpInput = document.getElementById('finalOtp');
            otpInputs[0].focus();

            otpInputs.forEach((input, index) => {
                input.addEventListener('input', (e) => {
                    const value = e.target.value;
                    if (/^\d$/.test(value)) {
                        if (index < otpInputs.length - 1) {
                            otpInputs[index + 1].focus();
                        } else {
                            otpInputs[index].blur();
                        }
                    } else {
                        input.value = '';
                    }
                    finalOtpInput.value = Array.from(otpInputs).map(i => i.value).join('');
                });

                input.addEventListener('keydown', (e) => {
                    if (e.key === "Backspace") {
                        input.value = '';
                        if (index > 0) {
                            otpInputs[index - 1].focus();
                        }
                    }
                });
            });
        });
    </script>

    <?php if ($verification_success): ?>
        <script>
            document.addEventListener("DOMContentLoaded", function () {
                var successModal = new bootstrap.Modal(document.getElementById('successModal'), {
                    backdrop: 'static',
                    keyboard: false
                });
                successModal.show();
            });
        </script>
    <?php endif; ?>

    <!-- Success Modal -->
    <div class="modal" id="successModal" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-sm" role="document">
            <div class="modal-content">
                <div class="modal-status bg-success"></div>
                <div class="modal-body text-center py-4">
                    <svg xmlns="http://www.w3.org/2000/svg" class="icon mb-2 text-green icon-lg" width="24" height="24"
                        viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round"
                        stroke-linejoin="round">
                        <path stroke="none" d="M0 0h24v24H0z" fill="none" />
                        <circle cx="12" cy="12" r="9" />
                        <path d="M9 12l2 2l4 -4" />
                    </svg>
                    <h3>Phone Verified</h3>
                    <div class="text-secondary">
                        Your phone number verification has been successful.
                        WELCOME 
                    </div>
                </div>
                <div class="modal-footer">
                    <div class="w-100">
                        <div class="row">
                            <div class="col">
                                <a href="logout.php" class="btn w-100">
                                    Logout
                                </a>
                            </div>
                            <div class="col">
                                <a href="logout.php" class="btn btn-success w-100">
                                    Done
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>

</html>