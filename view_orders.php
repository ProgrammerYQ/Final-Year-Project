<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare('
    SELECT 
        o.OrderID, 
        o.OrderDate, 
        o.Status,
        COUNT(CASE WHEN boi.OrderItemID IS NOT NULL THEN 1 END) + 
        COUNT(CASE WHEN soi.OrderItemID IS NOT NULL THEN 1 END) as ItemCount,
        SUM(CASE WHEN boi.OrderItemID IS NOT NULL THEN boi.Quantity * boi.PriceAtOrder 
                 WHEN soi.OrderItemID IS NOT NULL THEN soi.Quantity * soi.PriceAtOrder 
                 ELSE 0 END) as Total
    FROM Orders o
    LEFT JOIN BookOrderItems boi ON o.OrderID = boi.OrderID
    LEFT JOIN StationeryOrderItems soi ON o.OrderID = soi.OrderID
    WHERE o.UserID = ?
    GROUP BY o.OrderID, o.OrderDate, o.Status
    ORDER BY o.OrderDate DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

$total_orders = 0;
$total_spent = 0;
$order_stats = [
    'pending' => 0,
    'processing' => 0,
    'shipped' => 0,
    'delivered' => 0,
    'cancelled' => 0
];

$order_list = [];
while ($order = $orders->fetch_assoc()) {
    $order_list[] = $order;
    $total_orders++;
    $total_spent += $order['Total'];
    $status = strtolower($order['Status']);
    if (isset($order_stats[$status])) {
        $order_stats[$status]++;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order History - Otaku Haven</title>
    <link rel="stylesheet" href="profile.css">
    <style>
        body {
            background-image: url('background image.png');
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .orders-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
        }
        .order-stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: rgba(231, 76, 60, 0.1);
            padding: 20px;
            border-radius: 10px;
            text-align: center;
        }
        .stat-number {
            font-size: 2em;
            font-weight: bold;
            color: #e74c3c;
        }
        .orders-list {
            margin-bottom: 30px;
        }
        .order-item {
            background: rgba(255, 255, 255, 0.9);
            border-radius: 15px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            border-left: 5px solid #3498db;
            transition: transform 0.2s;
        }
        .order-item:hover {
            transform: translateY(-2px);
        }
        .order-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
            flex-wrap: wrap;
            gap: 10px;
        }
        .order-id {
            font-size: 1.2em;
            font-weight: bold;
            color: #2c3e50;
        }
        .order-date {
            color: #7f8c8d;
            font-size: 0.9em;
        }
        .order-details {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
            gap: 15px;
            margin-bottom: 15px;
        }
        .detail-item {
            text-align: center;
        }
        .detail-label {
            font-size: 0.9em;
            color: #7f8c8d;
            margin-bottom: 5px;
        }
        .detail-value {
            font-weight: bold;
            color: #2c3e50;
        }
        .order-actions {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
            display: inline-block;
            text-align: center;
            transition: background-color 0.2s;
        }
        .btn-primary {
            background: #3498db;
            color: white;
        }
        .btn-primary:hover {
            background: #2980b9;
        }
        .btn-secondary {
            background: #95a5a6;
            color: white;
        }
        .btn-secondary:hover {
            background: #7f8c8d;
        }
        .btn-danger {
            background: #e74c3c;
            color: white;
        }
        .btn-danger:hover {
            background: #c0392b;
        }
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 14px;
            font-weight: bold;
            color: white;
        }
        .status-pending { background: #f39c12; }
        .status-processing { background: #3498db; }
        .status-shipped { background: #9b59b6; }
        .status-delivered { background: #27ae60; }
        .status-cancelled { background: #e74c3c; }
        .empty-orders {
            text-align: center;
            padding: 50px 20px;
            color: #7f8c8d;
        }
        .empty-orders h3 {
            margin-bottom: 20px;
            color: #34495e;
        }
        .back-btn {
            display: inline-block;
            background: #3498db;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            margin-top: 20px;
        }
        .back-btn:hover {
            background: #2980b9;
        }
        .filter-section {
            margin-bottom: 20px;
            padding: 15px;
            background: rgba(52, 152, 219, 0.1);
            border-radius: 10px;
        }
        .filter-section h4 {
            margin-top: 0;
            color: #2c3e50;
        }
        .filter-buttons {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
        }
        .filter-btn {
            padding: 5px 12px;
            border: 1px solid #3498db;
            background: white;
            color: #3498db;
            border-radius: 15px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .filter-btn.active {
            background: #3498db;
            color: white;
        }
        .filter-btn:hover {
            background: #3498db;
            color: white;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="orders-header">
            <h1>Order History</h1>
            <p>Track all your orders and their current status</p>
        </div>

        <?php if (count($order_list) > 0): ?>
            <div class="order-stats">
                <div class="stat-card">
                    <div class="stat-number"><?php echo $total_orders; ?></div>
                    <div>Total Orders</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">$<?php echo number_format($total_spent, 2); ?></div>
                    <div>Total Spent</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $order_stats['delivered']; ?></div>
                    <div>Delivered</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number"><?php echo $order_stats['pending'] + $order_stats['processing'] + $order_stats['shipped']; ?></div>
                    <div>In Progress</div>
                </div>
            </div>

            <div class="filter-section">
                <h4>Filter by Status:</h4>
                <div class="filter-buttons">
                    <button class="filter-btn active" onclick="filterOrders('all')">All Orders</button>
                    <button class="filter-btn" onclick="filterOrders('pending')">Pending (<?php echo $order_stats['pending']; ?>)</button>
                    <button class="filter-btn" onclick="filterOrders('processing')">Processing (<?php echo $order_stats['processing']; ?>)</button>
                    <button class="filter-btn" onclick="filterOrders('shipped')">Shipped (<?php echo $order_stats['shipped']; ?>)</button>
                    <button class="filter-btn" onclick="filterOrders('delivered')">Delivered (<?php echo $order_stats['delivered']; ?>)</button>
                    <button class="filter-btn" onclick="filterOrders('cancelled')">Cancelled (<?php echo $order_stats['cancelled']; ?>)</button>
                </div>
            </div>

            <div class="orders-list" id="ordersList">
                <?php foreach ($order_list as $order): ?>
                    <div class="order-item" data-status="<?php echo strtolower($order['Status']); ?>">
                        <div class="order-header">
                            <div>
                                <div class="order-id">ORD-<?php echo str_pad($order['OrderID'], 6, '0', STR_PAD_LEFT); ?></div>
                                <div class="order-date"><?php echo date('F j, Y g:i A', strtotime($order['OrderDate'])); ?></div>
                            </div>
                            <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                <?php echo ucfirst($order['Status']); ?>
                            </span>
                        </div>
                        
                        <div class="order-details">
                            <div class="detail-item">
                                <div class="detail-label">Items</div>
                                <div class="detail-value"><?php echo $order['ItemCount']; ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Total</div>
                                <div class="detail-value">$<?php echo number_format($order['Total'], 2); ?></div>
                            </div>
                            <div class="detail-item">
                                <div class="detail-label">Order Date</div>
                                <div class="detail-value"><?php echo date('M j, Y', strtotime($order['OrderDate'])); ?></div>
                            </div>
                        </div>
                        
                        <div class="order-actions">
                            <button class="btn btn-primary" onclick="viewOrderDetails(<?php echo $order['OrderID']; ?>)">
                                View Details
                            </button>
                            <?php if (strtolower($order['Status']) === 'pending'): ?>
                                <button class="btn btn-danger" onclick="cancelOrder(<?php echo $order['OrderID']; ?>)">
                                    Cancel Order
                                </button>
                            <?php endif; ?>
                            <?php if (strtolower($order['Status']) === 'delivered'): ?>
                                <button class="btn btn-secondary" onclick="reorder(<?php echo $order['OrderID']; ?>)">
                                    Reorder
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-orders">
                <h3>No orders yet</h3>
                <p>Start shopping to see your order history here!</p>
                <a href="OtakuHavenProto.html" class="btn btn-primary">Start Shopping</a>
            </div>
        <?php endif; ?>

        <div style="text-align: center;">
            <a href="profile.php" class="back-btn">Back to Profile</a>
        </div>
    </div>

    <script>
        function viewOrderDetails(orderId) {
            window.location.href = `order_details.php?id=${orderId}`;
        }

        function cancelOrder(orderId) {
            if (confirm('Are you sure you want to cancel this order?')) {
                fetch('cancel_order.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: `order_id=${orderId}`
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        location.reload();
                    } else {
                        alert('Error cancelling order: ' + data.message);
                    }
                })
                .catch(error => {
                    alert('Error cancelling order');
                });
            }
        }

        function reorder(orderId) {
            if (confirm('Reorder all items from this order?')) {
                window.location.href = `reorder.php?order_id=${orderId}`;
            }
        }

        function filterOrders(status) {
            const orderItems = document.querySelectorAll('.order-item');
            const filterButtons = document.querySelectorAll('.filter-btn');
            
            filterButtons.forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');
            
            orderItems.forEach(item => {
                if (status === 'all' || item.dataset.status === status) {
                    item.style.display = 'block';
                } else {
                    item.style.display = 'none';
                }
            });
        }
    </script>
</body>
</html> 