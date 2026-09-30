<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/functions.php';

// Kalau sudah login, langsung redirect ke dashboard
if (is_logged_in()) {
    if (current_role() === 'admin') {
        redirect(APP_URL . '/admin/dashboard.php');
    } else {
        redirect(APP_URL . '/siswa/dashboard.php');
    }
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verifikasi CSRF
    csrf_verify();

    // 2. Ambil & bersihkan input
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    // 3. Validasi tidak kosong
    if (empty($username) || empty($password)) {
        $error = 'Username dan password wajib diisi.';
    } else {
        // 4. Cari user di database (prepared statement)
        $db   = get_db();
        $stmt = $db->prepare('SELECT id, nama, username, password, role FROM users WHERE username = ? LIMIT 1');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        // 5. Verifikasi password
        if ($user && password_verify($password, $user['password'])) {
            // Login berhasil — regenerate session ID (cegah session fixation)
            session_regenerate_id(true);

            $_SESSION['user_id']  = $user['id'];
            $_SESSION['nama']     = $user['nama'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['role']     = $user['role'];

            // Kalau siswa, simpan juga siswa_id di session
            if ($user['role'] === 'siswa') {
                $s = $db->prepare('SELECT id FROM siswa WHERE user_id = ? LIMIT 1');
                $s->execute([$user['id']]);
                $siswa = $s->fetch();
                $_SESSION['siswa_id'] = $siswa ? $siswa['id'] : null;
            }

            // Redirect ke dashboard sesuai role
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

  <!-- Bootstrap 5 CDN -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Bootstrap Icons -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">

  <style>
    body {
      background: linear-gradient(135deg, #1e3a5f 0%, #1a2a45 100%);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }

    .login-card {
      width: 100%;
      max-width: 420px;
      border: none;
      border-radius: 16px;
      box-shadow: 0 8px 32px rgba(0,0,0,0.35);
    }

    .login-header {
      background: linear-gradient(135deg, #F5C400 0%, #e6b800 100%);
      border-radius: 16px 16px 0 0;
      padding: 2rem;
      text-align: center;
      color: #1e3a5f;
    }

    .login-header h4 { color: #1e3a5f; font-weight: 800; }
    .login-header p  { color: rgba(30,58,95,0.75); }

    .login-body { padding: 2rem; }

    .form-control:focus {
      border-color: #F5C400;
      box-shadow: 0 0 0 0.2rem rgba(245,196,0,0.25);
    }

    .btn-login {
      background: linear-gradient(135deg, #F5C400, #e6b800);
      border: none;
      color: #1e3a5f;
      padding: 0.65rem;
      font-weight: 700;
      letter-spacing: 0.5px;
      transition: opacity 0.2s;
    }

    .btn-login:hover { opacity: 0.9; color: #1e3a5f; }

    .input-group-text {
      background: #f8f9fa;
      border-right: none;
      color: #F5C400;
    }

    .form-control { border-left: none; }
    .form-control:not(:focus) { border-left: none; }
  </style>
</head>
<body>

<div class="login-card card mx-3">
  <!-- Header -->
  <div class="login-header">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png"
         alt="Logo SMK N 6 Surakarta"
         style="width:72px;height:72px;object-fit:contain;margin-bottom:0.75rem">
    <h4 class="mb-0 fw-bold">SIMONA</h4>
    <p class="mb-1 opacity-75 small fw-semibold">SMK Negeri 6 Surakarta</p>
    <p class="mb-0 opacity-60 small">Sistem Monitoring dan Nilai PKL</p>
  </div>

  <!-- Body -->
  <div class="login-body">
    <?php if ($error): ?>
      <div class="alert alert-danger alert-dismissible fade show py-2" role="alert">
        <i class="bi bi-exclamation-circle me-1"></i> <?= e($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
      </div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <?= csrf_input() ?>

      <!-- Username -->
      <div class="mb-3">
        <label for="username" class="form-label fw-semibold">Username</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-person"></i></span>
          <input
            type="text"
            class="form-control"
            id="username"
            name="username"
            placeholder="Masukkan username"
            value="<?= e($_POST['username'] ?? '') ?>"
            required
            autocomplete="username"
          >
        </div>
      </div>

      <!-- Password -->
      <div class="mb-4">
        <label for="password" class="form-label fw-semibold">Password</label>
        <div class="input-group">
          <span class="input-group-text"><i class="bi bi-lock"></i></span>
          <input
            type="password"
            class="form-control"
            id="password"
            name="password"
            placeholder="Masukkan password"
            required
            autocomplete="current-password"
          >
          <button class="btn btn-outline-secondary" type="button" id="togglePassword" tabindex="-1">
            <i class="bi bi-eye" id="eyeIcon"></i>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-login w-100 rounded-3">
        <i class="bi bi-box-arrow-in-right me-1"></i> Masuk
      </button>
    </form>

    <hr class="my-3">
    <p class="text-center text-muted small mb-0">
      <i class="bi bi-info-circle me-1"></i>
      Akun demo: <strong>admin</strong> / <strong>admin123</strong>
    </p>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
  // Toggle show/hide password
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
</script>
</body>
</html>
