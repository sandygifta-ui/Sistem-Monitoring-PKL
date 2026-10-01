<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('siswa');

$db       = get_db();
$user     = current_user();
$siswa_id = $user['siswa_id'];

// Ambil data PKL siswa
$stmt = $db->prepare("
    SELECT s.*, u.nama AS nama_guru
    FROM siswa s
    JOIN users u ON s.guru_pembimbing_id = u.id
    WHERE s.id = ?
");
$stmt->execute([$siswa_id]);
$data_pkl = $stmt->fetch();

// Statistik
$total_jurnal     = $db->prepare('SELECT COUNT(*) FROM jurnal_harian WHERE siswa_id = ?');
$total_jurnal->execute([$siswa_id]);
$total_jurnal = $total_jurnal->fetchColumn();

$diverifikasi = $db->prepare("SELECT COUNT(*) FROM jurnal_harian WHERE siswa_id = ? AND status_verifikasi = 'diverifikasi'");
$diverifikasi->execute([$siswa_id]);
$diverifikasi = $diverifikasi->fetchColumn();

$ditolak = $db->prepare("SELECT COUNT(*) FROM jurnal_harian WHERE siswa_id = ? AND status_verifikasi = 'ditolak'");
$ditolak->execute([$siswa_id]);
$ditolak = $ditolak->fetchColumn();

// Nilai rata-rata
$stmt_nilai = $db->prepare('SELECT nilai FROM nilai WHERE siswa_id = ?');
$stmt_nilai->execute([$siswa_id]);
$semua_nilai = $stmt_nilai->fetchAll(PDO::FETCH_COLUMN);
$rata = empty($semua_nilai) ? null : round(array_sum($semua_nilai) / count($semua_nilai), 1);

// Jurnal terbaru (5)
$stmt_jurnal = $db->prepare("
    SELECT id, tanggal, kegiatan, status_verifikasi, catatan_admin
    FROM jurnal_harian
    WHERE siswa_id = ?
    ORDER BY tanggal DESC
    LIMIT 5
");
$stmt_jurnal->execute([$siswa_id]);
$jurnal_terbaru = $stmt_jurnal->fetchAll();

// Data grafik: jurnal per minggu (6 minggu terakhir)
$stmt_chart = $db->prepare("
    SELECT
        DATE_FORMAT(MIN(tanggal), '%d %b') AS label,
        COUNT(*) AS total,
        SUM(status_verifikasi='diverifikasi') AS diverifikasi,
        SUM(status_verifikasi='menunggu')     AS menunggu,
        SUM(status_verifikasi='ditolak')      AS ditolak
    FROM jurnal_harian
    WHERE siswa_id = ? AND tanggal >= DATE_SUB(CURDATE(), INTERVAL 6 WEEK)
    GROUP BY YEARWEEK(tanggal, 1)
    ORDER BY YEARWEEK(tanggal, 1)
");
$stmt_chart->execute([$siswa_id]);
$chart_data = $stmt_chart->fetchAll();

$c_labels = json_encode(array_column($chart_data, 'label'));
$c_verif  = json_encode(array_map('intval', array_column($chart_data, 'diverifikasi')));
$c_tunggu = json_encode(array_map('intval', array_column($chart_data, 'menunggu')));
$c_tolak  = json_encode(array_map('intval', array_column($chart_data, 'ditolak')));

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Sapaan & info PKL -->
<div class="card border-0 shadow-sm mb-4" style="background:linear-gradient(135deg,#1e3a5f,#2d6a9f);color:white">
  <div class="card-body p-4">
    <h5 class="fw-bold mb-1">Halo, <?= e($user['nama']) ?>! 👋</h5>
    <?php if ($data_pkl): ?>
      <p class="mb-3 opacity-75">Semangat menjalani PKL hari ini.</p>
      <div class="row g-2">
        <div class="col-sm-6">
          <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:0.75rem">
            <div class="small opacity-75 mb-1"><i class="bi bi-building me-1"></i>Tempat PKL</div>
            <div class="fw-semibold"><?= e($data_pkl['tempat_pkl']) ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:0.75rem">
            <div class="small opacity-75 mb-1"><i class="bi bi-person-check me-1"></i>Guru Pembimbing</div>
            <div class="fw-semibold"><?= e($data_pkl['nama_guru']) ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:0.75rem">
            <div class="small opacity-75 mb-1"><i class="bi bi-calendar-range me-1"></i>Periode PKL</div>
            <div class="fw-semibold"><?= format_tanggal($data_pkl['tgl_mulai']) ?> – <?= format_tanggal($data_pkl['tgl_selesai']) ?></div>
          </div>
        </div>
        <div class="col-sm-6">
          <div style="background:rgba(255,255,255,0.1);border-radius:8px;padding:0.75rem">
            <div class="small opacity-75 mb-1"><i class="bi bi-person-workspace me-1"></i>Pembimbing Industri</div>
            <div class="fw-semibold"><?= e($data_pkl['nama_pembimbing_industri']) ?></div>
          </div>
        </div>
      </div>
    <?php else: ?>
      <p class="opacity-75 mb-0">Data PKL kamu belum diisi oleh admin. Hubungi guru pembimbing.</p>
    <?php endif; ?>
  </div>
</div>

<!-- Kartu statistik -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body py-3">
        <div class="fs-2 fw-bold" style="color:#1e3a5f"><?= $total_jurnal ?></div>
        <div class="text-muted small">Total Jurnal</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body py-3">
        <div class="fs-2 fw-bold text-success"><?= $diverifikasi ?></div>
        <div class="text-muted small">Diverifikasi</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body py-3">
        <div class="fs-2 fw-bold text-danger"><?= $ditolak ?></div>
        <div class="text-muted small">Ditolak</div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm text-center h-100">
      <div class="card-body py-3">
        <div class="fs-2 fw-bold" style="color:#7b2d8b">
          <?= $rata !== null ? $rata : '-' ?>
        </div>
        <div class="text-muted small">Nilai Rata-rata</div>
      </div>
    </div>
  </div>
</div>

<!-- Grafik jurnal per minggu -->
<div class="card border-0 shadow-sm mb-4">
  <div class="card-header bg-white py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-bar-chart-line me-2" style="color:#E11D74"></i>
      Jurnal per Minggu (6 Minggu Terakhir)
    </h6>
  </div>
  <div class="card-body">
    <?php if (empty($chart_data)): ?>
      <div class="text-center text-muted py-3 small">Belum ada data jurnal.</div>
    <?php else: ?>
      <canvas id="chartJurnalSiswa" height="100"></canvas>
    <?php endif; ?>
  </div>
</div>

<!-- Jurnal terbaru -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-journal-text me-2" style="color:#1e3a5f"></i>
      Jurnal Terbaru
    </h6>
    <a href="<?= APP_URL ?>/siswa/jurnal/tambah.php" class="btn btn-sm btn-primary">
      <i class="bi bi-plus-lg me-1"></i> Tambah Jurnal
    </a>
  </div>

  <div class="card-body p-0">
    <?php if (empty($jurnal_terbaru)): ?>
      <div class="text-center py-4 text-muted">
        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
        Belum ada jurnal. Yuk mulai catat kegiatan PKL kamu!
      </div>
    <?php else: ?>
      <div class="list-group list-group-flush">
        <?php foreach ($jurnal_terbaru as $j): ?>
          <div class="list-group-item px-3 py-3">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div style="min-width:0">
                <div class="fw-semibold text-truncate"><?= e($j['kegiatan']) ?></div>
                <div class="text-muted small mt-1">
                  <i class="bi bi-calendar3 me-1"></i><?= format_tanggal($j['tanggal']) ?>
                </div>
                <?php if ($j['catatan_admin']): ?>
                  <div class="small mt-1 text-secondary">
                    <i class="bi bi-chat-left-text me-1"></i>
                    <em><?= e($j['catatan_admin']) ?></em>
                  </div>
                <?php endif; ?>
              </div>
              <div class="d-flex flex-column align-items-end gap-1 flex-shrink-0">
                <?= badge_status($j['status_verifikasi']) ?>
                <?php if ($j['status_verifikasi'] === 'menunggu' || $j['status_verifikasi'] === 'ditolak'): ?>
                  <a href="<?= APP_URL ?>/siswa/jurnal/edit.php?id=<?= $j['id'] ?>"
                     class="btn btn-sm btn-outline-secondary py-0 px-2" style="font-size:0.75rem">
                    Edit
                  </a>
                <?php endif; ?>
              </div>
            </div>
          </div>
        <?php endforeach; ?>
      </div>
      <div class="card-footer bg-white text-center">
        <a href="<?= APP_URL ?>/siswa/jurnal/index.php" class="text-decoration-none small">
          Lihat semua jurnal <i class="bi bi-arrow-right"></i>
        </a>
      </div>
    <?php endif; ?>
  </div>
</div>

<?php if (!empty($chart_data)): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
new Chart(document.getElementById('chartJurnalSiswa'), {
  type: 'bar',
  data: {
    labels: <?= $c_labels ?>,
    datasets: [
      { label: 'Diverifikasi', data: <?= $c_verif ?>,  backgroundColor: '#10B981', borderRadius: 4 },
      { label: 'Menunggu',     data: <?= $c_tunggu ?>, backgroundColor: '#F59E0B', borderRadius: 4 },
      { label: 'Ditolak',      data: <?= $c_tolak ?>,  backgroundColor: '#EF4444', borderRadius: 4 },
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } }
    },
    scales: {
      x: { stacked: true, grid: { display: false } },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
