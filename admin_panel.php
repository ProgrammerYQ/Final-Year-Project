<?php
session_start();
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: login.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Panel - Otaku Haven</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .admin-container {
            max-width: 1000px;
            margin: 40px auto;
            padding: 40px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
            backdrop-filter: blur(10px);
        }
        .admin-header {
            text-align: center;
            margin-bottom: 40px;
        }
        .admin-header h1 {
            color: #333;
            font-size: 2.5rem;
            margin-bottom: 10px;
        }
        .admin-header p {
            color: #666;
            font-size: 1.1rem;
        }
        .management-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 30px;
            margin-bottom: 40px;
        }
        .management-section {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 25px;
            border-left: 4px solid #3498db;
            transition: transform 0.3s ease;
        }
        .management-section:hover {
            transform: translateY(-5px);
        }
        .management-section h3 {
            color: #333;
            margin-bottom: 20px;
            font-size: 1.3rem;
        }
        .management-links {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }
        .admin-link {
            display: block;
            padding: 12px 15px;
            background: #3498db;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: bold;
            transition: all 0.3s ease;
            text-align: center;
        }
        .admin-link:hover {
            background: #2980b9;
            transform: translateY(-2px);
        }
        .admin-link.danger {
            background: #e74c3c;
        }
        .admin-link.danger:hover {
            background: #c0392b;
        }
        .admin-link.warning {
            background: #f39c12;
        }
        .admin-link.warning:hover {
            background: #e67e22;
        }
        .admin-link.success {
            background: #27ae60;
        }
        .admin-link.success:hover {
            background: #229954;
        }
        .stats-section {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 15px;
            padding: 25px;
            text-align: center;
            margin-bottom: 30px;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 20px;
            margin-top: 20px;
        }
        .stat-item {
            background: rgba(255, 255, 255, 0.2);
            padding: 15px;
            border-radius: 10px;
        }
        .stat-number {
            font-size: 2rem;
            font-weight: bold;
            margin-bottom: 5px;
        }
        .logout-section {
            text-align: center;
            margin-top: 40px;
            padding-top: 30px;
            border-top: 2px solid #eee;
        }
    </style>
</head>
<body>
    <div class="admin-container">
        <div class="admin-header">
            <h1>Admin Panel</h1>
            <p>Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?> (<?php echo htmlspecialchars($_SESSION['role']); ?>)</p>
        </div>

        <!-- Quick Statistics -->
        <div class="stats-section">
            <h3>System Overview</h3>
            <div class="stats-grid">
                <div class="stat-item">
                    <div class="stat-number">150+</div>
                    <div>Products</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">25</div>
                    <div>Orders Today</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">$2,450</div>
                    <div>Revenue Today</div>
                </div>
                <div class="stat-item">
                    <div class="stat-number">8</div>
                    <div>Pending Orders</div>
                </div>
            </div>
        </div>

        <div class="management-grid">
            <!-- Product Management -->
            <div class="management-section">
                <h3>📦 Product Management</h3>
                <div class="management-links">
                    <a href="product_management.php" class="admin-link">Manage Products</a>
                    <a href="product_management.php?action=add" class="admin-link success">Add New Product</a>
                    <a href="product_management.php?action=list" class="admin-link warning">View All Products</a>
                </div>
            </div>

            <!-- Order Management -->
            <div class="management-section">
                <h3>📋 Order Management</h3>
                <div class="management-links">
                    <a href="order_management.php" class="admin-link">View All Orders</a>
                    <a href="order_management.php?status=Pending" class="admin-link warning">Pending Orders</a>
                    <a href="order_management.php?status=Processing" class="admin-link">Processing Orders</a>
                </div>
            </div>

            <!-- User Management -->
            <div class="management-section">
                <h3>👥 User Management</h3>
                <div class="management-links">
                    <a href="add_staff.php" class="admin-link success">Add Staff/Admin</a>
                    <a href="user_management.php" class="admin-link">Manage Users</a>
                    <a href="customer_analytics.php" class="admin-link warning">Customer Analytics</a>
                </div>
            </div>

            <!-- System Management -->
            <div class="management-section">
                <h3>⚙️ System Management</h3>
                <div class="management-links">
                    <a href="system_reports.php" class="admin-link">Generate Reports</a>
                    <a href="inventory_alerts.php" class="admin-link warning">Inventory Alerts</a>
                    <a href="system_settings.php" class="admin-link">System Settings</a>
                </div>
            </div>

            <!-- Content Management -->
            <div class="management-section">
                <h3>📝 Content Management</h3>
                <div class="management-links">
                    <a href="category_management.php" class="admin-link">Manage Categories</a>
                    <a href="review_management.php" class="admin-link warning">Manage Reviews</a>
                    <a href="promotion_management.php" class="admin-link success">Promotions</a>
                </div>
            </div>

            <!-- Analytics & Reports -->
            <div class="management-section">
                <h3>📊 Analytics & Reports</h3>
                <div class="management-links">
                    <a href="sales_reports.php" class="admin-link">Sales Reports</a>
                    <a href="inventory_reports.php" class="admin-link warning">Inventory Reports</a>
                    <a href="customer_reports.php" class="admin-link success">Customer Reports</a>
                </div>
            </div>
        </div>

        <div class="logout-section">
            <a href="logout.php" class="admin-link danger">Logout</a>
            <p style="margin-top: 15px; color: #666;">© 2024 Otaku Haven - Admin Panel</p>
        </div>
    </div>
</body>
</html> 