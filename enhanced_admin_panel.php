<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';

// Security checks
Security::requireAdmin();
Security::regenerateSession();

// Get admin user info
$adminId = $_SESSION['user_id'];
$adminUsername = $_SESSION['username'];

// Handle actions
$action = $_GET['action'] ?? 'dashboard';
$message = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        switch ($_POST['admin_action'] ?? '') {
            case 'delete_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                if ($userId && $userId !== $adminId) {
                    $stmt = $conn->prepare('DELETE FROM Users WHERE UserID = ?');
                    $stmt->bind_param('i', $userId);
                    if ($stmt->execute()) {
                        Security::logActivity($adminId, 'delete_user', "Deleted user ID: $userId");
                        $message = 'User deleted successfully.';
                    } else {
                        $error = 'Failed to delete user.';
                    }
                    $stmt->close();
                }
                break;
                
            case 'toggle_user_status':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newStatus = (int)($_POST['new_status'] ?? 0);
                if ($userId && $userId !== $adminId) {
                    $stmt = $conn->prepare('UPDATE Users SET Active = ? WHERE UserID = ?');
                    $stmt->bind_param('ii', $newStatus, $userId);
                    if ($stmt->execute()) {
                        $statusText = $newStatus ? 'activated' : 'deactivated';
                        Security::logActivity($adminId, 'toggle_user_status', "User ID: $userId $statusText");
                        $message = "User $statusText successfully.";
                    } else {
                        $error = 'Failed to update user status.';
                    }
                    $stmt->close();
                }
                break;
                
            case 'change_user_role':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newRole = $_POST['new_role'] ?? '';
                if ($userId && $userId !== $adminId && in_array($newRole, ['user', 'staff', 'admin'])) {
                    $stmt = $conn->prepare('UPDATE Users SET Role = ? WHERE UserID = ?');
                    $stmt->bind_param('si', $newRole, $userId);
                    if ($stmt->execute()) {
                        Security::logActivity($adminId, 'change_user_role', "User ID: $userId role changed to: $newRole");
                        $message = "User role changed to $newRole successfully.";
                    } else {
                        $error = 'Failed to change user role.';
                    }
                    $stmt->close();
                }
                break;
        }
    }
}

// Get statistics
$stats = [];
$result = $conn->query('SELECT * FROM user_stats');
if ($result) {
    $stats = $result->fetch_assoc();
}

// Get security overview
$security = [];
$result = $conn->query('SELECT * FROM security_overview');
if ($result) {
    $security = $result->fetch_assoc();
}

// Get recent users
$recentUsers = [];
$stmt = $conn->prepare('SELECT UserID, Username, Email, Role, RegistrationDate, LastLogin, Active, EmailVerified FROM Users ORDER BY RegistrationDate DESC LIMIT 10');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentUsers[] = $row;
}
$stmt->close();

// Get recent login attempts
$recentLogins = [];
$stmt = $conn->prepare('SELECT ll.*, u.Username FROM login_logs ll LEFT JOIN Users u ON ll.UserID = u.UserID ORDER BY ll.LoginTime DESC LIMIT 20');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $recentLogins[] = $row;
}
$stmt->close();

