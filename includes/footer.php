<?php if (current_user()): ?>
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
