<?php
/**
 * Helper autentikasi & proteksi halaman.
 * 
 * Fungsi tersedia:
 *   require_login()          — redirect ke login jika belum login
 *   require_role($role)      — redirect jika role tidak sesuai
 *   is_logged_in()           — cek apakah sudah login
 *   current_user()           — ambil data user dari session
 *   current_role()           — ambil role user saat ini
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Paksa halaman agar hanya bisa diakses user yang sudah login.
 * Jika belum login, redirect ke halaman login.
 */
function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: ' . APP_URL . '/auth/login.php');
        exit;
    }
}

/**
 * Paksa halaman agar hanya bisa diakses role tertentu.
 * @param string $role  'admin' atau 'siswa'
 */
function require_role(string $role): void
{
    require_login();

    if (current_role() !== $role) {
        // Arahkan ke dashboard masing-masing jika salah role
        if (current_role() === 'admin') {
            header('Location: ' . APP_URL . '/admin/dashboard.php');
        } else {
            header('Location: ' . APP_URL . '/siswa/dashboard.php');
        }
        exit;
    }
}

/**
 * Cek apakah user sudah login.
 */
function is_logged_in(): bool
{
    return isset($_SESSION['user_id'], $_SESSION['role']);
}

/**
 * Ambil semua data user dari session sebagai array.
 * Keys: user_id, nama, username, role, siswa_id (jika role=siswa)
 */
function current_user(): array
{
    return [
        'user_id'  => $_SESSION['user_id']  ?? null,
        'nama'     => $_SESSION['nama']     ?? '',
        'username' => $_SESSION['username'] ?? '',
        'role'     => $_SESSION['role']     ?? '',
        'siswa_id' => $_SESSION['siswa_id'] ?? null,
    ];
}

/**
 * Ambil role user saat ini.
 */
function current_role(): string
{
    return $_SESSION['role'] ?? '';
}
