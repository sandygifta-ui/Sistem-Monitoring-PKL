<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('siswa');

$db       = get_db();
$user     = current_user();
$siswa_id = $user['siswa_id'];

$errors = [];
$input  = ['tanggal' => date('Y-m-d'), 'kegiatan' => '', 'kendala' => ''];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input = [
        'tanggal'  => trim($_POST['tanggal']  ?? ''),
        'kegiatan' => trim($_POST['kegiatan'] ?? ''),
        'kendala'  => trim($_POST['kendala']  ?? ''),
    ];

    // Validasi
    if (empty($input['tanggal']))  $errors[] = 'Tanggal wajib diisi.';
    if (empty($input['kegiatan'])) $errors[] = 'Kegiatan wajib diisi.';

    // Cek duplikat tanggal (1 jurnal per siswa per hari)
    if (empty($errors)) {
        $cek = $db->prepare("
            SELECT id FROM jurnal_harian
            WHERE siswa_id = ? AND tanggal = ?
        ");
        $cek->execute([$siswa_id, $input['tanggal']]);
        if ($cek->fetch()) {
            $errors[] = 'Kamu sudah punya jurnal untuk tanggal <strong>'
                      . e(format_tanggal($input['tanggal']))
                      . '</strong>. Satu hari hanya boleh satu jurnal.';
        }
    }

    if (empty($errors)) {
        $stmt = $db->prepare("
            INSERT INTO jurnal_harian (siswa_id, tanggal, kegiatan, kendala)
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $siswa_id,
            $input['tanggal'],
            $input['kegiatan'],
            $input['kendala'] ?: null,
        ]);
        set_flash('success', 'Jurnal tanggal <strong>' . e(format_tanggal($input['tanggal'])) . '</strong> berhasil disimpan.');
        redirect(APP_URL . '/siswa/jurnal/index.php');
    }
}

$page_title = 'Tambah Jurnal';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-3">
  <a href="<?= APP_URL ?>/siswa/jurnal/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<div class="card border-0 shadow-sm" style="max-width:640px">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-journal-plus me-2" style="color:#1e3a5f"></i>Tambah Jurnal Harian
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

      <div class="mb-3">
        <label class="form-label fw-semibold">Tanggal <span class="text-danger">*</span></label>
        <input type="date" name="tanggal" class="form-control"
               value="<?= e($input['tanggal']) ?>"
               max="<?= date('Y-m-d') ?>"
               required>
        <div class="form-text">Tidak bisa memilih tanggal yang akan datang.</div>
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">
          Kegiatan <span class="text-danger">*</span>
        </label>
        <textarea name="kegiatan" class="form-control" rows="5"
                  placeholder="Ceritakan kegiatan yang kamu lakukan hari ini..."
                  required><?= e($input['kegiatan']) ?></textarea>
        <div class="form-text">Tulis sejelas mungkin agar mudah diverifikasi guru.</div>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          Kendala
          <span class="text-muted fw-normal">(opsional)</span>
        </label>
        <textarea name="kendala" class="form-control" rows="3"
                  placeholder="Tulis kendala yang dihadapi, jika ada..."><?= e($input['kendala']) ?></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-1"></i>Kirim Jurnal
        </button>
        <a href="<?= APP_URL ?>/siswa/jurnal/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
