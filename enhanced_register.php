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

$username = $email = $password = $confirm_password = $shipping_address = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = 'Invalid request. Please try again.';
    } else {
        // Get and sanitize input
        $username = trim($_POST['username'] ?? '');
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';
        $shipping_address = trim($_POST['shipping_address'] ?? '');
        $agree_terms = isset($_POST['agree_terms']);

        // Enhanced validation
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = 'Please enter a valid email address.';
        }

        if (empty($password)) {
            $errors[] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        } elseif (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        } elseif (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        } elseif (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one digit.';
        } elseif (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }

        if ($password !== $confirm_password) {
            $errors[] = 'Passwords do not match.';
        }

        if (empty($shipping_address)) {
            $errors[] = 'Shipping address is required.';
        } elseif (strlen($shipping_address) < 10) {
            $errors[] = 'Please provide a complete shipping address.';
        }

        if (!$agree_terms) {
            $errors[] = 'You must agree to the Terms of Service and Privacy Policy.';
        }

        // Check for existing email and username
        if (empty($errors)) {
            $stmt = $conn->prepare('SELECT UserID FROM Users WHERE Email = ? OR Username = ?');
            $stmt->bind_param('ss', $email, $username);
            $stmt->execute();
            $stmt->store_result();
            
            if ($stmt->num_rows > 0) {
                // Check which one exists
                $stmt = $conn->prepare('SELECT Email, Username FROM Users WHERE Email = ? OR Username = ?');
                $stmt->bind_param('ss', $email, $username);
                $stmt->execute();
                $result = $stmt->get_result();
                
                while ($row = $result->fetch_assoc()) {
                    if ($row['Email'] === $email) {
                        $errors[] = 'Email is already registered.';
                    }
                    if ($row['Username'] === $username) {
                        $errors[] = 'Username is already taken.';
                    }
                }
            }
            $stmt->close();
        }

        // Register user
        if (empty($errors)) {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            $role = 'user';
            $email_verification_token = bin2hex(random_bytes(32));
            $active = 1; // Set to 0 if you want email verification required
            
            $stmt = $conn->prepare('INSERT INTO Users (Username, Password, Email, Role, ShippingAddress, EmailVerificationToken, Active, EmailVerified) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $email_verified = $active ? 1 : 0;
            $stmt->bind_param('ssssssii', $username, $hashed_password, $email, $role, $shipping_address, $email_verification_token, $active, $email_verified);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;
                
                // Send verification email (if email verification is enabled)
                if (!$active) {
                    // sendVerificationEmail($email, $username, $email_verification_token);
                }
                
                // Auto-login after registration
                $_SESSION['user_id'] = $user_id;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                $_SESSION['email'] = $email;
                $_SESSION['email_verified'] = $email_verified;
                $_SESSION['last_activity'] = time();
                
                // Log registration
                $ip = $_SERVER['REMOTE_ADDR'];
                $stmt = $conn->prepare('INSERT INTO registration_logs (UserID, IP_Address) VALUES (?, ?)');
                $stmt->bind_param('is', $user_id, $ip);
                $stmt->execute();
                $stmt->close();
                
                header('Location: OtakuHavenProto.html');
                exit;
            } else {
                $errors[] = 'Registration failed. Please try again.';
            }
            $stmt->close();
        }
    }
}

