    <!-- Footer Content -->
    <footer class="main-footer">
        &copy; <?php echo date('Y'); ?> Khaservice HR Management System. All rights reserved.
    </footer>
    </div> <!-- End Main Content -->
</div> <!-- End Wrapper -->

<!-- Scripts -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css"/>
    <script src="<?php echo BASE_URL; ?>assets/js/main.js?v=<?php echo time(); ?>"></script>
    <script src="<?php echo BASE_URL; ?>assets/js/tour.js?v=<?php echo time(); ?>"></script>
    <script>
        $(document).ready(function() {
            // Show Toast from Session
            <?php if (isset($_SESSION['toast'])): ?>
                Toast.show('<?php echo $_SESSION['toast']['type']; ?>', 'Thông báo', '<?php echo $_SESSION['toast']['message']; ?>');
                <?php unset($_SESSION['toast']); ?>
            <?php endif; ?>
        });
    </script>
</body>
</html>
