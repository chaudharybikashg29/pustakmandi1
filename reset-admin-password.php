<?php
/**
 * ONE-TIME UTILITY
 * Run this once in your browser (http://localhost/pustakmandi/reset-admin-password.php)
 * to correctly set the default admin password to: Admin@123
 *
 * DELETE THIS FILE after running it once, for security.
 */
require_once 'config/db.php';

$new_password = 'Admin@123';
$hash = password_hash($new_password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@pustakmandi.com'");
$stmt->execute([$hash]);

echo "Admin password has been reset.<br>";
echo "Login email: admin@pustakmandi.com<br>";
echo "Login password: Admin@123<br><br>";
echo "<strong>Please delete this file (reset-admin-password.php) now.</strong>";
