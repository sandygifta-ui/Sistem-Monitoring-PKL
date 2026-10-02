<?php
/**
 * Header & Sidebar — di-include di setiap halaman setelah require_login().
 * Variabel yang bisa di-set sebelum include:
 *   $page_title  — judul tab browser (default: 'Monitoring PKL')
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/auth.php';

$page_title = $page_title ?? 'Monitoring PKL';
$user       = current_user();
$role       = current_role();

// Tentukan prefix URL menu sesuai role
$base = ($role === 'admin') ? APP_URL . '/admin' : APP_URL . '/siswa';

// Halaman aktif untuk highlight menu
$current = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= e($page_title) ?> — SIMONA</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    :root {
      --sidebar-width: 240px;
      --primary: #2E0A4F;
      --accent: #E11D74;
      --accent-soft: #7C3AED;
      --accent-light: rgba(225,29,116,0.1);
      --accent-soft-light: rgba(124,58,237,0.1);
    }

    body { background: #FAF5FF; font-family: 'Segoe UI', sans-serif; }

    /* ── Sidebar ── */
    .sidebar {
      width: var(--sidebar-width);
      min-height: 100%;
      height: 100%;
      background: var(--primary);
      position: fixed;
      top: 0; left: 0; bottom: 0;
      z-index: 1000;
      transition: transform 0.3s ease;
      display: flex;
      flex-direction: column;
      box-shadow: 2px 0 16px rgba(46,10,79,0.3);
    }

    .sidebar-brand {
      padding: 1.25rem 1rem;
      border-bottom: 1px solid rgba(255,255,255,0.08);
      text-decoration: none;
    }

    .sidebar-brand h6 { color: #fff; font-weight: 700; margin: 0; font-size: 0.9rem; }
    .sidebar-brand small { color: rgba(255,255,255,0.4); font-size: 0.75rem; }

    .sidebar-nav { padding: 0.5rem 0; flex: 1; }

    .sidebar-label {
      padding: 0.75rem 1rem 0.25rem;
      font-size: 0.62rem;
      font-weight: 700;
      letter-spacing: 1.2px;
      text-transform: uppercase;
      color: rgba(255,255,255,0.25);
    }

    .sidebar-nav .nav-link {
      color: rgba(255,255,255,0.6);
      padding: 0.6rem 1rem;
      display: flex;
      align-items: center;
      gap: 0.65rem;
      font-size: 0.875rem;
      transition: all 0.2s;
      margin: 1px 0;
      border-left: 3px solid transparent;
    }

    .sidebar-nav .nav-link:hover {
      background: rgba(225,29,116,0.12);
      color: #fff;
      border-left-color: rgba(225,29,116,0.5);
    }

    .sidebar-nav .nav-link.active {
      background: rgba(225,29,116,0.18);
      color: #fff;
      border-left-color: var(--accent);
    }

    .sidebar-nav .nav-link i {
      color: #c084fc;
      font-size: 1rem;
      width: 18px;
      text-align: center;
    }

    .sidebar-nav .nav-link.active i { color: var(--accent); }

    .sidebar-footer {
      padding: 1rem;
      border-top: 1px solid rgba(255,255,255,0.07);
    }

    /* ── Main content ── */
    .main-wrapper {
      margin-left: var(--sidebar-width);
      min-height: 100vh;
      display: flex;
      flex-direction: column;
      overflow: visible;
    }

    /* ── Topbar ── */
    .topbar {
      background: #fff;
      border-bottom: 1px solid #ede9fe;
      padding: 0.75rem 1.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      position: sticky;
      top: 0;
      z-index: 999;
      box-shadow: 0 1px 4px rgba(124,58,237,0.07);
    }

    .topbar .page-heading {
      font-weight: 700;
      color: var(--primary);
      margin: 0;
      font-size: 1.1rem;
    }

    /* Content area animasi masuk */
    .content-area {
      padding: 1.5rem;
      flex: 1;
      opacity: 0;
      animation: contentFadeIn 0.5s ease 0.05s forwards;
      overflow: visible;
    }

    @keyframes contentFadeIn {
      from { opacity: 0; transform: translateY(16px); }
      to   { opacity: 1; transform: translateY(0); }
    }

    /* ── Bootstrap overrides ── */
    .btn-primary {
      background: var(--accent) !important;
      border-color: var(--accent) !important;
      color: #fff !important;
      font-weight: 600 !important;
    }
    .btn-primary:hover {
      background: #be185d !important;
      border-color: #be185d !important;
    }
    .btn-outline-primary {
      color: var(--accent) !important;
      border-color: var(--accent) !important;
    }
    .btn-outline-primary:hover {
      background: var(--accent) !important;
      color: #fff !important;
    }
    .btn-outline-secondary {
      color: var(--accent-soft) !important;
      border-color: var(--accent-soft) !important;
    }
    .btn-outline-secondary:hover {
      background: var(--accent-soft) !important;
      color: #fff !important;
    }

    /* Badge */
    .badge.bg-primary  { background: var(--accent) !important; }
    .badge.bg-warning  { background: var(--accent-soft) !important; color: #fff !important; }
    .badge.bg-success  { background: #059669 !important; }
    .badge.bg-danger   { background: #dc2626 !important; }
    .badge.bg-light    { background: #f3e8ff !important; color: var(--primary) !important; }

    /* Link */
    a { color: var(--accent-soft); }
    a:hover { color: var(--accent); }

    /* Ikon kartu */
    .icon-accent {
      background: var(--accent-light);
      color: var(--accent);
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }

    .icon-soft {
      background: var(--accent-soft-light);
      color: var(--accent-soft);
      width: 46px; height: 46px;
      border-radius: 12px;
      display: flex; align-items: center; justify-content: center;
      font-size: 1.3rem;
      flex-shrink: 0;
    }

    /* Kartu */
    .card { border-radius: 12px !important; }

    /* Hover effect kartu */
    .card.hover-lift {
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      cursor: default;
    }
    .card.hover-lift:hover {
      transform: translateY(-4px);
      box-shadow: 0 8px 24px rgba(46,10,79,0.15) !important;
    }

    /* Form focus */
    .form-control:focus, .form-select:focus {
      border-color: var(--accent-soft) !important;
      box-shadow: 0 0 0 0.2rem rgba(124,58,237,0.15) !important;
    }

    /* ── Toast notification ── */
    .toast-container-custom {
      position: fixed;
      bottom: 1.5rem;
      right: 1.5rem;
      z-index: 9999;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
    }

    .toast-custom {
      background: #fff;
      border-radius: 12px;
      padding: 0.85rem 1.1rem;
      box-shadow: 0 4px 20px rgba(0,0,0,0.15);
      display: flex;
      align-items: center;
      gap: 0.75rem;
      min-width: 280px;
      max-width: 360px;
      opacity: 0;
      transform: translateX(40px);
      transition: opacity 0.3s ease, transform 0.3s ease;
      border-left: 4px solid var(--accent);
    }

    .toast-custom.show {
      opacity: 1;
      transform: translateX(0);
    }

    .toast-custom.toast-success { border-left-color: #10B981; }
    .toast-custom.toast-danger  { border-left-color: #EF4444; }
    .toast-custom.toast-warning { border-left-color: #F59E0B; }
    .toast-custom.toast-info    { border-left-color: #7C3AED; }

    .toast-custom .toast-icon { font-size: 1.2rem; flex-shrink: 0; }
    .toast-custom .toast-msg  { font-size: 0.85rem; color: #374151; flex: 1; }
    .toast-custom .toast-close {
      background: none; border: none; padding: 0;
      color: #9ca3af; cursor: pointer; font-size: 1rem;
    }

    /* ── Responsive ── */
    @media (max-width: 768px) {
      .sidebar { transform: translateX(-100%); }
      .sidebar.show { transform: translateX(0); }
      .main-wrapper { margin-left: 0; }
      .sidebar-overlay {
        display: none;
        position: fixed; inset: 0;
        background: rgba(0,0,0,0.4);
        z-index: 999;
      }
      .sidebar-overlay.show { display: block; }
    }
  </style>
</head>
<body>

<!-- Overlay mobile -->
<div class="sidebar-overlay" id="sidebarOverlay" onclick="toggleSidebar()"></div>

<!-- ══ SIDEBAR ══ -->
<nav class="sidebar" id="sidebar">
  <!-- Brand -->
  <a href="<?= $base ?>/dashboard.php" class="sidebar-brand d-flex align-items-center gap-2 text-decoration-none">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png"
         alt="Logo SMK"
         style="width:36px;height:36px;object-fit:contain;flex-shrink:0">
    <div>
      <h6 class="mb-0">SIMONA</h6>
      <small>Monitoring & Nilai PKL</small>
    </div>
  </a>

  <!-- Menu -->
  <div class="sidebar-nav">
    <?php if ($role === 'admin'): ?>
      <div class="sidebar-label">Menu Utama</div>
      <a href="<?= APP_URL ?>/admin/dashboard.php"
         class="nav-link <?= $current === 'dashboard.php' && $current_dir === 'admin' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>
      <a href="<?= APP_URL ?>/admin/siswa/index.php"
         class="nav-link <?= $current_dir === 'siswa' ? 'active' : '' ?>">
        <i class="bi bi-people"></i> Data Siswa
      </a>

      <div class="sidebar-label">PKL</div>
      <a href="<?= APP_URL ?>/admin/jurnal/index.php"
         class="nav-link <?= $current_dir === 'jurnal' ? 'active' : '' ?>">
        <i class="bi bi-journal-check"></i> Jurnal Harian
      </a>
      <a href="<?= APP_URL ?>/admin/nilai/index.php"
         class="nav-link <?= $current_dir === 'nilai' ? 'active' : '' ?>">
        <i class="bi bi-award"></i> Penilaian
      </a>

      <div class="sidebar-label">Laporan</div>
      <a href="<?= APP_URL ?>/admin/laporan/index.php"
         class="nav-link <?= $current_dir === 'laporan' ? 'active' : '' ?>">
        <i class="bi bi-file-earmark-bar-graph"></i> Rekap & Export
      </a>
      <div class="sidebar-label">Pengaturan</div>
      <a href="<?= APP_URL ?>/admin/akun/index.php"
         class="nav-link <?= $current_dir === 'akun' ? 'active' : '' ?>">
        <i class="bi bi-people-fill"></i> Kelola Akun Guru
      </a>

    <?php else: ?>
      <div class="sidebar-label">Menu Utama</div>
      <a href="<?= APP_URL ?>/siswa/dashboard.php"
         class="nav-link <?= $current === 'dashboard.php' && $current_dir === 'siswa' ? 'active' : '' ?>">
        <i class="bi bi-speedometer2"></i> Dashboard
      </a>

      <div class="sidebar-label">PKL Saya</div>
      <a href="<?= APP_URL ?>/siswa/jurnal/index.php"
         class="nav-link <?= $current_dir === 'jurnal' ? 'active' : '' ?>">
        <i class="bi bi-journal-text"></i> Jurnal Harian
      </a>
      <a href="<?= APP_URL ?>/siswa/nilai/index.php"
         class="nav-link <?= $current_dir === 'nilai' ? 'active' : '' ?>">
        <i class="bi bi-award"></i> Nilai Saya
      </a>
    <?php endif; ?>
  </div>

  <!-- Sidebar footer: info user -->
  <div class="sidebar-footer">
    <div class="d-flex align-items-center gap-2">
      <div style="width:32px;height:32px;background:rgba(255,255,255,0.2);border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0">
        <i class="bi bi-person-fill text-white small"></i>
      </div>
      <div style="min-width:0">
        <div class="text-white small fw-semibold text-truncate"><?= e($user['nama']) ?></div>
        <div style="color:rgba(255,255,255,0.5);font-size:0.7rem"><?= $role === 'admin' ? 'Admin' : 'Siswa' ?></div>
      </div>
    </div>
  </div>
</nav>

<!-- ══ MAIN WRAPPER ══ -->
<div class="main-wrapper">

  <!-- Topbar -->
  <div class="topbar">
    <div class="d-flex align-items-center gap-3">
      <!-- Tombol hamburger (mobile) -->
      <button class="btn btn-sm btn-light d-md-none" onclick="toggleSidebar()">
        <i class="bi bi-list fs-5"></i>
      </button>
      <h5 class="page-heading"><?= e($page_title) ?></h5>
    </div>

    <!-- Dropdown user -->
    <div class="d-flex align-items-center gap-2">

      <!-- Lonceng notifikasi (khusus admin) -->
      <?php if ($role === 'admin'):
        $db_bell = get_db();
        $pending_count = $db_bell->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_verifikasi='menunggu'")->fetchColumn();
      ?>
      <div class="dropdown">
        <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown" aria-expanded="false">
          <i class="bi bi-bell fs-5"></i>
          <?php if ($pending_count > 0): ?>
            <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger"
                  style="font-size:0.6rem">
              <?= $pending_count > 9 ? '9+' : $pending_count ?>
            </span>
          <?php endif; ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" style="min-width:300px;max-width:340px">
          <li>
            <h6 class="dropdown-header d-flex justify-content-between align-items-center">
              <span>Jurnal Menunggu Verifikasi</span>
              <?php if ($pending_count > 0): ?>
                <span class="badge bg-danger rounded-pill"><?= $pending_count ?></span>
              <?php endif; ?>
            </h6>
          </li>
          <li><hr class="dropdown-divider my-0"></li>
          <?php
          $bell_jurnal = $db_bell->query("
            SELECT j.id, j.tanggal, j.kegiatan, u.nama AS nama_siswa
            FROM jurnal_harian j
            JOIN siswa s ON j.siswa_id = s.id
            JOIN users u ON s.user_id = u.id
            WHERE j.status_verifikasi = 'menunggu'
            ORDER BY j.created_at DESC
            LIMIT 5
          ")->fetchAll();
          ?>
          <?php if (empty($bell_jurnal)): ?>
            <li>
              <div class="text-center py-3 text-muted small">
                <i class="bi bi-check-circle text-success d-block fs-4 mb-1"></i>
                Tidak ada jurnal pending
              </div>
            </li>
          <?php else: ?>
            <?php foreach ($bell_jurnal as $bj): ?>
            <li>
              <a class="dropdown-item py-2" href="<?= APP_URL ?>/admin/jurnal/index.php">
                <div class="fw-semibold small"><?= e($bj['nama_siswa']) ?></div>
                <div class="text-muted" style="font-size:0.75rem;white-space:normal">
                  <?= e(mb_substr($bj['kegiatan'], 0, 60)) ?>...
                </div>
                <div class="text-muted" style="font-size:0.7rem"><?= format_tanggal($bj['tanggal']) ?></div>
              </a>
            </li>
            <?php endforeach; ?>
            <?php if ($pending_count > 5): ?>
            <li><hr class="dropdown-divider my-0"></li>
            <li>
              <a class="dropdown-item text-center small" href="<?= APP_URL ?>/admin/jurnal/index.php?status=menunggu">
                Lihat semua <?= $pending_count ?> jurnal →
              </a>
            </li>
            <?php endif; ?>
          <?php endif; ?>
        </ul>
      </div>
      <?php endif; ?>

      <!-- Dropdown akun -->
      <div class="dropdown">
        <button class="btn btn-sm btn-light dropdown-toggle d-flex align-items-center gap-2" data-bs-toggle="dropdown">
          <i class="bi bi-person-circle"></i>
          <span class="d-none d-sm-inline"><?= e($user['nama']) ?></span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow-sm">
          <li><h6 class="dropdown-header"><?= e($user['username']) ?></h6></li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item" href="<?= APP_URL ?>/profil.php">
              <i class="bi bi-person-circle me-2"></i>Profil Saya
            </a>
          </li>
          <li><hr class="dropdown-divider"></li>
          <li>
            <a class="dropdown-item text-danger" href="<?= APP_URL ?>/auth/logout.php"
               onclick="return confirm('Yakin ingin keluar?')">
              <i class="bi bi-box-arrow-right me-2"></i>Keluar
            </a>
          </li>
        </ul>
      </div>

    </div>
  </div>

  <!-- Content area -->
  <div class="content-area">
    <?= get_flash() ?>
