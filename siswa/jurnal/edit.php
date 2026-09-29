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
$id       = (int)($_GET['id'] ?? 0);

// Ambil jurnal — pastikan milik siswa ini
$stmt = $db->prepare("
    SELECT * FROM jurnal_harian
    WHERE id = ? AND siswa_id = ?
");
$stmt->execute([$id, $siswa_id]);
$jurnal = $stmt->fetch();

if (!$jurnal) {
    set_flash('danger', 'Jurnal tidak ditemukan.');
    redirect(APP_URL . '/siswa/jurnal/index.php');
}

// Jurnal yang sudah diverifikasi tidak bisa diedit
if ($jurnal['status_verifikasi'] === 'diverifikasi') {
    set_flash('danger', 'Jurnal yang sudah diverifikasi tidak bisa diedit.');
    redirect(APP_URL . '/siswa/jurnal/index.php');
}

$errors = [];
$input  = $jurnal; // isi form dengan data existing

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input = [
        'tanggal'  => trim($_POST['tanggal']  ?? ''),
        'kegiatan' => trim($_POST['kegiatan'] ?? ''),
        'kendala'  => trim($_POST['kendala']  ?? ''),
    ];

    if (empty($input['tanggal']))  $errors[] = 'Tanggal wajib diisi.';
    if (empty($input['kegiatan'])) $errors[] = 'Kegiatan wajib diisi.';

    // Cek duplikat tanggal (kecuali jurnal ini sendiri)
    if (empty($errors)) {
        $cek = $db->prepare("
            SELECT id FROM jurnal_harian
            WHERE siswa_id = ? AND tanggal = ? AND id != ?
        ");
        $cek->execute([$siswa_id, $input['tanggal'], $id]);
        if ($cek->fetch()) {
            $errors[] = 'Kamu sudah punya jurnal untuk tanggal <strong>'
                      . e(format_tanggal($input['tanggal'])) . '</strong>.';
        }
    }

    if (empty($errors)) {
        // Saat diedit, status kembali ke "menunggu" supaya guru bisa verifikasi ulang
        $db->prepare("
            UPDATE jurnal_harian
            SET tanggal=?, kegiatan=?, kendala=?, status_verifikasi='menunggu', catatan_admin=NULL
            WHERE id=? AND siswa_id=?
        ")->execute([
            $input['tanggal'],
            $input['kegiatan'],
            $input['kendala'] ?: null,
            $id, $siswa_id,
        ]);

        set_flash('success', 'Jurnal berhasil diperbarui dan dikirim ulang untuk verifikasi.');
        redirect(APP_URL . '/siswa/jurnal/index.php');
    }
}

$page_title = 'Edit Jurnal';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-3">
  <a href="<?= APP_URL ?>/siswa/jurnal/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i>Kembali
  </a>
</div>

<?php if ($jurnal['status_verifikasi'] === 'ditolak'): ?>
  <div class="alert alert-warning d-flex gap-2 align-items-start" style="max-width:640px">
    <i class="bi bi-exclamation-triangle-fill mt-1 flex-shrink-0"></i>
    <div>
      <strong>Jurnal ini ditolak.</strong>
      <?php if ($jurnal['catatan_admin']): ?>
        Catatan guru: <em>"<?= e($jurnal['catatan_admin']) ?>"</em>
      <?php endif; ?>
      <br>Perbaiki dan kirim ulang.
    </div>
  </div>
<?php endif; ?>

<div class="card border-0 shadow-sm" style="max-width:640px">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-pencil-square me-2" style="color:#1e3a5f"></i>
      Edit Jurnal — <?= format_tanggal($jurnal['tanggal']) ?>
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
      </div>

      <div class="mb-3">
        <label class="form-label fw-semibold">Kegiatan <span class="text-danger">*</span></label>
        <textarea name="kegiatan" class="form-control" rows="5"
                  required><?= e($input['kegiatan']) ?></textarea>
      </div>

      <div class="mb-4">
        <label class="form-label fw-semibold">
          Kendala <span class="text-muted fw-normal">(opsional)</span>
        </label>
        <textarea name="kendala" class="form-control" rows="3"><?= e($input['kendala'] ?? '') ?></textarea>
      </div>

      <div class="alert alert-info py-2 small">
        <i class="bi bi-info-circle me-1"></i>
        Setelah diedit, jurnal akan kembali ke status <strong>Menunggu</strong> dan perlu diverifikasi ulang oleh guru.
      </div>

      <div class="d-flex gap-2 mt-3">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-send me-1"></i>Simpan & Kirim Ulang
        </button>
        <a href="<?= APP_URL ?>/siswa/jurnal/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
