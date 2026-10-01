<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

if (is_logged_in()) {
    if (current_role() === 'admin') redirect(APP_URL . '/admin/dashboard.php');
    else redirect(APP_URL . '/siswa/dashboard.php');
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
                $s = get_db()->prepare('SELECT id FROM siswa WHERE user_id = ? LIMIT 1');
                $s->execute([$user['id']]);
                $siswa = $s->fetch();
                $_SESSION['siswa_id'] = $siswa ? $siswa['id'] : null;
            }

            if ($user['role'] === 'admin') redirect(APP_URL . '/admin/dashboard.php');
            else redirect(APP_URL . '/siswa/dashboard.php');
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
    * { margin:0; padding:0; box-sizing:border-box; }

    body {
      font-family: 'Segoe UI', sans-serif;
      background: #f0f2f5;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    /* ── NAVBAR ── */
    .top-nav {
      background: #fff;
      padding: 0.85rem 2.5rem;
      display: flex;
      align-items: center;
      justify-content: space-between;
      box-shadow: 0 1px 4px rgba(0,0,0,0.08);
      position: relative;
      z-index: 10;
      opacity: 0;
      transform: translateY(-16px);
      animation: slideDown 0.5s ease 0.1s forwards;
    }

    .nav-brand {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .nav-brand img { width: 40px; height: 40px; object-fit: contain; }

    .nav-brand-text .title {
      font-weight: 800;
      font-size: 1.1rem;
      color: #1e3a5f;
      line-height: 1.1;
    }

    .nav-brand-text .sub {
      font-size: 0.7rem;
      color: #6c757d;
    }

    /* ── HERO AREA ── */
    .hero {
      flex: 1;
      display: flex;
      flex-direction: column;
      align-items: center;
      justify-content: center;
      padding: 2rem 2.5rem;
    }

    /* Teks VISKA di atas gambar */
    .viska-section {
      width: 85%;
      max-width: 960px;
      margin-bottom: 1.25rem;
    }

    .viska-section h1 {
      font-size: clamp(1.6rem, 3.5vw, 2.8rem);
      font-weight: 800;
      color: #1e3a5f;
      line-height: 1.4;
    }

    .viska-word.yellow { color: #F5C400; }
    .viska-word        { color: #1e3a5f; margin-right: 0.3rem; }

    /* ── MAIN CARD: gambar + form ── */
    .main-card {
      width: 85%;
      max-width: 960px;
      position: relative;
      border-radius: 24px;
      overflow: visible;
      box-shadow: 0 12px 48px rgba(0,0,0,0.15);
      opacity: 0;
      transform: translateY(30px);
      transition: opacity 0.7s ease, transform 0.7s ease;
    }
    .main-card.show { opacity:1; transform:translateY(0); }

    /* Foto gedung */
    .card-bg {
      width: 100%;
      height: 380px;
      object-fit: cover;
      display: block;
      border-radius: 24px;
    }

    /* Overlay gelap tipis di atas foto */
    .card-overlay {
      position: absolute;
      inset: 0;
      border-radius: 24px;
      background: linear-gradient(
        to right,
        rgba(10,20,50,0.55) 0%,
        rgba(10,20,50,0.15) 55%,
        rgba(10,20,50,0.05) 100%
      );
    }

    /* Stats di kiri bawah foto */
    .card-stats {
      position: absolute;
      bottom: 2rem;
      left: -1.5rem;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      z-index: 5;
    }

    .stat-pill {
      background: rgba(10,20,50,0.82);
      backdrop-filter: blur(6px);
      color: white;
      padding: 0.6rem 1.1rem;
      border-radius: 12px;
      display: inline-flex;
      flex-direction: column;
      min-width: 190px;
    }

    .stat-pill .num {
      font-size: 1.4rem;
      font-weight: 800;
      color: #F5C400;
      line-height: 1;
    }

    .stat-pill .lbl {
      font-size: 0.72rem;
      opacity: 0.8;
      margin-top: 2px;
    }

    /* ── FORM LOGIN (menempel di kanan atas foto) ── */
    .login-card {
      position: absolute;
      top: 50%;
      right: -1.5rem;
      transform: translateY(-50%);
      width: 320px;
      background: #fff;
      border-radius: 16px;
      padding: 1.75rem;
      box-shadow: 0 8px 32px rgba(0,0,0,0.18);
      opacity: 0;
      transition: opacity 0.6s ease, right 0.6s ease;
    }
    .login-card.show { opacity: 1; }

    .login-card .lc-title {
      font-weight: 800;
      font-size: 1.15rem;
      color: #1e3a5f;
      margin-bottom: 0.2rem;
    }

    .login-card .lc-sub {
      font-size: 0.78rem;
      color: #6c757d;
      margin-bottom: 1.25rem;
    }

    .form-label { font-size: 0.82rem; font-weight: 600; color: #1e3a5f; margin-bottom: 4px; }

    .form-control:focus {
      border-color: #1e3a5f;
      box-shadow: 0 0 0 0.18rem rgba(30,58,95,0.15);
    }

    .input-group-text {
      background: #f8f9fa;
      border-right: none;
      color: #1e3a5f;
      font-size: 0.85rem;
    }

    .form-control { border-left: none; font-size: 0.88rem; }

    .btn-masuk {
      background: #1e3a5f;
      border: none;
      color: white;
      padding: 0.6rem;
      font-weight: 700;
      font-size: 0.9rem;
      border-radius: 8px;
      transition: background 0.2s, transform 0.1s;
      width: 100%;
      margin-top: 0.5rem;
    }

    .btn-masuk:hover  { background: #2d6a9f; color: white; }
    .btn-masuk:active { transform: scale(0.98); }

    .demo-info {
      text-align: center;
      font-size: 0.7rem;
      color: #adb5bd;
      margin-top: 1rem;
    }

    /* Transition overlay */
    .login-transition {
      position: fixed; inset: 0;
      background: #1e3a5f;
      z-index: 9999;
      opacity: 0;
      pointer-events: none;
      transition: opacity 0.5s ease;
    }

    /* Animasi */
    @keyframes slideDown {
      to { opacity:1; transform:translateY(0); }
    }
    @keyframes fadeUp {
      to { opacity:1; transform:translateY(0); }
    }

    /* VISKA kata per kata */
    .viska-word {
      display: inline-block;
      opacity: 0;
      transform: translateY(20px);
      transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .viska-word.show { opacity:1; transform:translateY(0); }

    /* Stats */
    .stat-pill {
      opacity: 0;
      transform: translateX(-20px);
      transition: opacity 0.5s ease, transform 0.5s ease;
    }
    .stat-pill.show { opacity:1; transform:translateX(0); }

    /* Mobile */
    @media (max-width: 768px) {
      .hero { padding: 1rem; }
      .card-bg { height: 260px; }
      .login-card {
        position: relative;
        top: auto; right: auto;
        transform: none;
        width: 100%;
        border-radius: 0 0 16px 16px;
        box-shadow: none;
        border-top: 1px solid #f0f2f5;
      }
      .card-stats { bottom: 1rem; left: 1rem; }
      .stat-pill { min-width: 140px; }
    }
  </style>
</head>
<body>

<!-- Overlay transisi -->
<div class="login-transition" id="loginTransition"></div>

<!-- NAVBAR -->
<nav class="top-nav">
  <div class="nav-brand">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png" alt="Logo SMK">
    <div class="nav-brand-text">
      <div class="title">SIMONA</div>
      <div class="sub">SMK Negeri 6 Surakarta</div>
    </div>
  </div>
  <div style="font-size:0.8rem;color:#6c757d">
    <a href="https://smkn6solo.sch.id/" target="_blank" rel="noopener"
       class="btn btn-sm btn-outline-secondary rounded-pill px-3">
      <i class="bi bi-globe2 me-1"></i>Selengkapnya
    </a>
  </div>
</nav>

<!-- HERO -->
<div class="hero">

  <!-- Teks VISKA -->
  <div class="viska-section">
    <h1>
      <span class="viska-word yellow">Visioner</span>
      <span class="viska-word">Inovatif</span><br>
      <span class="viska-word yellow">Sinergi</span>
      <span class="viska-word">Kompeten</span>
      <span class="viska-word yellow">Amanah</span>
    </h1>
  </div>

  <!-- Main card: foto + form -->
  <div class="main-card">

    <!-- Foto gedung -->
    <img src="<?= APP_URL ?>/assets/img/gedung-smk.png"
         class="card-bg" alt="Gedung SMK N 6 Surakarta">

    <!-- Overlay -->
    <div class="card-overlay"></div>

    <!-- Stats kiri -->
    <div class="card-stats">
      <div class="stat-pill">
        <span class="num">VISKA</span>
        <span class="lbl">SMK Negeri 6 Surakarta</span>
      </div>
      <div class="stat-pill">
        <span class="num">SIMONA</span>
        <span class="lbl">Sistem Monitoring & Nilai PKL</span>
      </div>
    </div>

    <!-- Form login -->
    <div class="login-card">
      <div class="lc-title">Selamat Datang 👋</div>
      <div class="lc-sub">Masuk ke akun SIMONA kamu</div>

      <?php if ($error): ?>
        <div class="alert alert-danger py-2 mb-3" style="font-size:0.8rem">
          <i class="bi bi-exclamation-circle me-1"></i><?= e($error) ?>
        </div>
      <?php endif; ?>

      <form method="POST" novalidate id="loginForm" data-validate>
        <?= csrf_input() ?>

        <div class="mb-3">
          <label class="form-label">Username</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-person"></i></span>
            <input type="text" name="username" class="form-control"
                   placeholder="Username"
                   value="<?= e($_POST['username'] ?? '') ?>"
                   data-validasi="required"
                   data-label="Username"
                   required autocomplete="username">
          </div>
        </div>

        <div class="mb-2">
          <label class="form-label">Password</label>
          <div class="input-group input-group-sm">
            <span class="input-group-text"><i class="bi bi-lock"></i></span>
            <input type="password" name="password" id="password"
                   class="form-control" placeholder="Password"
                   data-validasi="required|min:6"
                   data-label="Password"
                   required autocomplete="current-password">
            <button class="btn btn-outline-secondary btn-sm" type="button"
                    id="togglePassword" tabindex="-1">
              <i class="bi bi-eye" id="eyeIcon"></i>
            </button>
          </div>
        </div>

        <button type="submit" class="btn-masuk">
          <i class="bi bi-box-arrow-in-right me-1"></i>Masuk
        </button>
      </form>

      <div class="demo-info">
        Demo: <code>admin</code>/<code>admin123</code>
        &bull; <code>siswa1</code>/<code>siswa123</code>
      </div>
    </div>

  </div><!-- /.main-card -->

</div><!-- /.hero -->

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  window.addEventListener('load', function () {
    // Kalau ada error login, tampilkan semua langsung tanpa animasi
    const hasError = <?= !empty($error) ? 'true' : 'false' ?>;

    if (hasError) {
      document.querySelectorAll('.viska-word').forEach(w => w.classList.add('show'));
      document.querySelector('.main-card').classList.add('show');
      document.querySelectorAll('.stat-pill').forEach(p => p.classList.add('show'));
      document.querySelector('.login-card').classList.add('show');
      return;
    }

    // Animasi normal kalau tidak ada error
    // 1. VISKA kata per kata
    const words = document.querySelectorAll('.viska-word');
    words.forEach(function(word, i) {
      setTimeout(function() {
        word.classList.add('show');
      }, 300 + (i * 250));
    });

    // 2. Foto gedung
    const totalViska = 300 + (words.length * 250) + 200;
    setTimeout(function() {
      document.querySelector('.main-card').classList.add('show');
    }, totalViska);

    // 3. Stats kiri
    const pills = document.querySelectorAll('.stat-pill');
    pills.forEach(function(pill, i) {
      setTimeout(function() {
        pill.classList.add('show');
      }, totalViska + 400 + (i * 200));
    });

    // 4. Form login terakhir
    const formDelay = totalViska + 400 + (pills.length * 200) + 300;
    setTimeout(function() {
      document.querySelector('.login-card').classList.add('show');
    }, formDelay);
  });

  // Toggle password
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

  // Transisi smooth saat submit
  document.getElementById('loginForm').addEventListener('submit', function () {
    const overlay = document.getElementById('loginTransition');
    overlay.style.opacity = '1';
    overlay.style.pointerEvents = 'all';
  });
</script>
<script src="<?= APP_URL ?>/assets/js/validasi.js"></script>
</body>
</html>
