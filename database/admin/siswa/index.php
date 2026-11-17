<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db = get_db();

// ── Hapus siswa ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    csrf_verify();
    $hapus_id = (int)$_POST['hapus_id'];
    // Hapus user terkait → siswa, jurnal, nilai ikut terhapus (CASCADE)
    $stmt = $db->prepare('DELETE FROM users WHERE id = (SELECT user_id FROM siswa WHERE id = ?)');
    $stmt->execute([$hapus_id]);
    set_flash('success', 'Data siswa berhasil dihapus.');
    redirect(APP_URL . '/admin/siswa/index.php');
}

// ── Parameter pencarian & pagination ──
$cari  = trim($_GET['cari'] ?? '');
$kelas = trim($_GET['kelas'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

// ── Query dasar ──
$where  = 'WHERE 1=1';
$params = [];

if ($cari !== '') {
    $where   .= ' AND (u.nama LIKE ? OR s.nis LIKE ? OR s.tempat_pkl LIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
if ($kelas !== '') {
    $where   .= ' AND s.kelas = ?';
    $params[] = $kelas;
}

// Total data untuk pagination
$count_sql = "SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id = u.id $where";
$stmt = $db->prepare($count_sql);
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// Data siswa
$sql = "
    SELECT s.id, s.nis, s.kelas, s.tempat_pkl, s.tgl_mulai, s.tgl_selesai,
           s.nama_pembimbing_industri,
           u.nama AS nama_siswa, u.username,
           g.nama AS nama_guru
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    JOIN users g ON s.guru_pembimbing_id = g.id
    $where
    ORDER BY s.kelas, u.nama
    LIMIT $per_page OFFSET $offset
";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();

// Daftar kelas unik untuk filter dropdown
$kelas_list = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Data Siswa';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <div>
    <p class="text-muted mb-0 small">Total: <strong><?= $total ?></strong> siswa ditemukan</p>
  </div>
  <a href="<?= APP_URL ?>/admin/siswa/tambah.php" class="btn btn-primary btn-sm">
    <i class="bi bi-plus-lg me-1"></i> Tambah Siswa
  </a>
</div>

<!-- Form pencarian -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-5">
        <input type="text" name="cari" class="form-control form-control-sm"
               placeholder="Cari nama, NIS, atau tempat PKL..."
               value="<?= e($cari) ?>">
      </div>
      <div class="col-6 col-md-3">
        <select name="kelas" class="form-select form-select-sm">
          <option value="">Semua Kelas</option>
          <?php foreach ($kelas_list as $k): ?>
            <option value="<?= e($k) ?>" <?= $kelas === $k ? 'selected' : '' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm">
          <i class="bi bi-search me-1"></i> Cari
        </button>
        <a href="<?= APP_URL ?>/admin/siswa/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Tabel siswa -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($siswa_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-search fs-3 d-block mb-2"></i>
        Tidak ada data siswa ditemukan.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>No</th>
              <th>Nama Siswa</th>
              <th>Kelas</th>
              <th>Tempat PKL</th>
              <th>Guru Pembimbing</th>
              <th>Periode</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($siswa_list as $i => $s): ?>
            <tr>
              <td class="text-muted small"><?= $offset + $i + 1 ?></td>
              <td>
                <div class="fw-semibold"><?= e($s['nama_siswa']) ?></div>
                <div class="text-muted small">NIS: <?= e($s['nis']) ?> &bull; @<?= e($s['username']) ?></div>
              </td>
              <td><span class="badge bg-light text-dark"><?= e($s['kelas']) ?></span></td>
              <td>
                <div><?= e($s['tempat_pkl']) ?></div>
                <div class="text-muted small">Mentor: <?= e($s['nama_pembimbing_industri']) ?></div>
              </td>
              <td class="small"><?= e($s['nama_guru']) ?></td>
              <td class="small text-nowrap">
                <?= format_tanggal($s['tgl_mulai']) ?><br>
                <span class="text-muted">s/d <?= format_tanggal($s['tgl_selesai']) ?></span>
              </td>
              <td class="text-center text-nowrap">
                <a href="<?= APP_URL ?>/admin/siswa/edit.php?id=<?= $s['id'] ?>"
                   class="btn btn-sm btn-outline-primary me-1">
                  <i class="bi bi-pencil"></i>
                </a>
                <form method="POST" class="d-inline"
                      onsubmit="return confirm('Hapus siswa <?= e(addslashes($s['nama_siswa'])) ?>? Semua jurnal dan nilainya ikut terhapus!')">
                  <?= csrf_input() ?>
                  <input type="hidden" name="hapus_id" value="<?= $s['id'] ?>">
                  <button type="submit" class="btn btn-sm btn-outline-danger">
                    <i class="bi bi-trash"></i>
                  </button>
                </form>
              </td>
            </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>

      <!-- Pagination -->
      <?php if ($total_pages > 1): ?>
      <div class="d-flex justify-content-between align-items-center px-3 py-2 border-top">
        <small class="text-muted">
          Halaman <?= $page ?> dari <?= $total_pages ?>
        </small>
        <?= render_pagination($page, $total_pages) ?>
      </div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
