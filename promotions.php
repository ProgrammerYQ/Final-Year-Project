<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireRole('admin');
?>
<!DOCTYPE html>
<html>
<head><title>Promotions</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>Promotions</h1>
</body>
</html> 