// Get admin audit logs
$auditLogs = [];
$stmt = $conn->prepare('SELECT al.*, u.Username as AdminUsername FROM admin_audit_logs al JOIN Users u ON al.AdminUserID = u.UserID ORDER BY al.Timestamp DESC LIMIT 50');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $auditLogs[] = $row;
}
$stmt->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Otaku Haven</title>
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
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
        }

        .admin-container {
            display: flex;
            min-height: 100vh;
        }

        .sidebar {
            width: 250px;
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(10px);
            padding: 20px;
            box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
        }

        .sidebar h2 {
            font-family: 'Indie Flower', cursive;
            color: #333;
            margin-bottom: 30px;
            text-align: center;
        }

        .nav-menu {
            list-style: none;
        }

        .nav-menu li {
            margin-bottom: 10px;
        }

        .nav-menu a {
            display: block;
            padding: 12px 15px;
            color: #333;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }

        .nav-menu a:hover,
        .nav-menu a.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }

        .main-content {
            flex: 1;
            padding: 30px;
            overflow-y: auto;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
        }

        .header h1 {
            font-family: 'Indie Flower', cursive;
            color: #333;
        }

        .user-info {
            text-align: right;
        }

        .user-info p {
            margin: 5px 0;
            color: #666;
        }

        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }

        .stat-card {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            text-align: center;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .stat-card h3 {
            color: #333;
            margin-bottom: 10px;
            font-size: 1.2em;
        }

        .stat-number {
            font-size: 2.5em;
            font-weight: bold;
            color: #667eea;
            margin-bottom: 10px;
        }

        .stat-description {
            color: #666;
            font-size: 0.9em;
        }

        .content-section {
            background: rgba(255, 255, 255, 0.95);
            padding: 25px;
            border-radius: 15px;
            backdrop-filter: blur(10px);
            margin-bottom: 30px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
        }

        .content-section h2 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.5em;
        }

        .table-container {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }

        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }

        tr:hover {
            background: #f8f9fa;
        }

        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8em;
            font-weight: 500;
        }

        .status-active {
            background: #d4edda;
            color: #155724;
        }

        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }

        .status-verified {
            background: #d1ecf1;
            color: #0c5460;
        }

        .status-unverified {
            background: #fff3cd;
            color: #856404;
        }

        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.9em;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-danger {
            background: #dc3545;
            color: white;
        }

        .btn-danger:hover {
            background: #c82333;
        }

        .btn-warning {
            background: #ffc107;
            color: #212529;
        }

        .btn-warning:hover {
            background: #e0a800;
        }

        .btn-success {
            background: #28a745;
            color: white;
        }

        .btn-success:hover {
            background: #218838;
        }

        .btn-primary {
            background: #007bff;
            color: white;
        }

        .btn-primary:hover {
            background: #0056b3;
        }

        .message {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }

        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }

        .form-group {
            margin-bottom: 15px;
        }

        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #333;
        }

        .form-group select,
        .form-group input {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 6px;
            font-size: 14px;
        }

        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.5);
        }

        .modal-content {
            background-color: white;
            margin: 10% auto;
            padding: 30px;
            border-radius: 15px;
            width: 90%;
            max-width: 500px;
        }

        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }

        .close:hover {
            color: #000;
        }

        @media (max-width: 768px) {
            .admin-container {
                flex-direction: column;
            }
            
            .sidebar {
                width: 100%;
                order: 2;
            }
            
            .main-content {
                order: 1;
            }
            
            .stats-grid {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="sidebar">
            <h2>Admin Panel</h2>
            <ul class="nav-menu">
                <li><a href="?action=dashboard" class="<?php echo $action === 'dashboard' ? 'active' : ''; ?>">📊 Dashboard</a></li>
                <li><a href="?action=users" class="<?php echo $action === 'users' ? 'active' : ''; ?>">👥 Users</a></li>
                <li><a href="?action=security" class="<?php echo $action === 'security' ? 'active' : ''; ?>">🔒 Security</a></li>
                <li><a href="?action=audit" class="<?php echo $action === 'audit' ? 'active' : ''; ?>">📝 Audit Logs</a></li>
                <li><a href="?action=products" class="<?php echo $action === 'products' ? 'active' : ''; ?>">📦 Products</a></li>
                <li><a href="?action=orders" class="<?php echo $action === 'orders' ? 'active' : ''; ?>">🛒 Orders</a></li>
                <li><a href="logout.php">🚪 Logout</a></li>
            </ul>
        </div>

        <div class="main-content">
            <div class="header">
                <h1>Welcome, <?php echo htmlspecialchars($adminUsername); ?>!</h1>
                <div class="user-info">
                    <p><strong>Role:</strong> <?php echo ucfirst($_SESSION['role']); ?></p>
                    <p><strong>Last Login:</strong> <?php echo formatDate($_SESSION['last_activity'] ?? 'now'); ?></p>
                </div>
            </div>

            <?php if ($message): ?>
                <div class="message success"><?php echo htmlspecialchars($message); ?></div>
            <?php endif; ?>

            <?php if ($error): ?>
                <div class="message error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <?php if ($action === 'dashboard'): ?>
                <!-- Dashboard -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Total Users</h3>
                        <div class="stat-number"><?php echo $stats['total_users'] ?? 0; ?></div>
                        <div class="stat-description">Registered users</div>
                    </div>
                    <div class="stat-card">
                        <h3>Active Users</h3>
                        <div class="stat-number"><?php echo $stats['active_users'] ?? 0; ?></div>
                        <div class="stat-description">Currently active</div>
                    </div>
                    <div class="stat-card">
                        <h3>Verified Users</h3>
                        <div class="stat-number"><?php echo $stats['verified_users'] ?? 0; ?></div>
                        <div class="stat-description">Email verified</div>
                    </div>
                    <div class="stat-card">
                        <h3>Recent Users</h3>
                        <div class="stat-number"><?php echo $stats['recent_users'] ?? 0; ?></div>
                        <div class="stat-description">Last 30 days</div>
                    </div>
                </div>

                <div class="content-section">
                    <h2>Recent Users</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Username</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Registered</th>
                                    <th>Last Login</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentUsers as $user): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($user['Username']); ?></td>
                                        <td><?php echo htmlspecialchars($user['Email']); ?></td>
                                        <td><?php echo ucfirst($user['Role']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $user['Active'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $user['Active'] ? 'Active' : 'Inactive'; ?>
                                            </span>
                                            <br>
                                            <span class="status-badge <?php echo $user['EmailVerified'] ? 'status-verified' : 'status-unverified'; ?>">
                                                <?php echo $user['EmailVerified'] ? 'Verified' : 'Unverified'; ?>
                                            </span>
                                        </td>
                                        <td><?php echo formatDate($user['RegistrationDate']); ?></td>
                                        <td><?php echo $user['LastLogin'] ? formatDate($user['LastLogin']) : 'Never'; ?></td>
                                        <td>
                                            <button class="btn btn-primary" onclick="editUser(<?php echo $user['UserID']; ?>)">Edit</button>
                                            <?php if ($user['UserID'] !== $adminId): ?>
                                                <button class="btn btn-danger" onclick="deleteUser(<?php echo $user['UserID']; ?>)">Delete</button>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($action === 'security'): ?>
                <!-- Security Overview -->
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>Login Attempts (24h)</h3>
                        <div class="stat-number"><?php echo $security['total_login_attempts'] ?? 0; ?></div>
                        <div class="stat-description">Total attempts</div>
                    </div>
                    <div class="stat-card">
                        <h3>Successful Logins</h3>
                        <div class="stat-number"><?php echo $security['successful_logins'] ?? 0; ?></div>
                        <div class="stat-description">Last 24 hours</div>
                    </div>
                    <div class="stat-card">
                        <h3>Failed Logins</h3>
                        <div class="stat-number"><?php echo $security['failed_logins'] ?? 0; ?></div>
                        <div class="stat-description">Last 24 hours</div>
                    </div>
                    <div class="stat-card">
                        <h3>Unique IPs</h3>
                        <div class="stat-number"><?php echo $security['unique_ips'] ?? 0; ?></div>
                        <div class="stat-description">Last 24 hours</div>
                    </div>
                </div>

                <div class="content-section">
                    <h2>Recent Login Attempts</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Time</th>
                                    <th>User</th>
                                    <th>IP Address</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentLogins as $login): ?>
                                    <tr>
                                        <td><?php echo formatDate($login['LoginTime']); ?></td>
                                        <td><?php echo $login['Username'] ? htmlspecialchars($login['Username']) : 'Unknown'; ?></td>
                                        <td><?php echo htmlspecialchars($login['IP_Address']); ?></td>
                                        <td>
                                            <span class="status-badge <?php echo $login['Success'] ? 'status-active' : 'status-inactive'; ?>">
                                                <?php echo $login['Success'] ? 'Success' : 'Failed'; ?>
                                            </span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php elseif ($action === 'audit'): ?>
                <!-- Audit Logs -->
                <div class="content-section">
                    <h2>Admin Audit Logs</h2>
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Timestamp</th>
                                    <th>Admin</th>
                                    <th>Action</th>
                                    <th>Target</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($auditLogs as $log): ?>
                                    <tr>
                                        <td><?php echo formatDate($log['Timestamp']); ?></td>
                                        <td><?php echo htmlspecialchars($log['AdminUsername']); ?></td>
                                        <td><?php echo htmlspecialchars($log['Action']); ?></td>
                                        <td><?php echo htmlspecialchars($log['TargetType']); ?> <?php echo $log['TargetID']; ?></td>
                                        <td><?php echo htmlspecialchars($log['Details']); ?></td>
                                        <td><?php echo htmlspecialchars($log['IP_Address']); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Edit User</h2>
            <form method="post" action="">
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="admin_action" value="change_user_role">
                <input type="hidden" name="user_id" id="editUserId">
                
                <div class="form-group">
                    <label for="newRole">Role:</label>
                    <select name="new_role" id="newRole" required>
                        <option value="user">User</option>
                        <option value="staff">Staff</option>
                        <option value="admin">Admin</option>
                    </select>
                </div>
                
                <button type="submit" class="btn btn-primary">Update Role</button>
            </form>
        </div>
    </div>

    <script>
        // Modal functionality
        const modal = document.getElementById('editUserModal');
        const span = document.getElementsByClassName('close')[0];

        span.onclick = function() {
            modal.style.display = 'none';
        }

        window.onclick = function(event) {
            if (event.target == modal) {
                modal.style.display = 'none';
            }
        }

        function editUser(userId) {
            document.getElementById('editUserId').value = userId;
            modal.style.display = 'block';
        }

        function deleteUser(userId) {
            if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                const form = document.createElement('form');
                form.method = 'POST';
                form.innerHTML = `
                    <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                    <input type="hidden" name="admin_action" value="delete_user">
                    <input type="hidden" name="user_id" value="${userId}">
                `;
                document.body.appendChild(form);
                form.submit();
            }
        }

        function toggleUserStatus(userId, currentStatus) {
            const newStatus = currentStatus ? 0 : 1;
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = `
                <input type="hidden" name="csrf_token" value="<?php echo Security::generateCSRFToken(); ?>">
                <input type="hidden" name="admin_action" value="toggle_user_status">
                <input type="hidden" name="user_id" value="${userId}">
                <input type="hidden" name="new_status" value="${newStatus}">
            `;
            document.body.appendChild(form);
            form.submit();
        }
    </script>
</body>
</html> 