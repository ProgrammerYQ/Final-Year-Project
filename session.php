<?php
// Session management
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Set default user ID for demo purposes (you can modify this based on your authentication system)
if (!isset($_SESSION['user_id'])) {
    $_SESSION['user_id'] = 1; // Default user ID for testing
}

// Function to check if user is logged in
function isLoggedIn() {
    return isset($_SESSION['user_id']) && $_SESSION['user_id'] > 0;
}

// Function to get current user ID
function getCurrentUserId() {
    return $_SESSION['user_id'] ?? 0;
}
?> 