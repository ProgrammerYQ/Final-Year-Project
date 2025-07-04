<?php
require_once 'config.php';
$email = '';
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Please enter a valid email address.';
    } else {
        $stmt = $conn->prepare('SELECT UserID FROM Users WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            // Simulate sending email
            $success = 'A password reset link has been sent to your email (simulation).';
        } else {
            $error = 'No account found with that email.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Forgot Password - Otaku Haven</title>
    <link rel="stylesheet" href="login.css">
    <style type="text/css">
        body{
            background-image: url(background\ image.png);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="form-box" id="forgot-password-form">
            <form action="" method="POST">
                <h2>Forgot Password</h2>
                <p class="instruction">Enter your email address and we'll send you a link to reset your password.</p>
                
                <input type="email" name="email" placeholder="Enter your email" required>
                
                <button type="submit" name="reset_password">Send Reset Link</button>
                
                <div class="links">
                    <p><a href="login.html">Back to Login</a></p>
                    <p>Don't have an account? <a href="sign in.html">Sign in</a></p>
                </div>
                
                <button type="button" class="cancel-btn" onclick="window.location.href='OtakuHavenProto.html'">Cancel</button>
            </form>
        </div>
    </div>

    <script src="script.js"></script>
    <script>
        // Handle form submission
        document.querySelector('form').addEventListener('submit', function(e) {
            e.preventDefault();
            
            const email = document.querySelector('input[name="email"]').value;
            
            if (email) {
                // Simulate sending reset email
                alert('If an account with that email exists, a password reset link has been sent to ' + email);
                
                // In a real application, you would:
                // 1. Check if the email exists in your database
                // 2. Generate a secure reset token
                // 3. Send an email with the reset link
                // 4. Store the token in the database with an expiration time
                
                // For demo purposes, redirect back to login
                setTimeout(() => {
                    window.location.href = 'login.html';
                }, 2000);
            }
        });
    </script>
</body>
</html> 