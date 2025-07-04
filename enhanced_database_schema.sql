-- Enhanced Database Schema for Otaku Haven
-- This includes additional tables for security, logging, and advanced features

-- Update existing Users table with new fields
ALTER TABLE Users 
ADD COLUMN EmailVerificationToken VARCHAR(255) NULL,
ADD COLUMN Active TINYINT(1) DEFAULT 1,
ADD COLUMN EmailVerified TINYINT(1) DEFAULT 0,
ADD COLUMN LastLogin DATETIME NULL,
ADD COLUMN PasswordResetToken VARCHAR(255) NULL,
ADD COLUMN PasswordResetExpires DATETIME NULL,
ADD COLUMN FailedLoginAttempts INT DEFAULT 0,
ADD COLUMN AccountLocked TINYINT(1) DEFAULT 0,
ADD COLUMN LockExpires DATETIME NULL;

-- Login attempts tracking (for rate limiting)
CREATE TABLE IF NOT EXISTS login_attempts (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ip_address VARCHAR(45) NOT NULL,
    email VARCHAR(255) NOT NULL,
    attempt_time DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_ip_time (ip_address, attempt_time),
    INDEX idx_email_time (email, attempt_time)
);

-- Remember me tokens
CREATE TABLE IF NOT EXISTS remember_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Token VARCHAR(255) NOT NULL UNIQUE,
    Expires DATETIME NOT NULL,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_token (Token),
    INDEX idx_expires (Expires)
);

-- Login logs for security monitoring
CREATE TABLE IF NOT EXISTS login_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NULL,
    IP_Address VARCHAR(45) NOT NULL,
    UserAgent TEXT,
    Success TINYINT(1) NOT NULL,
    LoginTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE SET NULL,
    INDEX idx_user_time (UserID, LoginTime),
    INDEX idx_ip_time (IP_Address, LoginTime)
);

-- Registration logs
CREATE TABLE IF NOT EXISTS registration_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    IP_Address VARCHAR(45) NOT NULL,
    RegistrationTime DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_user_time (UserID, RegistrationTime)
);

-- Password reset requests
CREATE TABLE IF NOT EXISTS password_resets (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    Token VARCHAR(255) NOT NULL UNIQUE,
    Expires DATETIME NOT NULL,
    Used TINYINT(1) DEFAULT 0,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_token (Token),
    INDEX idx_expires (Expires)
);

-- User sessions for better session management
CREATE TABLE IF NOT EXISTS user_sessions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    UserID INT NOT NULL,
    SessionID VARCHAR(255) NOT NULL UNIQUE,
    IP_Address VARCHAR(45) NOT NULL,
    UserAgent TEXT,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    LastActivity DATETIME DEFAULT CURRENT_TIMESTAMP,
    ExpiresAt DATETIME NOT NULL,
    FOREIGN KEY (UserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_session (SessionID),
    INDEX idx_user (UserID),
    INDEX idx_expires (ExpiresAt)
);

-- Admin audit logs
CREATE TABLE IF NOT EXISTS admin_audit_logs (
    id INT AUTO_INCREMENT PRIMARY KEY,
    AdminUserID INT NOT NULL,
    Action VARCHAR(100) NOT NULL,
    TargetType VARCHAR(50) NOT NULL,
    TargetID INT NULL,
    Details TEXT,
    IP_Address VARCHAR(45) NOT NULL,
    Timestamp DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (AdminUserID) REFERENCES Users(UserID) ON DELETE CASCADE,
    INDEX idx_admin_time (AdminUserID, Timestamp),
    INDEX idx_action (Action)
);

-- Email templates for system emails
CREATE TABLE IF NOT EXISTS email_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    TemplateName VARCHAR(100) NOT NULL UNIQUE,
    Subject VARCHAR(255) NOT NULL,
    Body TEXT NOT NULL,
    Variables TEXT COMMENT 'JSON array of available variables',
    Active TINYINT(1) DEFAULT 1,
    CreatedAt DATETIME DEFAULT CURRENT_TIMESTAMP,
    UpdatedAt DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Insert default email templates
INSERT INTO email_templates (TemplateName, Subject, Body, Variables) VALUES
('welcome', 'Welcome to Otaku Haven!', 
'<h2>Welcome to Otaku Haven!</h2>
<p>Hi {{username}},</p>
<p>Thank you for joining our community! We\'re excited to have you as part of the Otaku Haven family.</p>
<p>You can now:</p>
<ul>
    <li>Browse our extensive collection of manga and comics</li>
    <li>Add items to your wishlist</li>
    <li>Place orders and track them</li>
    <li>Write reviews and ratings</li>
</ul>
<p>If you have any questions, feel free to contact our support team.</p>
<p>Best regards,<br>The Otaku Haven Team</p>',
'["username", "email"]'),

