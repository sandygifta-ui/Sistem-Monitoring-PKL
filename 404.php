<?php
require_once __DIR__ . '/config/config.php';
http_response_code(404);
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>404 — Halaman Tidak Ditemukan | SIMONA</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    body {
      background: linear-gradient(135deg,#1e3a5f,#2d6a9f);
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      font-family: 'Segoe UI', sans-serif;
    }
  </style>
</head>
<body>
  <div class="text-center text-white px-3">
    <div style="font-size:6rem;line-height:1">😵</div>
    <h1 class="display-4 fw-bold mt-3">404</h1>
    <p class="fs-5 opacity-75">Halaman yang kamu cari tidak ditemukan.</p>
    <a href="<?= APP_URL ?>" class="btn btn-light btn-lg mt-2 px-4 rounded-pill">
      <i class="bi bi-house me-2"></i>Kembali ke Beranda
    </a>
  </div>
</body>
</html>
