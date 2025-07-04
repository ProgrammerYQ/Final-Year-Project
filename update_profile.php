<?php
session_start();
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $shipping_address = trim($_POST['shipping_address'] ?? '');
    $profile_image_path = null;

    // Handle profile image upload
    if (isset($_FILES['profile_image']) && $_FILES['profile_image']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/profile_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $ext = pathinfo($_FILES['profile_image']['name'], PATHINFO_EXTENSION);
        $filename = 'user_' . $user_id . '_' . time() . '.' . $ext;
        $target = $upload_dir . $filename;
        if (move_uploaded_file($_FILES['profile_image']['tmp_name'], $target)) {
            $profile_image_path = 'profile_images/' . $filename;
        }
    }

    // Validation
    if (empty($username)) {
        $errors[] = 'Username is required.';
    }
    
    if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Valid email is required.';
    }
    
    if (empty($shipping_address)) {
        $errors[] = 'Shipping address is required.';
    }

    // Check if email is already taken by another user
    if (empty($errors)) {
        $stmt = $conn->prepare('SELECT UserID FROM Users WHERE Email = ? AND UserID != ?');
        $stmt->bind_param('si', $email, $user_id);
        $stmt->execute();
        $stmt->store_result();
        if ($stmt->num_rows > 0) {
            $errors[] = 'Email is already taken by another user.';
        }
        $stmt->close();
    }

    // Update user data if no errors
    if (empty($errors)) {
        if ($profile_image_path) {
            $stmt = $conn->prepare('UPDATE Users SET Username = ?, Email = ?, ShippingAddress = ?, ProfileImage = ? WHERE UserID = ?');
            $stmt->bind_param('ssssi', $username, $email, $shipping_address, $profile_image_path, $user_id);
        } else {
            $stmt = $conn->prepare('UPDATE Users SET Username = ?, Email = ?, ShippingAddress = ? WHERE UserID = ?');
            $stmt->bind_param('sssi', $username, $email, $shipping_address, $user_id);
        }
        if ($stmt->execute()) {
            // Update session data
            $_SESSION['username'] = $username;
            if ($profile_image_path) {
                $_SESSION['profile_image'] = $profile_image_path;
            }
            // Redirect back to profile with success message
            header('Location: profile.php?success=1');
            exit;
        } else {
            $errors[] = 'Failed to update profile. Please try again.';
        }
        $stmt->close();
    }
}

// If there are errors, redirect back with error messages
if (!empty($errors)) {
    $error_string = implode(', ', $errors);
    header('Location: profile.php?error=' . urlencode($error_string));
    exit;
}
?> 