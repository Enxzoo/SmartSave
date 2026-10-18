    </main>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="assets/js/theme.js"></script>
<script src="assets/js/app.js"></script>
<?php if (!empty($openModal)): ?>
<script>document.addEventListener('DOMContentLoaded', function () { bootstrap.Modal.getOrCreateInstance(document.getElementById(<?= json_encode($openModal) ?>)).show(); });</script>
<?php endif; ?>
</body>
</html>
