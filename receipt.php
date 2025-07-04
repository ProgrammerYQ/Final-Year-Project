<?php
// receipt.php — Displays receipt details after an order

session_start();
include 'session.php';
include 'config.php';

$orderID = $_SESSION['last_order_id'] ?? 0;

// Fallback values
$items = [];
$total = 0;
$address = $_SESSION['shipping_address'] ?? 'N/A';
$status = 'Processing';

if ($orderID > 0) {
  // Get status
  $stmt = $conn->prepare("SELECT Status FROM Orders WHERE OrderID = ?");
  $stmt->bind_param("i", $orderID);
  $stmt->execute();
  $stmt->bind_result($status);
  $stmt->fetch();
  $stmt->close();

  // Get payment amount
  $stmt = $conn->prepare("SELECT Amount FROM Payments WHERE OrderID = ?");
  $stmt->bind_param("i", $orderID);
  $stmt->execute();
  $stmt->bind_result($total);
  $stmt->fetch();
  $stmt->close();

  // Get book order items
  $stmt = $conn->prepare("SELECT b.Title, 'Otaku Haven Books' AS Seller FROM BookOrderItems bo
                          JOIN Books b ON bo.BookID = b.BookID WHERE bo.OrderID = ?");
  $stmt->bind_param("i", $orderID);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $items[] = [
      'title' => $row['Title'],
      'seller' => $row['Seller']
    ];
  }
  $stmt->close();

  // Get stationery order items
  $stmt = $conn->prepare("SELECT s.Name, 'Otaku Haven Supplies' AS Seller FROM StationeryOrderItems so
                          JOIN Stationery s ON so.StationeryID = s.StationeryID WHERE so.OrderID = ?");
  $stmt->bind_param("i", $orderID);
  $stmt->execute();
  $result = $stmt->get_result();
  while ($row = $result->fetch_assoc()) {
    $items[] = [
      'title' => $row['Name'],
      'seller' => $row['Seller']
    ];
  }
  $stmt->close();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Receipt | Otaku Haven</title>
  <link rel="stylesheet" href="tsite-style.css">
</head>
<body class="tsite-theme receipt-background">
  <section class="receipt-wrapper">
    <h2>Order Receipt</h2>
    <p><strong>Order ID:</strong> #<?= htmlspecialchars($orderID) ?></p>

    <?php if (count($items) > 0): ?>
      <?php foreach ($items as $item): ?>
        <p><strong>Title:</strong> <?= htmlspecialchars($item['title']) ?></p>
        <p><strong>Seller:</strong> <?= htmlspecialchars($item['seller']) ?></p>
        <hr style="margin: 10px 0;">
      <?php endforeach; ?>
    <?php else: ?>
      <p>No items found for this order.</p>
    <?php endif; ?>

    <p><strong>Shipping Status:</strong> <?= htmlspecialchars($status) ?></p>
    <p><strong>Address:</strong> <?= htmlspecialchars($address) ?></p>
    <p><strong>Total:</strong> $<?= number_format($total, 2) ?></p>

    <button class="button" onclick="window.location.href='OtakuHavenProto.html'">Return to Home</button>
  </section>
</body>
</html>
