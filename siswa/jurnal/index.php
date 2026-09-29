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

// ── Hapus jurnal ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    csrf_verify();
    $hapus_id = (int)$_POST['hapus_id'];

    // Pastikan jurnal milik siswa ini & belum diverifikasi
    $cek = $db->prepare("
        SELECT id, status_verifikasi FROM jurnal_harian
        WHERE id = ? AND siswa_id = ?
    ");
    $cek->execute([$hapus_id, $siswa_id]);
    $jurnal = $cek->fetch();

    if (!$jurnal) {
        set_flash('danger', 'Jurnal tidak ditemukan.');
    } elseif ($jurnal['status_verifikasi'] === 'diverifikasi') {
        set_flash('danger', 'Jurnal yang sudah diverifikasi tidak bisa dihapus.');
    } else {
        $db->prepare('DELETE FROM jurnal_harian WHERE id = ?')->execute([$hapus_id]);
        set_flash('success', 'Jurnal berhasil dihapus.');
    }
    redirect(APP_URL . '/siswa/jurnal/index.php');
}

// ── Filter & pagination ──
$status = trim($_GET['status'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$where  = 'WHERE siswa_id = ?';
$params = [$siswa_id];

if ($status !== '') {
    $where   .= ' AND status_verifikasi = ?';
    $params[] = $status;
}

$cnt = $db->prepare("SELECT COUNT(*) FROM jurnal_harian $where");
$cnt->execute($params);
$total       = (int)$cnt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

$stmt = $db->prepare("
    SELECT id, tanggal, kegiatan, kendala, status_verifikasi, catatan_admin
    FROM jurnal_harian
    $where
    ORDER BY tanggal DESC
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$jurnal_list = $stmt->fetchAll();

$page_title = 'Jurnal Harian Saya';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div class="d-flex gap-2 align-items-center flex-wrap">
    <!-- Filter status -->
    <a href="?status=" class="btn btn-sm <?= $status==='' ? 'btn-primary' : 'btn-outline-secondary' ?>">Semua</a>
    <a href="?status=menunggu" class="btn btn-sm <?= $status==='menunggu' ? 'btn-warning' : 'btn-outline-secondary' ?>">Menunggu</a>
    <a href="?status=diverifikasi" class="btn btn-sm <?= $status==='diverifikasi' ? 'btn-success' : 'btn-outline-secondary' ?>">Diverifikasi</a>
    <a href="?status=ditolak" class="btn btn-sm <?= $status==='ditolak' ? 'btn-danger' : 'btn-outline-secondary' ?>">Ditolak</a>
  </div>
  <a href="<?= APP_URL ?>/siswa/jurnal/tambah.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i>Tambah Jurnal
  </a>
</div>

<p class="text-muted small mb-2"><?= $total ?> jurnal ditemukan</p>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($jurnal_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-journal-x fs-3 d-block mb-2"></i>
        Belum ada jurnal. Mulai catat kegiatan PKL kamu!
      </div>
    <?php else: ?>
      <!-- Desktop: tabel -->
      <div class="table-responsive d-none d-md-block">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Tanggal</th>
              <th>Kegiatan</th>
              <th>Kendala</th>
              <th>Status</th>
              <th>Catatan Admin</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($jurnal_list as $j): ?>
            <tr>
              <td class="text-nowrap small"><?= format_tanggal($j['tanggal']) ?></td>
              <td>
                <div style="max-width:250px" class="text-truncate"><?= e($j['kegiatan']) ?></div>
              </td>
              <td>
                <div style="max-width:180px" class="text-truncate text-muted small">
                  <?= $j['kendala'] ? e($j['kendala']) : '—' ?>
                </div>
              </td>
              <td><?= badge_status($j['status_verifikasi']) ?></td>
              <td>
                <div style="max-width:180px" class="text-truncate small text-secondary">
                  <?= $j['catatan_admin'] ? e($j['catatan_admin']) : '—' ?>
                </div>
              </td>
              <td class="text-center text-nowrap">
                <?php if ($j['status_verifikasi'] !== 'diverifikasi'): ?>
                  <a href="<?= APP_URL ?>/siswa/jurnal/edit.php?id=<?= $j['id'] ?>"
                     class="btn btn-sm btn-outline-primary me-1">
                    <i class="bi bi-pencil"></i>
                  </a>
                  <form method="POST" class="d-inline"
                        onsubmit="return confirm('Hapus jurnal tanggal <?= e(format_tanggal($j['tanggal'])) ?>?')">
                    <?= csrf_input() ?>
                    <input type="hidden" name="hapus_id" value="<?= $j['id'] ?>">
                    <button class="btn btn-sm btn-outline-danger">
                      <i class="bi bi-trash"></i>
                    </button>
                  </form>
                <?php else: ?>
                  <span class="text-muted small"><i class="bi bi-lock"></i> Terkunci</span>
                <?php endif; ?>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Mobile: card list -->
      <div class="d-md-none">
        <?php foreach ($jurnal_list as $j): ?>
        <div class="border-bottom px-3 py-3">
          <div class="d-flex justify-content-between align-items-start mb-1">
            <span class="small text-muted"><i class="bi bi-calendar3 me-1"></i><?= format_tanggal($j['tanggal']) ?></span>
            <?= badge_status($j['status_verifikasi']) ?>
          </div>
          <div class="fw-semibold mb-1"><?= e($j['kegiatan']) ?></div>
          <?php if ($j['kendala']): ?>
            <div class="small text-muted mb-1">
              <i class="bi bi-exclamation-circle me-1"></i><?= e($j['kendala']) ?>
            </div>
          <?php endif; ?>
          <?php if ($j['catatan_admin']): ?>
            <div class="small text-primary mb-2">
              <i class="bi bi-chat-left-text me-1"></i><?= e($j['catatan_admin']) ?>
            </div>
          <?php endif; ?>
          <?php if ($j['status_verifikasi'] !== 'diverifikasi'): ?>
            <div class="d-flex gap-2 mt-2">
              <a href="<?= APP_URL ?>/siswa/jurnal/edit.php?id=<?= $j['id'] ?>"
                 class="btn btn-sm btn-outline-primary">
                <i class="bi bi-pencil me-1"></i>Edit
              </a>
              <form method="POST"
                    onsubmit="return confirm('Hapus jurnal ini?')">
                <?= csrf_input() ?>
                <input type="hidden" name="hapus_id" value="<?= $j['id'] ?>">
                <button class="btn btn-sm btn-outline-danger">
                  <i class="bi bi-trash me-1"></i>Hapus
                </button>
              </form>
            </div>
          <?php else: ?>
            <div class="small text-muted mt-1"><i class="bi bi-lock me-1"></i>Jurnal terkunci</div>
          <?php endif; ?>
        </div>
        <?php endforeach; ?>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
