<?php
// Change the password below to your desired new password
$new_password = 'NewAdminPass123!';
echo password_hash($new_password, PASSWORD_DEFAULT);
?> 