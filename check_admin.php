<?php
require_once 'config/database.php';

$db = getDB();
$stmt = $db->query('SELECT id, email, password FROM admins');
$admin = $stmt->fetch();

echo '<h2>Current Admin Status</h2>';
echo 'Email: ' . $admin['email'] . '<br>';
echo 'Password Hash: ' . substr($admin['password'], 0, 30) . '...<br>';
echo 'Testing password "admin123": ';

if (password_verify('admin123', $admin['password'])) {
    echo '<strong style="color: green;">SUCCESS ✓</strong><br>';
    echo '<p>Password is correct! You can login now.</p>';
} else {
    echo '<strong style="color: red;">FAILED ✗</strong><br>';
    echo '<p>Password hash is incorrect. Click button below to fix it.</p>';
    echo '<form method="POST" action="fix_password.php">';
    echo '<button type="submit" style="padding: 10px 20px; background: #0d6efd; color: white; border: none; cursor: pointer;">Fix Admin Password</button>';
    echo '</form>';
}
?>
