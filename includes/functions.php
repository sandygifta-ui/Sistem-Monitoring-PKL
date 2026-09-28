<?php
/**
 * Fungsi-fungsi utilitas umum yang dipakai di seluruh aplikasi.
 */

/**
 * Escape string untuk output HTML — SELALU pakai ini saat tampilkan data dari DB.
 * Mencegah XSS (Cross-Site Scripting).
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect ke URL lain lalu hentikan script.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Simpan pesan flash ke session (tampil sekali, lalu hilang otomatis).
 * @param string $type  'success' | 'danger' | 'warning' | 'info'
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil & hapus pesan flash, kembalikan sebagai HTML alert Bootstrap.
 * Kembalikan string kosong jika tidak ada pesan.
 */
function get_flash(): string
{
    if (!isset($_SESSION['flash'])) return '';

    $type    = e($_SESSION['flash']['type']);
    $message = e($_SESSION['flash']['message']);
    unset($_SESSION['flash']);

    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
         . $message
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>'
         . '</div>';
}

/**
 * Format tanggal dari Y-m-d ke format Indonesia: "1 Januari 2026"
 */
function format_tanggal(string $tanggal): string
{
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';

    $bulan = ['', 'Januari','Februari','Maret','April','Mei','Juni',
              'Juli','Agustus','September','Oktober','November','Desember'];
    $ts = strtotime($tanggal);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Kembalikan badge HTML Bootstrap sesuai status verifikasi jurnal.
 */
function badge_status(string $status): string
{
    $map = [
        'menunggu'     => ['bg-warning text-dark', 'Menunggu'],
        'diverifikasi' => ['bg-success',           'Diverifikasi'],
        'ditolak'      => ['bg-danger',            'Ditolak'],
    ];
    [$class, $label] = $map[$status] ?? ['bg-secondary', ucfirst($status)];
    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Buat URL pagination dengan mempertahankan query string yang ada.
 */
function pagination_url(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

/**
 * Render komponen pagination Bootstrap 5.
 */
function render_pagination(int $current, int $total): string
{
    if ($total <= 1) return '';

    $html = '<nav><ul class="pagination pagination-sm mb-0">';

    // Tombol sebelumnya
    $html .= '<li class="page-item ' . ($current <= 1 ? 'disabled' : '') . '">'
           . '<a class="page-link" href="' . pagination_url($current - 1) . '">‹</a></li>';

    // Nomor halaman
    $start = max(1, $current - 2);
    $end   = min($total, $current + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . pagination_url(1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }
    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">'
               . '<a class="page-link" href="' . pagination_url($i) . '">' . $i . '</a></li>';
    }
    if ($end < $total) {
        if ($end < $total - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . pagination_url($total) . '">' . $total . '</a></li>';
    }

    // Tombol berikutnya
    $html .= '<li class="page-item ' . ($current >= $total ? 'disabled' : '') . '">'
           . '<a class="page-link" href="' . pagination_url($current + 1) . '">›</a></li>';

    $html .= '</ul></nav>';
    return $html;
}
