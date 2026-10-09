  </div><!-- /.content-area -->

  <!-- Footer -->
  <footer class="text-center text-muted py-3 border-top bg-white" style="font-size:0.8rem">
    &copy; <?= date('Y') ?> SIMONA &mdash; Sistem Monitoring dan Nilai PKL
  </footer>

</div><!-- /.main-wrapper -->

<!-- Toast container -->
<div class="toast-container-custom" id="toastContainer"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}

// ── Toast notification ──
function showToast(message, type = 'success') {
  const icons = {
    success: '<i class="bi bi-check-circle-fill text-success"></i>',
    danger : '<i class="bi bi-x-circle-fill text-danger"></i>',
    warning: '<i class="bi bi-exclamation-triangle-fill text-warning"></i>',
    info   : '<i class="bi bi-info-circle-fill" style="color:#7C3AED"></i>',
  };

  const toast = document.createElement('div');
  toast.className = `toast-custom toast-${type}`;
  toast.innerHTML = `
    <span class="toast-icon">${icons[type] || icons.info}</span>
    <span class="toast-msg">${message}</span>
    <button class="toast-close" onclick="this.closest('.toast-custom').remove()">
      <i class="bi bi-x"></i>
    </button>
  `;

  document.getElementById('toastContainer').appendChild(toast);

  // Animasi masuk
  requestAnimationFrame(() => {
    requestAnimationFrame(() => toast.classList.add('show'));
  });

  // Hilang otomatis setelah 3.5 detik
  setTimeout(() => {
    toast.classList.remove('show');
    setTimeout(() => toast.remove(), 350);
  }, 3500);
}

// Auto-show toast jika ada flash message dari PHP
<?php
$flash = $_SESSION['flash_toast'] ?? null;
if ($flash) {
    unset($_SESSION['flash_toast']);
    echo "showToast(" . json_encode(strip_tags($flash['message'])) . ", '" . $flash['type'] . "');";
}
?>

// ── Animasi smooth saat pindah halaman dihapus ──
// (menyebabkan pointer-events: none permanen, tombol tidak bisa diklik)
</script>
</body>
</html>
