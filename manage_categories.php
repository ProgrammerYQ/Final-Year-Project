<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head><title>Manage Categories</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>Manage Categories</h1>
</body>
</html> 