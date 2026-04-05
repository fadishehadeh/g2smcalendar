<?php if (\App\Core\Auth::check()): ?>
        </main>
    </div>
</div>
<?php endif; ?>
<script>
    window.G2_APP = {
        baseUrl: <?= json_encode($config['app']['base_url']) ?>,
        currentRoute: <?= json_encode($currentRoute ?? '') ?>
    };
</script>
<?php $jsVersion = @filemtime(dirname(__DIR__, 3) . '/public/assets/js/app.js') ?: time(); ?>
<script src="<?= htmlspecialchars($config['app']['base_url']) ?>/public/assets/js/app.js?v=<?= (int) $jsVersion ?>"></script>
</body>
</html>
