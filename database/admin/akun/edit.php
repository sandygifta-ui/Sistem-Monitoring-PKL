<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db   = get_db();
$id   = (int)($_GET['id'] ?? 0);

$stmt = $db->prepare("SELECT * FROM users WHERE id = ? AND role = 'admin'");
$stmt->execute([$id]);
$akun = $stmt->fetch();

if (!$akun) {
    set_flash('danger', 'Akun tidak ditemukan.');
    redirect(APP_URL . '/admin/akun/index.php');
}

$errors = [];
$input  = $akun;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input = [
        'nama'      => trim($_POST['nama']      ?? ''),
        'password'  => $_POST['password']       ?? '',
        'konfirmasi'=> $_POST['konfirmasi']      ?? '',
    ];

    if (empty($input['nama'])) $errors[] = 'Nama lengkap wajib diisi.';

    if (!empty($input['password'])) {
        if (strlen($input['password']) < 6) $errors[] = 'Password minimal 6 karakter.';
        if ($input['password'] !== $input['konfirmasi']) $errors[] = 'Konfirmasi password tidak cocok.';
    }

    if (empty($errors)) {
        if (!empty($input['password'])) {
            $db->prepare('UPDATE users SET nama=?, password=? WHERE id=?')
               ->execute([$input['nama'], password_hash($input['password'], PASSWORD_BCRYPT), $id]);
        } else {
            $db->prepare('UPDATE users SET nama=? WHERE id=?')
               ->execute([$input['nama'], $id]);
        }
        set_flash('success', 'Akun berhasil diperbarui.');
        redirect(APP_URL . '/admin/akun/index.php');
    }
}

$page_title = 'Edit Akun Guru';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-3">
  <a href="<?= APP_URL ?>/admin/akun/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="card border-0 shadow-sm" style="max-width:480px">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-pencil-square me-2" style="color:#1e3a5f"></i>Edit Akun: <?= e($akun['nama']) ?>
    </h6>
  </div>
  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $e): ?><li><?= $e ?></li><?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrf_input() ?>

      <div class="mb-3">
        <label class="form-label fw-semibold">Username</label>
        <input type="text" class="form-control bg-light" value="<?= e($akun['username']) ?>" disabled>
        <div class="form-text">Username tidak bisa diubah.</div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control"
               value="<?= e($input['nama']) ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password Baru</label>
        <input type="password" name="password" class="form-control"
               placeholder="Kosongkan jika tidak ingin mengubah" autocomplete="new-password">
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Konfirmasi Password Baru</label>
        <input type="password" name="konfirmasi" class="form-control"
               placeholder="Ulangi password baru">
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Simpan Perubahan
        </button>
        <a href="<?= APP_URL ?>/admin/akun/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
