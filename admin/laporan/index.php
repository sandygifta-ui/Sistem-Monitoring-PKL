<?php
require_once __DIR__ . '/../../config/config.php';
require_once __DIR__ . '/../../includes/db.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/functions.php';

require_role('admin');

$db = get_db();

$aspek_list = ['Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama'];

// ── Filter ──
$kelas      = trim($_GET['kelas']      ?? '');
$siswa_id   = (int)($_GET['siswa_id']  ?? 0);
$tgl_dari   = trim($_GET['tgl_dari']   ?? '');
$tgl_sampai = trim($_GET['tgl_sampai'] ?? '');
$export     = trim($_GET['export']     ?? '');

// Daftar kelas & siswa untuk dropdown
$kelas_list = $db->query("SELECT DISTINCT kelas FROM siswa ORDER BY kelas")->fetchAll(PDO::FETCH_COLUMN);

$siswa_query = 'SELECT s.id, u.nama, s.kelas FROM siswa s JOIN users u ON s.user_id=u.id ORDER BY s.kelas, u.nama';
$siswa_list  = $db->query($siswa_query)->fetchAll();

// ── Bangun query rekap ──
$where  = 'WHERE 1=1';
$params = [];

if ($kelas !== '') {
    $where   .= ' AND s.kelas = ?';
    $params[] = $kelas;
}
if ($siswa_id > 0) {
    $where   .= ' AND s.id = ?';
    $params[] = $siswa_id;
}

