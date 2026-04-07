<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$canCreateClient = in_array($authUser['role_name'] ?? '', ['master_admin', 'employee'], true);
$canDeleteClient = in_array($authUser['role_name'] ?? '', ['master_admin', 'employee'], true);
$pageActions = [
    ['label' => 'Client Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard.client', 'class' => 'btn-primary', 'icon' => 'plus'],
];
$title = 'Clients';
$subtitle = count($clients) . ' registered client accounts across the workspace.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="toolbar-card">
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid">
        <input type="hidden" name="route" value="clients">
        <div class="input-with-icon">
            <span class="input-icon"><?= Ui::icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Search clients, contacts, email...">
        </div>
    </form>
</section>

<?php if ($clients === []): ?>
    <?php
    $emptyTitle = 'No clients have been added yet';
    $emptyMessage = 'Start with the Client Onboarding Wizard. It guides you through contact details, account ownership, and workflow setup.';
    $emptyActions = [
        ['label' => 'Open Client Wizard', 'href' => $config['app']['base_url'] . '/index.php?route=wizard.client', 'class' => 'btn-primary'],
    ];
    require dirname(__DIR__) . '/partials/empty-state.php';
    ?>
<?php else: ?>
    <section class="entity-grid">
        <?php foreach ($clients as $client): ?>
            <article class="entity-card">
                <div class="entity-card-head">
                    <div class="entity-summary">
                        <span class="avatar avatar-soft"><?= htmlspecialchars(Ui::initials($client['company_name'])) ?></span>
                        <div>
                            <h3><?= htmlspecialchars($client['company_name']) ?></h3>
                            <p><?= htmlspecialchars($client['contact_name']) ?></p>
                        </div>
                    </div>
                    <span class="status-badge <?= Ui::statusClass($client['status']) ?>"><?= htmlspecialchars(ucfirst($client['status'])) ?></span>
                </div>
                <dl class="entity-meta">
                    <div><dt>Email</dt><dd><?= htmlspecialchars($client['contact_email']) ?></dd></div>
                    <div><dt>Calendars</dt><dd><?= (int) $client['calendars_count'] ?></dd></div>
                    <div><dt>Employees</dt><dd><?= (int) $client['employees_count'] ?></dd></div>
                </dl>
                <div class="entity-actions">
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=clients.show&client_id=<?= (int) $client['id'] ?>">View</a>
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.client">Add Another</a>
                    <?php if ($canDeleteClient): ?>
                        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=clients.delete" data-loading-form data-confirm-exact-name="<?= htmlspecialchars($client['company_name']) ?>" data-confirm-entity="client">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                            <input type="hidden" name="client_id" value="<?= (int) $client['id'] ?>">
                            <button class="btn btn-danger-soft" type="submit" data-loading-text="Deleting client...">Delete Client</button>
                        </form>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<?php if ($canCreateClient): ?>
<section class="card form-card" id="client-form">
    <div class="card-head">
        <div>
            <h3>Add Client</h3>
            <p>Quick create is available here. The guided Client Wizard is still the better path for a full setup flow.</p>
        </div>
    </div>
    <div class="helper-block">Recommended path: use the Client Wizard to create the client, assign the account owner, and save workflow preferences in one flow.</div>
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=clients.store" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <label><span>Company Name</span><input name="company_name" required></label>
        <label><span>Contact Name</span><input name="contact_name" required></label>
        <label><span>Contact Email</span><input type="email" name="contact_email" required></label>
        <label><span>Contact Phone</span><input name="contact_phone"></label>
        <label>
            <span>Client Login</span>
            <select name="client_user_id">
                <option value="">Select client user</option>
                <?php foreach ($clientUsers as $clientUser): ?>
                    <option value="<?= (int) $clientUser['id'] ?>"><?= htmlspecialchars($clientUser['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Create Portal Access</span>
            <select name="create_portal_access">
                <option value="0">No</option>
                <option value="1">Yes</option>
            </select>
        </label>
        <label>
            <span>Password Setup</span>
            <select name="password_mode">
                <option value="auto">Generate automatically</option>
                <option value="manual">Set manually</option>
            </select>
        </label>
        <label>
            <span>Manual Password</span>
            <input type="text" name="client_password" placeholder="Used only when password mode is manual">
        </label>
        <label>
            <span>Account Owner</span>
            <select name="account_owner_employee_id">
                <option value="">Choose responsible employee</option>
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars($employee['name'] . ' - ' . $employee['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Assigned Employees</span>
            <select name="employee_ids[]" multiple size="6">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars($employee['name'] . ' - ' . $employee['email']) ?></option>
                <?php endforeach; ?>
            </select>
        </label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label>
        <div class="helper-block">Choose one account owner, use the multi-select list to add all employees who should handle this client, and optionally create the client login with an automatic or manual password.</div>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create Client</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
