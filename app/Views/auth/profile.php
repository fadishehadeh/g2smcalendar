<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$title = 'My Profile';
$subtitle = 'Manage your personal account details and password.';
require dirname(__DIR__) . '/partials/page-header.php';
?>

<section class="settings-grid">
    <article class="card">
        <div class="card-head">
            <div>
                <h3>Account Details</h3>
                <p>Update your display name and login email.</p>
            </div>
        </div>
        <div class="detail-stats">
            <div><span>Role</span><strong><?= htmlspecialchars(Ui::roleLabel($profileUser['role_name'] ?? '')) ?></strong></div>
            <div><span>Status</span><strong><?= htmlspecialchars(ucfirst((string) ($profileUser['status'] ?? 'active'))) ?></strong></div>
            <div><span>Last Login</span><strong><?= htmlspecialchars((string) ($profileUser['last_login_at'] ?? '-')) ?></strong></div>
            <div><span>User ID</span><strong>#<?= (int) ($profileUser['id'] ?? 0) ?></strong></div>
        </div>
        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=profile.update" class="stack compact">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <label>
                <span>Name</span>
                <input type="text" name="name" value="<?= htmlspecialchars((string) ($profileUser['name'] ?? '')) ?>" required>
            </label>
            <label>
                <span>Email</span>
                <input type="email" name="email" value="<?= htmlspecialchars((string) ($profileUser['email'] ?? '')) ?>" required>
            </label>
            <div class="page-actions">
                <button class="btn btn-primary" type="submit">Save Profile</button>
            </div>
        </form>
    </article>

    <article class="card">
        <div class="card-head">
            <div>
                <h3>Change Password</h3>
                <p>Use your current password to set a new one.</p>
            </div>
        </div>
        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=profile.password" class="stack compact">
            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
            <label>
                <span>Current Password</span>
                <input type="password" name="current_password" required>
            </label>
            <label>
                <span>New Password</span>
                <input type="password" name="new_password" minlength="8" required>
            </label>
            <label>
                <span>Confirm New Password</span>
                <input type="password" name="confirm_password" minlength="8" required>
            </label>
            <div class="page-actions">
                <button class="btn btn-primary" type="submit">Change Password</button>
            </div>
        </form>
    </article>
</section>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
