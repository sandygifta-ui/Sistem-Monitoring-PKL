<?php
/**
 * Halaman bantu: generate hash password Demo@1234
 * Akses sekali lewat browser: http://localhost/Monitoring-PKL/database/generate_hash.php
 * Setelah dapat hashnya, HAPUS file ini atau pindah ke luar htdocs.
 */

$password = 'Demo@1234';
$hash = password_hash($password, PASSWORD_BCRYPT);

echo "<pre>";
echo "Password : " . htmlspecialchars($password) . "\n";
echo "Hash     : " . htmlspecialchars($hash) . "\n\n";
echo "Verifikasi: " . (password_verify($password, $hash) ? 'COCOK ✓' : 'TIDAK COCOK ✗');
echo "</pre>";
echo "<p style='color:red'><strong>Hapus file ini setelah selesai!</strong></p>";
