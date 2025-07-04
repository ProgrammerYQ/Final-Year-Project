<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head><title>Pending Orders</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>Pending Orders</h1>
</body>
</html> 