    <!-- Footer Content -->
    <footer class="main-footer">
        &copy; <?php echo date('Y'); ?> Khaservice HR Management System. All rights reserved.
    </footer>
    </div> <!-- End Main Content -->
</div> <!-- End Wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/khaservice-hr/assets/js/main.js"></script>
<script>
    $(document).ready(function() {
        // Sidebar Toggle Logic
        $('#sidebarToggle').on('click', function() {
            if ($(window).width() > 768) {
                // Desktop: Collapse/Expand
                $('.sidebar').toggleClass('collapsed');
                $('.main-content').toggleClass('expanded');
                // Save state
                localStorage.setItem('sidebar-collapsed', $('.sidebar').hasClass('collapsed'));
            } else {
                // Mobile: Show/Hide
                $('.sidebar').toggleClass('active');
            }
        });

        // Restore Desktop Sidebar state
        if ($(window).width() > 768) {
            if (localStorage.getItem('sidebar-collapsed') === 'true') {
                $('.sidebar').addClass('collapsed');
                $('.main-content').addClass('expanded');
            }
        }

        // Show Toast from Session
        <?php if (isset($_SESSION['toast'])): ?>
            Toast.show('<?php echo $_SESSION['toast']['type']; ?>', 'Thông báo', '<?php echo $_SESSION['toast']['message']; ?>');
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    });
</script>
</body>
</html>
