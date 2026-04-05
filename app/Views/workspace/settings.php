<?php require dirname(__DIR__) . '/partials/header.php'; ?>
<?php
$subtitle = 'Core workspace settings, system preferences, and operational policies.';
require dirname(__DIR__) . '/partials/page-header.php';

$mailer = $config['app']['mailer'] ?? [];
$driver = strtoupper((string) ($mailer['driver'] ?? 'log'));
$mailjetConfigured = !empty($mailer['mailjet']['api_key']) && !empty($mailer['mailjet']['api_secret']);
?>
<section class="settings-grid">
    <article class="card">
        <div class="card-head"><div><h3>Branding</h3><p>Manage workspace name, email sender, and G2 presentation defaults.</p></div></div>
        <div class="settings-list">
            <div><strong>Workspace</strong><span>G2 Social Media Calendar</span></div>
            <div><strong>Email Sender</strong><span><?= htmlspecialchars((string) ($mailer['from_email'] ?? 'no-reply@g2.local')) ?></span></div>
            <div><strong>Default Accent</strong><span>G2 Red</span></div>
        </div>
    </article>
    <article class="card">
        <div class="card-head"><div><h3>Security</h3><p>Session, approval, and download policy overview.</p></div></div>
        <div class="settings-list">
            <div><strong>Authentication</strong><span>Password + role based</span></div>
            <div><strong>Download Gate</strong><span>Approved and ready only</span></div>
            <div><strong>Comment Visibility</strong><span>Shared and internal notes</span></div>
        </div>
    </article>
    <article class="card">
        <div class="card-head"><div><h3>Email Delivery</h3><p>Outbound notification transport and Mailjet readiness.</p></div></div>
        <div class="settings-list">
            <div><strong>Driver</strong><span><?= htmlspecialchars($driver) ?></span></div>
            <div><strong>Log Only</strong><span><?= !empty($mailer['log_only']) ? 'Enabled' : 'Disabled' ?></span></div>
            <div><strong>Mailjet</strong><span><?= $mailjetConfigured ? 'Configured' : 'Missing API credentials' ?></span></div>
            <div><strong>App URL</strong><span><?= htmlspecialchars((string) ($config['app']['url'] ?? '')) ?></span></div>
        </div>
        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=settings.test-email" class="page-actions" style="margin-top:16px;">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <button class="btn btn-primary" type="submit">Send Test Email</button>
        </form>
    </article>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
