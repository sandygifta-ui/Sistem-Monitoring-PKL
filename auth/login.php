<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    if (current_role() === 'admin') {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        redirect(APP_URL . '/siswa/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        $db   = get_db();
        $stmt = $db->prepare('SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            session_regenerate_id(true);
            $_SESSION['user_id']  = $user['id'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            if ($user['role'] === 'siswa') {
                $db2 = get_db();
                $s   = $db2->prepare('SELECT id FROM siswa WHERE user_id = ? LIMIT 1');
                $s->execute([$user['id']]);
                $siswa = $s->fetch();
                $_SESSION['siswa_id'] = $siswa ? $siswa['id'] : null;
            }

            // Flag untuk trigger animasi transition ke dashboard
            $_SESSION['just_logged_in'] = true;

            if ($user['role'] === 'admin') {
                redirect(APP_URL . '/admin/dashboard.php');
            } else {
                redirect(APP_URL . '/siswa/dashboard.php');
            }
        } else {
            $error = 'Username atau password salah.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — SIMONA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    * { margin: 0; padding: 0; box-sizing: border-box; }

    body {
      font-family: 'Segoe UI', sans-serif;
      height: 100vh;
      overflow: hidden;
      display: flex;
    }

    /* ── KIRI: foto gedung ── */
    .side-left {
      flex: 1;
      position: relative;
      overflow: hidden;
      display: flex;
      flex-direction: column;
      justify-content: flex-end;
      padding: 3rem;
    }

    .side-left .bg-img {
      position: absolute;
      inset: 0;
      background: url('<?= APP_URL ?>/assets/img/gedung-smk.png') center/cover no-repeat;
      transform: scale(1.05);
      transition: transform 8s ease;
    }

    .side-left .bg-img.zoomed { transform: scale(1); }

    .side-left .overlay {
      position: absolute;
      inset: 0;
      background: linear-gradient(
        to bottom,
        rgba(10,20,50,0.45) 0%,
        rgba(10,20,50,0.75) 60%,
        rgba(10,20,50,0.92) 100%
      );
    }

    .side-left .content {
      position: relative;
      z-index: 2;
      color: white;
    }

    /* Logo sekolah */
    .school-logo {
      position: absolute;
      top: 2rem;
      left: 2rem;
      z-index: 2;
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .school-logo img {
      width: 48px;
      height: 48px;
      object-fit: contain;
      filter: drop-shadow(0 2px 4px rgba(0,0,0,0.4));
    }

    .school-logo span {
      color: white;
      font-weight: 700;
      font-size: 0.95rem;
      text-shadow: 0 1px 3px rgba(0,0,0,0.5);
      line-height: 1.3;
    }

    /* Teks VISKA */
    .viska-wrap { margin-bottom: 1.5rem; }

    .viska-word {
      display: inline-block;
      font-size: clamp(1.8rem, 3vw, 2.6rem);
      font-weight: 800;
      line-height: 1.25;
      opacity: 0;
      transform: translateY(24px);
      transition: opacity 0.5s ease, transform 0.5s ease;
      margin-right: 0.4rem;
    }

    .viska-word.show {
      opacity: 1;
      transform: translateY(0);
    }

    .viska-word.yellow { color: #F5C400; }
    .viska-word.white  { color: #ffffff; }

    .tagline {
      font-size: 0.9rem;
      color: rgba(255,255,255,0.7);
      letter-spacing: 0.5px;
      opacity: 0;
      transform: translateY(12px);
      transition: opacity 0.6s ease 1.2s, transform 0.6s ease 1.2s;
    }
    .tagline.show { opacity: 1; transform: translateY(0); }

    /* Stats kecil */
    .stats-row {
      display: flex;
      gap: 1.5rem;
      margin-top: 1.5rem;
      opacity: 0;
      transform: translateY(12px);
      transition: opacity 0.6s ease 1.6s, transform 0.6s ease 1.6s;
    }
    .stats-row.show { opacity: 1; transform: translateY(0); }

    .stat-item .num {
      font-size: 1.6rem;
      font-weight: 800;
      color: #F5C400;
      line-height: 1;
    }
    .stat-item .label {
      font-size: 0.72rem;
      color: rgba(255,255,255,0.65);
      margin-top: 2px;
    }

    /* ── KANAN: form login ── */
    .side-right {
      width: 420px;
      flex-shrink: 0;
      background: #fff;
      display: flex;
      flex-direction: column;
      justify-content: center;
      padding: 2.5rem;
      box-shadow: -8px 0 40px rgba(0,0,0,0.15);
      opacity: 0;
      transform: translateX(40px);
      transition: opacity 0.7s ease 0.3s, transform 0.7s ease 0.3s;
      overflow-y: auto;
    }

    .side-right.show { opacity: 1; transform: translateX(0); }

    .form-title {
      font-size: 1.5rem;
      font-weight: 800;
      color: #1e3a5f;
      margin-bottom: 0.25rem;
    }

    .form-subtitle {
      font-size: 0.85rem;
      color: #6c757d;
      margin-bottom: 2rem;
    }

    .form-control:focus {
      border-color: #1e3a5f;
      box-shadow: 0 0 0 0.2rem rgba(30,58,95,0.15);
    }

    .input-group-text {
      background: #f8f9fa;
      border-right: none;
      color: #1e3a5f;
    }

    .form-control { border-left: none; }

    .btn-masuk {
      background: #1e3a5f;
      border: none;
      color: white;
      padding: 0.7rem;
      font-weight: 700;
      font-size: 0.95rem;
      letter-spacing: 0.5px;
      border-radius: 8px;
      transition: background 0.2s, transform 0.1s;
    }

    .btn-masuk:hover  { background: #2d6a9f; color: white; }
    .btn-masuk:active { transform: scale(0.98); }

    .divider-text {
      font-size: 0.75rem;
      color: #adb5bd;
      text-align: center;
      position: relative;
      margin: 1.25rem 0;
    }

    .divider-text::before,
    .divider-text::after {
      content: '';
      position: absolute;
      top: 50%;
      width: 38%;
      height: 1px;
      background: #dee2e6;
    }

    .divider-text::before { left: 0; }
    .divider-text::after  { right: 0; }

    /* Transition overlay saat berhasil login */
    .login-transition {
      position: fixed;
      inset: 0;
      background: #1e3a5f;
      z-index: 9999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
    }

    /* Mobile responsive */
    @media (max-width: 768px) {
      body { flex-direction: column; overflow: auto; }
      .side-left {
        height: 40vh;
        flex: none;
        padding: 1.5rem;
      }
      .side-right {
        width: 100%;
        padding: 2rem 1.5rem;
        box-shadow: none;
        transform: translateY(20px);
      }
      .side-right.show { transform: translateY(0); }
      .viska-word { font-size: 1.5rem; }
      .school-logo { top: 1rem; left: 1rem; }
    }
  </style>
</head>
<body>

<!-- Overlay transisi -->
<div class="login-transition" id="loginTransition"></div>

<!-- KIRI -->
<div class="side-left">
  <div class="bg-img" id="bgImg"></div>
  <div class="overlay"></div>

  <!-- Logo sekolah -->
  <div class="school-logo">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png" alt="Logo SMK N 6 Surakarta">
    <span>SMK Negeri 6<br>Surakarta</span>
  </div>

  <!-- Konten bawah -->
  <div class="content">
    <div class="viska-wrap">
      <div>
        <span class="viska-word yellow" data-delay="0">Visioner</span>
        <span class="viska-word white"  data-delay="150">Inovatif</span>
      </div>
      <div>
        <span class="viska-word yellow" data-delay="300">Sinergi</span>
        <span class="viska-word white"  data-delay="450">Kompeten</span>
        <span class="viska-word yellow" data-delay="600">Amanah</span>
      </div>
    </div>

    <p class="tagline">
      <i class="bi bi-mortarboard-fill me-1"></i>
      Sistem Monitoring dan Nilai PKL — SIMONA
    </p>

    <div class="stats-row">
      <div class="stat-item">
        <div class="num">VISKA</div>
        <div class="label">Nilai SMK N 6 Surakarta</div>
      </div>
    </div>
  </div>
</div>

<!-- KANAN -->
<div class="side-right" id="sideRight">
  <div style="text-align:center;margin-bottom:1.5rem">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png"
         alt="Logo" style="width:56px;height:56px;object-fit:contain">
  </div>

  <div class="form-title">Selamat Datang 👋</div>
  <div class="form-subtitle">Masuk ke SIMONA — Sistem Monitoring dan Nilai PKL</div>

  <?php if ($error): ?>
    <div class="alert alert-danger py-2 small" role="alert">
      <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
    </div>
  <?php endif; ?>

  <form method="POST" novalidate id="loginForm">
    <?= csrf_input() ?>

    <div class="mb-3">
      <label class="form-label fw-semibold small">Username</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-person"></i></span>
        <input type="text" name="username" class="form-control"
               placeholder="Masukkan username"
               value="<?= e($_POST['username'] ?? '') ?>"
               required autocomplete="username">
      </div>
    </div>

    <div class="mb-4">
      <label class="form-label fw-semibold small">Password</label>
      <div class="input-group">
        <span class="input-group-text"><i class="bi bi-lock"></i></span>
        <input type="password" name="password" id="password"
               class="form-control" placeholder="Masukkan password"
               required autocomplete="current-password">
        <button class="btn btn-outline-secondary" type="button"
                id="togglePassword" tabindex="-1">
          <i class="bi bi-eye" id="eyeIcon"></i>
        </button>
      </div>
    </div>

    <button type="submit" class="btn btn-masuk w-100">
      <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
    </button>
  </form>

  <div class="divider-text">akun demo</div>

  <p class="text-center text-muted small mb-0">
    <code>admin</code> / <code>admin123</code>
    &nbsp;&bull;&nbsp;
    <code>siswa1</code> / <code>siswa123</code>
  </p>

  <p class="text-center mt-4 mb-0" style="font-size:0.7rem;color:#adb5bd">
    &copy; <?= date('Y') ?> SIMONA &mdash; SMK Negeri 6 Surakarta
  </p>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // ── Animasi masuk ──
  window.addEventListener('load', function () {
    // Zoom out foto gedung
    setTimeout(() => document.getElementById('bgImg').classList.add('zoomed'), 100);

    // Form slide in
    setTimeout(() => document.getElementById('sideRight').classList.add('show'), 200);

    // VISKA kata per kata
    document.querySelectorAll('.viska-word').forEach(function(el) {
      const delay = parseInt(el.dataset.delay) + 400;
      setTimeout(() => el.classList.add('show'), delay);
    });

    // Tagline & stats
    setTimeout(() => document.querySelector('.tagline').classList.add('show'), 1300);
    setTimeout(() => document.querySelector('.stats-row').classList.add('show'), 1700);
  });

  // ── Toggle password ──
  document.getElementById('togglePassword').addEventListener('click', function () {
    const pwd  = document.getElementById('password');
    const icon = document.getElementById('eyeIcon');
    if (pwd.type === 'password') {
      pwd.type = 'text';
      icon.classList.replace('bi-eye', 'bi-eye-slash');
    } else {
      pwd.type = 'password';
      icon.classList.replace('bi-eye-slash', 'bi-eye');
    }
  });

  // ── Transisi smooth saat submit berhasil ──
  // (hanya kalau tidak ada error — PHP redirect otomatis)
  document.getElementById('loginForm').addEventListener('submit', function () {
    const overlay = document.getElementById('loginTransition');
    overlay.style.opacity = '1';
    overlay.style.pointerEvents = 'all';
  });
</script>
</body>
</html>
