<?php if (current_user()): ?>
    <footer class="app-footer">
        <span><?= e(APP_NAME) ?></span>
        <span>MICEI Information Technology Department</span>
    </footer>
    </main>
</div>
<?php else: ?>
</div>
<?php endif; ?>
<script>
window.APP_CSRF = <?= json_encode(csrf_token()) ?>;
</script>
</body>
</html>
