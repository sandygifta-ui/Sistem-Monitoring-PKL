<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/includes/db.php';
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/csrf.php';

require_login();

$db   = get_db();
$user = current_user();

$errors  = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $password_lama  = $_POST['password_lama']  ?? '';
    $password_baru  = $_POST['password_baru']  ?? '';
    $konfirmasi     = $_POST['konfirmasi']      ?? '';

    // Ambil hash password saat ini
    $stmt = $db->prepare('SELECT password FROM users WHERE id = ?');
    $stmt->execute([$user['user_id']]);
    $hash_sekarang = $stmt->fetchColumn();

    if (empty($password_lama))  $errors[] = 'Password lama wajib diisi.';
    if (empty($password_baru))  $errors[] = 'Password baru wajib diisi.';
    if (strlen($password_baru) < 6) $errors[] = 'Password baru minimal 6 karakter.';
    if ($password_baru !== $konfirmasi) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors) && !password_verify($password_lama, $hash_sekarang)) {
        $errors[] = 'Password lama tidak sesuai.';
    }

    if (empty($errors)) {
        $db->prepare('UPDATE users SET password = ? WHERE id = ?')
           ->execute([password_hash($password_baru, PASSWORD_BCRYPT), $user['user_id']]);
        set_flash('success', 'Password berhasil diubah. Silakan login ulang.');
        redirect(APP_URL . '/auth/logout.php');
    }
}

$page_title = 'Profil Saya';
require_once __DIR__ . '/includes/header.php';
?>

<div class="row g-4" style="max-width:800px">

  <!-- Info akun -->
  <div class="col-md-5">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body text-center py-4">
        <div style="width:90px;height:90px;margin:0 auto 1rem">
          <img src="<?= APP_URL ?>/assets/img/logo-smk.png"
               alt="Logo SMK N 6 Surakarta"
               style="width:100%;height:100%;object-fit:contain">
        </div>
        <h5 class="fw-bold mb-1"><?= e($user['nama']) ?></h5>
        <p class="text-muted mb-1 small">SMK Negeri 6 Surakarta</p>
        <p class="text-muted mb-2">@<?= e($user['username']) ?></p>
        <span class="badge <?= $user['role']==='admin' ? 'bg-primary' : 'bg-success' ?>">
          <?= $user['role'] === 'admin' ? 'Admin / Guru' : 'Siswa' ?>
        </span>
      </div>
    </div>
  </div>

  <!-- Ganti password -->
  <div class="col-md-7">
    <div class="card border-0 shadow-sm">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-shield-lock me-2" style="color:#1e3a5f"></i>Ganti Password
        </h6>
      </div>
      <div class="card-body">

        <?php if (!empty($errors)): ?>
          <div class="alert alert-danger">
            <ul class="mb-0 ps-3">
              <?php foreach ($errors as $e): ?><li><?= e($e) ?></li><?php endforeach; ?>
            </ul>
          </div>
        <?php endif; ?>

        <form method="POST" novalidate>
          <?= csrf_input() ?>

          <div class="mb-3">
            <label class="form-label fw-semibold">Password Lama <span class="text-danger">*</span></label>
            <input type="password" name="password_lama" class="form-control" required>
          </div>
          <div class="mb-3">
            <label class="form-label fw-semibold">Password Baru <span class="text-danger">*</span></label>
            <input type="password" name="password_baru" class="form-control"
                   placeholder="Minimal 6 karakter" required>
          </div>
          <div class="mb-4">
            <label class="form-label fw-semibold">Konfirmasi Password Baru <span class="text-danger">*</span></label>
            <input type="password" name="konfirmasi" class="form-control" required>
          </div>

          <button type="submit" class="btn btn-primary">
            <i class="bi bi-check-lg me-1"></i>Simpan Password Baru
          </button>
        </form>
      </div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
