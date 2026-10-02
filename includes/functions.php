<?php
/**
 * Fungsi-fungsi utilitas umum yang dipakai di seluruh aplikasi.
 */

/**
 * Escape string untuk output HTML (cegah XSS).
 * Selalu pakai fungsi ini saat menampilkan data dari DB atau input user.
 */
function e(string $string): string
{
    return htmlspecialchars($string, ENT_QUOTES, 'UTF-8');
}

/**
 * Redirect ke URL tertentu lalu hentikan eksekusi.
 */
function redirect(string $url): void
{
    header('Location: ' . $url);
    exit;
}

/**
 * Simpan pesan flash ke session (tampil sekali lalu hilang).
 * @param string $type  'success', 'danger', 'warning', 'info'
 */
function set_flash(string $type, string $message): void
{
    $_SESSION['flash']       = ['type' => $type, 'message' => $message];
    $_SESSION['flash_toast'] = ['type' => $type, 'message' => $message];
}

/**
 * Ambil dan hapus pesan flash dari session.
 * Kembalikan string HTML alert Bootstrap, atau string kosong.
 */
function get_flash(): string
{
    if (!isset($_SESSION['flash'])) {
        return '';
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);

    $type    = e($flash['type']);
    $message = e($flash['message']);

    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">'
         . $message
         . '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Tutup"></button>'
         . '</div>';
}

/**
 * Format tanggal dari format database (Y-m-d) ke format Indonesia (d F Y).
 * Contoh: '2026-07-01' → '1 Juli 2026'
 */
function format_tanggal(string $tanggal): string
{
    if (empty($tanggal) || $tanggal === '0000-00-00') return '-';

    $bulan = [
        1  => 'Januari',  2  => 'Februari', 3  => 'Maret',
        4  => 'April',    5  => 'Mei',       6  => 'Juni',
        7  => 'Juli',     8  => 'Agustus',   9  => 'September',
        10 => 'Oktober',  11 => 'November',  12 => 'Desember',
    ];

    $ts = strtotime($tanggal);
    return date('j', $ts) . ' ' . $bulan[(int)date('n', $ts)] . ' ' . date('Y', $ts);
}

/**
 * Kembalikan badge HTML Bootstrap sesuai status verifikasi jurnal.
 */
function badge_status(string $status): string
{
    $map = [
        'menunggu'    => ['bg-warning text-dark', 'Menunggu'],
        'diverifikasi'=> ['bg-success',           'Diverifikasi'],
        'ditolak'     => ['bg-danger',            'Ditolak'],
    ];

    [$class, $label] = $map[$status] ?? ['bg-secondary', ucfirst($status)];

    return '<span class="badge ' . $class . '">' . $label . '</span>';
}

/**
 * Hitung nilai rata-rata dari array nilai.
 * Kembalikan float dengan 1 desimal, atau '-' jika array kosong.
 */
function rata_rata(array $nilai_list): string
{
    if (empty($nilai_list)) return '-';
    return number_format(array_sum($nilai_list) / count($nilai_list), 1);
}

/**
 * Buat URL pagination dengan mempertahankan query string yang ada.
 * @param int $page  Nomor halaman target
 */
function pagination_url(int $page): string
{
    $params = $_GET;
    $params['page'] = $page;
    return '?' . http_build_query($params);
}

/**
 * Render komponen pagination Bootstrap 5.
 * @param int $current_page  Halaman saat ini
 * @param int $total_pages   Total halaman
 */
function render_pagination(int $current_page, int $total_pages): string
{
    if ($total_pages <= 1) return '';

    $html = '<nav aria-label="Navigasi halaman"><ul class="pagination pagination-sm mb-0">';

    // Tombol Sebelumnya
    $prev_disabled = $current_page <= 1 ? 'disabled' : '';
    $html .= '<li class="page-item ' . $prev_disabled . '">'
           . '<a class="page-link" href="' . pagination_url($current_page - 1) . '">‹ Sebelumnya</a>'
           . '</li>';

    // Nomor halaman (tampilkan max 5 di sekitar halaman aktif)
    $start = max(1, $current_page - 2);
    $end   = min($total_pages, $current_page + 2);

    if ($start > 1) {
        $html .= '<li class="page-item"><a class="page-link" href="' . pagination_url(1) . '">1</a></li>';
        if ($start > 2) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
    }

    for ($i = $start; $i <= $end; $i++) {
        $active = $i === $current_page ? 'active' : '';
        $html .= '<li class="page-item ' . $active . '">'
               . '<a class="page-link" href="' . pagination_url($i) . '">' . $i . '</a>'
               . '</li>';
    }

    if ($end < $total_pages) {
        if ($end < $total_pages - 1) $html .= '<li class="page-item disabled"><span class="page-link">…</span></li>';
        $html .= '<li class="page-item"><a class="page-link" href="' . pagination_url($total_pages) . '">' . $total_pages . '</a></li>';
    }

    // Tombol Berikutnya
    $next_disabled = $current_page >= $total_pages ? 'disabled' : '';
    $html .= '<li class="page-item ' . $next_disabled . '">'
           . '<a class="page-link" href="' . pagination_url($current_page + 1) . '">Berikutnya ›</a>'
           . '</li>';

    $html .= '</ul></nav>';
    return $html;
}

/**
 * Validasi: cek apakah semua field required tidak kosong.
 * @param array $fields  Array nama field yang wajib diisi
 * @param array $data    Array data ($_POST)
 * @return array         Array pesan error (kosong jika valid)
 */
function validate_required(array $fields, array $data): array
{
    $errors = [];
    foreach ($fields as $field) {
        if (empty(trim($data[$field] ?? ''))) {
            $errors[] = "Field <strong>$field</strong> wajib diisi.";
        }
    }
    return $errors;
}
