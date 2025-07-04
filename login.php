<?php
session_start();
require_once 'config.php';

$email = $password = '';
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Valid email is required.';
    if (empty($password)) $errors[] = 'Password is required.';

    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT UserID, Username, Password, Role FROM Users WHERE Email = ?');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows === 1) {
            $stmt->bind_result($userID, $username, $hashed_password, $role);
            $stmt->fetch();
            if (password_verify($password, $hashed_password)) {
                $_SESSION['user_id'] = $userID;
                $_SESSION['username'] = $username;
                $_SESSION['role'] = $role;
                // Redirect based on role
                if ($role === 'admin' || $role === 'staff') {
                    header('Location: admin_panel.php');
                } else {
                    header('Location: profile.php');
                }
                exit;
            } else {
                $errors[] = 'Incorrect password.';
            }
        } else {
            $errors[] = 'No account found with that email.';
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Login - Otaku Haven</title>
    <link rel="stylesheet" href="GG.css">
    <style>
        .form-container { max-width: 400px; margin: 40px auto; background: #fff8f3; padding: 32px; border-radius: 16px; box-shadow: 0 2px 12px #ffdabe; color: #6a1616; }
        .form-container h2 { text-align: center; }
        .form-container input { width: 100%; padding: 10px; margin: 8px 0 16px 0; border-radius: 8px; border: 1px solid #e0bfae; font-family: 'Indie Flower', cursive; }
        .form-container button { width: 100%; padding: 10px; background: #ffbfae; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; }
        .form-container button:hover { background: #ff4444; }
        .error { color: #b00020; margin-bottom: 10px; }
    </style>
</head>
<body>
    <div class="form-container">
        <h2>Login</h2>
        <?php if ($errors): ?>
            <div class="error">
                <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
            </div>
        <?php endif; ?>
        <form method="post" action="">
            <label>Email*</label>
            <input type="email" name="email" value="<?php echo htmlspecialchars($email); ?>" required>
            <label>Password*</label>
            <input type="password" name="password" required>
            <button type="submit">Login</button>
        </form>
        <p style="text-align:center;">Don't have an account? <a href="register.php">Register</a></p>
        <p style="text-align:center;"><a href="forgot_password.php">Forgot Password?</a></p>
    </div>
</body>
</html> 