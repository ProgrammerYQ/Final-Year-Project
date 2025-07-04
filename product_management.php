<?php
session_start();
require_once 'config.php';

// Check if user is admin/staff
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: login.php');
    exit;
}

$action = $_GET['action'] ?? 'list';
$message = '';
$errors = [];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'add' || $action === 'edit') {
        $title = trim($_POST['title'] ?? '');
        $category = trim($_POST['category'] ?? '');
        $price = floatval($_POST['price'] ?? 0);
        $stock = intval($_POST['stock'] ?? 0);
        $description = trim($_POST['description'] ?? '');
        $image_url = trim($_POST['image_url'] ?? '');

        // Validation
        if (empty($title)) $errors[] = 'Product title is required.';
        if (empty($category)) $errors[] = 'Category is required.';
        if ($price <= 0) $errors[] = 'Price must be greater than 0.';
        if ($stock < 0) $errors[] = 'Stock cannot be negative.';
        if (empty($description)) $errors[] = 'Description is required.';
        if (empty($image_url)) $errors[] = 'Image URL is required.';

        if (empty($errors)) {
            if ($action === 'add') {
                $stmt = $conn->prepare('INSERT INTO Products (Title, Category, Price, Stock, Description, ImageURL, CreatedDate) VALUES (?, ?, ?, ?, ?, ?, NOW())');
                $stmt->bind_param('ssdsss', $title, $category, $price, $stock, $description, $image_url);
            } else {
                $product_id = intval($_POST['product_id']);
                $stmt = $conn->prepare('UPDATE Products SET Title=?, Category=?, Price=?, Stock=?, Description=?, ImageURL=? WHERE ProductID=?');
                $stmt->bind_param('ssdsssi', $title, $category, $price, $stock, $description, $image_url, $product_id);
            }

            if ($stmt->execute()) {
                $message = 'Product ' . ($action === 'add' ? 'added' : 'updated') . ' successfully!';
                $action = 'list';
            } else {
                $errors[] = 'Database error. Please try again.';
            }
            $stmt->close();
        }
    } elseif ($action === 'delete') {
        $product_id = intval($_POST['product_id']);
        $stmt = $conn->prepare('DELETE FROM Products WHERE ProductID = ?');
        $stmt->bind_param('i', $product_id);
        if ($stmt->execute()) {
            $message = 'Product deleted successfully!';
        } else {
            $errors[] = 'Failed to delete product.';
        }
        $stmt->close();
        $action = 'list';
    }
}

// Get products for listing
$products = [];
$search = $_GET['search'] ?? '';
$category_filter = $_GET['category'] ?? '';

if ($action === 'list') {
    $sql = 'SELECT * FROM Products WHERE 1=1';
    $params = [];
    $types = '';

    if (!empty($search)) {
        $sql .= ' AND (Title LIKE ? OR Description LIKE ?)';
        $search_param = "%$search%";
        $params[] = $search_param;
        $params[] = $search_param;
        $types .= 'ss';
    }

    if (!empty($category_filter)) {
        $sql .= ' AND Category = ?';
        $params[] = $category_filter;
        $types .= 's';
    }

    $sql .= ' ORDER BY CreatedDate DESC';

    $stmt = $conn->prepare($sql);
    if (!empty($params)) {
        $stmt->bind_param($types, ...$params);
    }
    $stmt->execute();
    $result = $stmt->get_result();
    $products = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
}

