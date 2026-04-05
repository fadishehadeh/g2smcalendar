<?php

require dirname(__DIR__) . '/partials/header.php';
$subtitle = 'Store provider credentials, test connections, and log sync attempts for AI and social analytics tooling.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="settings-grid">
    <?php foreach ($providers as $provider): ?>
        <article class="card">
            <div class="card-head">
                <div>
                    <h3><?= htmlspecialchars(strtoupper($provider['provider'])) ?></h3>
                    <p><?= $provider['configured'] ? 'Credentials saved.' : 'No credentials saved yet.' ?></p>
                </div>
            </div>
            <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.save" class="stack">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                <label><span><input type="checkbox" name="enabled" value="1" <?= $provider['enabled'] ? 'checked' : '' ?> style="width:auto;min-height:auto;"> Enabled</span></label>
                <label><span>API Key / Token</span><input type="text" name="api_key" value="" placeholder="<?= htmlspecialchars($provider['masked_key'] ?: 'Paste key or token') ?>"></label>
                <div class="page-actions">
                    <button class="btn btn-secondary" type="submit">Save</button>
                </div>
            </form>
            <div class="page-actions" style="margin-top:12px;">
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.test">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                    <button class="btn btn-secondary" type="submit">Test</button>
                </form>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.sync">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                    <button class="btn btn-primary" type="submit">Sync Metrics</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="table-card">
    <table class="data-table">
        <thead>
            <tr><th>Provider</th><th>Action</th><th>Status</th><th>Message</th><th>User</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td><?= htmlspecialchars((string) $log['provider']) ?></td>
                    <td><?= htmlspecialchars((string) $log['action']) ?></td>
                    <td><span class="status-badge <?= \App\Core\Ui::statusClass((string) $log['status']) ?>"><?= htmlspecialchars((string) $log['status']) ?></span></td>
                    <td><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></td>
                    <td><?= htmlspecialchars((string) ($log['triggered_by_name'] ?: 'System')) ?></td>
                    <td><?= htmlspecialchars((string) $log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
