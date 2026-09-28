<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db = get_db();
$id = (int)($_GET['id'] ?? 0);

// Ambil data siswa yang akan diedit
$stmt = $db->prepare("
    SELECT s.*, u.nama, u.username, u.id AS uid
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    set_flash('danger', 'Data siswa tidak ditemukan.');
    redirect(APP_URL . '/admin/siswa/index.php');
}

$guru_list = $db->query("SELECT id, nama FROM users WHERE role = 'admin' ORDER BY nama")->fetchAll();
$errors    = [];
$input     = $siswa; // isi form dengan data yang ada

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input = [
        'nama'                       => trim($_POST['nama'] ?? ''),
        'username'                   => trim($_POST['username'] ?? ''),
        'password'                   => $_POST['password'] ?? '',
        'nis'                        => trim($_POST['nis'] ?? ''),
        'kelas'                      => trim($_POST['kelas'] ?? ''),
        'tempat_pkl'                 => trim($_POST['tempat_pkl'] ?? ''),
        'alamat_pkl'                 => trim($_POST['alamat_pkl'] ?? ''),
        'nama_pembimbing_industri'   => trim($_POST['nama_pembimbing_industri'] ?? ''),
        'no_hp_pembimbing_industri'  => trim($_POST['no_hp_pembimbing_industri'] ?? ''),
        'guru_pembimbing_id'         => (int)($_POST['guru_pembimbing_id'] ?? 0),
        'tgl_mulai'                  => trim($_POST['tgl_mulai'] ?? ''),
        'tgl_selesai'                => trim($_POST['tgl_selesai'] ?? ''),
    ];

    // Validasi wajib
    $wajib = ['nama','username','nis','kelas','tempat_pkl','nama_pembimbing_industri','tgl_mulai','tgl_selesai'];
    foreach ($wajib as $f) {
        if (empty($input[$f])) $errors[] = "Field <strong>$f</strong> wajib diisi.";
    }
    if ($input['guru_pembimbing_id'] === 0) $errors[] = 'Guru pembimbing wajib dipilih.';

    if (!empty($input['tgl_mulai']) && !empty($input['tgl_selesai'])) {
        if ($input['tgl_selesai'] < $input['tgl_mulai'])
            $errors[] = 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    }

    // Cek unik username (kecuali milik sendiri)
    if (empty($errors)) {
        $cek = $db->prepare('SELECT id FROM users WHERE username = ? AND id != ?');
        $cek->execute([$input['username'], $siswa['uid']]);
        if ($cek->fetch()) $errors[] = 'Username sudah digunakan akun lain.';

        $cek2 = $db->prepare('SELECT id FROM siswa WHERE nis = ? AND id != ?');
        $cek2->execute([$input['nis'], $id]);
        if ($cek2->fetch()) $errors[] = 'NIS sudah terdaftar untuk siswa lain.';
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            // Update users
            if (!empty($input['password'])) {
                $db->prepare('UPDATE users SET nama=?, username=?, password=? WHERE id=?')
                   ->execute([$input['nama'], $input['username'],
                              password_hash($input['password'], PASSWORD_BCRYPT),
                              $siswa['uid']]);
            } else {
                $db->prepare('UPDATE users SET nama=?, username=? WHERE id=?')
                   ->execute([$input['nama'], $input['username'], $siswa['uid']]);
            }

            // Update siswa
            $db->prepare("
                UPDATE siswa SET
                  nis=?, kelas=?, tempat_pkl=?, alamat_pkl=?,
                  nama_pembimbing_industri=?, no_hp_pembimbing_industri=?,
                  guru_pembimbing_id=?, tgl_mulai=?, tgl_selesai=?
                WHERE id=?
            ")->execute([
                $input['nis'], $input['kelas'],
                $input['tempat_pkl'], $input['alamat_pkl'] ?: null,
                $input['nama_pembimbing_industri'],
                $input['no_hp_pembimbing_industri'] ?: null,
                $input['guru_pembimbing_id'],
                $input['tgl_mulai'], $input['tgl_selesai'], $id,
            ]);

            $db->commit();
            set_flash('success', 'Data siswa <strong>' . e($input['nama']) . '</strong> berhasil diperbarui.');
            redirect(APP_URL . '/admin/siswa/index.php');

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Gagal memperbarui data. Silakan coba lagi.';
        }
    }
}