// Data siswa untuk rekap
$stmt = $db->prepare("
    SELECT s.id, s.nis, s.kelas, s.tempat_pkl,
           s.tgl_mulai, s.tgl_selesai,
           s.nama_pembimbing_industri,
           u.nama AS nama_siswa,
           g.nama AS nama_guru
    FROM siswa s
    JOIN users u ON s.user_id = u.id
    JOIN users g ON s.guru_pembimbing_id = g.id
    $where
    ORDER BY s.kelas, u.nama
");
$stmt->execute($params);
$rekap_siswa = $stmt->fetchAll();

// Ambil nilai semua siswa di rekap
$ids = array_column($rekap_siswa, 'id');
$nilai_map = [];
if (!empty($ids)) {
    $in = implode(',', array_fill(0, count($ids), '?'));
    $n  = $db->prepare("SELECT siswa_id, aspek_penilaian, nilai FROM nilai WHERE siswa_id IN ($in)");
    $n->execute($ids);
    foreach ($n->fetchAll() as $row) {
        $nilai_map[$row['siswa_id']][$row['aspek_penilaian']] = $row['nilai'];
    }
}

// Ambil ringkasan jurnal per siswa
$jurnal_map = [];
if (!empty($ids)) {
    $in = implode(',', array_fill(0, count($ids), '?'));

    $where_tgl = '';
    $tgl_params = $ids;
    if ($tgl_dari !== '') { $where_tgl .= ' AND tanggal >= ?'; $tgl_params[] = $tgl_dari; }
    if ($tgl_sampai !== '') { $where_tgl .= ' AND tanggal <= ?'; $tgl_params[] = $tgl_sampai; }

    $j = $db->prepare("
        SELECT siswa_id,
               COUNT(*) AS total,
               SUM(status_verifikasi='diverifikasi') AS diverifikasi,
               SUM(status_verifikasi='menunggu')     AS menunggu,
               SUM(status_verifikasi='ditolak')      AS ditolak
        FROM jurnal_harian
        WHERE siswa_id IN ($in) $where_tgl
        GROUP BY siswa_id
    ");
    $j->execute($tgl_params);
    foreach ($j->fetchAll() as $row) {
        $jurnal_map[$row['siswa_id']] = $row;
    }
}

// ── Export CSV ──
if ($export === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="rekap_pkl_' . date('Ymd') . '.csv"');

    $out = fopen('php://output', 'w');
    fprintf($out, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8 agar Excel terbaca

    // Header kolom
    fputcsv($out, [
        'No', 'Nama Siswa', 'NIS', 'Kelas', 'Tempat PKL',
        'Guru Pembimbing', 'Pembimbing Industri',
        'Tgl Mulai', 'Tgl Selesai',
        'Total Jurnal', 'Diverifikasi', 'Menunggu', 'Ditolak',
        'Kedisiplinan', 'Kompetensi', 'Sikap', 'Kerja Sama', 'Rata-rata'
    ]);

    foreach ($rekap_siswa as $i => $s) {
        $nv  = $nilai_map[$s['id']] ?? [];
        $jv  = $jurnal_map[$s['id']] ?? [];
        $ang = array_values($nv);
        $rt  = empty($ang) ? '' : round(array_sum($ang)/count($ang), 1);

        fputcsv($out, [
            $i + 1,
            $s['nama_siswa'],
            $s['nis'],
            $s['kelas'],
            $s['tempat_pkl'],
            $s['nama_guru'],
            $s['nama_pembimbing_industri'],
            $s['tgl_mulai'],
            $s['tgl_selesai'],
            $jv['total']        ?? 0,
            $jv['diverifikasi'] ?? 0,
            $jv['menunggu']     ?? 0,
            $jv['ditolak']      ?? 0,
            $nv['Kedisiplinan'] ?? '',
            $nv['Kompetensi']   ?? '',
            $nv['Sikap']        ?? '',
            $nv['Kerja Sama']   ?? '',
            $rt,
        ]);
    }

    fclose($out);
    exit;
}

$page_title = 'Rekap & Laporan';
require_once __DIR__ . '/../../includes/header.php';
?>

<!-- Filter -->
<div class="card border-0 shadow-sm mb-3">
  <div class="card-body py-2">
    <form method="GET" class="row g-2 align-items-end">
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm mb-1">Kelas</label>
        <select name="kelas" class="form-select form-select-sm">
          <option value="">Semua</option>
          <?php foreach ($kelas_list as $k): ?>
            <option value="<?= e($k) ?>" <?= $kelas===$k?'selected':'' ?>><?= e($k) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-12 col-md-3">
        <label class="form-label form-label-sm mb-1">Siswa</label>
        <select name="siswa_id" class="form-select form-select-sm">
          <option value="">Semua Siswa</option>
          <?php foreach ($siswa_list as $s): ?>
            <option value="<?= $s['id'] ?>" <?= $siswa_id==$s['id']?'selected':'' ?>>
              <?= e($s['nama']) ?> (<?= e($s['kelas']) ?>)
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm mb-1">Jurnal dari</label>
        <input type="date" name="tgl_dari" class="form-control form-control-sm" value="<?= e($tgl_dari) ?>">
      </div>
      <div class="col-6 col-md-2">
        <label class="form-label form-label-sm mb-1">Sampai</label>
        <input type="date" name="tgl_sampai" class="form-control form-control-sm" value="<?= e($tgl_sampai) ?>">
      </div>
      <div class="col-12 col-md-3 d-flex gap-2 flex-wrap">
        <button type="submit" class="btn btn-primary btn-sm"><i class="bi bi-search me-1"></i>Filter</button>
        <a href="<?= APP_URL ?>/admin/laporan/index.php" class="btn btn-outline-secondary btn-sm">Reset</a>
      </div>
    </form>
  </div>
</div>

<!-- Tombol aksi -->
<div class="d-flex gap-2 mb-3 flex-wrap">
  <!-- Export CSV -->
  <a href="?<?= http_build_query(array_merge($_GET, ['export'=>'csv'])) ?>"
     class="btn btn-success btn-sm">
    <i class="bi bi-file-earmark-excel me-1"></i>Export CSV
  </a>
  <!-- Print -->
  <button onclick="window.print()" class="btn btn-outline-secondary btn-sm">
    <i class="bi bi-printer me-1"></i>Cetak
  </button>
</div>

<!-- Tabel rekap -->
<div class="card border-0 shadow-sm" id="tabel-rekap">
  <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
    <h6 class="mb-0 fw-bold">
      <i class="bi bi-file-earmark-bar-graph me-2" style="color:#1e3a5f"></i>
      Rekap Jurnal & Nilai PKL
      <?php if ($kelas): ?> — Kelas <?= e($kelas) ?><?php endif; ?>
    </h6>
    <small class="text-muted">Dicetak: <?= format_tanggal(date('Y-m-d')) ?></small>
  </div>
  <div class="card-body p-0">
    <?php if (empty($rekap_siswa)): ?>
      <div class="text-center py-5 text-muted">
        <i class="bi bi-search fs-3 d-block mb-2"></i>Tidak ada data.
      </div>
    <?php else: ?>
    <div class="table-responsive">
      <table class="table table-bordered table-hover align-middle mb-0 small">
        <thead class="table-light">
          <tr>
            <th rowspan="2" class="align-middle text-center">No</th>
            <th rowspan="2" class="align-middle">Nama Siswa</th>
            <th rowspan="2" class="align-middle text-center">Kelas</th>
            <th rowspan="2" class="align-middle">Tempat PKL</th>
            <th colspan="4" class="text-center bg-light-subtle">Jurnal</th>
            <th colspan="5" class="text-center" style="background:#e8f4fd">Nilai</th>
          </tr>
          <tr>
            <th class="text-center">Total</th>
            <th class="text-center text-success">✓</th>
            <th class="text-center text-warning">⏳</th>
            <th class="text-center text-danger">✗</th>
            <?php foreach ($aspek_list as $a): ?>
              <th class="text-center" style="background:#e8f4fd;font-size:0.7rem"><?= e($a) ?></th>
            <?php endforeach; ?>
            <th class="text-center fw-bold" style="background:#e8f4fd">Rata</th>
          </tr>
        </thead>
        <tbody>
          <?php foreach ($rekap_siswa as $i => $s):
            $nv  = $nilai_map[$s['id']] ?? [];
            $jv  = $jurnal_map[$s['id']] ?? [];
            $ang = array_values($nv);
            $rt  = empty($ang) ? null : round(array_sum($ang)/count($ang), 1);
          ?>
          <tr>
            <td class="text-center text-muted"><?= $i+1 ?></td>
            <td>
              <div class="fw-semibold"><?= e($s['nama_siswa']) ?></div>
              <div class="text-muted" style="font-size:0.7rem">NIS: <?= e($s['nis']) ?></div>
            </td>
            <td class="text-center">
              <span class="badge bg-light text-dark"><?= e($s['kelas']) ?></span>
            </td>
            <td><?= e($s['tempat_pkl']) ?></td>
            <td class="text-center fw-bold"><?= $jv['total']        ?? 0 ?></td>
            <td class="text-center text-success"><?= $jv['diverifikasi'] ?? 0 ?></td>
            <td class="text-center text-warning"><?= $jv['menunggu']     ?? 0 ?></td>
            <td class="text-center text-danger"><?= $jv['ditolak']       ?? 0 ?></td>
            <?php foreach ($aspek_list as $a): ?>
              <td class="text-center">
                <?php if (isset($nv[$a])): ?>
                  <span class="fw-bold <?= $nv[$a]>=75?'text-success':'text-danger' ?>">
                    <?= $nv[$a] ?>
                  </span>
                <?php else: ?>
                  <span class="text-muted">—</span>
                <?php endif; ?>
              </td>
            <?php endforeach; ?>
            <td class="text-center fw-bold">
              <?php if ($rt !== null):
                [$h,$l,$w] = ['A','','success'];
                if ($rt < 90) [$h,$l,$w] = ['B','','primary'];
                if ($rt < 80) [$h,$l,$w] = ['C','','warning'];
                if ($rt < 70) [$h,$l,$w] = ['D','','danger'];
              ?>
                <span class="text-<?= $w ?>"><?= $rt ?></span>
              <?php else: ?>
                <span class="text-muted">—</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endforeach; ?>
        </tbody>
      </table>
    </div>
    <?php endif; ?>
  </div>
</div>

<!-- Kop surat (hanya muncul saat print) -->
<div id="kop-surat" style="display:none">
  <div style="display:flex;align-items:center;gap:16px;margin-bottom:12px;border-bottom:3px solid #2E0A4F;padding-bottom:12px">
    <img src="<?= APP_URL ?>/assets/img/logo-smk.png"
         style="width:70px;height:70px;object-fit:contain">
    <div>
      <div style="font-size:11pt;font-weight:bold;color:#2E0A4F;letter-spacing:0.5px">
        PEMERINTAH DAERAH KOTA SURAKARTA
      </div>
      <div style="font-size:16pt;font-weight:800;color:#2E0A4F;line-height:1.2">
        SMK NEGERI 6 SURAKARTA
      </div>
      <div style="font-size:8.5pt;color:#555;margin-top:2px">
        Jl. LU. Adisucipto No.38, Surakarta &bull; Telp. (0271) 726036
      </div>
    </div>
  </div>
  <div style="text-align:center;margin-bottom:12px">
    <div style="font-size:13pt;font-weight:bold;text-transform:uppercase;letter-spacing:1px">
      Rekap Jurnal & Nilai PKL
    </div>
    <div style="font-size:9pt;color:#555">
      <?php if ($kelas): ?>Kelas: <?= e($kelas) ?> &bull; <?php endif; ?>
      Dicetak: <?= format_tanggal(date('Y-m-d')) ?>
    </div>
  </div>
</div>

<!-- CSS Print -->
<style>
@media print {
  /* Sembunyikan elemen yang tidak perlu */
  .sidebar, .topbar, .btn, form,
  .card-header .text-muted,
  #tabel-rekap .card-header .d-flex,
  .toast-container-custom { display: none !important; }

  /* Tampilkan kop surat */
  #kop-surat { display: block !important; }

  /* Layout */
  .main-wrapper { margin-left: 0 !important; }
  .content-area { padding: 0 !important; }

  /* Kartu */
  .card {
    box-shadow: none !important;
    border: 1px solid #ccc !important;
    border-radius: 4px !important;
  }

  /* Tabel */
  body { font-size: 10px; }
  table { border-collapse: collapse !important; }
  th, td { border: 1px solid #ccc !important; padding: 4px 6px !important; }
  thead { background: #f0f0f0 !important; -webkit-print-color-adjust: exact; }

  /* Badge status */
  .badge { border: 1px solid #999 !important; padding: 2px 6px !important; }

  /* Hindari terpotong di tengah baris */
  tr { page-break-inside: avoid; }

  /* Footer tanda tangan */
  .ttd-print { display: block !important; }
}

/* Tanda tangan — hanya muncul saat print */
.ttd-print {
  display: none;
  margin-top: 32px;
}
</style>

<!-- Tanda tangan (hanya print) -->
<div class="ttd-print">
  <div style="display:flex;justify-content:space-between;margin-top:24px">
    <div style="text-align:center;width:220px">
      <div>Mengetahui,</div>
      <div style="font-weight:bold">Kepala Sekolah</div>
      <div style="margin-top:60px;border-top:1px solid #000;padding-top:4px">
        (____________________________)
      </div>
      <div style="font-size:9pt">NIP.</div>
    </div>
    <div style="text-align:center;width:220px">
      <div>Surakarta, <?= format_tanggal(date('Y-m-d')) ?></div>
      <div style="font-weight:bold">Guru Pembimbing</div>
      <div style="margin-top:60px;border-top:1px solid #000;padding-top:4px">
        (____________________________)
      </div>
      <div style="font-size:9pt">NIP.</div>
    </div>
  </div>
</div>

<?php require_once __DIR__ . '/../../includes/footer.php'; ?>
