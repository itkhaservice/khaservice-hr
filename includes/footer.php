    <!-- Footer Content (Bottom of Main Content) -->
    <footer style="padding: 20px; text-align: center; color: #777; font-size: 12px; border-top: 1px solid #eee;">
        &copy; <?php echo date('Y'); ?> Khaservice HR Management System. All rights reserved.
    </footer>
    </div> <!-- End Main Content -->
</div> <!-- End Wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="/khaservice-hr/assets/js/main.js"></script>
<script>
    $(document).ready(function() {
        $('#sidebarToggle').on('click', function() {
            $('.sidebar').toggleClass('active');
        });

        // Show Toast from Session
        <?php if (isset($_SESSION['toast'])): ?>
            Toast.show('<?php echo $_SESSION['toast']['type']; ?>', 'Thông báo', '<?php echo $_SESSION['toast']['message']; ?>');
            <?php unset($_SESSION['toast']); ?>
        <?php endif; ?>
    });
</script>
</body>
</html>
