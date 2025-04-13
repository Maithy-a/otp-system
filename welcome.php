<?php
session_start();
if (!isset($_SESSION['phone'])) {
    header('Location: index.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Welcome - OTP System</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/izitoast@1.4.0/dist/css/iziToast.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
        .welcome-container {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
        }

        .welcome-card {
            background: white;
            border-radius: 20px;
            padding: 2rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            text-align: center;
            max-width: 500px;
            width: 90%;
        }

        .welcome-icon {
            font-size: 4rem;
            color: #4f46e5;
            margin-bottom: 1.5rem;
        }

        .welcome-title {
            font-size: 2rem;
            font-weight: 600;
            margin-bottom: 1rem;
            color: #1f2937;
        }

        .welcome-message {
            color: #6b7280;
            margin-bottom: 2rem;
        }

        .welcome-phone {
            background: #f3f4f6;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            display: inline-block;
            margin-bottom: 2rem;
            color: #4f46e5;
            font-weight: 500;
        }

        .welcome-button {
            background: #4f46e5;
            color: white;
            border: none;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .welcome-button:hover {
            background: #4338ca;
            transform: translateY(-2px);
        }
    </style>
</head>

<body>
    <div class="welcome-container">
        <div class="welcome-card">
            <div class="welcome-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="welcome-title">Welcome!</h1>
            <p class="welcome-message">Your phone number has been successfully verified.</p>
            <div class="welcome-phone">
                <?php echo htmlspecialchars($_SESSION['phone']); ?>
            </div>
            <div>
                <button onclick="logout()" class="welcome-button">Logout</button>
            </div>
        </div>
    </div>
    <script>

        document.addEventListener('DOMContentLoaded', () => {
            iziToast.success({
                title: 'Success!',
                message: 'Verified successfully.',
                position: 'topRight'
            });
        });

        function logout() {
            fetch('logout.php', { method: 'POST' })
                .then(() => {
                    window.location.href = 'index.php';
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