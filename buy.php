<?php
session_start();
include 'config.php';
include 'session.php';

$orderPlaced = false;

// If user is redirected here with wishlist selection
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['wishlist'])) {
  $_SESSION['selected_wishlist'] = $_POST['wishlist'];
}

// Load selected items from session
$selectedItems = $_SESSION['selected_wishlist'] ?? [];
$displayItems = [];

foreach ($selectedItems as $entry) {
  list($type, $id) = explode(':', $entry);

  if ($type === 'book') {
    $stmt = $conn->prepare("SELECT b.BookID, b.Title, b.Price, a.Name AS Author
                            FROM Books b
                            LEFT JOIN Authors a ON b.AuthorID = a.AuthorID
                            WHERE b.BookID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($bookID, $title, $price, $author);
    if ($stmt->fetch()) {
      $displayItems[] = [
        'type' => 'book',
        'id' => $bookID,
        'title' => $title,
        'author' => $author,
        'price' => $price,
        'seller' => "Otaku Haven Books",
        'contact' => "otakuhaven@example.com"
      ];
    }
    $stmt->close();
  }

  if ($type === 'stationery') {
    $stmt = $conn->prepare("SELECT StationeryID, Name, Price FROM Stationery WHERE StationeryID = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $stmt->bind_result($stationeryID, $name, $price);
    if ($stmt->fetch()) {
      $displayItems[] = [
        'type' => 'stationery',
        'id' => $stationeryID,
        'title' => $name,
        'author' => '',
        'price' => $price,
        'seller' => "Otaku Haven Supplies",
        'contact' => "stationery@example.com"
      ];
    }
    $stmt->close();
  }
}

// Handle order placement form
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['shipping'])) {
  $userID = $_SESSION['user_id'];
  $shipping = $_POST['shipping'];
  $payment = $_POST['payment'];
  $address = $_POST['address'];
  $status = "Processing";
  $total = 0;

  $_SESSION['shipping_address'] = $address;

  // Insert into Orders
  $stmt = $conn->prepare("INSERT INTO Orders (UserID, OrderDate, Status) VALUES (?, NOW(), ?)");
  $stmt->bind_param("is", $userID, $status);
  $stmt->execute();
  $orderID = $stmt->insert_id;
  $stmt->close();

  // Add order items
  foreach ($selectedItems as $entry) {
    list($type, $id) = explode(':', $entry);
    $qty = 1;

    if ($type === 'book') {
      $stmt = $conn->prepare("SELECT Price FROM Books WHERE BookID = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($price);
      $stmt->fetch();
      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO BookOrderItems (OrderID, BookID, Quantity, PriceAtOrder) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("iiid", $orderID, $id, $qty, $price);
      $stmt->execute();
      $stmt->close();
    }

    if ($type === 'stationery') {
      $stmt = $conn->prepare("SELECT Price FROM Stationery WHERE StationeryID = ?");
      $stmt->bind_param("i", $id);
      $stmt->execute();
      $stmt->bind_result($price);
      $stmt->fetch();
      $stmt->close();

      $stmt = $conn->prepare("INSERT INTO StationeryOrderItems (OrderID, StationeryID, Quantity, PriceAtOrder) VALUES (?, ?, ?, ?)");
      $stmt->bind_param("iiid", $orderID, $id, $qty, $price);
      $stmt->execute();
      $stmt->close();
    }

    $total += $price;
  }

  // Insert payment
  $stmt = $conn->prepare("INSERT INTO Payments (OrderID, PaymentDate, Amount, PaymentMethod) VALUES (?, NOW(), ?, ?)");
  $stmt->bind_param("ids", $orderID, $total, $payment);
  $stmt->execute();
  $stmt->close();

  $_SESSION['last_order_id'] = $orderID;

  header("Location: buy.php?success=1");
  exit;
}

if (isset($_GET['success'])) {
  $orderPlaced = true;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Buy Now | Otaku Haven</title>
  <link rel="stylesheet" href="tsite-style.css">
</head>
<body class="tsite-theme buy-background">
  <button class="icon-button" onclick="window.location.href='OtakuHavenProto.html'">🏠</button>
  <div class="buy-wrapper">
    <h2 class="form-title">Buy Now</h2>

    <?php if ($orderPlaced): ?>
      <p class="success-message">✅ Your order was placed successfully!</p>
      <a class="button" href="receipt.php">View Receipt</a>
    <?php else: ?>
      <?php if (count($displayItems) === 0): ?>
        <p>No item selected.</p>
      <?php else: ?>
        <?php foreach ($displayItems as $item): ?>
          <div class="product-info">
            <p><strong>Title:</strong> <?= htmlspecialchars($item['title']) ?></p>
            <?php if ($item['author']): ?>
              <p><strong>Author:</strong> <?= htmlspecialchars($item['author']) ?></p>
            <?php endif; ?>
            <p><strong>Seller:</strong> <?= htmlspecialchars($item['seller']) ?></p>
            <p><strong>Price:</strong> $<?= number_format($item['price'], 2) ?></p>
            <p><strong>Contact:</strong> <?= htmlspecialchars($item['contact']) ?></p>
          </div>
        <?php endforeach; ?>

        <form id="buy-form" method="post" class="tsite-form">
          <div class="form-group">
            <label>Shipping Method</label>
            <select name="shipping" required>
              <option value="fast">Fast</option>
              <option value="standard">Standard</option>
            </select>
          </div>

          <div class="form-group">
            <label>Payment Method</label>
            <select name="payment" required>
              <option value="bank">Bank Transfer</option>
              <option value="ewallet">E-Wallet</option>
            </select>
          </div>

          <div class="form-group">
            <label>Delivery Address</label>
            <textarea name="address" required></textarea>
          </div>

          <div class="form-actions">
            <button type="submit" class="button">Place Order</button>
          </div>
        </form>
      <?php endif; ?>
    <?php endif; ?>
  </div>
  <script src="script.js"></script>
</body>
</html>
