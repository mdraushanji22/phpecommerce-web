<?php
require_once 'config/database.php';

$db = getDB();

// Generate correct hash for 'admin123'
$correctHash = password_hash('admin123', PASSWORD_DEFAULT);

// Update the password
$stmt = $db->prepare("UPDATE admins SET password = ? WHERE email = 'admin@ecommerce.com'");
$stmt->execute([$correctHash]);

echo '<h2 style="color: green;">✓ Password Fixed Successfully!</h2>';
echo '<p>You can now login with:</p>';
echo '<ul>';
echo '<li><strong>Email:</strong> admin@ecommerce.com</li>';
echo '<li><strong>Password:</strong> admin123</li>';
echo '</ul>';
echo '<p><a href="admin/login.php" style="padding: 10px 20px; background: #0d6efd; color: white; text-decoration: none; display: inline-block;">Go to Admin Login</a></p>';
echo '<p><small>Please delete check_admin.php and fix_password.php files after successful login for security.</small></p>';
?>
