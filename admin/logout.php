<?php
require_once __DIR__ . '/../config/config.php';

// Destroy all session data
session_destroy();

// Redirect to admin login
header('Location: ' . ADMIN_URL . '/login.php');
exit;
?>
