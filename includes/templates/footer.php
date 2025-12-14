<?php
// includes/templates/footer.php
?>
    </main>

    <footer class="footer mt-auto py-3 bg-light">
        <div class="container text-center">
            <span class="text-muted">© <?= date('Y') ?> מערכת ניהול סושיאל. כל הזכויות שמורות.</span>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <?php if (isset($extraScripts)): ?>
        <?php foreach ($extraScripts as $script): ?>
            <script src="<?= $script ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <script>
    // Global JavaScript functions
    function confirmDelete(message = 'האם אתה בטוח שברצונך למחוק?') {
        return confirm(message);
    }

    // Initialize tooltips
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
    var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl)
    });
    </script>

    <?php if (isset($extraJs)): ?>
        <script>
            <?= $extraJs ?>
        </script>
    <?php endif; ?>
</body>
</html>