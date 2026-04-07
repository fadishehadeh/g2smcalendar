<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$title = $client['company_name'];
$subtitle = 'Client contact details, assigned team members, access status, and recent calendars.';
$pageActions = [
    ['label' => 'Back to Clients', 'href' => $config['app']['base_url'] . '/index.php?route=clients', 'class' => 'btn-secondary', 'icon' => 'left'],
    ['label' => 'Open Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar&client_id=' . (int) $client['id'], 'class' => 'btn-secondary', 'icon' => 'calendar'],
];
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="client-detail-grid">
    <article class="card client-detail-hero">
        <div class="entity-card-head client-detail-head">
            <div class="entity-summary">
                <span class="avatar avatar-soft"><?= htmlspecialchars(Ui::initials($client['company_name'])) ?></span>
                <div>
                    <h2><?= htmlspecialchars($client['company_name']) ?></h2>
                    <p><?= htmlspecialchars($client['contact_name']) ?></p>
                </div>
            </div>
            <span class="status-badge <?= Ui::statusClass($client['status']) ?>"><?= htmlspecialchars(ucfirst($client['status'])) ?></span>
        </div>

        <dl class="entity-meta client-detail-meta">
            <div><dt>Contact Email</dt><dd><?= htmlspecialchars((string) $client['contact_email']) ?></dd></div>
            <div><dt>Contact Phone</dt><dd><?= htmlspecialchars((string) ($client['contact_phone'] ?: '-')) ?></dd></div>
            <div><dt>Client Login</dt><dd><?= htmlspecialchars((string) ($client['client_user_email'] ?? 'No linked login')) ?></dd></div>
            <div><dt>Account Owner</dt><dd><?= htmlspecialchars((string) ($client['account_owner_name'] ?? 'Not assigned')) ?></dd></div>
            <div><dt>Calendars</dt><dd><?= (int) $client['calendars_count'] ?></dd></div>
            <div><dt>Employees</dt><dd><?= (int) $client['employees_count'] ?></dd></div>
        </dl>

        <div class="entity-actions">
            <?php if (!empty($client['client_user_id'])): ?>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=clients.resend-welcome" data-loading-form>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                    <button class="btn btn-primary" type="submit" data-loading-text="Sending email...">Send Welcome Email Again</button>
                </form>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=clients.send-reset-email" data-loading-form>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                    <button class="btn btn-secondary" type="submit" data-loading-text="Sending reset email...">Send Password Reset Email</button>
                </form>
            <?php else: ?>
                <div class="helper-block">This client does not have a linked login yet. Create portal access first if you want to resend access credentials.</div>
            <?php endif; ?>
        </div>
    </article>

    <div class="client-detail-side">
        <article class="card">
            <div class="card-head">
                <div>
                    <h3>Assigned Employees</h3>
                    <p>Team members currently handling this account.</p>
                </div>
            </div>
            <?php if ($assignedEmployees === []): ?>
                <div class="helper-block">No employees are assigned to this client yet.</div>
            <?php else: ?>
                <div class="stack-list">
                    <?php foreach ($assignedEmployees as $employee): ?>
                        <div class="simple-list-row">
                            <strong><?= htmlspecialchars($employee['name']) ?></strong>
                            <span><?= htmlspecialchars($employee['email']) ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>

        <article class="card">
            <div class="card-head">
                <div>
                    <h3>Recent Calendars</h3>
                    <p>The most recent calendars linked to this client.</p>
                </div>
            </div>
            <?php if ($recentCalendars === []): ?>
                <div class="helper-block">No calendars have been created for this client yet.</div>
            <?php else: ?>
                <div class="stack-list">
                    <?php foreach ($recentCalendars as $calendar): ?>
                        <a class="simple-list-row is-link" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar&client_id=<?= (int) $client['id'] ?>">
                            <strong><?= htmlspecialchars($calendar['title']) ?></strong>
                            <span><?= htmlspecialchars(sprintf('%02d/%04d · %s', (int) $calendar['month'], (int) $calendar['year'], (string) $calendar['status'])) ?></span>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </article>
    </div>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
