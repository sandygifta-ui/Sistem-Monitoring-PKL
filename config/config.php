<?php
/**
 * Konfigurasi umum aplikasi.
 */

define('APP_NAME', 'Monitoring PKL');
define('APP_URL', 'http://localhost/Monitoring-PKL'); // Ganti saat deploy

// Durasi session (detik): 2 jam
define('SESSION_LIFETIME', 7200);

// Timezone
date_default_timezone_set('Asia/Jakarta');

// Mulai session dengan pengaturan aman
if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => SESSION_LIFETIME,
        'path'     => '/',
        'secure'   => false, // Ganti true jika pakai HTTPS
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
