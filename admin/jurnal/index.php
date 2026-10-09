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
        set_flash('success', "Jurnal berhasil " . ($aksi === 'diverifikasi' ? 'diverifikasi' : 'ditolak') . ".");
    }

    $qs = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
    redirect(APP_URL . '/admin/jurnal/index.php' . $qs);
}

// ── Filter & pagination ──
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

<p class="text-muted small mb-2">
  <?= $total ?> jurnal ditemukan
  <?php if ($pending > 0): ?>
    &bull; <span class="text-warning fw-semibold"><?= $pending ?> menunggu verifikasi</span>
  <?php endif; ?>
</p>

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

            <!-- Baris utama -->
            <tr>
              <td>
                <div class="fw-semibold"><?= e($j['nama_siswa']) ?></div>
                <div class="text-muted small"><?= e($j['kelas']) ?> &bull; <?= e($j['tempat_pkl']) ?></div>
              </td>
              <td class="text-nowrap small"><?= format_tanggal($j['tanggal']) ?></td>
              <td>
                <div style="max-width:260px">
                  <div class="text-truncate"><?= e($j['kegiatan']) ?></div>
                  <?php if ($j['catatan_admin']): ?>
                    <div class="small text-truncate" style="color:#7C3AED">
                      <i class="bi bi-chat-left-text me-1"></i><?= e($j['catatan_admin']) ?>
                    </div>
                  <?php endif; ?>
                </div>
              </td>
              <td><?= badge_status($j['status_verifikasi']) ?></td>
              <td class="text-center">
                <button type="button"
                        class="btn btn-sm btn-outline-primary"
                        onclick="toggleDetail(<?= $j['id'] ?>)">
                  <i class="bi bi-eye"></i>
                </button>
              </td>
            </tr>

            <!-- Baris detail (tersembunyi, expand saat klik) -->
            <tr id="detail-<?= $j['id'] ?>" style="display:none;background:#faf5ff">
              <td colspan="5" class="p-3">
                <div class="row g-3">
                  <div class="col-md-7">
                    <div class="mb-2">
                      <div class="text-muted small fw-semibold mb-1">KEGIATAN</div>
                      <div class="p-2 bg-white rounded border" style="white-space:pre-wrap"><?= e($j['kegiatan']) ?></div>
                    </div>
                    <?php if ($j['kendala']): ?>
                    <div>
                      <div class="text-muted small fw-semibold mb-1">KENDALA</div>
                      <div class="p-2 bg-white rounded border" style="white-space:pre-wrap"><?= e($j['kendala']) ?></div>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="col-md-5">
                    <form method="POST">
                      <?= csrf_input() ?>
                      <input type="hidden" name="jurnal_id" value="<?= $j['id'] ?>">
                      <input type="hidden" name="aksi" id="aksi-<?= $j['id'] ?>">

                      <div class="mb-2">
                        <div class="text-muted small fw-semibold mb-1">STATUS SAAT INI</div>
                        <?= badge_status($j['status_verifikasi']) ?>
                      </div>

                      <div class="mb-3">
                        <label class="form-label small fw-semibold">Catatan untuk Siswa <span class="text-muted fw-normal">(opsional)</span></label>
                        <textarea name="catatan_admin" class="form-control form-control-sm" rows="3"
                                  placeholder="Tulis catatan..."><?= e($j['catatan_admin'] ?? '') ?></textarea>
                      </div>

                      <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-danger btn-sm flex-grow-1"
                                onclick="document.getElementById('aksi-<?= $j['id'] ?>').value='ditolak'">
                          <i class="bi bi-x-circle me-1"></i>Tolak
                        </button>
                        <button type="submit" class="btn btn-success btn-sm flex-grow-1"
                                onclick="document.getElementById('aksi-<?= $j['id'] ?>').value='diverifikasi'">
                          <i class="bi bi-check-circle me-1"></i>Verifikasi
                        </button>
                      </div>
                    </form>
                  </div>
                </div>
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

<script>
function toggleDetail(id) {
  var row = document.getElementById('detail-' + id);
  if (row.style.display === 'none') {
    row.style.display = 'table-row';
  } else {
    row.style.display = 'none';
  }
}
</script>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
