<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head><title>Manage Products</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>Manage Products</h1>
</body>
</html> 