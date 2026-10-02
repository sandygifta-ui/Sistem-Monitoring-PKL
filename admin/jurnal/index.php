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
        $stmt = $db->prepare("
            UPDATE jurnal_harian
            SET status_verifikasi = ?, catatan_admin = ?
            WHERE id = ?
        ");
        $stmt->execute([$aksi, $catatan ?: null, $jurnal_id]);

        $label = $aksi === 'diverifikasi' ? 'diverifikasi' : 'ditolak';
        set_flash('success', "Jurnal berhasil $label.");
    }

    // Redirect kembali ke halaman yang sama (dengan filter yang sama)
    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect(APP_URL . '/admin/jurnal/index.php' . $qs);
}

// ── Parameter filter & pagination ──
$cari       = trim($_GET['cari'] ?? '');
$status     = trim($_GET['status'] ?? '');
$tgl_dari   = trim($_GET['tgl_dari'] ?? '');
$tgl_sampai = trim($_GET['tgl_sampai'] ?? '');
$page       = max(1, (int)($_GET['page'] ?? 1));
$per_page   = 10;
$offset     = ($page - 1) * $per_page;

// ── Bangun WHERE ──
$where  = 'WHERE 1=1';
$params = [];

if ($cari !== '') {
    $where   .= ' AND u.nama LIKE ?';
    $params[] = "%$cari%";
}
if ($status !== '') {
    $where   .= ' AND j.status_verifikasi = ?';
    $params[] = $status;
}
if ($tgl_dari !== '') {
    $where   .= ' AND j.tanggal >= ?';
    $params[] = $tgl_dari;
}
if ($tgl_sampai !== '') {
    $where   .= ' AND j.tanggal <= ?';
    $params[] = $tgl_sampai;
}

// Total
$count_sql = "
    SELECT COUNT(*)
    FROM jurnal_harian j
    JOIN siswa s ON j.siswa_id = s.id
    JOIN users u ON s.user_id  = u.id
    $where
";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total       = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// Data jurnal
$sql = "
    SELECT j.id, j.tanggal, j.kegiatan, j.kendala,
           j.status_verifikasi, j.catatan_admin, j.created_at,
           u.nama AS nama_siswa, s.kelas, s.tempat_pkl, s.id AS siswa_id
    FROM jurnal_harian j
    JOIN siswa s ON j.siswa_id = s.id
    JOIN users u ON s.user_id  = u.id
    $where
    ORDER BY j.tanggal DESC, j.created_at DESC
    LIMIT $per_page OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

