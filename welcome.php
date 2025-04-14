<?php
ob_start();
session_start();
session_regenerate_id(true);

error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);

if (!isset($_SESSION['phone'])) {
    error_log("No phone session in welcome.php");
    header('Location: index.php');
    exit;
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
    <title>Welcome - OTP System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <script src="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/js/iziToast.min.js"></script>
</head>
<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <i class="fas fa-check-circle welcome-icon"></i>
            <h1 class="welcome-title">Welcome!</h1>
            <p class="welcome-message">Your phone number has been successfully verified.</p>
            <div class="welcome-phone">
                <?php echo htmlspecialchars($_SESSION['phone'], ENT_QUOTES, 'UTF-8'); ?>
            </div>
            <button onclick="logout()" class="welcome-button" aria-label="Logout">Logout</button>
        </div>
    </div>
    <script>
        iziToast.success({
            title: 'Success!',
            message: 'Verified successfully.',
            position: 'topRight'
        });

        function logout() {
            fetch('logout.php', { method: 'POST' })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        window.location.href = 'index.php';
                    } else {
                        throw new Error('Logout failed');
                    }
                })
                .catch(error => {
                    console.error('Logout failed:', error);
                    iziToast.error({
                        title: 'Error',
                        message: 'Logout failed. Try again.',
                        position: 'topRight'
                    });
                });
        }
    </script>
</body>
</html>