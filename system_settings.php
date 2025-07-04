<?php
session_start();
require_once 'config.php';
require_once 'security_functions.php';
Security::requireAdmin();
?>
<!DOCTYPE html>
<html>
<head><title>System Settings</title></head>
<body>
<?php include 'admin_nav.php'; ?>
<h1>System Settings</h1>
</body>
</html> 