$page_title = 'Edit Siswa';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-3">
  <a href="<?= APP_URL ?>/admin/siswa/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali
  </a>
</div>

<div class="card border-0 shadow-sm" style="max-width:700px">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-pencil-square me-2" style="color:#1e3a5f"></i>
      Edit Data Siswa: <?= e($siswa['nama']) ?>
    </h6>
  </div>
  <div class="card-body">

    <?php if (!empty($errors)): ?>
      <div class="alert alert-danger">
        <ul class="mb-0 ps-3">
          <?php foreach ($errors as $err): ?>
            <li><?= $err ?></li>
          <?php endforeach; ?>
        </ul>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrf_input() ?>

      <h6 class="fw-bold text-muted small text-uppercase mb-2">Akun Login Siswa</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
          <input type="text" name="nama" class="form-control"
                 value="<?= e($input['nama']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Username <span class="text-danger">*</span></label>
          <input type="text" name="username" class="form-control"
                 value="<?= e($input['username']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Password Baru</label>
          <input type="password" name="password" class="form-control" autocomplete="new-password">
          <div class="form-text">Kosongkan jika tidak ingin mengubah password.</div>
        </div>
        <div class="col-md-3">
          <label class="form-label">NIS <span class="text-danger">*</span></label>
          <input type="text" name="nis" class="form-control"
                 value="<?= e($input['nis']) ?>" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Kelas <span class="text-danger">*</span></label>
          <input type="text" name="kelas" class="form-control"
                 value="<?= e($input['kelas']) ?>" required>
        </div>
      </div>

      <hr>

      <h6 class="fw-bold text-muted small text-uppercase mb-2">Data PKL</h6>
      <div class="row g-3 mb-3">
        <div class="col-md-6">
          <label class="form-label">Tempat PKL <span class="text-danger">*</span></label>
          <input type="text" name="tempat_pkl" class="form-control"
                 value="<?= e($input['tempat_pkl']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Alamat PKL</label>
          <input type="text" name="alamat_pkl" class="form-control"
                 value="<?= e($input['alamat_pkl'] ?? '') ?>">
        </div>
        <div class="col-md-6">
          <label class="form-label">Nama Pembimbing Industri <span class="text-danger">*</span></label>
          <input type="text" name="nama_pembimbing_industri" class="form-control"
                 value="<?= e($input['nama_pembimbing_industri']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">No. HP Pembimbing Industri</label>
          <input type="text" name="no_hp_pembimbing_industri" class="form-control"
                 value="<?= e($input['no_hp_pembimbing_industri'] ?? '') ?>">
        </div>
        <div class="col-md-12">
          <label class="form-label">Guru Pembimbing Sekolah <span class="text-danger">*</span></label>
          <select name="guru_pembimbing_id" class="form-select" required>
            <option value="">-- Pilih Guru --</option>
            <?php foreach ($guru_list as $g): ?>
              <option value="<?= $g['id'] ?>"
                <?= $input['guru_pembimbing_id'] == $g['id'] ? 'selected' : '' ?>>
                <?= e($g['nama']) ?>
              </option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Mulai PKL <span class="text-danger">*</span></label>
          <input type="date" name="tgl_mulai" class="form-control"
                 value="<?= e($input['tgl_mulai']) ?>" required>
        </div>
        <div class="col-md-6">
          <label class="form-label">Tanggal Selesai PKL <span class="text-danger">*</span></label>
          <input type="date" name="tgl_selesai" class="form-control"
                 value="<?= e($input['tgl_selesai']) ?>" required>
        </div>
      </div>

      <div class="d-flex gap-2 mt-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
        </button>
        <a href="<?= APP_URL ?>/admin/siswa/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
