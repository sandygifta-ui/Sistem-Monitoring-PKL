<?php
require_once __DIR__ . '/../config/config.php';

// Hapus semua data session
$_SESSION = [];

// Hapus cookie session
if (ini_get('session.use_cookies')) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params['path'], $params['domain'],
        $params['secure'], $params['httponly']
    );
}

session_destroy();

// Redirect ke halaman login
header('Location: ' . APP_URL . '/auth/login.php');
exit;
