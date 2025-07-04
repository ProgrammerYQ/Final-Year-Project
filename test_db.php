<?php
// Simple database connection test
echo "<h1>Database Connection Test</h1>";

try {
    require_once 'config.php';
    echo "<p style='color: green;'>✅ Database connection successful!</p>";
    
    // Test if Users table exists
    $result = $conn->query("SHOW TABLES LIKE 'Users'");
    if ($result && $result->num_rows > 0) {
        echo "<p style='color: green;'>✅ Users table exists!</p>";
        
        // Check table structure
        $result = $conn->query("DESCRIBE Users");
        if ($result) {
            echo "<h3>Users Table Structure:</h3>";
            echo "<table border='1' style='border-collapse: collapse;'>";
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
        }
        
        // Check if there are any users
        $result = $conn->query("SELECT COUNT(*) as user_count FROM Users");
        if ($result) {
            $row = $result->fetch_assoc();
            echo "<p><strong>Total users in database:</strong> " . $row['user_count'] . "</p>";
        }
        
    } else {
        echo "<p style='color: red;'>❌ Users table does not exist!</p>";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>";
}

echo "<hr>";
echo "<p><a href='login.php'>Go to Login</a> | <a href='register.php'>Go to Register</a></p>";
?> 