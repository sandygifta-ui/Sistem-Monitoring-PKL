<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$db = get_db();

// ── Statistik kartu ──
$total_siswa   = $db->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
$jurnal_pending= $db->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_verifikasi='menunggu'")->fetchColumn();
$total_jurnal  = $db->query('SELECT COUNT(*) FROM jurnal_harian')->fetchColumn();
$sudah_dinilai = $db->query('SELECT COUNT(DISTINCT siswa_id) FROM nilai')->fetchColumn();

// ── Jurnal terbaru menunggu verifikasi ──
$jurnal_terbaru = $db->query("
    SELECT j.id, j.tanggal, j.kegiatan, j.status_verifikasi,
           u.nama AS nama_siswa, s.kelas, s.tempat_pkl
    FROM jurnal_harian j
    JOIN siswa s ON j.siswa_id = s.id
    JOIN users u ON s.user_id  = u.id
    WHERE j.status_verifikasi = 'menunggu'
    ORDER BY j.tanggal DESC
    LIMIT 5
")->fetchAll();

// ── Data grafik: jurnal per minggu (8 minggu terakhir) ──
$jurnal_per_minggu = $db->query("
    SELECT
        DATE_FORMAT(MIN(tanggal), '%d %b') AS label,
        COUNT(*) AS total,
        SUM(status_verifikasi='diverifikasi') AS diverifikasi,
        SUM(status_verifikasi='menunggu')     AS menunggu,
        SUM(status_verifikasi='ditolak')      AS ditolak
    FROM jurnal_harian
    WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL 8 WEEK)
    GROUP BY YEARWEEK(tanggal, 1)
    ORDER BY YEARWEEK(tanggal, 1)
")->fetchAll();

// ── Data grafik: donut status verifikasi ──
$status_count = $db->query("
    SELECT
        SUM(status_verifikasi='diverifikasi') AS diverifikasi,
        SUM(status_verifikasi='menunggu')     AS menunggu,
        SUM(status_verifikasi='ditolak')      AS ditolak
    FROM jurnal_harian
")->fetch();

// Siapkan data untuk JavaScript
$chart_labels      = json_encode(array_column($jurnal_per_minggu, 'label'));
$chart_diverifikasi= json_encode(array_map('intval', array_column($jurnal_per_minggu, 'diverifikasi')));
$chart_menunggu    = json_encode(array_map('intval', array_column($jurnal_per_minggu, 'menunggu')));
$chart_ditolak     = json_encode(array_map('intval', array_column($jurnal_per_minggu, 'ditolak')));
$donut_data        = json_encode([
    (int)$status_count['diverifikasi'],
    (int)$status_count['menunggu'],
    (int)$status_count['ditolak'],
]);

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu statistik -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 hover-lift">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-accent"><i class="bi bi-people-fill"></i></div>
        <div>
          <div class="fs-3 fw-bold" style="color:#2E0A4F"><?= $total_siswa ?></div>
          <div class="text-muted small">Total Siswa</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 hover-lift">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="icon-soft"><i class="bi bi-hourglass-split text-warning"></i></div>
        <div>
          <div class="fs-3 fw-bold text-warning"><?= $jurnal_pending ?></div>
          <div class="text-muted small">Jurnal Pending</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 hover-lift">
      <div class="card-body d-flex align-items-center gap-3">
        <div style="background:#d1fae5;border-radius:12px;width:46px;height:46px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi bi-journal-check fs-5 text-success"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold text-success"><?= $total_jurnal ?></div>
          <div class="text-muted small">Total Jurnal</div>
        </div>
      </div>
    </div>
  </div>
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100 hover-lift">
      <div class="card-body d-flex align-items-center gap-3">
        <div style="background:#ede9fe;border-radius:12px;width:46px;height:46px;display:flex;align-items:center;justify-content:center;flex-shrink:0">
          <i class="bi bi-award-fill fs-5" style="color:#7C3AED"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold" style="color:#7C3AED"><?= $sudah_dinilai ?></div>
          <div class="text-muted small">Siswa Dinilai</div>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Grafik -->
<div class="row g-3 mb-4">

  <!-- Bar chart: jurnal per minggu -->
  <div class="col-12 col-md-8">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-bar-chart-line me-2" style="color:#E11D74"></i>
          Jurnal per Minggu (8 Minggu Terakhir)
        </h6>
      </div>
      <div class="card-body">
        <canvas id="chartJurnal" height="120"></canvas>
      </div>
    </div>
  </div>

  <!-- Donut chart: status verifikasi -->
  <div class="col-12 col-md-4">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-header bg-white py-3">
        <h6 class="mb-0 fw-bold">
          <i class="bi bi-pie-chart me-2" style="color:#7C3AED"></i>
          Status Verifikasi
        </h6>
      </div>
      <div class="card-body d-flex flex-column align-items-center justify-content-center">
        <canvas id="chartDonut" style="max-width:200px;max-height:200px"></canvas>
        <div class="d-flex gap-3 mt-3 flex-wrap justify-content-center" style="font-size:0.8rem">
          <span><span style="display:inline-block;width:12px;height:12px;background:#10B981;border-radius:2px;margin-right:4px"></span>Diverifikasi</span>
          <span><span style="display:inline-block;width:12px;height:12px;background:#F59E0B;border-radius:2px;margin-right:4px"></span>Menunggu</span>
          <span><span style="display:inline-block;width:12px;height:12px;background:#EF4444;border-radius:2px;margin-right:4px"></span>Ditolak</span>
        </div>
      </div>
    </div>
  </div>
</div>

<!-- Jurnal menunggu verifikasi -->
<div class="card border-0 shadow-sm">
  <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-clock-history me-2 text-warning"></i>
      Jurnal Menunggu Verifikasi
    </h6>
    <a href="<?= APP_URL ?>/admin/jurnal/index.php" class="btn btn-sm btn-outline-primary">
      Lihat Semua <i class="bi bi-arrow-right ms-1"></i>
    </a>
  </div>
  <div class="card-body p-0">
    <?php if (empty($jurnal_terbaru)): ?>
      <div class="text-center py-4 text-muted">
        <i class="bi bi-check-circle fs-3 text-success d-block mb-2"></i>
        Tidak ada jurnal yang menunggu verifikasi.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Siswa</th>
              <th>Kelas</th>
              <th>Tanggal</th>
              <th>Kegiatan</th>
              <th>Status</th>
              <th></th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jurnal_terbaru as $j): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($j['nama_siswa']) ?></div>
                <div class="text-muted small"><?= e($j['tempat_pkl']) ?></div>
              </td>
              <td><span class="badge bg-light text-dark"><?= e($j['kelas']) ?></span></td>
              <td class="text-nowrap"><?= format_tanggal($j['tanggal']) ?></td>
              <td>
                <div style="max-width:220px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">
                  <?= e($j['kegiatan']) ?>
                </div>
              </td>
              <td><?= badge_status($j['status_verifikasi']) ?></td>
              <td>
                <a href="<?= APP_URL ?>/admin/jurnal/index.php?id=<?= $j['id'] ?>"
                   class="btn btn-sm btn-primary">
                  Verifikasi
                </a>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php endif; ?>
  </div>
</div>

<!-- Chart.js CDN -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.3/dist/chart.umd.min.js"></script>
<script>
const accent  = '#E11D74';
const violet  = '#7C3AED';
const success = '#10B981';
const warning = '#F59E0B';
const danger  = '#EF4444';

// ── Bar chart: jurnal per minggu ──
new Chart(document.getElementById('chartJurnal'), {
  type: 'bar',
  data: {
    labels: <?= $chart_labels ?>,
    datasets: [
      {
        label: 'Diverifikasi',
        data: <?= $chart_diverifikasi ?>,
        backgroundColor: success,
        borderRadius: 4,
      },
      {
        label: 'Menunggu',
        data: <?= $chart_menunggu ?>,
        backgroundColor: warning,
        borderRadius: 4,
      },
      {
        label: 'Ditolak',
        data: <?= $chart_ditolak ?>,
        backgroundColor: danger,
        borderRadius: 4,
      },
    ]
  },
  options: {
    responsive: true,
    plugins: {
      legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 11 } } },
    },
    scales: {
      x: { stacked: true, grid: { display: false } },
      y: { stacked: true, beginAtZero: true, ticks: { precision: 0 } }
    }
  }
});

// ── Donut chart: status verifikasi ──
new Chart(document.getElementById('chartDonut'), {
  type: 'doughnut',
  data: {
    labels: ['Diverifikasi', 'Menunggu', 'Ditolak'],
    datasets: [{
      data: <?= $donut_data ?>,
      backgroundColor: [success, warning, danger],
      borderWidth: 2,
      borderColor: '#fff',
    }]
  },
  options: {
    responsive: true,
    cutout: '70%',
    plugins: {
      legend: { display: false },
      tooltip: {
        callbacks: {
          label: ctx => ` ${ctx.label}: ${ctx.raw} jurnal`
        }
      }
    }
  }
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
