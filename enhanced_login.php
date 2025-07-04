<?php
session_start();
require_once 'config.php';

// Prevent session fixation
if (!isset($_SESSION['initiated'])) {
    session_regenerate_id(true);
    $_SESSION['initiated'] = true;
}

// CSRF protection
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$email = $password = '';
$errors = [];
$success_message = '';

// Handle logout
if (isset($_GET['logout'])) {
    session_destroy();
    $success_message = 'You have been successfully logged out.';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember_me = isset($_POST['remember_me']);

        // Validation
        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }
        if (empty($password)) {
            $errors[] = 'Password is required.';
        }

        if (empty($errors)) {
            // Rate limiting (simple implementation)
            $ip = $_SERVER['REMOTE_ADDR'];
            $stmt = $conn->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL 15 MINUTE)');
            $stmt->bind_param('s', $ip);
            $stmt->execute();
            $stmt->bind_result($attempt_count);
            $stmt->fetch();
            $stmt->close();

            if ($attempt_count >= 5) {
                $errors[] = 'Too many login attempts. Please try again in 15 minutes.';
            } else {
                // Attempt login
                $stmt = $conn->prepare('SELECT UserID, Username, Password, Role, EmailVerified FROM Users WHERE Email = ? AND Active = 1');
                $stmt->bind_param('s', $email);
                $stmt->execute();
                $stmt->store_result();
                
                if ($stmt->num_rows === 1) {
                    $stmt->bind_result($userID, $username, $hashed_password, $role, $email_verified);
                    $stmt->fetch();
                    
                    if (password_verify($password, $hashed_password)) {
                        // Clear failed attempts
                        $stmt = $conn->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
                        $stmt->bind_param('s', $ip);
                        $stmt->execute();
                        $stmt->close();

                        // Set session variables
                        $_SESSION['user_id'] = $userID;
                        $_SESSION['username'] = $username;
                        $_SESSION['role'] = $role;
                        $_SESSION['email'] = $email;
                        $_SESSION['email_verified'] = $email_verified;
                        $_SESSION['last_activity'] = time();

                        // Remember me functionality
                        if ($remember_me) {
                            $token = bin2hex(random_bytes(32));
                            $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
                            $stmt = $conn->prepare('INSERT INTO remember_tokens (UserID, Token, Expires) VALUES (?, ?, ?)');
                            $stmt->bind_param('iss', $userID, $token, $expires);
                            $stmt->execute();
                            $stmt->close();
                            setcookie('remember_token', $token, time() + (30 * 24 * 60 * 60), '/', '', true, true);
                        }

                        // Log successful login
                        $stmt = $conn->prepare('INSERT INTO login_logs (UserID, IP_Address, Success) VALUES (?, ?, 1)');
                        $stmt->bind_param('is', $userID, $ip);
                        $stmt->execute();
                        $stmt->close();

                        // Redirect based on role
                        if ($role === 'admin' || $role === 'staff') {
                            header('Location: admin_panel.php');
                        } else {
                            header('Location: profile.php');
                        }
                        exit;
                    } else {
                        $errors[] = 'Invalid email or password.';
                    }
                } else {
                    $errors[] = 'Invalid email or password.';
                }
                $stmt->close();

                // Log failed attempt
                if (!empty($errors)) {
                    $stmt = $conn->prepare('INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())');
                    $stmt->bind_param('ss', $ip, $email);
                    $stmt->execute();
                    $stmt->close();
                }
            }
        }
    }
}

