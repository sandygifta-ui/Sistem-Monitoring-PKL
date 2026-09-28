<?php
/**
 * Helper CSRF (Cross-Site Request Forgery) Protection.
 *
 * Cara pakai di form:
 *   echo csrf_input();          ← taruh di dalam <form>
 *
 * Cara validasi di proses POST:
 *   csrf_verify();              ← panggil di awal script yang proses form
 */

require_once __DIR__ . '/../config/config.php';

/**
 * Generate atau ambil token CSRF dari session.
 */
function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Kembalikan hidden input HTML berisi token CSRF.
 * Langsung echo di dalam <form>.
 */
function csrf_input(): string
{
    return '<input type="hidden" name="csrf_token" value="'
         . htmlspecialchars(csrf_token(), ENT_QUOTES, 'UTF-8')
         . '">';
}

/**
 * Verifikasi token CSRF dari request POST.
 * Jika tidak valid, hentikan eksekusi dengan pesan error 403.
 */
function csrf_verify(): void
{
    $token = $_POST['csrf_token'] ?? '';

    if (!hash_equals(csrf_token(), $token)) {
        http_response_code(403);
        die('<div style="font-family:sans-serif;padding:20px;color:red">'
          . '<h3>403 – Permintaan tidak valid.</h3>'
          . '<p>Token keamanan tidak cocok. Silakan kembali dan coba lagi.</p>'
          . '</div>');
    }
}