function sendVerificationEmail($email, $username, $token) {
    $subject = "Verify Your Email - Otaku Haven";
    $verification_link = "http://localhost/Prototype/verify_email.php?token=" . $token;
    
    $message = "
    <html>
    <head>
        <title>Email Verification</title>
    </head>
    <body>
        <h2>Welcome to Otaku Haven!</h2>
        <p>Hi $username,</p>
        <p>Thank you for registering with Otaku Haven. Please click the link below to verify your email address:</p>
        <p><a href='$verification_link'>Verify Email Address</a></p>
        <p>If the link doesn't work, copy and paste this URL into your browser:</p>
        <p>$verification_link</p>
        <p>This link will expire in 24 hours.</p>
        <p>Best regards,<br>The Otaku Haven Team</p>
    </body>
    </html>
    ";
    
    $headers = "MIME-Version: 1.0" . "\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8" . "\r\n";
    $headers .= "From: Otaku Haven <noreply@otakuhaven.com>" . "\r\n";
    
    mail($email, $subject, $message, $headers);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - Otaku Haven</title>
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
            max-width: 500px;
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

        .form-group input, .form-group textarea {
            width: 100%;
            padding: 15px;
            border: 2px solid #e0e0e0;
            border-radius: 10px;
            font-size: 16px;
            transition: all 0.3s ease;
            background: #fff;
            font-family: 'Roboto', sans-serif;
        }

        .form-group input:focus, .form-group textarea:focus {
            outline: none;
            border-color: #ff9a9e;
            box-shadow: 0 0 0 3px rgba(255, 154, 158, 0.1);
        }

        .form-group textarea {
            resize: vertical;
            min-height: 100px;
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

        .password-strength {
            margin-top: 5px;
            font-size: 0.8em;
        }

        .strength-weak { color: #d32f2f; }
        .strength-medium { color: #f57c00; }
        .strength-strong { color: #388e3c; }

        .terms-checkbox {
            display: flex;
            align-items: flex-start;
            margin-bottom: 20px;
        }

        .terms-checkbox input[type="checkbox"] {
            width: auto;
            margin-right: 10px;
            margin-top: 3px;
        }

        .terms-checkbox label {
            margin-bottom: 0;
            font-size: 0.9em;
            color: #666;
            line-height: 1.4;
        }

        .terms-checkbox a {
            color: #ff9a9e;
            text-decoration: none;
        }

        .terms-checkbox a:hover {
            text-decoration: underline;
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

        .btn:disabled {
            background: #ccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        .links {
            text-align: center;
            margin-top: 20px;
        }

        .links a {
            color: #ff9a9e;
            text-decoration: none;
            font-size: 0.9em;
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

        .requirements {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.85em;
            color: #666;
        }

        .requirements h4 {
            margin-bottom: 10px;
            color: #333;
        }

        .requirements ul {
            list-style: none;
            padding-left: 0;
        }

        .requirements li {
            margin-bottom: 5px;
            padding-left: 20px;
            position: relative;
        }

        .requirements li:before {
            content: "✓";
            position: absolute;
            left: 0;
            color: #4caf50;
            font-weight: bold;
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
            <p>Create your account to get started</p>
        </div>

        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $error): ?>
                    <div><?php echo htmlspecialchars($error); ?></div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <form method="post" action="" id="registerForm">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            
            <div class="form-group">
                <label for="username">Username*</label>
                <input type="text" id="username" name="username" value="<?php echo htmlspecialchars($username); ?>" required minlength="3" maxlength="50" pattern="[a-zA-Z0-9_]+">
            </div>

            <div class="form-group">
                <label for="email">Email Address*</label>
                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required autocomplete="email">
            </div>

            <div class="form-group">
                <label for="password">Password*</label>
                <div class="password-toggle">
                    <input type="password" id="password" name="password" required minlength="8">
                    <button type="button" onclick="togglePassword('password')">👁️</button>
                </div>
                <div class="password-strength" id="passwordStrength"></div>
            </div>

            <div class="form-group">
                <label for="confirm_password">Confirm Password*</label>
                <div class="password-toggle">
                    <input type="password" id="confirm_password" name="confirm_password" required minlength="8">
                    <button type="button" onclick="togglePassword('confirm_password')">👁️</button>
                </div>
            </div>

            <div class="form-group">
                <label for="shipping_address">Shipping Address*</label>
                <textarea id="shipping_address" name="shipping_address" required minlength="10"><?php echo htmlspecialchars($shipping_address); ?></textarea>
            </div>

            <div class="requirements">
                <h4>Password Requirements:</h4>
                <ul>
                    <li>At least 8 characters long</li>
                    <li>At least one uppercase letter</li>
                    <li>At least one lowercase letter</li>
                    <li>At least one digit</li>
                    <li>At least one special character</li>
                </ul>
            </div>

            <div class="terms-checkbox">
                <input type="checkbox" id="agree_terms" name="agree_terms" required>
                <label for="agree_terms">
                    I agree to the <a href="#" onclick="alert('Terms of Service would be displayed here')">Terms of Service</a> and <a href="#" onclick="alert('Privacy Policy would be displayed here')">Privacy Policy</a>*
                </label>
            </div>

            <button type="submit" class="btn" id="submitBtn">Create Account</button>
        </form>

        <div class="links">
            Already have an account? <a href="login.php">Sign In</a>
        </div>
    </div>

    <script>
        function togglePassword(fieldId) {
            const passwordInput = document.getElementById(fieldId);
            const toggleButton = passwordInput.nextElementSibling;
            
            if (passwordInput.type === 'password') {
                passwordInput.type = 'text';
                toggleButton.textContent = '🙈';
            } else {
                passwordInput.type = 'password';
                toggleButton.textContent = '👁️';
            }
        }

        function checkPasswordStrength(password) {
            let strength = 0;
            let feedback = [];

            if (password.length >= 8) strength++;
            if (/[A-Z]/.test(password)) strength++;
            if (/[a-z]/.test(password)) strength++;
            if (/[0-9]/.test(password)) strength++;
            if (/[^a-zA-Z0-9]/.test(password)) strength++;

            const strengthElement = document.getElementById('passwordStrength');
            
            if (strength <= 2) {
                strengthElement.textContent = 'Weak password';
                strengthElement.className = 'password-strength strength-weak';
            } else if (strength <= 3) {
                strengthElement.textContent = 'Medium strength password';
                strengthElement.className = 'password-strength strength-medium';
            } else {
                strengthElement.textContent = 'Strong password';
                strengthElement.className = 'password-strength strength-strong';
            }
        }

        function validateForm() {
            const username = document.getElementById('username').value.trim();
            const email = document.getElementById('email').value.trim();
            const password = document.getElementById('password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            const shippingAddress = document.getElementById('shipping_address').value.trim();
            const agreeTerms = document.getElementById('agree_terms').checked;
            
            let isValid = true;
            let errorMessage = '';

            if (!username || username.length < 3) {
                errorMessage += 'Username must be at least 3 characters long.\n';
                isValid = false;
            }

            if (!email || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                errorMessage += 'Please enter a valid email address.\n';
                isValid = false;
            }

            if (password.length < 8) {
                errorMessage += 'Password must be at least 8 characters long.\n';
                isValid = false;
            }

            if (password !== confirmPassword) {
                errorMessage += 'Passwords do not match.\n';
                isValid = false;
            }

            if (!shippingAddress || shippingAddress.length < 10) {
                errorMessage += 'Please provide a complete shipping address.\n';
                isValid = false;
            }

            if (!agreeTerms) {
                errorMessage += 'You must agree to the Terms of Service and Privacy Policy.\n';
                isValid = false;
            }

            if (!isValid) {
                alert('Please correct the following errors:\n' + errorMessage);
            }

            return isValid;
        }

        // Event listeners
        document.getElementById('password').addEventListener('input', function() {
            checkPasswordStrength(this.value);
        });

        document.getElementById('registerForm').addEventListener('submit', function(e) {
            if (!validateForm()) {
                e.preventDefault();
            }
        });

        // Real-time password confirmation check
        document.getElementById('confirm_password').addEventListener('input', function() {
            const password = document.getElementById('password').value;
            const confirmPassword = this.value;
            
            if (confirmPassword && password !== confirmPassword) {
                this.style.borderColor = '#d32f2f';
            } else {
                this.style.borderColor = '#e0e0e0';
            }
        });

        // Auto-focus on username field
        document.getElementById('username').focus();
    </script>
</body>
</html> 