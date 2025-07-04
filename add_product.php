<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireRole('admin');

$type = $_POST['type'] ?? '';
$title = $author = $genre = $name = $category = '';
$price = $stock = '';
$errors = [];
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $type = $_POST['type'] ?? '';
    $price = $_POST['price'] ?? '';
    $stock = $_POST['stock'] ?? '';
    if ($type === 'book') {
        $title = trim($_POST['title'] ?? '');
        $author = trim($_POST['author'] ?? '');
        $genre = trim($_POST['genre'] ?? '');
        if (empty($title)) $errors[] = 'Book title is required.';
        if (empty($author)) $errors[] = 'Author is required.';
        if (empty($genre)) $errors[] = 'Genre is required.';
    } elseif ($type === 'stationery') {
        $name = trim($_POST['name'] ?? '');
        $category = trim($_POST['category'] ?? '');
        if (empty($name)) $errors[] = 'Stationery name is required.';
        if (empty($category)) $errors[] = 'Category is required.';
    } else {
        $errors[] = 'Please select a product type.';
    }
    if (!is_numeric($price) || $price < 0) $errors[] = 'Price must be a non-negative number.';
    if (!is_numeric($stock) || $stock < 0) $errors[] = 'Stock must be a non-negative number.';

    if (empty($errors)) {
        if ($type === 'book') {
            // Insert author if not exists
            $authorID = null;
            $stmt = $conn->prepare('SELECT AuthorID FROM Authors WHERE Name = ?');
            $stmt->bind_param('s', $author);
            $stmt->execute();
            $stmt->bind_result($authorID);
            if (!$stmt->fetch()) {
                $stmt->close();
                $stmt = $conn->prepare('INSERT INTO Authors (Name) VALUES (?)');
                $stmt->bind_param('s', $author);
                $stmt->execute();
                $authorID = $stmt->insert_id;
            }
            $stmt->close();
            $stmt = $conn->prepare('INSERT INTO Books (Title, AuthorID, Genre, Price, Stock) VALUES (?, ?, ?, ?, ?)');
            $stmt->bind_param('sissd', $title, $authorID, $genre, $price, $stock);
            if ($stmt->execute()) {
                $success = 'Book added successfully!';
                $title = $author = $genre = $price = $stock = '';
            } else {
                $errors[] = 'Failed to add book.';
            }
            $stmt->close();
        } elseif ($type === 'stationery') {
            $stmt = $conn->prepare('INSERT INTO Stationery (Name, Category, Price, Stock) VALUES (?, ?, ?, ?)');
            $stmt->bind_param('ssdd', $name, $category, $price, $stock);
            if ($stmt->execute()) {
                $success = 'Stationery added successfully!';
                $name = $category = $price = $stock = '';
            } else {
                $errors[] = 'Failed to add stationery.';
            }
            $stmt->close();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Add Product - Otaku Haven</title>
    <link rel="stylesheet" href="GG.css">
    <style>
        .form-container { max-width: 500px; margin: 40px auto; background: #fff8f3; padding: 32px; border-radius: 16px; box-shadow: 0 2px 12px #ffdabe; color: #6a1616; }
        .form-container h2 { text-align: center; }
        .form-container input, .form-container select, .form-container textarea { width: 100%; padding: 10px; margin: 8px 0 16px 0; border-radius: 8px; border: 1px solid #e0bfae; font-family: 'Indie Flower', cursive; }
        .form-container button { width: 100%; padding: 10px; background: #ffbfae; color: #fff; border: none; border-radius: 8px; font-size: 1.1em; cursor: pointer; }
        .form-container button:hover { background: #ff4444; }
        .error { color: #b00020; margin-bottom: 10px; }
        .success { color: #008800; margin-bottom: 10px; }
        .hidden { display: none; }
    </style>
    <script>
    function toggleFields() {
        var type = document.getElementById('type').value;
        document.getElementById('book-fields').style.display = (type === 'book') ? 'block' : 'none';
        document.getElementById('stationery-fields').style.display = (type === 'stationery') ? 'block' : 'none';
    }
    </script>
</head>
<body onload="toggleFields()">
<?php include 'admin_nav.php'; ?>
<div class="form-container">
    <h2>Add Product</h2>
    <?php if ($errors): ?>
        <div class="error">
            <?php foreach ($errors as $e) echo htmlspecialchars($e) . '<br>'; ?>
        </div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>
    <form method="post" action="" autocomplete="off">
        <label>Product Type*</label>
        <select name="type" id="type" onchange="toggleFields()" required>
            <option value="">Select Type</option>
            <option value="book" <?php if($type==='book') echo 'selected'; ?>>Book</option>
            <option value="stationery" <?php if($type==='stationery') echo 'selected'; ?>>Stationery</option>
        </select>
        <div id="book-fields" class="hidden">
            <label>Title*</label>
            <input type="text" name="title" value="<?php echo htmlspecialchars($title); ?>">
            <label>Author*</label>
            <input type="text" name="author" value="<?php echo htmlspecialchars($author); ?>">
            <label>Genre*</label>
            <input type="text" name="genre" value="<?php echo htmlspecialchars($genre); ?>">
        </div>
        <div id="stationery-fields" class="hidden">
            <label>Name*</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($name); ?>">
            <label>Category*</label>
            <input type="text" name="category" value="<?php echo htmlspecialchars($category); ?>">
        </div>
        <label>Price*</label>
        <input type="number" name="price" min="0" step="0.01" value="<?php echo htmlspecialchars($price); ?>" required>
        <label>Stock*</label>
        <input type="number" name="stock" min="0" step="1" value="<?php echo htmlspecialchars($stock); ?>" required>
        <button type="submit">Add Product</button>
    </form>
    <p style="text-align:center;"><a href="admin_products.php">Back to Product Management</a></p>
</div>
</body>
</html> 