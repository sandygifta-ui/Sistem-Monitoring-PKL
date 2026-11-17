<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('siswa');

$db       = get_db();
$user     = current_user();
$siswa_id = $user['siswa_id'];

$aspek_list = ['Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama'];

$stmt = $db->prepare("
    SELECT n.aspek_penilaian, n.nilai, n.tanggal_input, u.nama AS nama_guru
    FROM nilai n
    JOIN users u ON n.diberikan_oleh = u.id
    WHERE n.siswa_id = ?
");
$stmt->execute([$siswa_id]);
$nilai_rows = $stmt->fetchAll();

$nilai_map  = [];
foreach ($nilai_rows as $n) {
    $nilai_map[$n['aspek_penilaian']] = $n;
}

$angka_list = array_column($nilai_rows, 'nilai');
$rata       = empty($angka_list) ? null : round(array_sum($angka_list) / count($angka_list), 1);

function predikat(float $nilai): array {
    if ($nilai >= 90) return ['A', 'Sangat Baik',   '#10B981', '#d1fae5'];
    if ($nilai >= 80) return ['B', 'Baik',           '#7C3AED', '#ede9fe'];
    if ($nilai >= 70) return ['C', 'Cukup',          '#F59E0B', '#fef3c7'];
    return                   ['D', 'Perlu Perbaikan','#EF4444', '#fee2e2'];
}

$page_title = 'Nilai PKL Saya';
require_once __DIR__ . '/../../includes/header.php';
?>

<?php if (empty($nilai_rows)): ?>
<!-- Empty state -->
<div class="card border-0 shadow-sm">
  <div class="card-body text-center py-5">
    <div style="width:80px;height:80px;background:#f3e8ff;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 1rem">
      <i class="bi bi-award" style="font-size:2rem;color:#7C3AED"></i>
    </div>
    <h5 class="fw-bold mb-1" style="color:#2E0A4F">Nilai belum tersedia</h5>
    <p class="text-muted mb-0">Nilai kamu belum diinput oleh guru. Sabar ya! 😊</p>
  </div>
</div>

<?php else: ?>

<!-- Header nilai rata-rata -->
<?php [$huruf, $label, $warna, $bg] = predikat($rata); ?>
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#2E0A4F,#7C3AED);overflow:hidden;position:relative">
  <div class="card-body p-4 text-white">
    <div class="row align-items-center">
      <div class="col-auto">
        <div style="width:90px;height:90px;background:rgba(255,255,255,0.15);border-radius:50%;display:flex;align-items:center;justify-content:center;font-size:2.5rem;font-weight:800;color:white">
          <?= $huruf ?>
        </div>
      </div>
      <div class="col">
        <div style="font-size:0.85rem;opacity:0.75">Nilai Rata-rata PKL</div>
        <div style="font-size:3rem;font-weight:800;line-height:1"><?= $rata ?></div>
        <div style="font-size:1rem;opacity:0.9"><?= $label ?></div>
      </div>
      <div class="col-auto d-none d-md-block">
        <i class="bi bi-award-fill" style="font-size:5rem;opacity:0.1;position:absolute;right:1.5rem;top:50%;transform:translateY(-50%)"></i>
      </div>
    </div>
  </div>
</div>

<!-- Kartu per aspek -->
<div class="row g-3 mb-4">
  <?php foreach ($aspek_list as $aspek):
    $d = $nilai_map[$aspek] ?? null;
    $pct = $d ? (int)$d['nilai'] : 0;
    [$h, $l, $w, $bg] = $d ? predikat((float)$d['nilai']) : ['—', 'Belum dinilai', '#9ca3af', '#f3f4f6'];
  ?>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100" style="border-top:3px solid <?= $w ?> !important">
      <div class="card-body text-center py-3">
        <!-- Lingkaran nilai -->
        <div style="position:relative;width:80px;height:80px;margin:0 auto 0.75rem">
          <svg width="80" height="80" style="transform:rotate(-90deg)">
            <circle cx="40" cy="40" r="34" fill="none" stroke="#f0f0f0" stroke-width="8"/>
            <circle cx="40" cy="40" r="34" fill="none"
                    stroke="<?= $w ?>" stroke-width="8"
                    stroke-dasharray="<?= round(2 * 3.14159 * 34 * $pct / 100, 1) ?> 999"
                    stroke-linecap="round"/>
          </svg>
          <div style="position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);font-size:1.1rem;font-weight:800;color:<?= $w ?>">
            <?= $d ? $d['nilai'] : '—' ?>
          </div>
        </div>
        <div class="fw-semibold small" style="color:#2E0A4F"><?= e($aspek) ?></div>
        <div class="mt-1">
          <span class="badge rounded-pill" style="background:<?= $bg ?>;color:<?= $w ?>;font-size:0.7rem">
            <?= $d ? "$h — $l" : 'Belum dinilai' ?>
          </span>
        </div>
      </div>
    </div>
  </div>
  <?php endforeach; ?>
</div>

<!-- Tabel detail -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-list-check me-2" style="color:#E11D74"></i>Detail Penilaian
    </h6>
  </div>
  <div class="card-body p-0">
    <table class="table align-middle mb-0">
      <thead class="table-light">
        <tr>
          <th>Aspek</th>
          <th>Nilai</th>
          <th class="text-center">Predikat</th>
          <th class="d-none d-md-table-cell">Tanggal</th>
          <th class="d-none d-md-table-cell">Guru</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($aspek_list as $aspek):
          $d = $nilai_map[$aspek] ?? null;
          [$h, $l, $w, $bg] = $d ? predikat((float)$d['nilai']) : ['—','Belum dinilai','#9ca3af','#f3f4f6'];
        ?>
        <tr>
          <td class="fw-semibold"><?= e($aspek) ?></td>
          <td style="min-width:160px">
            <?php if ($d): ?>
              <div class="d-flex align-items-center gap-2">
                <div class="progress flex-grow-1" style="height:8px;border-radius:4px">
                  <div class="progress-bar" style="width:<?= $d['nilai'] ?>%;background:<?= $w ?>;border-radius:4px"></div>
                </div>
                <span class="fw-bold small" style="min-width:28px;color:<?= $w ?>"><?= $d['nilai'] ?></span>
              </div>
            <?php else: ?>
              <span class="text-muted small">Belum dinilai</span>
            <?php endif; ?>
          </td>
          <td class="text-center">
            <span class="badge rounded-pill small" style="background:<?= $bg ?>;color:<?= $w ?>">
              <?= $d ? "$h — $l" : '—' ?>
            </span>
          </td>
          <td class="small text-muted d-none d-md-table-cell">
            <?= $d ? format_tanggal($d['tanggal_input']) : '—' ?>
          </td>
          <td class="small d-none d-md-table-cell">
            <?= $d ? e($d['nama_guru']) : '—' ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
      <tfoot>
        <tr style="background:#faf5ff">
          <td class="fw-bold" style="color:#2E0A4F">Rata-rata</td>
          <td>
            <div class="d-flex align-items-center gap-2">
              <div class="progress flex-grow-1" style="height:8px;border-radius:4px">
                <div class="progress-bar" style="width:<?= $rata ?>%;background:<?= $warna ?>;border-radius:4px"></div>
              </div>
              <span class="fw-bold small" style="color:<?= $warna ?>"><?= $rata ?></span>
            </div>
          </td>
          <td class="text-center">
            <span class="badge rounded-pill" style="background:<?= $bg ?>;color:<?= $warna ?>">
              <?= $huruf ?> — <?= $label ?>
            </span>
          </td>
          <td colspan="2" class="d-none d-md-table-cell"></td>
        </tr>
      </tfoot>
    </table>
  </div>
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
