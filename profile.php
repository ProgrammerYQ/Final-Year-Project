<?php
session_start();
// Debug: Show session info
if (isset($_GET['debug'])) {
    echo '<pre style="background:#fff8f3;color:#6a1616;padding:10px;border-radius:8px;">';
    print_r($_SESSION);
    echo '</pre>';
}
require_once 'config.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$username = $_SESSION['username'] ?? '';

// Get success/error messages
$success_message = '';
$error_message = '';
if (isset($_GET['success'])) {
    $success_message = 'Profile updated successfully!';
}
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

// Fetch user data from database
$stmt = $conn->prepare('SELECT UserID, Username, Email, Role, RegistrationDate, ShippingAddress, ProfileImage FROM Users WHERE UserID = ?');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$stmt->close();

// Fetch user's orders
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
    LIMIT 10
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$orders = $stmt->get_result();
$stmt->close();

// Fetch user's wishlist
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
        END as ItemID
    FROM Wishlist w
    LEFT JOIN Books b ON w.BookID = b.BookID
    LEFT JOIN Stationery s ON w.StationeryID = s.StationeryID
    WHERE w.UserID = ?
    ORDER BY w.WishlistID DESC
    LIMIT 10
');
$stmt->bind_param('i', $user_id);
$stmt->execute();
$wishlist = $stmt->get_result();
$stmt->close();

