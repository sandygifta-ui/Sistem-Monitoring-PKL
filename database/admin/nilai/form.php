<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db       = get_db();
$user     = current_user();
$siswa_id = (int)($_GET['siswa_id'] ?? 0);

// Aspek tetap
$aspek_list = ['Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama'];

// Ambil data siswa
$stmt = $db->prepare("
    SELECT s.*, u.nama AS nama_siswa, u.username
    FROM siswa s JOIN users u ON s.user_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    set_flash('danger', 'Data siswa tidak ditemukan.');
    redirect(APP_URL . '/admin/nilai/index.php');
}

// Ambil nilai yang sudah ada
$n_stmt = $db->prepare("SELECT aspek_penilaian, nilai FROM nilai WHERE siswa_id = ?");
$n_stmt->execute([$siswa_id]);
$nilai_existing = [];
foreach ($n_stmt->fetchAll() as $n) {
    $nilai_existing[$n['aspek_penilaian']] = $n['nilai'];
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $input_nilai = $_POST['nilai'] ?? [];
    $today       = date('Y-m-d');

    // Validasi semua aspek harus diisi dan 0-100
    foreach ($aspek_list as $aspek) {
        $val = trim($input_nilai[$aspek] ?? '');
        if ($val === '') {
            $errors[] = "Nilai <strong>$aspek</strong> wajib diisi.";
        } elseif (!is_numeric($val) || (int)$val < 0 || (int)$val > 100) {
            $errors[] = "Nilai <strong>$aspek</strong> harus berupa angka 0–100.";
        }
    }

    if (empty($errors)) {
        try {
            $db->beginTransaction();

            foreach ($aspek_list as $aspek) {
                $val = (int)$input_nilai[$aspek];

                if (isset($nilai_existing[$aspek])) {
                    // UPDATE jika sudah ada
                    $db->prepare("
                        UPDATE nilai SET nilai=?, tanggal_input=?, diberikan_oleh=?
                        WHERE siswa_id=? AND aspek_penilaian=?
                    ")->execute([$val, $today, $user['user_id'], $siswa_id, $aspek]);
                } else {
                    // INSERT jika belum ada
                    $db->prepare("
                        INSERT INTO nilai (siswa_id, aspek_penilaian, nilai, tanggal_input, diberikan_oleh)
                        VALUES (?, ?, ?, ?, ?)
                    ")->execute([$siswa_id, $aspek, $val, $today, $user['user_id']]);
                }
            }

            $db->commit();
            set_flash('success', 'Nilai <strong>' . e($siswa['nama_siswa']) . '</strong> berhasil disimpan.');
            redirect(APP_URL . '/admin/nilai/index.php');

        } catch (Exception $e) {
            $db->rollBack();
            $errors[] = 'Gagal menyimpan nilai. Silakan coba lagi.';
        }
    }
}

$page_title = 'Input Nilai — ' . $siswa['nama_siswa'];
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="mb-3">
  <a href="<?= APP_URL ?>/admin/nilai/index.php" class="btn btn-sm btn-outline-secondary">
    <i class="bi bi-arrow-left me-1"></i> Kembali
  </a>
</div>

<!-- Info siswa -->
<div class="card border-0 shadow-sm mb-3" style="max-width:600px">
  <div class="card-body py-2 px-3">
    <div class="d-flex align-items-center gap-3">
      <div style="width:42px;height:42px;background:#e8f4fd;border-radius:10px;display:flex;align-items:center;justify-content:center">
        <i class="bi bi-person-fill" style="color:#1e3a5f;font-size:1.2rem"></i>
      </div>
      <div>
        <div class="fw-bold"><?= e($siswa['nama_siswa']) ?></div>
        <div class="text-muted small">
          <?= e($siswa['kelas']) ?> &bull; NIS <?= e($siswa['nis']) ?> &bull; <?= e($siswa['tempat_pkl']) ?>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Form nilai -->
<div class="card border-0 shadow-sm" style="max-width:600px">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-award me-2" style="color:#1e3a5f"></i>
      Input Nilai PKL
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

      <?php foreach ($aspek_list as $aspek):
        $existing = $nilai_existing[$aspek] ?? '';
        $posted   = isset($_POST['nilai'][$aspek]) ? (int)$_POST['nilai'][$aspek] : $existing;
      ?>
      <div class="mb-4">
        <label class="form-label fw-semibold">
          <?= e($aspek) ?>
          <span class="text-danger">*</span>
          <span class="text-muted fw-normal small">(0 – 100)</span>
        </label>
        <div class="d-flex align-items-center gap-3">
          <!-- Slider -->
          <input type="range" class="form-range flex-grow-1"
                 min="0" max="100" step="1"
                 value="<?= e($posted !== '' ? $posted : 75) ?>"
                 oninput="document.getElementById('num_<?= $aspek ?>').value=this.value">
          <!-- Input angka -->
          <input type="number" name="nilai[<?= e($aspek) ?>]"
                 id="num_<?= $aspek ?>"
                 class="form-control text-center fw-bold"
                 style="width:72px"
                 min="0" max="100"
                 value="<?= e($posted !== '' ? $posted : 75) ?>"
                 oninput="syncSlider(this, '<?= $aspek ?>')"
                 required>
        </div>
        <!-- Bar visual -->
        <div class="progress mt-1" style="height:4px">
          <div class="progress-bar <?= ($posted !== '' && $posted >= 75) ? 'bg-success' : 'bg-warning' ?>"
               id="bar_<?= $aspek ?>"
               style="width:<?= $posted !== '' ? $posted : 75 ?>%"></div>
        </div>
      </div>
      <?php endforeach; ?>

      <hr>
      <div class="d-flex gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="bi bi-check-lg me-1"></i> Simpan Nilai
        </button>
        <a href="<?= APP_URL ?>/admin/nilai/index.php" class="btn btn-outline-secondary">Batal</a>
      </div>
    </form>
  </div>
</div>

<script>
function syncSlider(input, aspek) {
  let val = parseInt(input.value);
  if (isNaN(val)) val = 0;
  if (val < 0)   val = 0;
  if (val > 100) val = 100;
  input.value = val;

  // Sync slider
  const slider = input.previousElementSibling;
  if (slider && slider.type === 'range') slider.value = val;

  // Update progress bar
  const bar = document.getElementById('bar_' + aspek);
  if (bar) {
    bar.style.width = val + '%';
    bar.className = 'progress-bar ' + (val >= 75 ? 'bg-success' : 'bg-warning');
  }
}

// Sync slider → angka & bar
document.querySelectorAll('input[type=range]').forEach(function(slider) {
  slider.addEventListener('input', function() {
    const val   = this.value;
    const num   = this.nextElementSibling;
    const aspek = num.id.replace('num_', '');
    if (num) num.value = val;
    const bar = document.getElementById('bar_' + aspek);
    if (bar) {
      bar.style.width = val + '%';
      bar.className = 'progress-bar ' + (val >= 75 ? 'bg-success' : 'bg-warning');
    }
  });
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