// Hitung badge pending untuk info
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
               placeholder="Cari nama siswa..."
               value="<?= e($cari) ?>">
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
        <input type="date" name="tgl_dari" class="form-control form-control-sm"
               value="<?= e($tgl_dari) ?>" title="Dari tanggal">
      </div>
      <div class="col-6 col-md-2">
        <input type="date" name="tgl_sampai" class="form-control form-control-sm"
               value="<?= e($tgl_sampai) ?>" title="Sampai tanggal">
      </div>
      <div class="col-6 col-md-3 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-search me-1"></i>Cari
        </button>
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
        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
        Tidak ada jurnal ditemukan.
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
                    <div class="small text-primary text-truncate">
                      <i class="bi bi-chat-left-text me-1"></i><?= e($j['catatan_admin']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td><?= badge_status($j['status_verifikasi']) ?></td>
              <td class="text-center">
                <!-- Tombol Detail & Verifikasi -->
                <button type="button" class="btn btn-sm btn-outline-primary"
                  data-bs-toggle="modal"
                  data-bs-target="#modalVerif"
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

<!-- ══ Modal Verifikasi ══ -->
<div class="modal fade" id="modalVerif" tabindex="-1" aria-labelledby="modalVerifLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title fw-bold" id="modalVerifLabel">
          <i class="bi bi-journal-check me-2"></i>Detail Jurnal
        </h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
      </div>

      <div class="modal-body">
        <!-- Info jurnal -->
        <div class="mb-3">
          <div class="row g-2">
            <div class="col-md-6">
              <div class="text-muted small">Siswa</div>
              <div class="fw-semibold" id="mv-nama"></div>
            </div>
            <div class="col-md-6">
              <div class="text-muted small">Tanggal</div>
              <div class="fw-semibold" id="mv-tanggal"></div>
            </div>
          </div>
        </div>

        <div class="mb-3">
          <div class="text-muted small mb-1">Kegiatan</div>
          <div class="p-3 bg-light rounded" id="mv-kegiatan" style="white-space:pre-wrap"></div>
        </div>

        <div class="mb-3" id="mv-kendala-wrap">
          <div class="text-muted small mb-1">Kendala</div>
          <div class="p-3 bg-light rounded" id="mv-kendala" style="white-space:pre-wrap"></div>
        </div>

        <div class="mb-3">
          <div class="text-muted small mb-1">Status Saat Ini</div>
          <div id="mv-status"></div>
        </div>

        <!-- Form verifikasi -->
        <form method="POST" id="formVerif">
          <?= csrf_input() ?>
          <input type="hidden" name="jurnal_id" id="mv-jurnal-id">
          <input type="hidden" name="aksi" id="mv-aksi">

          <div class="mb-3">
            <label class="form-label fw-semibold">Catatan untuk Siswa <span class="text-muted fw-normal">(opsional)</span></label>
            <textarea name="catatan_admin" id="mv-catatan-input" class="form-control" rows="2"
                      placeholder="Tulis catatan atau alasan penolakan..."></textarea>
          </div>
        </form>
      </div>

      <div class="modal-footer gap-2">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Tutup</button>
        <button type="button" class="btn btn-danger" id="btnTolak">
          <i class="bi bi-x-circle me-1"></i>Tolak
        </button>
        <button type="button" class="btn btn-success" id="btnVerif">
          <i class="bi bi-check-circle me-1"></i>Verifikasi
        </button>
      </div>    </div>
  </div>
</div>

<script>
// Isi modal saat dibuka
document.getElementById('modalVerif').addEventListener('show.bs.modal', function (e) {
  const btn = e.relatedTarget;
  document.getElementById('mv-nama').textContent     = btn.dataset.nama;
  document.getElementById('mv-tanggal').textContent  = btn.dataset.tanggal;
  document.getElementById('mv-kegiatan').textContent = btn.dataset.kegiatan;
  document.getElementById('mv-jurnal-id').value      = btn.dataset.id;

  // Kendala
  const kendala = btn.dataset.kendala;
  document.getElementById('mv-kendala-wrap').style.display = kendala ? '' : 'none';
  document.getElementById('mv-kendala').textContent = kendala;

  // Status badge
  const statusMap = {
    'menunggu':     '<span class="badge bg-warning text-dark">Menunggu</span>',
    'diverifikasi': '<span class="badge bg-success">Diverifikasi</span>',
    'ditolak':      '<span class="badge bg-danger">Ditolak</span>',
  };
  document.getElementById('mv-status').innerHTML = statusMap[btn.dataset.status] || btn.dataset.status;

  // Isi catatan yang sudah ada
  document.getElementById('mv-catatan-input').value = btn.dataset.catatan;
});

// Tombol Verifikasi
document.getElementById('btnVerif').addEventListener('click', function () {
  if (!confirm('Verifikasi jurnal ini?')) return;
  document.getElementById('mv-aksi').value = 'diverifikasi';
  // Tutup modal dulu, baru submit
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalVerif'));
  if (modal) modal.hide();
  setTimeout(function() {
    document.getElementById('formVerif').submit();
  }, 300);
});

// Tombol Tolak
document.getElementById('btnTolak').addEventListener('click', function () {
  if (!confirm('Tolak jurnal ini?')) return;
  document.getElementById('mv-aksi').value = 'ditolak';
  // Tutup modal dulu, baru submit
  const modal = bootstrap.Modal.getInstance(document.getElementById('modalVerif'));
  if (modal) modal.hide();
  setTimeout(function() {
    document.getElementById('formVerif').submit();
  }, 300);
});
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