// Get product for editing
$product = null;
if ($action === 'edit' && isset($_GET['id'])) {
    $product_id = intval($_GET['id']);
    $stmt = $conn->prepare('SELECT * FROM Products WHERE ProductID = ?');
    $stmt->bind_param('i', $product_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $product = $result->fetch_assoc();
    $stmt->close();
}

// Get categories for filter
$categories = [];
$stmt = $conn->prepare('SELECT DISTINCT Category FROM Products ORDER BY Category');
$stmt->execute();
$result = $stmt->get_result();
while ($row = $result->fetch_assoc()) {
    $categories[] = $row['Category'];
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Product Management - Otaku Haven</title>
    <link rel="stylesheet" href="login.css">
    <style>
        .management-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
            background: rgba(255, 255, 255, 0.95);
            border-radius: 20px;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.1);
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        .search-filters {
            display: flex;
            gap: 15px;
            margin-bottom: 20px;
            flex-wrap: wrap;
        }
        .search-filters input,
        .search-filters select {
            padding: 10px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-family: 'Indie Flower', cursive;
        }
        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .product-card {
            background: #f8f9fa;
            border-radius: 15px;
            padding: 20px;
            border-left: 4px solid #3498db;
        }
        .product-card img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            border-radius: 10px;
            margin-bottom: 15px;
        }
        .product-actions {
            display: flex;
            gap: 10px;
            margin-top: 15px;
        }
        .btn {
            padding: 8px 15px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-family: 'Indie Flower', cursive;
            font-weight: bold;
            text-decoration: none;
            display: inline-block;
            text-align: center;
        }
        .btn-primary { background: #3498db; color: white; }
        .btn-warning { background: #f39c12; color: white; }
        .btn-danger { background: #e74c3c; color: white; }
        .btn:hover { transform: translateY(-2px); }
        .form-section {
            margin-bottom: 20px;
        }
        .form-section label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
        }
        .form-section input,
        .form-section textarea,
        .form-section select {
            width: 100%;
            padding: 12px;
            border: 2px solid #ddd;
            border-radius: 10px;
            font-family: 'Indie Flower', cursive;
        }
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        .message.success { background: #d4edda; color: #155724; }
        .message.error { background: #f8d7da; color: #721c24; }
    </style>
</head>
<body>
    <div class="management-container">
        <div class="header">
            <h1>Product Management</h1>
            <a href="admin_panel.php" class="btn btn-primary">Back to Admin Panel</a>
        </div>

        <?php if ($message): ?>
            <div class="message success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>

        <?php if ($errors): ?>
            <div class="message error">
                <?php foreach ($errors as $error) echo htmlspecialchars($error) . '<br>'; ?>
            </div>
        <?php endif; ?>

        <?php if ($action === 'list'): ?>
            <div class="search-filters">
                <form method="GET" action="" style="display: flex; gap: 15px; flex-wrap: wrap;">
                    <input type="text" name="search" placeholder="Search products..." value="<?php echo htmlspecialchars($search); ?>">
                    <select name="category">
                        <option value="">All Categories</option>
                        <?php foreach ($categories as $cat): ?>
                            <option value="<?php echo htmlspecialchars($cat); ?>" <?php if($category_filter === $cat) echo 'selected'; ?>>
                                <?php echo htmlspecialchars($cat); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" class="btn btn-primary">Search</button>
                    <a href="?action=list" class="btn btn-warning">Clear</a>
                </form>
            </div>

            <a href="?action=add" class="btn btn-primary" style="margin-bottom: 20px;">Add New Product</a>

            <div class="product-grid">
                <?php foreach ($products as $prod): ?>
                    <div class="product-card">
                        <img src="<?php echo htmlspecialchars($prod['ImageURL']); ?>" alt="<?php echo htmlspecialchars($prod['Title']); ?>">
                        <h3><?php echo htmlspecialchars($prod['Title']); ?></h3>
                        <p><strong>Category:</strong> <?php echo htmlspecialchars($prod['Category']); ?></p>
                        <p><strong>Price:</strong> $<?php echo number_format($prod['Price'], 2); ?></p>
                        <p><strong>Stock:</strong> <?php echo $prod['Stock']; ?></p>
                        <p><?php echo htmlspecialchars(substr($prod['Description'], 0, 100)) . '...'; ?></p>
                        <div class="product-actions">
                            <a href="?action=edit&id=<?php echo $prod['ProductID']; ?>" class="btn btn-warning">Edit</a>
                            <form method="POST" action="?action=delete" style="display: inline;" onsubmit="return confirm('Are you sure you want to delete this product?')">
                                <input type="hidden" name="product_id" value="<?php echo $prod['ProductID']; ?>">
                                <button type="submit" class="btn btn-danger">Delete</button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>

        <?php elseif ($action === 'add' || $action === 'edit'): ?>
            <h2><?php echo $action === 'add' ? 'Add New Product' : 'Edit Product'; ?></h2>
            <form method="POST" action="">
                <?php if ($action === 'edit'): ?>
                    <input type="hidden" name="product_id" value="<?php echo $product['ProductID']; ?>">
                <?php endif; ?>

                <div class="form-section">
                    <label>Product Title *</label>
                    <input type="text" name="title" value="<?php echo htmlspecialchars($product['Title'] ?? ''); ?>" required>
                </div>

                <div class="form-section">
                    <label>Category *</label>
                    <select name="category" required>
                        <option value="">Select Category</option>
                        <option value="Japanese Manga" <?php if(($product['Category'] ?? '') === 'Japanese Manga') echo 'selected'; ?>>Japanese Manga</option>
                        <option value="Korean Comics" <?php if(($product['Category'] ?? '') === 'Korean Comics') echo 'selected'; ?>>Korean Comics</option>
                        <option value="Chinese Comics" <?php if(($product['Category'] ?? '') === 'Chinese Comics') echo 'selected'; ?>>Chinese Comics</option>
                        <option value="Western Comics" <?php if(($product['Category'] ?? '') === 'Western Comics') echo 'selected'; ?>>Western Comics</option>
                        <option value="Novels" <?php if(($product['Category'] ?? '') === 'Novels') echo 'selected'; ?>>Novels</option>
                        <option value="Stationery" <?php if(($product['Category'] ?? '') === 'Stationery') echo 'selected'; ?>>Stationery</option>
                    </select>
                </div>

                <div class="form-section">
                    <label>Price *</label>
                    <input type="number" name="price" step="0.01" min="0" value="<?php echo $product['Price'] ?? ''; ?>" required>
                </div>

                <div class="form-section">
                    <label>Stock Quantity *</label>
                    <input type="number" name="stock" min="0" value="<?php echo $product['Stock'] ?? ''; ?>" required>
                </div>

                <div class="form-section">
                    <label>Description *</label>
                    <textarea name="description" rows="4" required><?php echo htmlspecialchars($product['Description'] ?? ''); ?></textarea>
                </div>

                <div class="form-section">
                    <label>Image URL *</label>
                    <input type="url" name="image_url" value="<?php echo htmlspecialchars($product['ImageURL'] ?? ''); ?>" required>
                </div>

                <button type="submit" class="btn btn-primary">
                    <?php echo $action === 'add' ? 'Add Product' : 'Update Product'; ?>
                </button>
                <a href="?action=list" class="btn btn-warning">Cancel</a>
            </form>
        <?php endif; ?>
    </div>
</body>
</html> 