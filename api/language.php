<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $lang = sanitize($_POST['lang'] ?? 'en');
        if (!in_array($lang, ['en', 'hi'])) {
            $lang = 'en';
        }
        setLanguage($lang);
        echo json_encode(['success' => true, 'lang' => $lang, 'redirect' => $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/']);
    } else {
        $lang = sanitize($_GET['lang'] ?? 'en');
        if (!in_array($lang, ['en', 'hi'])) {
            $lang = 'en';
        }
        setLanguage($lang);
        $referer = $_SERVER['HTTP_REFERER'] ?? SITE_URL . '/';
        header('Location: ' . $referer);
        exit;
    }
} catch (Exception $e) {
    error_log('Language API error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Server error. Please try again.']);
}
