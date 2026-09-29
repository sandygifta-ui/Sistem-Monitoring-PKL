<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db = get_db();

// Aspek penilaian (tetap/hardcode sesuai kesepakatan)
$aspek_list = ['Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama'];

// ── Parameter filter & pagination ──
$cari  = trim($_GET['cari'] ?? '');
$kelas = trim($_GET['kelas'] ?? '');
$page  = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset   = ($page - 1) * $per_page;

$where  = 'WHERE 1=1';
$params = [];
if ($cari !== '') {
    $where   .= ' AND (u.nama LIKE ? OR s.nis LIKE ?)';
    $params[] = "%$cari%";
    $params[] = "%$cari%";
}
if ($kelas !== '') {
    $where   .= ' AND s.kelas = ?';
    $params[] = $kelas;
}

$total = (int)$db->prepare("SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id=u.id $where")
                 ->execute($params) ? $db->prepare("SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id=u.id $where") : 0;

// Hitung total dengan cara yang benar
$cnt_stmt = $db->prepare("SELECT COUNT(*) FROM siswa s JOIN users u ON s.user_id=u.id $where");
$cnt_stmt->execute($params);
$total = (int)$cnt_stmt->fetchColumn();
$total_pages = max(1, (int)ceil($total / $per_page));

// Data siswa
$stmt = $db->prepare("
    SELECT s.id, s.nis, s.kelas, s.tempat_pkl, u.nama AS nama_siswa
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    $where
    ORDER BY s.kelas, u.nama
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$siswa_list = $stmt->fetchAll();

// Ambil semua nilai sekaligus (untuk siswa yang tampil di halaman ini)
$siswa_ids = array_column($siswa_list, 'id');
$nilai_map = []; // [siswa_id][aspek] = nilai
if (!empty($siswa_ids)) {
    $in = implode(',', array_fill(0, count($siswa_ids), '?'));
    $n_stmt = $db->prepare("SELECT siswa_id, aspek_penilaian, nilai FROM nilai WHERE siswa_id IN ($in)");
    $n_stmt->execute($siswa_ids);
    foreach ($n_stmt->fetchAll() as $n) {
        $nilai_map[$n['siswa_id']][$n['aspek_penilaian']] = $n['nilai'];
    }
}

$kelas_list = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

$page_title = 'Penilaian Siswa';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-12 col-md-5">
        <input type="text" name="cari" class="form-control form-control-sm"
               placeholder="Cari nama atau NIS..."
               value="<?= e($cari) ?>">
      </div>
      <div class="col-6 col-md-3">
        <select name="kelas" class="form-select form-select-sm">
          <option value="">Semua Kelas</option>
          <?php foreach ($kelas_list as $k): ?>
            <option value="<?= e($k) ?>" <?= $kelas===$k?'selected':'' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-4 d-flex gap-2">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Cari</button>
        <a href="<?= APP_URL ?>/admin/nilai/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<p class="text-muted small mb-2"><?= $total ?> siswa ditemukan</p>

<!-- Tabel nilai -->
<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <?php if (empty($siswa_list)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-search fs-3 d-block mb-2"></i>Tidak ada siswa ditemukan.
      </div>
    <?php else: ?>
      <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
          <thead class="table-light">
            <tr>
              <th>Siswa</th>
              <?php foreach ($aspek_list as $a): ?>
                <th class="text-center"><?= e($a) ?></th>
              <?php endforeach; ?>
              <th class="text-center">Rata-rata</th>
              <th class="text-center">Aksi</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($siswa_list as $s): ?>
            <?php
              $nilai_siswa = $nilai_map[$s['id']] ?? [];
              $semua = array_values($nilai_siswa);
              $rata  = empty($semua) ? null : round(array_sum($semua)/count($semua), 1);
            ?>
            <tr>
              <td>
                <div class="fw-semibold"><?= e($s['nama_siswa']) ?></div>
                <div class="text-muted small"><?= e($s['kelas']) ?> &bull; NIS <?= e($s['nis']) ?></div>
              </td>
              <?php foreach ($aspek_list as $a): ?>
                <td class="text-center">
                  <?php if (isset($nilai_siswa[$a])): ?>
                    <span class="badge <?= $nilai_siswa[$a] >= 75 ? 'bg-success' : 'bg-danger' ?> rounded-pill">
                      <?= $nilai_siswa[$a] ?>
                    </span>
                  <?php else: ?>
                    <span class="text-muted">—</span>
                  <?php endif; ?>
                </td>
              <?php endforeach; ?>
              <td class="text-center fw-bold">
                <?= $rata !== null ? $rata : '<span class="text-muted">—</span>' ?>
              </td>
              <td class="text-center">
                <a href="<?= APP_URL ?>/admin/nilai/form.php?siswa_id=<?= $s['id'] ?>"
                   class="btn btn-sm btn-outline-primary">
                  <i class="bi bi-pencil"></i> Input
                </a>
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

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
