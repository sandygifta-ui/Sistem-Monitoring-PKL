<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db = get_db();

// ── Aksi verifikasi / tolak ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();

    $jurnal_id = (int)($_POST['jurnal_id'] ?? 0);
    $aksi      = $_POST['aksi'] ?? '';
    $catatan   = trim($_POST['catatan_admin'] ?? '');

    if ($jurnal_id > 0 && in_array($aksi, ['diverifikasi', 'ditolak'])) {
        $stmt = $db->prepare("UPDATE jurnal_harian SET status_verifikasi = ?, catatan_admin = ? WHERE id = ?");
        $stmt->execute([$aksi, $catatan ?: null, $jurnal_id]);
        $label = $aksi === 'diverifikasi' ? 'diverifikasi' : 'ditolak';
        set_flash('success', "Jurnal berhasil $label.");
    }

    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect(APP_URL . '/admin/jurnal/index.php' . $qs);
}

$cari       = trim($_GET['cari'] ?? '');
$status     = trim($_GET['status'] ?? '');
$tgl_dari   = trim($_GET['tgl_dari'] ?? '');
$tgl_sampai = trim($_GET['tgl_sampai'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

$where  = 'WHERE 1=1';
$params = [];

if ($cari !== '')       { $where .= ' AND u.nama LIKE ?';          $params[] = "%$cari%"; }
if ($status !== '')     { $where .= ' AND j.status_verifikasi = ?'; $params[] = $status; }
if ($tgl_dari !== '')   { $where .= ' AND j.tanggal >= ?';          $params[] = $tgl_dari; }
if ($tgl_sampai !== '') { $where .= ' AND j.tanggal <= ?';          $params[] = $tgl_sampai; }

$stmt = $db->prepare("SELECT COUNT(*) FROM jurnal_harian j JOIN siswa s ON j.siswa_id=s.id JOIN users u ON s.user_id=u.id $where");
$stmt->execute($params);
$total       = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $db->prepare("
    SELECT j.id, j.tanggal, j.kegiatan, j.kendala,
           j.status_verifikasi, j.catatan_admin,
           u.nama AS nama_siswa, s.kelas, s.tempat_pkl
    FROM jurnal_harian j
    JOIN siswa s ON j.siswa_id = s.id
    JOIN users u ON s.user_id  = u.id
    $where
    ORDER BY j.tanggal DESC, j.id DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

$pending = $db->query("SELECT COUNT(*) FROM jurnal_harian WHERE status_verifikasi='menunggu'")->fetchColumn();

$page_title = 'Jurnal Harian';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-3">
        <input type="text" name="cari" class="form-control form-control-sm"
               placeholder="Cari nama siswa..." value="<?= e($cari) ?>">
      </div>
      <div class="col-6 col-md-2">
        <select name="status" class="form-select form-select-sm">
          <option value="">Semua Status</option>
          <option value="menunggu"     <?= $status==='menunggu'     ?'selected':'' ?>>Menunggu</option>
          <option value="diverifikasi" <?= $status==='diverifikasi' ?'selected':'' ?>>Diverifikasi</option>
          <option value="ditolak"      <?= $status==='ditolak'      ?'selected':'' ?>>Ditolak</option>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <input type="date" name="tgl_dari" class="form-control form-control-sm" value="<?= e($tgl_dari) ?>">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" name="tgl_sampai" class="form-control form-control-sm" value="<?= e($tgl_sampai) ?>">
      </div>
      <div class="col-6 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Cari</button>
        <a href="<?= APP_URL ?>/admin/jurnal/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<div class="d-flex justify-content-between align-items-center mb-2">
  <p class="text-muted small mb-0">
    <?= $total ?> jurnal ditemukan
    <?php if ($pending > 0): ?>
      &bull; <span class="text-warning fw-semibold"><?= $pending ?> menunggu verifikasi</span>
    <?php endif; ?>
  </p>
</div>

<!-- Tabel jurnal -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($jurnal_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>Tidak ada jurnal ditemukan.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Siswa</th>
              <th>Tanggal</th>
              <th>Kegiatan</th>
              <th>Status</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jurnal_list as $j): ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($j['nama_siswa']) ?></div>
                <div class="text-muted small"><?= e($j['kelas']) ?> &bull; <?= e($j['tempat_pkl']) ?></div>
              </td>
              <td class="text-nowrap small"><?= format_tanggal($j['tanggal']) ?></td>
              <td>
                <div style="max-width:260px">
                  <div class="text-truncate"><?= e($j['kegiatan']) ?></div>
                  <?php if ($j['kendala']): ?>
                    <div class="text-muted small text-truncate">
                      <i class="bi bi-exclamation-circle me-1"></i><?= e($j['kendala']) ?>
                    </div>
                  <?php endif; ?>
                  <?php if ($j['catatan_admin']): ?>
                    <div class="small text-truncate" style="color:#7C3AED">
                      <i class="bi bi-chat-left-text me-1"></i><?= e($j['catatan_admin']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td><?= badge_status($j['status_verifikasi']) ?></td>
              <td class="text-center">
                <button type="button" class="btn btn-sm btn-outline-primary btn-detail"
                  data-id="<?= $j['id'] ?>"
                  data-nama="<?= e($j['nama_siswa']) ?>"
                  data-tanggal="<?= e(format_tanggal($j['tanggal'])) ?>"
                  data-kegiatan="<?= e($j['kegiatan']) ?>"
                  data-kendala="<?= e($j['kendala'] ?? '') ?>"
                  data-status="<?= e($j['status_verifikasi']) ?>"
                  data-catatan="<?= e($j['catatan_admin'] ?? '') ?>">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <?php if ($total_pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
        <small class="text-muted">Halaman <?= $page ?> dari <?= $total_pages ?></small>
        <?= render_pagination($page, $total_pages) ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<!-- ══ Offcanvas Detail Jurnal (geser dari kanan, TANPA backdrop gelap) ══ -->
<div class="offcanvas offcanvas-end" tabindex="-1" id="offcanvasVerif"
     data-bs-backdrop="false" data-bs-scroll="true" style="width:420px;z-index:1045">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title fw-bold">
      <i class="bi bi-journal-check me-2" style="color:#E11D74"></i>Detail Jurnal
    </h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
  </div>
  <div class="offcanvas-body">

    <div class="row g-2 mb-3">
      <div class="col-6">
        <div class="text-muted small">Siswa</div>
        <div class="fw-semibold" id="mv-nama"></div>
      </div>
      <div class="col-6">
        <div class="text-muted small">Tanggal</div>
        <div class="fw-semibold" id="mv-tanggal"></div>
      </div>
    </div>

    <div class="mb-3">
      <div class="text-muted small mb-1">Kegiatan</div>
      <div class="p-3 bg-light rounded" id="mv-kegiatan" style="white-space:pre-wrap;font-size:0.9rem"></div>
    </div>

    <div class="mb-3" id="mv-kendala-wrap">
      <div class="text-muted small mb-1">Kendala</div>
      <div class="p-3 bg-light rounded" id="mv-kendala" style="white-space:pre-wrap;font-size:0.9rem"></div>
    </div>

    <div class="mb-3">
      <div class="text-muted small mb-1">Status Saat Ini</div>
      <div id="mv-status"></div>
    </div>

    <form method="POST" id="formVerif">
      <?= csrf_input() ?>
      <input type="hidden" name="jurnal_id" id="mv-jurnal-id">
      <input type="hidden" name="aksi"      id="mv-aksi">

      <div class="mb-3">
        <label class="form-label fw-semibold small">
          Catatan untuk Siswa <span class="text-muted fw-normal">(opsional)</span>
        </label>
        <textarea name="catatan_admin" id="mv-catatan" class="form-control" rows="3"
                  placeholder="Tulis catatan atau alasan penolakan..."></textarea>
      </div>

      <div class="d-flex gap-2">
        <button type="button" class="btn btn-outline-secondary flex-grow-1" data-bs-dismiss="offcanvas">
          Tutup
        </button>
        <button type="button" class="btn btn-danger flex-grow-1" id="btnTolak">
          <i class="bi bi-x-circle me-1"></i>Tolak
        </button>
        <button type="button" class="btn btn-success flex-grow-1" id="btnVerif">
          <i class="bi bi-check-circle me-1"></i>Verifikasi
        </button>
      </div>
    </form>
  </div>
</div>

<script>
const offcanvasEl = document.getElementById('offcanvasVerif');
const offcanvas   = new bootstrap.Offcanvas(offcanvasEl);

const statusMap = {
  'menunggu':     '<span class="badge bg-warning text-dark">Menunggu</span>',
  'diverifikasi': '<span class="badge bg-success">Diverifikasi</span>',
  'ditolak':      '<span class="badge bg-danger">Ditolak</span>',
};

// Buka offcanvas saat tombol mata diklik
document.querySelectorAll('.btn-detail').forEach(function(btn) {
  btn.addEventListener('click', function() {
    document.getElementById('mv-nama').textContent    = this.dataset.nama;
    document.getElementById('mv-tanggal').textContent = this.dataset.tanggal;
    document.getElementById('mv-kegiatan').textContent= this.dataset.kegiatan;
    document.getElementById('mv-jurnal-id').value     = this.dataset.id;
    document.getElementById('mv-catatan').value       = this.dataset.catatan;

    const kendala = this.dataset.kendala;
    document.getElementById('mv-kendala-wrap').style.display = kendala ? '' : 'none';
    document.getElementById('mv-kendala').textContent = kendala;
    document.getElementById('mv-status').innerHTML = statusMap[this.dataset.status] || this.dataset.status;

    offcanvas.show();
  });
});

// Verifikasi
document.getElementById('btnVerif').addEventListener('click', function() {
  if (!confirm('Verifikasi jurnal ini?')) return;
  document.getElementById('mv-aksi').value = 'diverifikasi';
  document.getElementById('formVerif').submit();
});

// Tolak
document.getElementById('btnTolak').addEventListener('click', function() {
  if (!confirm('Tolak jurnal ini?')) return;
  document.getElementById('mv-aksi').value = 'ditolak';
  document.getElementById('formVerif').submit();
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
