<?php
// wishlist.php - Displays the user's saved wishlist items dynamically

session_start();
require_once 'config.php';

if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

$stmt = $conn->prepare('
    SELECT 
        w.WishlistID,
        CASE 
            WHEN w.BookID IS NOT NULL THEN b.Title 
            WHEN w.StationeryID IS NOT NULL THEN s.Name 
            ELSE "Unknown Item" 
        END as ItemName,
        CASE 
            WHEN w.BookID IS NOT NULL THEN b.Price 
            WHEN w.StationeryID IS NOT NULL THEN s.Price 
            ELSE 0 
        END as Price,
        CASE 
            WHEN w.BookID IS NOT NULL THEN "book" 
            WHEN w.StationeryID IS NOT NULL THEN "stationery" 
            ELSE "unknown" 
        END as Type,
        CASE 
            WHEN w.BookID IS NOT NULL THEN b.BookID 
            WHEN w.StationeryID IS NOT NULL THEN s.StationeryID 
            ELSE NULL 
        END as ItemID,
        CASE 
            WHEN w.BookID IS NOT NULL THEN b.Genre 
            WHEN w.StationeryID IS NOT NULL THEN s.Category 
            ELSE "Unknown" 
        END as Category,
        CASE 
            WHEN w.BookID IS NOT NULL THEN b.Stock 
            WHEN w.StationeryID IS NOT NULL THEN s.Stock 
            ELSE 0 
        END as Stock
    FROM Wishlist w
    LEFT JOIN Books b ON w.BookID = b.BookID
    LEFT JOIN Stationery s ON w.StationeryID = s.StationeryID
    WHERE w.UserID = ?
    ORDER BY w.WishlistID DESC
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$wishlist_items = $stmt->get_result();
$stmt->close();

$total_value = 0;
$items = [];
while ($item = $wishlist_items->fetch_assoc()) {
    $items[] = $item;
    $total_value += $item['Price'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Wishlist - Otaku Haven</title>
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
    .wishlist-header {
      text-align: center;
      margin-bottom: 30px;
      padding-bottom: 20px;
      border-bottom: 2px solid #e74c3c;
    }
    .wishlist-stats {
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
    .wishlist-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
      gap: 20px;
      margin-bottom: 30px;
    }
    .wishlist-item {
      background: rgba(255, 255, 255, 0.9);
      border-radius: 15px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      border-left: 5px solid #e74c3c;
      transition: transform 0.2s;
    }
    .wishlist-item:hover {
      transform: translateY(-2px);
    }
    .item-header {
      display: flex;
      justify-content: space-between;
      align-items: start;
      margin-bottom: 15px;
    }
    .item-title {
      font-size: 1.2em;
      font-weight: bold;
      color: #2c3e50;
      margin: 0;
    }
    .item-type {
      background: #3498db;
      color: white;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
    }
    .item-details {
      margin-bottom: 15px;
    }
    .item-details p {
      margin: 5px 0;
      color: #555;
    }
    .item-price {
      font-size: 1.3em;
      font-weight: bold;
      color: #e74c3c;
      margin-bottom: 15px;
    }
    .item-actions {
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
      background: #27ae60;
      color: white;
    }
    .btn-primary:hover {
      background: #229954;
    }
    .btn-danger {
      background: #e74c3c;
      color: white;
    }
    .btn-danger:hover {
      background: #c0392b;
    }
    .btn-secondary {
      background: #95a5a6;
      color: white;
    }
    .btn-secondary:hover {
      background: #7f8c8d;
    }
    .empty-wishlist {
      text-align: center;
      padding: 50px 20px;
      color: #7f8c8d;
    }
    .empty-wishlist h3 {
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
    .stock-status {
      display: inline-block;
      padding: 3px 8px;
      border-radius: 12px;
      font-size: 12px;
      font-weight: bold;
      color: white;
    }
    .in-stock {
      background: #27ae60;
    }
    .low-stock {
      background: #f39c12;
    }
    .out-of-stock {
      background: #e74c3c;
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="wishlist-header">
      <h1>My Wishlist</h1>
      <p>Manage your favorite items and track your shopping list</p>
    </div>

    <?php if (count($items) > 0): ?>
      <div class="wishlist-stats">
        <div class="stat-card">
          <div class="stat-number"><?php echo count($items); ?></div>
          <div>Total Items</div>
        </div>
        <div class="stat-card">
          <div class="stat-number">$<?php echo number_format($total_value, 2); ?></div>
          <div>Total Value</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo count(array_filter($items, function($item) { return $item['Stock'] > 0; })); ?></div>
          <div>In Stock</div>
        </div>
        <div class="stat-card">
          <div class="stat-number"><?php echo count(array_filter($items, function($item) { return $item['Stock'] == 0; })); ?></div>
          <div>Out of Stock</div>
        </div>
      </div>

      <div class="wishlist-grid">
        <?php foreach ($items as $item): ?>
          <div class="wishlist-item">
            <div class="item-header">
              <h3 class="item-title"><?php echo htmlspecialchars($item['ItemName']); ?></h3>
              <span class="item-type"><?php echo ucfirst($item['Type']); ?></span>
            </div>
            
            <div class="item-details">
              <p><strong>Category:</strong> <?php echo htmlspecialchars($item['Category']); ?></p>
              <p><strong>Stock Status:</strong> 
                <?php if ($item['Stock'] > 10): ?>
                  <span class="stock-status in-stock">In Stock (<?php echo $item['Stock']; ?>)</span>
                <?php elseif ($item['Stock'] > 0): ?>
                  <span class="stock-status low-stock">Low Stock (<?php echo $item['Stock']; ?>)</span>
                <?php else: ?>
                  <span class="stock-status out-of-stock">Out of Stock</span>
                <?php endif; ?>
              </p>
            </div>
            
            <div class="item-price">
              $<?php echo number_format($item['Price'], 2); ?>
            </div>
            
            <div class="item-actions">
              <?php if ($item['Stock'] > 0): ?>
                <button class="btn btn-primary" onclick="buyNow(<?php echo $item['ItemID']; ?>, '<?php echo $item['Type']; ?>')">
                  Buy Now
                </button>
                <button class="btn btn-secondary" onclick="addToCart(<?php echo $item['ItemID']; ?>, '<?php echo $item['Type']; ?>')">
                  Add to Cart
                </button>
              <?php else: ?>
                <button class="btn btn-secondary" disabled>
                  Out of Stock
                </button>
              <?php endif; ?>
              <button class="btn btn-danger" onclick="removeFromWishlist(<?php echo $item['WishlistID']; ?>)">
                Remove
              </button>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-wishlist">
        <h3>Your wishlist is empty</h3>
        <p>Start adding items to your wishlist to keep track of products you're interested in!</p>
        <a href="OtakuHavenProto.html" class="btn btn-primary">Browse Products</a>
      </div>
    <?php endif; ?>

    <div style="text-align: center;">
      <a href="profile.php" class="back-btn">Back to Profile</a>
    </div>
  </div>

  <script>
    function buyNow(itemId, type) {
      window.location.href = `buy.php?id=${itemId}&type=${type}`;
    }

    function addToCart(itemId, type) {
      fetch('add_to_cart.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: `item_id=${itemId}&type=${type}&quantity=1`
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Item added to cart successfully!');
        } else {
          alert('Error adding item to cart: ' + data.message);
        }
      })
      .catch(error => {
        alert('Error adding item to cart');
      });
    }

    function removeFromWishlist(wishlistId) {
      if (confirm('Remove this item from your wishlist?')) {
        fetch('remove_wishlist.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
          },
          body: `wishlist_id=${wishlistId}`
        })
        .then(response => response.json())
        .then(data => {
          if (data.success) {
            location.reload();
          } else {
            alert('Error removing item from wishlist: ' + data.message);
          }
        })
        .catch(error => {
          alert('Error removing item from wishlist');
        });
      }
    }
  </script>
</body>
</html>

