<?php
// views/layout/footer.php
?>
        </main><!-- end content-body -->

        <!-- Footer kecil -->
        <footer class="content-footer-bar">
            <span>&copy; <?= date('Y') ?> SiAkad &mdash; Sistem Manajemen Akademik Mahasiswa</span>
        </footer>

    </div><!-- end main-content -->

</div><!-- end app-wrapper -->

<!-- Overlay untuk mobile -->
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- App JS -->
<script src="assets/js/app.js"></script>

<script>
// ── Toggle Sidebar Mobile ──
(function () {
    var toggle   = document.getElementById('sidebarToggle');
    var sidebar  = document.getElementById('sidebar');
    var overlay  = document.getElementById('sidebarOverlay');

    if (toggle && sidebar) {
        toggle.addEventListener('click', function () {
            sidebar.classList.toggle('sidebar-open');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('sidebar-open');
            overlay.classList.remove('active');
        });
    }
})();
</script>
</body>
</html>