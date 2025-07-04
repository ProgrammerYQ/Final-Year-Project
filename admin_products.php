<?php
session_start();
require_once 'config.php';
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['admin', 'staff'])) {
    header('Location: login.php');
    exit;
}

// Fetch all books
$books = [];
$result = $conn->query('SELECT BookID, Title, Genre, Price, Stock FROM Books');
while ($row = $result->fetch_assoc()) {
    $books[] = $row;
}
// Fetch all stationery
$stationery = [];
$result = $conn->query('SELECT StationeryID, Name, Category, Price, Stock FROM Stationery');
while ($row = $result->fetch_assoc()) {
    $stationery[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Product Management - Otaku Haven</title>
    <link rel="stylesheet" href="GG.css">
    <style>
        .panel-container { max-width: 900px; margin: 40px auto; background: #fff8f3; padding: 32px; border-radius: 16px; box-shadow: 0 2px 12px #ffdabe; color: #6a1616; }
        h2 { text-align: center; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 32px; }
        th, td { border: 1px solid #e0bfae; padding: 8px; text-align: left; }
        th { background: #ffbfae; color: #fff; }
        .actions a { margin-right: 10px; color: #ff4444; }
        .add-btn { display: inline-block; margin: 10px 0; padding: 8px 16px; background: #ffbfae; color: #fff; border-radius: 8px; text-decoration: none; }
        .add-btn:hover { background: #ff4444; }
    </style>
</head>
<body>
    <div class="panel-container">
        <h2>Product Management</h2>
        <a class="add-btn" href="add_product.php">Add New Product</a>
        <h3>Books</h3>
        <table>
            <tr><th>ID</th><th>Title</th><th>Genre</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
            <?php foreach ($books as $book): ?>
            <tr>
                <td><?php echo $book['BookID']; ?></td>
                <td><?php echo htmlspecialchars($book['Title']); ?></td>
                <td><?php echo htmlspecialchars($book['Genre']); ?></td>
                <td><?php echo $book['Price']; ?></td>
                <td><?php echo $book['Stock']; ?></td>
                <td class="actions">
                    <a href="edit_product.php?type=book&id=<?php echo $book['BookID']; ?>">Edit</a>
                    <a href="delete_product.php?type=book&id=<?php echo $book['BookID']; ?>" onclick="return confirm('Delete this book?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <h3>Stationery</h3>
        <table>
            <tr><th>ID</th><th>Name</th><th>Category</th><th>Price</th><th>Stock</th><th>Actions</th></tr>
            <?php foreach ($stationery as $item): ?>
            <tr>
                <td><?php echo $item['StationeryID']; ?></td>
                <td><?php echo htmlspecialchars($item['Name']); ?></td>
                <td><?php echo htmlspecialchars($item['Category']); ?></td>
                <td><?php echo $item['Price']; ?></td>
                <td><?php echo $item['Stock']; ?></td>
                <td class="actions">
                    <a href="edit_product.php?type=stationery&id=<?php echo $item['StationeryID']; ?>">Edit</a>
                    <a href="delete_product.php?type=stationery&id=<?php echo $item['StationeryID']; ?>" onclick="return confirm('Delete this item?');">Delete</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
        <a href="admin_panel.php">Back to Admin Panel</a>
    </div>
</body>
</html> 