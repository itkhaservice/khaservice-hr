    <!-- Footer Content -->
    <footer class="main-footer">
        &copy; <?php echo date('Y'); ?> Khaservice HR Management System. All rights reserved.
    </footer>
    </div> <!-- End Main Content -->
</div> <!-- End Wrapper -->

<!-- Scripts -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="<?php echo BASE_URL; ?>assets/js/main.js"></script>
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
