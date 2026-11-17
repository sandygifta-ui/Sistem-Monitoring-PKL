<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db     = get_db();
$errors = [];
$input  = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input = [
        'nama'     => trim($_POST['nama']     ?? ''),
        'username' => trim($_POST['username'] ?? ''),
        'password' => $_POST['password']      ?? '',
        'konfirmasi'=> $_POST['konfirmasi']   ?? '',
    ];

    if (empty($input['nama']))      $errors[] = 'Nama lengkap wajib diisi.';
    if (empty($input['username']))  $errors[] = 'Username wajib diisi.';
    if (empty($input['password']))  $errors[] = 'Password wajib diisi.';
    if (strlen($input['password']) < 6) $errors[] = 'Password minimal 6 karakter.';
    if ($input['password'] !== $input['konfirmasi']) $errors[] = 'Konfirmasi password tidak cocok.';

    if (empty($errors)) {
        $cek = $db->prepare('SELECT id FROM users WHERE username = ?');
        $cek->execute([$input['username']]);
        if ($cek->fetch()) $errors[] = 'Username <strong>' . e($input['username']) . '</strong> sudah digunakan.';
    }

    if (empty($errors)) {
        $db->prepare("INSERT INTO users (nama, username, password, role) VALUES (?, ?, ?, 'admin')")
           ->execute([
               $input['nama'],
               $input['username'],
               password_hash($input['password'], PASSWORD_BCRYPT),
           ]);
        set_flash('success', 'Akun guru <strong>' . e($input['nama']) . '</strong> berhasil ditambahkan.');
        redirect(APP_URL . '/admin/akun/index.php');
    }
}

$page_title = 'Tambah Akun Guru';
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
      <i class="bi bi-person-plus me-2" style="color:#1e3a5f"></i>Tambah Akun Guru
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
        <label class="form-label fw-semibold">Nama Lengkap <span class="text-danger">*</span></label>
        <input type="text" name="nama" class="form-control"
               placeholder="Contoh: Pak Hendra, S.Pd"
               value="<?= e($input['nama'] ?? '') ?>" required>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Username <span class="text-danger">*</span></label>
        <input type="text" name="username" class="form-control"
               placeholder="Contoh: guru2"
               value="<?= e($input['username'] ?? '') ?>" required autocomplete="off">
        <div class="form-text">Digunakan untuk login. Tidak bisa diubah setelah disimpan.</div>
      </div>
      <div class="mb-3">
        <label class="form-label fw-semibold">Password <span class="text-danger">*</span></label>
        <input type="password" name="password" class="form-control"
               placeholder="Minimal 6 karakter" required autocomplete="new-password">
      </div>
      <div class="mb-4">
        <label class="form-label fw-semibold">Konfirmasi Password <span class="text-danger">*</span></label>
        <input type="password" name="konfirmasi" class="form-control"
               placeholder="Ulangi password" required>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i>Simpan
        </button>
        <a href="<?= APP_URL ?>/admin/akun/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
