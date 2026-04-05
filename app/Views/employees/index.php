<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = count($employees) . ' team members in the agency workspace.';
$pageActions = [
    ['label' => 'Manage Assignments', 'href' => $config['app']['base_url'] . '/index.php?route=assignments', 'class' => 'btn-secondary', 'icon' => 'assignments'],
    ['label' => 'Add Employee', 'href' => '#employee-form', 'class' => 'btn-primary', 'icon' => 'plus'],
];
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="toolbar-card">
    <form method="get" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php" class="toolbar-grid">
        <input type="hidden" name="route" value="employees">
        <div class="input-with-icon">
            <span class="input-icon"><?= Ui::icon('search') ?></span>
            <input type="text" name="search" value="<?= htmlspecialchars((string) $search) ?>" placeholder="Search employees, email...">
        </div>
    </form>
</section>

<?php if ($employees === []): ?>
    <?php
    $emptyTitle = 'No employees have been added yet';
    $emptyMessage = 'Create the first employee account, then assign clients and active calendars from the assignment screen.';
    $emptyActions = [
        ['label' => 'Add Employee', 'href' => '#employee-form', 'class' => 'btn-primary'],
    ];
    require dirname(__DIR__) . '/partials/empty-state.php';
    ?>
<?php else: ?>
    <section class="entity-grid">
        <?php foreach ($employees as $employee): ?>
            <article class="entity-card">
                <div class="entity-card-head">
                    <div class="entity-summary">
                        <span class="avatar avatar-soft"><?= htmlspecialchars(Ui::initials($employee['name'])) ?></span>
                        <div>
                            <h3><?= htmlspecialchars($employee['name']) ?></h3>
                            <p><?= htmlspecialchars(Ui::roleLabel($employee['role_name'])) ?></p>
                        </div>
                    </div>
                    <span class="status-badge <?= Ui::statusClass($employee['status']) ?>"><?= htmlspecialchars(ucfirst($employee['status'])) ?></span>
                </div>
                <dl class="entity-meta">
                    <div><dt>Email</dt><dd><?= htmlspecialchars($employee['email']) ?></dd></div>
                    <div><dt>Clients</dt><dd><?= (int) $employee['clients_count'] ?></dd></div>
                    <div><dt>Active Tasks</dt><dd><?= (int) $employee['active_tasks'] ?></dd></div>
                </dl>
                <div class="pill-row">
                    <?php foreach (array_slice($employee['assigned_clients'], 0, 4) as $client): ?>
                        <span class="pill"><?= htmlspecialchars($client['company_name']) ?></span>
                    <?php endforeach; ?>
                </div>
                <div class="entity-actions">
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=assignments">Assignments</a>
                    <a class="btn btn-secondary" href="#employee-form">Quick Add</a>
                </div>
            </article>
        <?php endforeach; ?>
    </section>
<?php endif; ?>

<section class="card form-card" id="employee-form">
    <div class="card-head">
        <div>
            <h3>Add Employee</h3>
            <p>Create a new agency employee account. Use this quick form for fast internal setup.</p>
        </div>
    </div>
    <div class="helper-block">After the employee is created, open Assignments to link the right clients and workload context.</div>
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=employees.store" class="form-grid">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
        <label><span>Name</span><input name="name" required></label>
        <label><span>Email</span><input type="email" name="email" required></label>
        <label><span>Password</span><input type="password" name="password" required></label>
        <label>
            <span>Status</span>
            <select name="status">
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
        </label>
        <div class="form-actions">
            <button class="btn btn-primary" type="submit">Create Employee</button>
        </div>
    </form>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
