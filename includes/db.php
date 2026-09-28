<?php
/**
 * Koneksi database via PDO.
 * Panggil get_db() dari mana saja untuk mendapatkan instance PDO.
 * Koneksi dibuat sekali (singleton sederhana).
 */

require_once __DIR__ . '/../config/database.php';

function get_db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST
             . ";dbname=" . DB_NAME
             . ";charset=" . DB_CHARSET;

        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false, // Prepared statement asli
        ];

        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Tampilkan pesan ramah, sembunyikan detail teknis
            die('<div style="font-family:sans-serif;padding:20px;color:red">'
              . '<h3>Gagal terhubung ke database.</h3>'
              . '<p>Pastikan XAMPP MySQL sudah berjalan dan database <strong>'
              . DB_NAME . '</strong> sudah di-import.</p>'
              . '</div>');
        }
    }

    return $pdo;
}
