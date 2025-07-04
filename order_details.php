<?php
session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$order_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$order_id) {
    header('Location: profile.php?error=Invalid order ID');
    exit;
}

$stmt = $conn->prepare('
    SELECT o.*, u.Username, u.Email, u.ShippingAddress
    FROM Orders o
    JOIN Users u ON o.UserID = u.UserID
    WHERE o.OrderID = ? AND o.UserID = ?
');
$stmt->bind_param('ii', $order_id, $user_id);
$stmt->execute();
$order = $stmt->get_result()->fetch_assoc();
$stmt->close();

if (!$order) {
    header('Location: profile.php?error=Order not found');
    exit;
}

$stmt = $conn->prepare('
    SELECT 
        boi.OrderItemID,
        boi.Quantity,
        boi.PriceAtOrder,
        b.Title as ItemName,
        b.Genre as Category,
        "book" as Type
    FROM BookOrderItems boi
    JOIN Books b ON boi.BookID = b.BookID
    WHERE boi.OrderID = ?
    UNION ALL
    SELECT 
        soi.OrderItemID,
        soi.Quantity,
        soi.PriceAtOrder,
        s.Name as ItemName,
        s.Category,
        "stationery" as Type
    FROM StationeryOrderItems soi
    JOIN Stationery s ON soi.StationeryID = s.StationeryID
    WHERE soi.OrderID = ?
    ORDER BY Type, ItemName
');
$stmt->bind_param('ii', $order_id, $order_id);
$stmt->execute();
$order_items = $stmt->get_result();
$stmt->close();

$total = 0;
$items = [];
while ($item = $order_items->fetch_assoc()) {
    $items[] = $item;
    $total += $item['Quantity'] * $item['PriceAtOrder'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Order Details - Otaku Haven</title>
    <link rel="stylesheet" href="profile.css">
    <style>
        body {
            background-image: url('background image.png');
            font-family: Arial, sans-serif;
        }
        .container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        .order-header {
            text-align: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #e74c3c;
        }
        .order-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 30px;
        }
        .info-section {
            background: rgba(231, 76, 60, 0.1);
            padding: 15px;
            border-radius: 10px;
        }
        .info-section h3 {
            color: #e74c3c;
            margin-top: 0;
        }
        .order-items {
            margin-bottom: 30px;
        }
        .item-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #3498db;
        }
        .item-info {
            flex: 1;
        }
        .item-price {
            text-align: right;
            font-weight: bold;
        }
        .order-total {
            text-align: right;
            font-size: 1.2em;
            font-weight: bold;
            color: #e74c3c;
            padding: 20px;
            background: rgba(231, 76, 60, 0.1);
            border-radius: 10px;
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
    </style>
</head>

<body>
    <div class="container">
        <div class="order-header">
            <h1>Order Details</h1>
            <p>Order ID: ORD-<?php echo str_pad($order['OrderID'], 6, '0', STR_PAD_LEFT); ?></p>
        </div>

        <div class="order-info">
            <div class="info-section">
                <h3>Order Information</h3>
                <p><strong>Order Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['OrderDate'])); ?></p>
                <p><strong>Status:</strong> 
                    <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                        <?php echo ucfirst($order['Status']); ?>
                    </span>
                </p>
                <p><strong>Total Items:</strong> <?php echo count($items); ?></p>
            </div>

            <div class="info-section">
                <h3>Customer Information</h3>
                <p><strong>Name:</strong> <?php echo htmlspecialchars($order['Username']); ?></p>
                <p><strong>Email:</strong> <?php echo htmlspecialchars($order['Email']); ?></p>
                <p><strong>Shipping Address:</strong> <?php echo htmlspecialchars($order['ShippingAddress']); ?></p>
            </div>
        </div>

        <div class="order-items">
            <h3>Order Items</h3>
            <?php if (count($items) > 0): ?>
                <?php foreach ($items as $item): ?>
                    <div class="item-card">
                        <div class="item-info">
                            <h4><?php echo htmlspecialchars($item['ItemName']); ?></h4>
                            <p><strong>Type:</strong> <?php echo ucfirst($item['Type']); ?></p>
                            <p><strong>Category:</strong> <?php echo htmlspecialchars($item['Category']); ?></p>
                            <p><strong>Quantity:</strong> <?php echo $item['Quantity']; ?></p>
                        </div>
                        <div class="item-price">
                            <p><strong>Price:</strong> $<?php echo number_format($item['PriceAtOrder'], 2); ?></p>
                            <p><strong>Subtotal:</strong> $<?php echo number_format($item['Quantity'] * $item['PriceAtOrder'], 2); ?></p>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p>No items found in this order.</p>
            <?php endif; ?>
        </div>

        <div class="order-total">
            <p><strong>Total Order Amount:</strong> $<?php echo number_format($total, 2); ?></p>
        </div>

        <a href="profile.php" class="back-btn">Back to Profile</a>
    </div>
</body>
</html> 