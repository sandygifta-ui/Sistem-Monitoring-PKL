  </div><!-- /.content-area -->

  <!-- Footer -->
  <footer class="text-center text-muted py-3 border-top bg-white" style="font-size:0.8rem">
    &copy; <?= date('Y') ?> SIMONA &mdash; Sistem Monitoring dan Nilai PKL
  </footer>

</div><!-- /.main-wrapper -->

<!-- Overlay transisi antar halaman -->
<div id="pageTransition" style="
  position:fixed;inset:0;
  background:#2E0A4F;
  opacity:0;
  pointer-events:none;
  z-index:9998;
  transition:opacity 0.25s ease;
"></div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function toggleSidebar() {
  document.getElementById('sidebar').classList.toggle('show');
  document.getElementById('sidebarOverlay').classList.toggle('show');
}

// Animasi smooth saat pindah halaman
(function() {
  const overlay = document.getElementById('pageTransition');

  // Semua link di sidebar & topbar — kecuali anchor, modal trigger, logout confirm
  document.querySelectorAll('.sidebar-nav .nav-link, .topbar a').forEach(function(link) {
    link.addEventListener('click', function(e) {
      const href = this.getAttribute('href');

      // Skip: bukan link halaman biasa
      if (!href || href.startsWith('#') || href.startsWith('javascript')
          || this.dataset.bsToggle || this.getAttribute('onclick')) return;

      e.preventDefault();
      const target = href;

      // Fade out konten
      document.querySelector('.content-area').style.opacity = '0';
      document.querySelector('.content-area').style.transform = 'translateY(-8px)';
      document.querySelector('.content-area').style.transition = 'opacity 0.2s ease, transform 0.2s ease';

      setTimeout(function() {
        window.location.href = target;
      }, 200);
    });
  });
})();
</script>
</body>
</html>