// Check for remember me token
if (!isset($_SESSION['user_id']) && isset($_COOKIE['remember_token'])) {
    $token = $_COOKIE['remember_token'];
    $stmt = $conn->prepare('SELECT u.UserID, u.Username, u.Role, u.Email, u.EmailVerified FROM Users u JOIN remember_tokens rt ON u.UserID = rt.UserID WHERE rt.Token = ? AND rt.Expires > NOW() AND u.Active = 1');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $stmt->store_result();
    
    if ($stmt->num_rows === 1) {
        $stmt->bind_result($userID, $username, $role, $email, $email_verified);
        $stmt->fetch();
        $_SESSION['user_id'] = $userID;
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $role;
        $_SESSION['email'] = $email;
        $_SESSION['email_verified'] = $email_verified;
        $_SESSION['last_activity'] = time();
        
        if ($role === 'admin' || $role === 'staff') {
            header('Location: admin_panel.php');
        } else {
            header('Location: profile.php');
        }
        exit;
    }
    $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - Otaku Haven</title>
    <link rel="stylesheet" href="GG.css">
    <link href="https://fonts.googleapis.com/css2?family=Indie+Flower:wght@400;700&family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Roboto', sans-serif;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 50%, #fecfef 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }

        .form-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            padding: 40px;
            width: 100%;
            max-width: 450px;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .logo {
            text-align: center;
            margin-bottom: 30px;
        }

        .logo h1 {
            font-family: 'Indie Flower', cursive;
            font-size: 2.5em;
            color: #6a1616;
            margin-bottom: 10px;
        }

        .logo p {
            color: #666;
            font-size: 0.9em;
        }

        .form-group {
            margin-bottom: 20px;
        }

        .form-group label {
            display: block;
            margin-bottom: 8px;
            color: #333;
            font-weight: 500;
            font-size: 0.9em;
        }

        .form-group input {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
        }

        .form-group input:focus {
            outline: none;
            border-color: #ff9a9e;
            box-shadow: 0 0 0 3px rgba(255, 154, 158, 0.1);
        }

        .remember-me {
            display: flex;
            align-items: center;
            margin-bottom: 20px;
        }

        .remember-me input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
        }

        .remember-me label {
            margin-bottom: 0;
            font-size: 0.9em;
            color: #666;
        }

        .btn {
            width: 100%;
            padding: 15px;
            background: linear-gradient(135deg, #ff9a9e 0%, #fecfef 100%);
            color: white;
            border: none;
            border-radius: 10px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-bottom: 20px;
        }

        .btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 20px rgba(255, 154, 158, 0.3);
        }

        .btn:active {
            transform: translateY(0);
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #ff9a9e;
            text-decoration: none;
            font-size: 0.9em;
            margin: 0 10px;
            transition: color 0.3s ease;
        }

        .links a:hover {
            color: #6a1616;
        }

        .error {
            background: #ffebee;
            color: #c62828;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #c62828;
            font-size: 0.9em;
        }

        .success {
            background: #e8f5e8;
            color: #2e7d32;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            border-left: 4px solid #2e7d32;
            font-size: 0.9em;
        }

        .password-toggle {
            position: relative;
        }

        .password-toggle input {
            padding-right: 50px;
        }

        .password-toggle button {
            position: absolute;
            right: 15px;
            top: 50%;
            transform: translateY(-50%);
            background: none;
            border: none;
            cursor: pointer;
            color: #666;
            font-size: 18px;
        }

        @media (max-width: 480px) {
            .form-container {
                padding: 30px 20px;
            }
            
            .logo h1 {
                font-size: 2em;
            }
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="logo">
            <h1>Otaku Haven</h1>
            <p>Welcome back! Please sign in to your account.</p>
        </div>

        <?php if ($success_message): ?>
            <div class="success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <form method="post" action="" id="loginForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required autocomplete="current-password">
                    <button type="button" onclick="togglePassword()">👁️</button>
                </div>
            </div>

            <div class="remember-me">
                <input type="checkbox" id="remember_me" name="remember_me">
                <label for="remember_me">Remember me for 30 days</label>
            </div>

            <button type="submit" class="btn">Sign In</button>
        </form>

        <div class="links">
            <a href="forgot_password.php">Forgot Password?</a>
            <span>|</span>
            <a href="register.php">Create Account</a>
        </div>
    </div>

    <script>
        function togglePassword() {
            const passwordInput = document.getElementById('password');
            const toggleButton = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        // Form validation
        document.getElementById('loginForm').addEventListener('submit', function(e) {
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            
            if (!email || !password) {
                e.preventDefault();
                alert('Please fill in all required fields.');
                return false;
            }
            
            // Basic email validation
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(email)) {
                e.preventDefault();
                alert('Please enter a valid email address.');
                return false;
            }
        });

        // Auto-focus on email field
        document.getElementById('email').focus();
    </script>
</body>
</html> 