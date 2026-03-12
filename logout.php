<?php
require_once __DIR__ . '/config/config.php';

// Destroy all session data
session_destroy();

// Redirect to home page
header('Location: ' . SITE_URL . '/');
exit;
?>
