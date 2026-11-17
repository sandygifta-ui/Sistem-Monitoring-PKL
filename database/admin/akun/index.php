<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/../../includes/csrf.php';

require_role('admin');

$db   = get_db();
$user = current_user();

// ── Hapus akun admin (tidak bisa hapus diri sendiri) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hapus_id'])) {
    csrf_verify();
    $hapus_id = (int)$_POST['hapus_id'];

    if ($hapus_id === (int)$user['user_id']) {
        set_flash('danger', 'Tidak bisa menghapus akun sendiri.');
    } else {
        // Cek apakah masih membimbing siswa
        $cek = $db->prepare('SELECT COUNT(*) FROM siswa WHERE guru_pembimbing_id = ?');
        $cek->execute([$hapus_id]);
        if ($cek->fetchColumn() > 0) {
            set_flash('danger', 'Akun tidak bisa dihapus karena masih membimbing siswa.');
        } else {
            $db->prepare('DELETE FROM users WHERE id = ? AND role = ?')->execute([$hapus_id, 'admin']);
            set_flash('success', 'Akun berhasil dihapus.');
        }
    }
    redirect(APP_URL . '/admin/akun/index.php');
}

// Daftar semua akun admin
$admins = $db->query("
    SELECT u.id, u.nama, u.username, u.created_at,
           COUNT(s.id) AS jumlah_siswa
    FROM users u
    LEFT JOIN siswa s ON s.guru_pembimbing_id = u.id
    WHERE u.role = 'admin'
    GROUP BY u.id
    ORDER BY u.nama
")->fetchAll();

$page_title = 'Kelola Akun Guru';
require_once __DIR__ . '/../../includes/header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-3">
  <p class="text-muted small mb-0"><?= count($admins) ?> akun guru terdaftar</p>
  <a href="<?= APP_URL ?>/admin/akun/tambah.php" class="btn btn-primary btn-sm">
    <i class="bi bi-person-plus me-1"></i>Tambah Akun Guru
  </a>
</div>

<div class="card border-0 shadow-sm">
  <div class="card-body p-0">
    <div class="table-responsive">
      <table class="table table-hover align-middle mb-0">
        <thead class="table-light">
          <tr>
            <th>Nama Guru</th>
            <th>Username</th>
            <th class="text-center">Siswa Dibimbing</th>
            <th>Terdaftar</th>
            <th class="text-center">Aksi</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($admins as $a): ?>
          <tr>
            <td>
              <div class="fw-semibold"><?= e($a['nama']) ?></div>
              <?php if ($a['id'] == $user['user_id']): ?>
                <span class="badge bg-primary" style="font-size:0.65rem">Akun Anda</span>
              <?php endif; ?>
            </td>
            <td class="text-muted">@<?= e($a['username']) ?></td>
            <td class="text-center">
              <span class="badge bg-light text-dark"><?= $a['jumlah_siswa'] ?> siswa</span>
            </td>
            <td class="small text-muted"><?= format_tanggal(date('Y-m-d', strtotime($a['created_at']))) ?></td>
            <td class="text-center text-nowrap">
              <a href="<?= APP_URL ?>/admin/akun/edit.php?id=<?= $a['id'] ?>"
                 class="btn btn-sm btn-outline-primary me-1">
                <i class="bi bi-pencil"></i>
              </a>
              <?php if ($a['id'] != $user['user_id']): ?>
              <form method="POST" class="d-inline"
                    onsubmit="return confirm('Hapus akun <?= e(addslashes($a['nama'])) ?>?')">
                <?= csrf_input() ?>
                <input type="hidden" name="hapus_id" value="<?= $a['id'] ?>">
                <button class="btn btn-sm btn-outline-danger"
                  <?= $a['jumlah_siswa'] > 0 ? 'disabled title="Masih membimbing siswa"' : '' ?>>
                  <i class="bi bi-trash"></i>
                </button>
              </form>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
