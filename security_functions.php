<?php
/**
 * Security Functions for Otaku Haven
 * Provides comprehensive security utilities for authentication, validation, and protection
 */

require_once 'config.php';

/**
 * Security class with static methods for various security operations
 */
class Security {
    
    /**
     * Generate a secure random token
     */
    public static function generateToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    /**
     * Hash password using PHP's password_hash
     */
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Verify password against hash
     */
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    /**
     * Check if password needs rehashing
     */
    public static function passwordNeedsRehash($hash) {
        return password_needs_rehash($hash, PASSWORD_DEFAULT, ['cost' => 12]);
    }
    
    /**
     * Sanitize input data
     */
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate email address
     */
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    /**
     * Validate password strength
     */
    public static function validatePassword($password) {
        $errors = [];
        
        if (strlen($password) < 8) {
            $errors[] = 'Password must be at least 8 characters long.';
        }
        if (!preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }
        if (!preg_match('/[a-z]/', $password)) {
            $errors[] = 'Password must contain at least one lowercase letter.';
        }
        if (!preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one digit.';
        }
        if (!preg_match('/[^a-zA-Z0-9]/', $password)) {
            $errors[] = 'Password must contain at least one special character.';
        }
        
        return $errors;
    }
    
    /**
     * Check rate limiting for login attempts
     */
    public static function checkRateLimit($ip, $email, $maxAttempts = 5, $timeWindow = 900) {
        global $conn;
        
        $stmt = $conn->prepare('SELECT COUNT(*) FROM login_attempts WHERE ip_address = ? AND attempt_time > DATE_SUB(NOW(), INTERVAL ? SECOND)');
        $stmt->bind_param('si', $ip, $timeWindow);
        $stmt->execute();
        $stmt->bind_result($attemptCount);
        $stmt->fetch();
        $stmt->close();
        
        return $attemptCount < $maxAttempts;
    }
    
