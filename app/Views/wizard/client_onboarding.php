<?php

require dirname(__DIR__) . '/partials/header.php';

$draft = $draft ?? [];
$turnaroundOptions = ['12 hours', '24 hours', '48 hours', '72 hours', '96 hours', '120 hours'];
$subtitle = 'Add a client in a guided flow with account ownership, workflow expectations, and optional portal access.';
$pageActions = [
    ['label' => 'Back to Clients', 'href' => $config['app']['base_url'] . '/index.php?route=clients', 'class' => 'btn-secondary', 'icon' => 'left'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$wizardSteps = ['Contact Details', 'Team Assignment', 'Workflow Preferences', 'Review'];
$wizardCurrentStep = 1;
require dirname(__DIR__) . '/partials/wizard-stepper.php';
?>

<section class="wizard-shell" data-flow-wizard data-wizard-type="client-onboarding">
    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=wizard.client.store" class="card form-card wizard-form" data-wizard-form enctype="multipart/form-data">
        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">

        <section class="wizard-panel is-active" data-step-panel="1">
            <div class="card-head">
                <div>
                    <h3>Step 1. Add the client contact</h3>
                    <p>Keep this simple. Use the main person the team speaks with most often.</p>
                </div>
            </div>
            <div class="form-grid">
                <label><span>Company / Client Name</span><input type="text" name="company_name" value="<?= htmlspecialchars((string) ($draft['company_name'] ?? '')) ?>" placeholder="Acme Group" required></label>
                <label><span>Contact Person</span><input type="text" name="contact_name" value="<?= htmlspecialchars((string) ($draft['contact_name'] ?? '')) ?>" placeholder="Sarah Khalil" required></label>
                <label><span>Email</span><input type="email" name="contact_email" value="<?= htmlspecialchars((string) ($draft['contact_email'] ?? '')) ?>" placeholder="sarah@client.com" required></label>
                <label><span>Phone</span><input type="text" name="contact_phone" value="<?= htmlspecialchars((string) ($draft['contact_phone'] ?? '')) ?>" placeholder="+961..."></label>
                <label><span>Logo</span><input type="file" name="logo" accept="image/*"></label>
                <label>
                    <span>Existing Client Login</span>
                    <select name="client_user_id">
                        <option value="">Create or link later</option>
                        <?php foreach ($clientUsers as $clientUser): ?>
                            <option value="<?= (int) $clientUser['id'] ?>" <?= (string) ($draft['client_user_id'] ?? '') === (string) $clientUser['id'] ? 'selected' : '' ?>><?= htmlspecialchars($clientUser['name'] . ' - ' . $clientUser['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="selection-chip-row">
                <label class="selection-chip"><input type="checkbox" name="create_portal_access" value="1" <?= !empty($draft['create_portal_access']) ? 'checked' : '' ?>><span>Create portal access for this contact</span></label>
                <label class="selection-chip"><input type="checkbox" name="send_welcome_email" value="1" <?= !empty($draft['send_welcome_email']) ? 'checked' : '' ?>><span>Send welcome email after create</span></label>
            </div>
            <div class="form-grid">
                <label>
                    <span>Password Setup</span>
                    <select name="password_mode">
                        <option value="auto" <?= (($draft['password_mode'] ?? 'auto') === 'auto') ? 'selected' : '' ?>>Generate automatically</option>
                        <option value="manual" <?= (($draft['password_mode'] ?? '') === 'manual') ? 'selected' : '' ?>>Set manually</option>
                    </select>
                </label>
                <label>
                    <span>Manual Password</span>
                    <input type="text" name="client_password" value="<?= htmlspecialchars((string) ($draft['client_password'] ?? '')) ?>" placeholder="Used only when password mode is manual">
                </label>
            </div>
            <div class="helper-block">If portal access is enabled and no existing client login is linked, the system will create the client account using either an auto-generated password or the manual password you enter.</div>
        </section>

        <section class="wizard-panel" data-step-panel="2">
            <div class="card-head">
                <div>
                    <h3>Step 2. Assign the team</h3>
                    <p>Choose who handles this client day to day. The account owner is the main responsible person.</p>
                </div>
            </div>
            <div class="form-grid">
                <label>
                    <span>Account Owner</span>
                    <select name="account_owner_employee_id">
                        <option value="">Choose responsible employee</option>
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>" <?= (string) ($draft['account_owner_employee_id'] ?? '') === (string) $employee['id'] ? 'selected' : '' ?>><?= htmlspecialchars($employee['name'] . ' - ' . $employee['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <label>
                    <span>Status</span>
                    <select name="status">
                        <option value="active" <?= (($draft['status'] ?? 'active') === 'active') ? 'selected' : '' ?>>Active</option>
                        <option value="inactive" <?= (($draft['status'] ?? '') === 'inactive') ? 'selected' : '' ?>>Inactive</option>
                    </select>
                </label>
                <label class="field-span-2">
                    <span>Assigned Employees</span>
                    <select name="employee_ids[]" multiple size="8">
                        <?php foreach ($employees as $employee): ?>
                            <option value="<?= (int) $employee['id'] ?>" <?= in_array((string) $employee['id'], array_map('strval', (array) ($draft['employee_ids'] ?? [])), true) ? 'selected' : '' ?>><?= htmlspecialchars($employee['name'] . ' - ' . $employee['email']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <div class="helper-block">Pick one account owner from the dropdown, then use the multi-select list to assign additional employees to this client.</div>
        </section>

        <section class="wizard-panel" data-step-panel="3">
            <div class="card-head">
                <div>
                    <h3>Step 3. Set workflow preferences</h3>
                    <p>These notes help the team follow the client’s preferred review style from day one.</p>
                </div>
            </div>
            <div class="form-grid">
                <label><span>Default Workflow Preferences</span><input type="text" name="workflow_preferences" value="<?= htmlspecialchars((string) ($draft['workflow_preferences'] ?? 'Standard review + revision cycle')) ?>" placeholder="Standard review + revision cycle"></label>
                <label>
                    <span>Approval Turnaround Expectation</span>
                    <select name="approval_turnaround">
                        <?php foreach ($turnaroundOptions as $option): ?>
                            <option value="<?= htmlspecialchars($option) ?>" <?= (($draft['approval_turnaround'] ?? '48 hours') === $option) ? 'selected' : '' ?>><?= htmlspecialchars($option) ?></option>
                        <?php endforeach; ?>
                    </select>
                </label>
            </div>
            <label><span>Brand Notes</span><textarea name="brand_notes" placeholder="Tone, visual considerations, or reminders"><?= htmlspecialchars((string) ($draft['brand_notes'] ?? '')) ?></textarea></label>
            <label><span>Naming Conventions</span><textarea name="naming_conventions" placeholder="How this client likes calendars, campaigns, or files named"><?= htmlspecialchars((string) ($draft['naming_conventions'] ?? '')) ?></textarea></label>
        </section>

        <section class="wizard-panel" data-step-panel="4">
            <div class="card-head">
                <div>
                    <h3>Step 4. Final review</h3>
                    <p>Check the client summary, then create the record and move into assignment or planning.</p>
                </div>
            </div>
            <div class="summary-grid" data-summary-root></div>
        </section>

        <?php $wizardSubmitLabel = 'Create Client'; $wizardSaveDraft = true; require dirname(__DIR__) . '/partials/wizard-footer.php'; ?>
    </form>
</section>

<script type="application/json" data-wizard-draft="client-onboarding"><?= json_encode($draft, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?></script>
<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
