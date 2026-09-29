<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('siswa');

$db       = get_db();
$user     = current_user();
$siswa_id = $user['siswa_id'];

// Aspek tetap
$aspek_list = ['Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama'];

// Ambil semua nilai siswa ini
$stmt = $db->prepare("
    SELECT n.aspek_penilaian, n.nilai, n.tanggal_input, u.nama AS nama_guru
    FROM nilai n
    JOIN users u ON n.diberikan_oleh = u.id
    WHERE n.siswa_id = ?
");
$stmt->execute([$siswa_id]);
$nilai_rows = $stmt->fetchAll();

// Susun ke dalam map [aspek => data]
$nilai_map = [];
foreach ($nilai_rows as $n) {
    $nilai_map[$n['aspek_penilaian']] = $n;
}

// Hitung rata-rata
$angka_list = array_column($nilai_rows, 'nilai');
$rata       = empty($angka_list) ? null : round(array_sum($angka_list) / count($angka_list), 1);

// Predikat nilai rata-rata
function predikat(float $nilai): array {
    if ($nilai >= 90) return ['A', 'Sangat Baik',  'success'];
    if ($nilai >= 80) return ['B', 'Baik',          'primary'];
    if ($nilai >= 70) return ['C', 'Cukup',         'warning'];
    return                   ['D', 'Perlu Perbaikan','danger'];
}

$page_title = 'Nilai PKL Saya';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (empty($nilai_rows)): ?>
  <div class="card border-0 shadow-sm">
    <div class="card-body text-center py-5 text-muted">
      <i class="bi bi-award fs-2 d-block mb-2"></i>
      Nilai kamu belum diinput oleh guru. Sabar ya!
    </div>
  </div>

<?php else: ?>

<!-- Kartu rata-rata -->
<div class="row g-3 mb-4">
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body py-4">
        <?php [$huruf, $label, $warna] = predikat($rata); ?>
        <div class="display-4 fw-bold text-<?= $warna ?>"><?= $rata ?></div>
        <div class="fs-5 fw-semibold text-<?= $warna ?>"><?= $huruf ?> — <?= $label ?></div>
        <div class="text-muted small mt-1">Nilai Rata-rata</div>
      </div>
    </div>
  </div>

  <!-- Kartu per aspek -->
  <?php foreach ($aspek_list as $aspek):
    $d = $nilai_map[$aspek] ?? null;
  ?>
  <div class="col-6 col-md-2">
    <div class="card border-0 shadow-sm h-100 text-center">
      <div class="card-body py-3">
        <?php if ($d): ?>
          <?php [$h, $l, $w] = predikat((float)$d['nilai']); ?>
          <div class="fs-3 fw-bold text-<?= $w ?>"><?= $d['nilai'] ?></div>
        <?php else: ?>
          <div class="fs-3 fw-bold text-muted">—</div>
        <?php endif; ?>
        <div class="text-muted small"><?= e($aspek) ?></div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabel detail -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-table me-2" style="color:#1e3a5f"></i>Detail Penilaian
    </h6>
  </div>
  <div class="card-body p-0">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Aspek Penilaian</th>
          <th class="text-center">Nilai</th>
          <th class="text-center">Predikat</th>
          <th>Tanggal Input</th>
          <th>Dinilai Oleh</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aspek_list as $aspek):
          $d = $nilai_map[$aspek] ?? null;
        ?>
        <tr>
          <td class="fw-semibold"><?= e($aspek) ?></td>
          <td class="text-center">
            <?php if ($d): ?>
              <!-- Progress bar nilai -->
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px">
                  <?php [$h,$l,$w] = predikat((float)$d['nilai']); ?>
                  <div class="progress-bar bg-<?= $w ?>" style="width:<?= $d['nilai'] ?>%"></div>
                </div>
                <span class="fw-bold" style="min-width:32px"><?= $d['nilai'] ?></span>
              </div>
            <?php else: ?>
              <span class="text-muted">Belum dinilai</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <?php if ($d):
              [$h, $l, $w] = predikat((float)$d['nilai']);
            ?>
              <span class="badge bg-<?= $w ?>"><?= $h ?> — <?= $l ?></span>
            <?php else: ?>
              <span class="text-muted">—</span>
            <?php endif; ?>
          </td>
          <td class="small text-muted">
            <?= $d ? format_tanggal($d['tanggal_input']) : '—' ?>
          </td>
          <td class="small">
            <?= $d ? e($d['nama_guru']) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot class="table-light">
        <tr>
          <td class="fw-bold">Rata-rata</td>
          <td class="text-center fw-bold fs-5"><?= $rata ?></td>
          <td class="text-center">
            <?php [$h, $l, $w] = predikat($rata); ?>
            <span class="badge bg-<?= $w ?>"><?= $h ?> — <?= $l ?></span>
          </td>
          <td colspan="2"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
