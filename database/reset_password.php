<?php
/**
 * TOOL SEMENTARA: Reset semua password demo ke 'Demo@1234'
 * 
 * Jalankan SEKALI lewat browser jika login gagal setelah import SQL:
 * http://localhost/Monitoring-PKL/database/reset_password.php
 * 
 * HAPUS atau pindahkan file ini setelah selesai dipakai!
 */

require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $hash = password_hash('Demo@1234', PASSWORD_BCRYPT);

    $stmt = $pdo->prepare("UPDATE users SET password = ?");
    $stmt->execute([$hash]);

    $count = $stmt->rowCount();

    echo "<h2 style='font-family:sans-serif; color:green'>✓ Berhasil!</h2>";
    echo "<p style='font-family:sans-serif'>$count akun telah di-reset ke password: <strong>Demo@1234</strong></p>";
    echo "<p style='font-family:sans-serif'>Hash baru: <code>" . htmlspecialchars($hash) . "</code></p>";
    echo "<p style='font-family:sans-serif; color:red'><strong>Segera hapus file ini setelah selesai!</strong></p>";
    echo "<p><a href='http://localhost/Monitoring-PKL/auth/login.php'>→ Ke halaman Login</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Gagal koneksi database</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Pastikan XAMPP MySQL sudah berjalan dan database <strong>pkl_monitoring</strong> sudah di-import.</p>";
}
