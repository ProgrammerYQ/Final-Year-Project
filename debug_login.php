<?php
require_once 'config.php';

echo "<h2>Database Debug Information</h2>";

// Test database connection
echo "<h3>1. Database Connection Test</h3>";
if ($conn->ping()) {
    echo "✅ Database connection successful<br>";
} else {
    echo "❌ Database connection failed: " . $conn->error . "<br>";
}

// Check if Users table exists
echo "<h3>2. Users Table Structure</h3>";
$result = $conn->query("DESCRIBE Users");
if ($result) {
    echo "<table border='1'>";
    echo "<tr><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['Field'] . "</td>";
        echo "<td>" . $row['Type'] . "</td>";
        echo "<td>" . $row['Null'] . "</td>";
        echo "<td>" . $row['Key'] . "</td>";
        echo "<td>" . $row['Default'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ Users table not found or error: " . $conn->error . "<br>";
}

// Check existing users
echo "<h3>3. Existing Users</h3>";
$result = $conn->query("SELECT UserID, Username, Email, Role, RegistrationDate FROM Users LIMIT 10");
if ($result && $result->num_rows > 0) {
    echo "<table border='1'>";
    echo "<tr><th>UserID</th><th>Username</th><th>Email</th><th>Role</th><th>Registration Date</th></tr>";
    while ($row = $result->fetch_assoc()) {
        echo "<tr>";
        echo "<td>" . $row['UserID'] . "</td>";
        echo "<td>" . htmlspecialchars($row['Username']) . "</td>";
        echo "<td>" . htmlspecialchars($row['Email']) . "</td>";
        echo "<td>" . $row['Role'] . "</td>";
        echo "<td>" . $row['RegistrationDate'] . "</td>";
        echo "</tr>";
    }
    echo "</table>";
} else {
    echo "❌ No users found or error: " . $conn->error . "<br>";
}

// Test password hashing
echo "<h3>4. Password Hashing Test</h3>";
$test_password = "TestPassword123!";
$hash = password_hash($test_password, PASSWORD_DEFAULT);
echo "Test password: " . $test_password . "<br>";
echo "Generated hash: " . $hash . "<br>";
echo "Verification test: " . (password_verify($test_password, $hash) ? "✅ PASS" : "❌ FAIL") . "<br>";

// Check for admin users
echo "<h3>5. Admin Users Check</h3>";
$result = $conn->query("SELECT COUNT(*) as admin_count FROM Users WHERE Role = 'admin'");
if ($result) {
    $row = $result->fetch_assoc();
    echo "Admin users found: " . $row['admin_count'] . "<br>";
    if ($row['admin_count'] == 0) {
        echo "⚠️ No admin users found!<br>";
    }
} else {
    echo "❌ Error checking admin users: " . $conn->error . "<br>";
}

$conn->close();
?> 