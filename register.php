<?php
session_start();
require_once 'config.php'; // Assumes config.php sets up $conn (mysqli)

// Initialize variables
$username = $email = $password = $confirm_password = $shipping_address = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get and sanitize input
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    $shipping_address = trim($_POST['shipping_address'] ?? '');

    // Validation
    if (empty($username)) $errors[] = 'Username is required.';
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($password)) $errors[] = 'Password is required.';
    if (strlen($password) < 8) $errors[] = 'Password must be at least 8 characters.';
    if (!preg_match('/[A-Z]/', $password)) $errors[] = 'Password must contain at least one uppercase letter.';
    if (!preg_match('/[a-z]/', $password)) $errors[] = 'Password must contain at least one lowercase letter.';
    if (!preg_match('/[0-9]/', $password)) $errors[] = 'Password must contain at least one digit.';
    if (!preg_match('/[^a-zA-Z0-9]/', $password)) $errors[] = 'Password must contain at least one special character.';
    if ($password !== $confirm_password) $errors[] = 'Passwords do not match.';
    if (empty($shipping_address)) $errors[] = 'Shipping address is required.';

    // Check for existing email
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT UserID FROM Users WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email is already registered.';
        }
        $stmt->close();
    }

    // Register user
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
        $role = 'user';
        $stmt = $conn->prepare('INSERT INTO Users (Username, Password, Email, Role, ShippingAddress) VALUES (?, ?, ?, ?, ?)');
        $stmt->bind_param('sssss', $username, $hashed_password, $email, $role, $shipping_address);
        if ($stmt->execute()) {
            // Auto-login after registration
            $_SESSION['user_id'] = $stmt->insert_id;
            $_SESSION['username'] = $username;
            $_SESSION['role'] = $role;
            header('Location: OtakuHavenProto.html');
            exit;
        } else {
            $errors[] = 'Registration failed. Please try again.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register - Otaku Haven</title>
    <link rel="stylesheet" href="GG.css">
    <style>
        .form-container { max-width: 400px; margin: 40px auto; background: #fff8f3; padding: 32px; border-radius: 16px; box-shadow: 0 2px 12px #ffdabe; color: #6a1616; }
        .form-container h2 { text-align: center; }
        .form-container input, .form-container textarea { width: 100%; padding: 10px; margin: 8px 0 16px 0; border-radius: 8px; border: 1px solid #e0bfae; font-family: 'Indie Flower', cursive; }
        .form-container button { width: 100%; padding: 10px; background: #ffbfae; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; }
        .form-container button:hover { background: #ff4444; }
        .error { color: #b00020; margin-bottom: 10px; }
        .success { color: #008800; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Register</h2>
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>
        <?php if ($success): ?>
            <div class="success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="post" action="">
            <label>Username*</label>
            <input type="text" name="username" value="<?php echo htmlspecialchars($username); ?>" required>
            <label>Email*</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <label>Password*</label>
            <input type="password" name="password" required minlength="8">
            <label>Confirm Password*</label>
            <input type="password" name="confirm_password" required minlength="8">
            <label>Shipping Address*</label>
            <textarea name="shipping_address" required><?php echo htmlspecialchars($shipping_address); ?></textarea>
            <button type="submit">Register</button>
        </form>
        <p style="text-align:center;">Already have an account? <a href="login.php">Login</a></p>
    </div>
</body>
</html> 