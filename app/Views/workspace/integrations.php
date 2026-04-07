<?php

require dirname(__DIR__) . '/partials/header.php';
$subtitle = 'Store provider credentials, test connections, and log sync attempts for AI and social analytics tooling.';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="settings-grid" data-page-skeleton>
    <?php foreach ($providers as $provider): ?>
        <article class="card">
            <div class="card-head">
                <div>
                    <h3><?= htmlspecialchars(strtoupper($provider['provider'])) ?></h3>
                    <p><?= $provider['configured'] ? 'Credentials saved.' : 'No credentials saved yet.' ?></p>
                </div>
            </div>
            <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.save" class="stack" data-inline-validate data-loading-form>
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                <label><span><input type="checkbox" name="enabled" value="1" <?= $provider['enabled'] ? 'checked' : '' ?> style="width:auto;min-height:auto;"> Enabled</span></label>
                <label><span>API Key / Token</span><input type="text" name="api_key" value="" placeholder="<?= htmlspecialchars($provider['masked_key'] ?: 'Paste key or token') ?>" minlength="12"></label>
                <div class="page-actions">
                    <button class="btn btn-secondary" type="submit" data-loading-text="Saving integration...">Save</button>
                </div>
            </form>
            <div class="page-actions" style="margin-top:12px;">
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.test" data-loading-form>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                    <button class="btn btn-secondary" type="submit" data-loading-text="Testing connection...">Test</button>
                </form>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=integrations.sync" data-loading-form>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="provider" value="<?= htmlspecialchars($provider['provider']) ?>">
                    <button class="btn btn-primary" type="submit" data-loading-text="Syncing metrics...">Sync Metrics</button>
                </form>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<section class="table-card" data-page-skeleton>
    <table class="data-table">
        <thead>
            <tr><th>Provider</th><th>Action</th><th>Status</th><th>Message</th><th>User</th><th>Created</th></tr>
        </thead>
        <tbody>
            <?php foreach ($logs as $log): ?>
                <tr>
                    <td data-label="Provider"><?= htmlspecialchars((string) $log['provider']) ?></td>
                    <td data-label="Action"><?= htmlspecialchars((string) $log['action']) ?></td>
                    <td data-label="Status"><span class="status-badge <?= \App\Core\Ui::statusClass((string) $log['status']) ?>"><?= htmlspecialchars((string) $log['status']) ?></span></td>
                    <td data-label="Message"><?= htmlspecialchars((string) ($log['message'] ?? '')) ?></td>
                    <td data-label="User"><?= htmlspecialchars((string) ($log['triggered_by_name'] ?: 'System')) ?></td>
                    <td data-label="Created"><?= htmlspecialchars((string) $log['created_at']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
