<?php
/**
 * TOOL SEMENTARA: Reset password akun admin ke 'admin123'
 * Jalankan lewat browser: http://localhost/Monitoring-PKL/database/reset_password.php
 * HAPUS file ini setelah selesai!
 */

require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    // Reset hanya akun admin saja
    $hash = password_hash('admin123', PASSWORD_BCRYPT);
    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = 'admin'");
    $stmt->execute([$hash]);

    echo "<h2 style='font-family:sans-serif;color:green'>✓ Password admin berhasil di-reset!</h2>";
    echo "<table style='font-family:sans-serif;border-collapse:collapse'>";
    echo "<tr><th style='border:1px solid #ccc;padding:8px'>Username</th><th style='border:1px solid #ccc;padding:8px'>Password Baru</th></tr>";
    echo "<tr><td style='border:1px solid #ccc;padding:8px'>admin</td><td style='border:1px solid #ccc;padding:8px'>admin123</td></tr>";
    echo "</table>";
    echo "<p style='font-family:sans-serif;color:red;margin-top:16px'><strong>Segera hapus file ini!</strong></p>";
    echo "<p><a href='../auth/login.php' style='font-family:sans-serif'>→ Ke halaman Login</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Gagal koneksi database</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
}
