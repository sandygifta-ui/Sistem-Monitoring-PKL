<?php
/**
 * TOOL SEMENTARA: Reset password semua akun demo ke password aslinya.
 *
 * Jalankan SEKALI lewat browser jika login gagal setelah import SQL:
 *   http://localhost/Monitoring-PKL/database/reset_password.php
 *
 * HAPUS file ini setelah selesai dipakai!
 */

require_once __DIR__ . '/../config/database.php';

try {
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
    $pdo = new PDO($dsn, DB_USER, DB_PASS, [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

    $akun = [
        ['username' => 'admin',  'password' => 'admin123'],
        ['username' => 'siswa1', 'password' => 'siswa123'],
        ['username' => 'siswa2', 'password' => 'siswa123'],
    ];

    $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE username = ?");
    foreach ($akun as $a) {
        $hash = password_hash($a['password'], PASSWORD_BCRYPT);
        $stmt->execute([$hash, $a['username']]);
    }

    echo "<h2 style='font-family:sans-serif;color:green'>✓ Password berhasil di-reset!</h2>";
    echo "<table style='font-family:sans-serif;border-collapse:collapse'>";
    echo "<tr><th style='border:1px solid #ccc;padding:8px'>Username</th><th style='border:1px solid #ccc;padding:8px'>Password</th></tr>";
    foreach ($akun as $a) {
        echo "<tr><td style='border:1px solid #ccc;padding:8px'>{$a['username']}</td>"
           . "<td style='border:1px solid #ccc;padding:8px'>{$a['password']}</td></tr>";
    }
    echo "</table>";
    echo "<p style='font-family:sans-serif;color:red;margin-top:16px'><strong>Segera hapus file ini!</strong></p>";
    echo "<p><a href='../auth/login.php' style='font-family:sans-serif'>→ Ke halaman Login</a></p>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>Gagal koneksi database</h2>";
    echo "<p>" . htmlspecialchars($e->getMessage()) . "</p>";
    echo "<p>Pastikan XAMPP MySQL sudah jalan dan database <strong>"
       . DB_NAME . "</strong> sudah di-import.</p>";
}
