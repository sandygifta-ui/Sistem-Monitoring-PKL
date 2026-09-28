<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

require_role('admin');

$db = get_db();

// ── Statistik kartu ──
$total_siswa     = $db->query('SELECT COUNT(*) FROM siswa')->fetchColumn();
$jurnal_pending  = $db->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_verifikasi = 'menunggu'")->fetchColumn();
$total_jurnal    = $db->query('SELECT COUNT(*) FROM jurnal_harian')->fetchColumn();
$sudah_dinilai   = $db->query('SELECT COUNT(DISTINCT siswa_id) FROM nilai')->fetchColumn();

// ── Jurnal terbaru menunggu verifikasi (5 data) ──
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

$page_title = 'Dashboard';
require_once __DIR__ . '/../includes/header.php';
?>

<!-- Kartu statistik -->
<div class="row g-3 mb-4">
  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 p-3" style="background:#e8f4fd">
          <i class="bi bi-people-fill fs-4" style="color:#1e3a5f"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold" style="color:#1e3a5f"><?= $total_siswa ?></div>
          <div class="text-muted small">Total Siswa</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 p-3" style="background:#fff3cd">
          <i class="bi bi-hourglass-split fs-4 text-warning"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold text-warning"><?= $jurnal_pending ?></div>
          <div class="text-muted small">Jurnal Pending</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 p-3" style="background:#d1f2eb">
          <i class="bi bi-journal-check fs-4 text-success"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold text-success"><?= $total_jurnal ?></div>
          <div class="text-muted small">Total Jurnal</div>
        </div>
      </div>
    </div>
  </div>

  <div class="col-6 col-md-3">
    <div class="card border-0 shadow-sm h-100">
      <div class="card-body d-flex align-items-center gap-3">
        <div class="rounded-3 p-3" style="background:#fce8ff">
          <i class="bi bi-award-fill fs-4" style="color:#7b2d8b"></i>
        </div>
        <div>
          <div class="fs-3 fw-bold" style="color:#7b2d8b"><?= $sudah_dinilai ?></div>
          <div class="text-muted small">Siswa Dinilai</div>
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

<?php require_once __DIR__ . '/../includes/footer.php'; ?>
