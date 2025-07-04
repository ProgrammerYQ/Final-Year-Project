<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireAdmin();
?>
<!DOCTYPE html>
<html>
<head><title>Inventory Reports</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>Inventory Reports</h1>
</body>
</html> 