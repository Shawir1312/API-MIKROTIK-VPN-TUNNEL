<?php
if (!defined('IN_APP')) die('Direct access denied.');
?>
        </main>
    </div>
</div>

<footer class="py-3 text-center text-muted small border-top" style="background: #0d172e; border-color: var(--border-card) !important;">
    &copy; <?= date('Y') ?> <?= htmlspecialchars(APP_NAME) ?> — High-Performance Mikrotik Remote Engine (Full Pure PHP)
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function copyToClipboard(text, btnElement) {
    navigator.clipboard.writeText(text).then(() => {
        const originalHtml = btnElement.innerHTML;
        btnElement.innerHTML = '<i class="fa-solid fa-check text-success"></i> Tersalin!';
        setTimeout(() => {
            btnElement.innerHTML = originalHtml;
        }, 2000);
    });
}
</script>
</body>
</html>