('email_verification', 'Verify Your Email - Otaku Haven',
'<h2>Email Verification</h2>
<p>Hi {{username}},</p>
<p>Please click the link below to verify your email address:</p>
<p><a href="{{verification_link}}">Verify Email Address</a></p>
<p>If the link doesn\'t work, copy and paste this URL into your browser:</p>
<p>{{verification_link}}</p>
<p>This link will expire in 24 hours.</p>
<p>Best regards,<br>The Otaku Haven Team</p>',
'["username", "email", "verification_link"]'),

('password_reset', 'Reset Your Password - Otaku Haven',
'<h2>Password Reset Request</h2>
<p>Hi {{username}},</p>
<p>You requested to reset your password. Click the link below to create a new password:</p>
<p><a href="{{reset_link}}">Reset Password</a></p>
<p>If you didn\'t request this, please ignore this email.</p>
<p>This link will expire in 1 hour.</p>
<p>Best regards,<br>The Otaku Haven Team</p>',
'["username", "email", "reset_link"]');

-- Create indexes for better performance
CREATE INDEX idx_users_email ON Users(Email);
CREATE INDEX idx_users_username ON Users(Username);
CREATE INDEX idx_users_role ON Users(Role);
CREATE INDEX idx_users_active ON Users(Active);
CREATE INDEX idx_users_verification_token ON Users(EmailVerificationToken);

-- Create a view for user statistics
CREATE VIEW user_stats AS
SELECT 
    COUNT(*) as total_users,
    COUNT(CASE WHEN Active = 1 THEN 1 END) as active_users,
    COUNT(CASE WHEN EmailVerified = 1 THEN 1 END) as verified_users,
    COUNT(CASE WHEN Role = 'admin' THEN 1 END) as admin_users,
    COUNT(CASE WHEN Role = 'staff' THEN 1 END) as staff_users,
    COUNT(CASE WHEN Role = 'user' THEN 1 END) as regular_users,
    COUNT(CASE WHEN LastLogin > DATE_SUB(NOW(), INTERVAL 30 DAY) THEN 1 END) as recent_users
FROM Users;

-- Create a view for security monitoring
CREATE VIEW security_overview AS
SELECT 
    COUNT(*) as total_login_attempts,
    COUNT(CASE WHEN Success = 1 THEN 1 END) as successful_logins,
    COUNT(CASE WHEN Success = 0 THEN 1 END) as failed_logins,
    COUNT(DISTINCT IP_Address) as unique_ips,
    COUNT(DISTINCT UserID) as unique_users
FROM login_logs 
WHERE LoginTime > DATE_SUB(NOW(), INTERVAL 24 HOUR);

-- Insert default admin user (password: Admin123!)
INSERT INTO Users (Username, Password, Email, Role, Active, EmailVerified) VALUES 
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin@otakuhaven.com', 'admin', 1, 1)
ON DUPLICATE KEY UPDATE Username = Username;

-- Create stored procedure for cleaning old data
DELIMITER //
CREATE PROCEDURE CleanupOldData()
BEGIN
    -- Clean old login attempts (older than 30 days)
    DELETE FROM login_attempts WHERE attempt_time < DATE_SUB(NOW(), INTERVAL 30 DAY);
    
    -- Clean expired remember tokens
    DELETE FROM remember_tokens WHERE Expires < NOW();
    
    -- Clean old login logs (older than 90 days)
    DELETE FROM login_logs WHERE LoginTime < DATE_SUB(NOW(), INTERVAL 90 DAY);
    
    -- Clean expired password resets
    DELETE FROM password_resets WHERE Expires < NOW();
    
    -- Clean expired user sessions
    DELETE FROM user_sessions WHERE ExpiresAt < NOW();
    
    -- Clean old admin audit logs (older than 1 year)
    DELETE FROM admin_audit_logs WHERE Timestamp < DATE_SUB(NOW(), INTERVAL 1 YEAR);
END //
DELIMITER ;

-- Create event to run cleanup daily
CREATE EVENT IF NOT EXISTS daily_cleanup
ON SCHEDULE EVERY 1 DAY
STARTS CURRENT_TIMESTAMP
DO CALL CleanupOldData(); 