// Fetch user's published products (if they have any)
$published_products = [];
if ($user['Role'] === 'admin' || $user['Role'] === 'staff') {
    // For admin/staff, show all products
    $stmt = $conn->prepare('
        SELECT 
            BookID as ID, 
            Title as Name, 
            Price, 
            "book" as Type,
            Stock,
            Genre as Category
        FROM Books
        UNION ALL
        SELECT 
            StationeryID as ID, 
            Name, 
            Price, 
            "stationery" as Type,
            Stock,
            Category
        FROM Stationery
        ORDER BY Type, Name
        LIMIT 15
    ');
    $stmt->execute();
    $published_products = $stmt->get_result();
    $stmt->close();
}

// Fetch all admins
$stmt = $conn->prepare("SELECT Username, Email FROM Users WHERE Role = 'admin'");
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    echo "Admin: " . htmlspecialchars($row['Username']) . " (" . htmlspecialchars($row['Email']) . ")<br>";
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Profile - Otaku Haven</title>
    <link rel="stylesheet" href="profile.css">
    <style>
        body {
            background-image: url('background image.png');
        }
        .message {
            padding: 10px;
            margin: 10px 0;
            border-radius: 5px;
            text-align: center;
            font-weight: bold;
        }
        .success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .error {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .view-all-btn {
            background: #3498db;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 14px;
            text-decoration: none;
        }
        .view-all-btn:hover {
            background: #2980b9;
        }
        .product-card {
            position: relative;
        }
        .product-actions {
            margin-top: 10px;
        }
        .product-actions button {
            margin-right: 5px;
            padding: 5px 10px;
            border: none;
            border-radius: 3px;
            cursor: pointer;
            font-size: 12px;
        }
        .edit-btn-small {
            background: #f39c12;
            color: white;
        }
        .delete-btn-small {
            background: #e74c3c;
            color: white;
        }
        .order-details {
            margin-top: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.5);
            border-radius: 5px;
        }
        .wishlist-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 15px;
            background: rgba(255, 255, 255, 0.8);
            border-radius: 10px;
            margin-bottom: 10px;
            border-left: 4px solid #e74c3c;
        }
        .wishlist-info {
            flex: 1;
        }
        .wishlist-actions {
            display: flex;
            gap: 10px;
        }
        .wishlist-actions button {
            padding: 5px 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
        .buy-now-btn {
            background: #27ae60;
            color: white;
        }
        .remove-wishlist-btn {
            background: #e74c3c;
            color: white;
        }
        .status-badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 12px;
            font-size: 12px;
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
        <h2 class="title">VIEW PROFILE</h2>
        
        <?php if ($success_message): ?>
            <div class="message success"><?php echo $success_message; ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="message error"><?php echo $error_message; ?></div>
        <?php endif; ?>
        
        <div class="profile-header">
            <div class="profile-pic">
                <div id="profileImageContainer" class="profile-image-container">
                    <?php if (!empty($user['ProfileImage'])): ?>
                        <img id="profileImage" src="<?php echo htmlspecialchars($user['ProfileImage']); ?>" alt="Profile Picture" style="display: block;">
                        <div id="defaultProfileIcon" class="default-profile-icon" style="display:none;"></div>
                    <?php else: ?>
                        <img id="profileImage" src="" alt="Profile Picture" style="display: none;">
                        <div id="defaultProfileIcon" class="default-profile-icon">
                            <span>:(</span>
                        </div>
                    <?php endif; ?>
                </div>
                <input type="file" id="profileImageInput" accept="image/*" name="profile_image" style="display: none;">
                <label for="profileImageInput" id="uploadBtn" class="upload-btn">Change Photo</label>
            </div>
        </div>

        <div class="profile-info">
            <h3>Account Information</h3>
            <div class="info-item">
                <label>Username:</label>
                <span id="displayName"><?php echo htmlspecialchars($user['Username']); ?></span>
            </div>
            <div class="info-item">
                <label>Email:</label>
                <span id="displayEmail"><?php echo htmlspecialchars($user['Email']); ?></span>
            </div>
            <div class="info-item">
                <label>Role:</label>
                <span id="displayRole"><?php echo ucfirst(htmlspecialchars($user['Role'])); ?></span>
            </div>
            <div class="info-item">
                <label>Member Since:</label>
                <span id="displayDate"><?php echo date('F j, Y', strtotime($user['RegistrationDate'])); ?></span>
            </div>
            <div class="info-item">
                <label>Shipping Address:</label>
                <span id="displayAddress"><?php echo htmlspecialchars($user['ShippingAddress'] ?? 'No address provided'); ?></span>
            </div>
        </div>

        <?php if ($user['Role'] === 'admin' || $user['Role'] === 'staff'): ?>
        <div class="published-products">
            <div class="section-header">
                <h3>Published Products</h3>
                <a href="manage_products.php" class="view-all-btn">Manage All Products</a>
            </div>
            <div id="publishedProductsGrid" class="products-grid">
                <?php if ($published_products->num_rows > 0): ?>
                    <?php while ($product = $published_products->fetch_assoc()): ?>
                        <div class="product-card">
                            <div class="product-info">
                                <h4><?php echo htmlspecialchars($product['Name']); ?></h4>
                                <p><strong>Type:</strong> <?php echo ucfirst($product['Type']); ?></p>
                                <p><strong>Category:</strong> <?php echo htmlspecialchars($product['Category']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($product['Price'], 2); ?></p>
                                <p><strong>Stock:</strong> <?php echo $product['Stock']; ?> units</p>
                                <div class="product-actions">
                                    <button class="edit-btn-small" onclick="editProduct(<?php echo $product['ID']; ?>, '<?php echo $product['Type']; ?>')">Edit</button>
                                    <button class="delete-btn-small" onclick="deleteProduct(<?php echo $product['ID']; ?>, '<?php echo $product['Type']; ?>')">Delete</button>
                                </div>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p class="no-products">No published products yet.</p>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <div class="order-history">
            <div class="section-header">
                <h3>Order History</h3>
                <a href="view_orders.php" class="view-all-btn">View All Orders</a>
            </div>
            <div id="orderList">
                <?php if ($orders->num_rows > 0): ?>
                    <?php while ($order = $orders->fetch_assoc()): ?>
                        <div class="order-item">
                            <p><strong>Order ID:</strong> ORD-<?php echo str_pad($order['OrderID'], 6, '0', STR_PAD_LEFT); ?></p>
                            <p><strong>Date:</strong> <?php echo date('F j, Y g:i A', strtotime($order['OrderDate'])); ?></p>
                            <p><strong>Items:</strong> <?php echo $order['ItemCount']; ?> item(s)</p>
                            <p><strong>Total:</strong> $<?php echo number_format($order['Total'], 2); ?></p>
                            <p><strong>Status:</strong> 
                                <span class="status-badge status-<?php echo strtolower($order['Status']); ?>">
                                    <?php echo ucfirst($order['Status']); ?>
                                </span>
                            </p>
                            <div class="order-details">
                                <button onclick="viewOrderDetails(<?php echo $order['OrderID']; ?>)" class="view-all-btn">View Details</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No orders yet. <a href="OtakuHavenProto.html">Start shopping!</a></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="wishlist">
            <div class="section-header">
                <h3>My Wishlist</h3>
                <a href="wishlist.php" class="view-all-btn">View Full Wishlist</a>
            </div>
            <div id="wishlistItems">
                <?php if ($wishlist->num_rows > 0): ?>
                    <?php while ($item = $wishlist->fetch_assoc()): ?>
                        <div class="wishlist-item">
                            <div class="wishlist-info">
                                <h4><?php echo htmlspecialchars($item['ItemName']); ?></h4>
                                <p><strong>Type:</strong> <?php echo ucfirst($item['Type']); ?></p>
                                <p><strong>Price:</strong> $<?php echo number_format($item['Price'], 2); ?></p>
                            </div>
                            <div class="wishlist-actions">
                                <button class="buy-now-btn" onclick="buyNow(<?php echo $item['ItemID']; ?>, '<?php echo $item['Type']; ?>')">Buy Now</button>
                                <button class="remove-wishlist-btn" onclick="removeFromWishlist(<?php echo $item['WishlistID']; ?>)">Remove</button>
                            </div>
                        </div>
                    <?php endwhile; ?>
                <?php else: ?>
                    <p>No wishlist items yet. <a href="OtakuHavenProto.html">Browse our products!</a></p>
                <?php endif; ?>
            </div>
        </div>

        <div class="button-group">
            <button class="edit-btn round-btn" onclick="openEditProfile()">Edit Profile</button>
            <button class="logout-btn round-btn" onclick="logout()">Logout</button>
            <button class="cancel-btn" onclick="window.location.href='OtakuHavenProto.html'">Back to Home</button>
        </div>
    </div>

    <!-- Edit Profile Modal -->
    <div id="editModal" class="modal" style="display: none;">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h3>Edit Profile</h3>
            
            <form id="editProfileForm" method="POST" action="update_profile.php" enctype="multipart/form-data">
                <div class="form-group">
                    <label>Username:</label>
                    <input type="text" id="editUsername" name="username" value="<?php echo htmlspecialchars($user['Username']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="editEmail" name="email" value="<?php echo htmlspecialchars($user['Email']); ?>" required>
                </div>
                <div class="form-group">
                    <label>Shipping Address:</label>
                    <textarea id="editAddress" name="shipping_address" required><?php echo htmlspecialchars($user['ShippingAddress'] ?? ''); ?></textarea>
                </div>
                <div class="form-group">
                    <label>Profile Image:</label>
                    <input type="file" name="profile_image" accept="image/*">
                </div>
                <div class="form-actions">
                    <button type="submit" class="save-btn">Save Changes</button>
                    <button type="button" class="cancel-btn" onclick="closeEditModal()">Cancel</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openEditProfile() {
            document.getElementById('editModal').style.display = 'block';
        }

        function closeEditModal() {
            document.getElementById('editModal').style.display = 'none';
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modal = document.getElementById('editModal');
            if (event.target === modal) {
                closeEditModal();
            }
        }

        function logout() {
            if (confirm('Are you sure you want to logout?')) {
                window.location.href = 'logout.php';
            }
        }

        function editProduct(id, type) {
            window.location.href = `edit_product.php?id=${id}&type=${type}`;
        }

        function deleteProduct(id, type) {
            if (confirm('Are you sure you want to delete this product?')) {
                window.location.href = `delete_product.php?id=${id}&type=${type}`;
            }
        }

        function viewOrderDetails(orderId) {
            window.location.href = `order_details.php?id=${orderId}`;
        }

        function buyNow(itemId, type) {
            window.location.href = `buy.php?id=${itemId}&type=${type}`;
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
                        alert('Error removing item from wishlist');
                    }
                });
            }
        }

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const messages = document.querySelectorAll('.message');
            messages.forEach(function(message) {
                message.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>
 