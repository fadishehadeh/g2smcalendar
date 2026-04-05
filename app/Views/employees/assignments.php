<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';
$subtitle = 'Manage employee-client assignments';
require dirname(__DIR__) . '/partials/page-header.php';
?>
<section class="assignment-stack">
    <?php foreach ($employees as $employee): ?>
        <article class="assignment-card">
            <div class="assignment-main">
                <div class="entity-summary">
                    <span class="avatar avatar-soft"><?= htmlspecialchars(Ui::initials($employee['name'])) ?></span>
                    <div>
                        <h3><?= htmlspecialchars($employee['name']) ?></h3>
                        <p><?= htmlspecialchars(Ui::roleLabel($employee['role_name'])) ?></p>
                    </div>
                </div>
                <div class="pill-row">
                    <?php foreach ($employee['assigned_clients'] as $client): ?>
                        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=assignments.remove" class="pill-form">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                            <input type="hidden" name="employee_user_id" value="<?= (int) $employee['id'] ?>">
                            <input type="hidden" name="client_id" value="<?= (int) ($client['id'] ?? 0) ?>">
                            <button class="pill pill-removable" type="submit"><?= htmlspecialchars($client['company_name']) ?><span>&times;</span></button>
                        </form>
                    <?php endforeach; ?>
                </div>
            </div>
            <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=assignments.store" class="assignment-action">
                <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                <input type="hidden" name="employee_user_id" value="<?= (int) $employee['id'] ?>">
                <select name="client_id">
                    <?php foreach ($clients as $client): ?>
                        <option value="<?= (int) $client['id'] ?>"><?= htmlspecialchars($client['company_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                <button class="btn btn-secondary" type="submit">Add Client</button>
            </form>
        </article>
    <?php endforeach; ?>
</section>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
