<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$pageActions = [
    ['label' => 'Add Client', 'href' => '#client-form', 'class' => 'btn-primary', 'icon' => 'plus'],
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
                <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar&client_id=<?= (int) $client['id'] ?>">View</a>
                <a class="btn btn-secondary" href="#client-form">Edit</a>
            </div>
        </article>
    <?php endforeach; ?>
</section>

<?php if (($authUser['role_name'] ?? '') === 'master_admin'): ?>
<section class="card form-card" id="client-form">
    <div class="card-head">
        <div>
            <h3>Add Client</h3>
            <p>Create a client profile and assign employees.</p>
        </div>
    </div>
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
            <span>Assigned Employees</span>
            <select name="employee_ids[]" multiple size="5">
                <?php foreach ($employees as $employee): ?>
                    <option value="<?= (int) $employee['id'] ?>"><?= htmlspecialchars($employee['name']) ?></option>
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
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create Client</button>
        </div>
    </form>
</section>
<?php endif; ?>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
