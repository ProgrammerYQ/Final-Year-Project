<?php
session_start();
require_once 'config.php';

echo "<h1>Profile System Test</h1>";

// Test database connection
if ($conn->connect_error) {
    echo "<p style='color: red;'>Database connection failed: " . $conn->connect_error . "</p>";
} else {
    echo "<p style='color: green;'>Database connection successful!</p>";
}

// Test if Users table exists and has data
$result = $conn->query("SHOW TABLES LIKE 'Users'");
if ($result->num_rows > 0) {
    echo "<p style='color: green;'>Users table exists!</p>";
    
    // Check if there are any users
    $result = $conn->query("SELECT COUNT(*) as count FROM Users");
    $row = $result->fetch_assoc();
    echo "<p>Number of users in database: " . $row['count'] . "</p>";
    
    // Show sample users (without passwords)
    $result = $conn->query("SELECT UserID, Username, Email, Role, RegistrationDate FROM Users LIMIT 5");
    if ($result->num_rows > 0) {
        echo "<h3>Sample Users:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registration Date</th></tr>";
        while ($row = $result->fetch_assoc()) {
            echo "<tr>";
            echo "<td>" . $row['UserID'] . "</td>";
            echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
            echo "<td>" . htmlspecialchars($row['Role']) . "</td>";
            echo "<td>" . $row['RegistrationDate'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: orange;'>No users found in database.</p>";
    }
} else {
    echo "<p style='color: red;'>Users table does not exist!</p>";
}

// Test session
echo "<h3>Session Information:</h3>";
echo "<p>Session ID: " . session_id() . "</p>";
echo "<p>User ID in session: " . ($_SESSION['user_id'] ?? 'Not set') . "</p>";
echo "<p>Username in session: " . ($_SESSION['username'] ?? 'Not set') . "</p>";

// Test profile page access
echo "<h3>Profile Page Test:</h3>";
if (isset($_SESSION['user_id'])) {
    echo "<p style='color: green;'>User is logged in. <a href='profile.php'>View Profile</a></p>";
} else {
    echo "<p style='color: orange;'>No user logged in. <a href='login.php'>Login</a> or <a href='register.php'>Register</a></p>";
}

echo "<h3>Quick Links:</h3>";
echo "<ul>";
echo "<li><a href='login.php'>Login Page</a></li>";
echo "<li><a href='register.php'>Register Page</a></li>";
echo "<li><a href='profile.php'>Profile Page</a></li>";
echo "<li><a href='logout.php'>Logout</a></li>";
echo "</ul>";
?> 