    /**
     * Log login attempt
     */
    public static function logLoginAttempt($ip, $email, $success = false) {
        global $conn;
        
        $stmt = $conn->prepare('INSERT INTO login_attempts (ip_address, email, attempt_time) VALUES (?, ?, NOW())');
        $stmt->bind_param('ss', $ip, $email);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Clear failed login attempts for an IP
     */
    public static function clearFailedAttempts($ip) {
        global $conn;
        
        $stmt = $conn->prepare('DELETE FROM login_attempts WHERE ip_address = ?');
        $stmt->bind_param('s', $ip);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Get client IP address
     */
    public static function getClientIP() {
        $ipKeys = ['HTTP_X_FORWARDED_FOR', 'HTTP_X_REAL_IP', 'HTTP_CLIENT_IP', 'REMOTE_ADDR'];
        
        foreach ($ipKeys as $key) {
            if (array_key_exists($key, $_SERVER) === true) {
                foreach (explode(',', $_SERVER[$key]) as $ip) {
                    $ip = trim($ip);
                    if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) !== false) {
                        return $ip;
                    }
                }
            }
        }
        
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    /**
     * Generate CSRF token
     */
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = self::generateToken();
        }
        return $_SESSION['csrf_token'];
    }
    
    /**
     * Verify CSRF token
     */
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    /**
     * Regenerate session ID to prevent session fixation
     */
    public static function regenerateSession() {
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }
    }
    
    /**
     * Check if user is authenticated
     */
    public static function isAuthenticated() {
        return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
    }
    
    /**
     * Check if user has specific role
     */
    public static function hasRole($role) {
        return self::isAuthenticated() && $_SESSION['role'] === $role;
    }
    
    /**
     * Check if user is admin
     */
    public static function isAdmin() {
        return self::hasRole('admin');
    }
    
    /**
     * Check if user is staff
     */
    public static function isStaff() {
        return self::hasRole('staff') || self::hasRole('admin');
    }
    
    /**
     * Require authentication - redirect if not logged in
     */
    public static function requireAuth($redirectUrl = 'login.php') {
        if (!self::isAuthenticated()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require specific role - redirect if not authorized
     */
    public static function requireRole($role, $redirectUrl = 'login.php') {
        if (!self::hasRole($role)) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Require admin access
     */
    public static function requireAdmin($redirectUrl = 'login.php') {
        if (!self::isAdmin()) {
            header("Location: $redirectUrl");
            exit;
        }
    }
    
    /**
     * Log user activity
     */
    public static function logActivity($userId, $action, $details = '') {
        global $conn;
        
        $stmt = $conn->prepare('INSERT INTO admin_audit_logs (AdminUserID, Action, TargetType, Details, IP_Address) VALUES (?, ?, ?, ?, ?)');
        $ip = self::getClientIP();
        $targetType = 'user_activity';
        $stmt->bind_param('issss', $userId, $action, $targetType, $details, $ip);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Create remember me token
     */
    public static function createRememberToken($userId) {
        global $conn;
        
        $token = self::generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+30 days'));
        
        $stmt = $conn->prepare('INSERT INTO remember_tokens (UserID, Token, Expires) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $userId, $token, $expires);
        $stmt->execute();
        $stmt->close();
        
        return $token;
    }
    
    /**
     * Validate remember me token
     */
    public static function validateRememberToken($token) {
        global $conn;
        
        $stmt = $conn->prepare('SELECT u.UserID, u.Username, u.Role, u.Email, u.EmailVerified FROM Users u JOIN remember_tokens rt ON u.UserID = rt.UserID WHERE rt.Token = ? AND rt.Expires > NOW() AND u.Active = 1');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Delete remember me token
     */
    public static function deleteRememberToken($token) {
        global $conn;
        
        $stmt = $conn->prepare('DELETE FROM remember_tokens WHERE Token = ?');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Create password reset token
     */
    public static function createPasswordResetToken($email) {
        global $conn;
        
        $stmt = $conn->prepare('SELECT UserID, Username FROM Users WHERE Email = ? AND Active = 1');
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();
        $stmt->close();
        
        if (!$user) {
            return false;
        }
        
        $token = self::generateToken();
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
        
        $stmt = $conn->prepare('INSERT INTO password_resets (UserID, Token, Expires) VALUES (?, ?, ?)');
        $stmt->bind_param('iss', $user['UserID'], $token, $expires);
        $stmt->execute();
        $stmt->close();
        
        return ['token' => $token, 'user' => $user];
    }
    
    /**
     * Validate password reset token
     */
    public static function validatePasswordResetToken($token) {
        global $conn;
        
        $stmt = $conn->prepare('SELECT pr.UserID, pr.Used, u.Username, u.Email FROM password_resets pr JOIN Users u ON pr.UserID = u.UserID WHERE pr.Token = ? AND pr.Expires > NOW() AND pr.Used = 0');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $result = $stmt->get_result();
        $stmt->close();
        
        return $result->fetch_assoc();
    }
    
    /**
     * Mark password reset token as used
     */
    public static function markPasswordResetUsed($token) {
        global $conn;
        
        $stmt = $conn->prepare('UPDATE password_resets SET Used = 1 WHERE Token = ?');
        $stmt->bind_param('s', $token);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Update user's last login time
     */
    public static function updateLastLogin($userId) {
        global $conn;
        
        $stmt = $conn->prepare('UPDATE Users SET LastLogin = NOW() WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Check if account is locked
     */
    public static function isAccountLocked($userId) {
        global $conn;
        
        $stmt = $conn->prepare('SELECT AccountLocked, LockExpires FROM Users WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($locked, $expires);
        $stmt->fetch();
        $stmt->close();
        
        if ($locked && $expires && strtotime($expires) > time()) {
            return true;
        }
        
        // Unlock account if lock has expired
        if ($locked && (!$expires || strtotime($expires) <= time())) {
            self::unlockAccount($userId);
        }
        
        return false;
    }
    
    /**
     * Lock account temporarily
     */
    public static function lockAccount($userId, $minutes = 15) {
        global $conn;
        
        $expires = date('Y-m-d H:i:s', strtotime("+$minutes minutes"));
        $stmt = $conn->prepare('UPDATE Users SET AccountLocked = 1, LockExpires = ? WHERE UserID = ?');
        $stmt->bind_param('si', $expires, $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Unlock account
     */
    public static function unlockAccount($userId) {
        global $conn;
        
        $stmt = $conn->prepare('UPDATE Users SET AccountLocked = 0, LockExpires = NULL, FailedLoginAttempts = 0 WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
    
    /**
     * Increment failed login attempts
     */
    public static function incrementFailedAttempts($userId) {
        global $conn;
        
        $stmt = $conn->prepare('UPDATE Users SET FailedLoginAttempts = FailedLoginAttempts + 1 WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
        
        // Check if account should be locked
        $stmt = $conn->prepare('SELECT FailedLoginAttempts FROM Users WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->bind_result($attempts);
        $stmt->fetch();
        $stmt->close();
        
        if ($attempts >= 5) {
            self::lockAccount($userId);
        }
    }
    
    /**
     * Reset failed login attempts
     */
    public static function resetFailedAttempts($userId) {
        global $conn;
        
        $stmt = $conn->prepare('UPDATE Users SET FailedLoginAttempts = 0 WHERE UserID = ?');
        $stmt->bind_param('i', $userId);
        $stmt->execute();
        $stmt->close();
    }
}

/**
 * Validation class for form validation
 */
class Validation {
    
    /**
     * Validate username
     */
    public static function validateUsername($username) {
        $errors = [];
        
        if (empty($username)) {
            $errors[] = 'Username is required.';
        } elseif (strlen($username) < 3 || strlen($username) > 50) {
            $errors[] = 'Username must be between 3 and 50 characters.';
        } elseif (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            $errors[] = 'Username can only contain letters, numbers, and underscores.';
        }
        
        return $errors;
    }
    
    /**
     * Validate email
     */
    public static function validateEmail($email) {
        $errors = [];
        
        if (empty($email)) {
            $errors[] = 'Email is required.';
        } elseif (!Security::validateEmail($email)) {
            $errors[] = 'Please enter a valid email address.';
        }
        
        return $errors;
    }
    
    /**
     * Validate shipping address
     */
    public static function validateShippingAddress($address) {
        $errors = [];
        
        if (empty($address)) {
            $errors[] = 'Shipping address is required.';
        } elseif (strlen($address) < 10) {
            $errors[] = 'Please provide a complete shipping address.';
        }
        
        return $errors;
    }
    
    /**
     * Check if email already exists
     */
    public static function emailExists($email, $excludeUserId = null) {
        global $conn;
        
        $sql = 'SELECT UserID FROM Users WHERE Email = ?';
        $params = [$email];
        $types = 's';
        
        if ($excludeUserId) {
            $sql .= ' AND UserID != ?';
            $params[] = $excludeUserId;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
    
    /**
     * Check if username already exists
     */
    public static function usernameExists($username, $excludeUserId = null) {
        global $conn;
        
        $sql = 'SELECT UserID FROM Users WHERE Username = ?';
        $params = [$username];
        $types = 's';
        
        if ($excludeUserId) {
            $sql .= ' AND UserID != ?';
            $params[] = $excludeUserId;
            $types .= 'i';
        }
        
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$params);
        $stmt->execute();
        $stmt->store_result();
        $exists = $stmt->num_rows > 0;
        $stmt->close();
        
        return $exists;
    }
}

/**
 * Utility functions
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

function isAjaxRequest() {
    return !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';
}

function jsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

function formatDate($date, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($date));
}

function timeAgo($datetime) {
    $time = time() - strtotime($datetime);
    
    if ($time < 60) {
        return 'just now';
    } elseif ($time < 3600) {
        $minutes = floor($time / 60);
        return $minutes . ' minute' . ($minutes > 1 ? 's' : '') . ' ago';
    } elseif ($time < 86400) {
        $hours = floor($time / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } else {
        $days = floor($time / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    }
}
?> 