<?php
session_start();
require_once 'config.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Not logged in']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Invalid request method']);
    exit;
}

$wishlist_id = $_POST['wishlist_id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$wishlist_id) {
    echo json_encode(['success' => false, 'message' => 'Wishlist ID is required']);
    exit;
}

try {
    $stmt = $conn->prepare('DELETE FROM Wishlist WHERE WishlistID = ? AND UserID = ?');
    $stmt->bind_param('ii', $wishlist_id, $user_id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            echo json_encode(['success' => true, 'message' => 'Item removed from wishlist']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Item not found or not authorized']);
        }
    } else {
        echo json_encode(['success' => false, 'message' => 'Database error']);
    }
    
    $stmt->close();
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Server error']);
}
?> 