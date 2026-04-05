<?php

$wizardSubmitLabel = $wizardSubmitLabel ?? 'Save';
$wizardSaveDraft = $wizardSaveDraft ?? true;
?>
<div class="wizard-footer">
    <button class="btn btn-secondary" type="button" data-step-back hidden>Back</button>
    <div class="wizard-footer-actions">
        <?php if ($wizardSaveDraft): ?>
            <button class="btn btn-secondary" type="submit" name="intent" value="save_draft" data-save-draft>Save Draft</button>
        <?php endif; ?>
        <button class="btn btn-primary" type="button" data-step-next>Next</button>
        <button class="btn btn-primary" type="submit" name="intent" value="submit" data-step-submit hidden><?= htmlspecialchars((string) $wizardSubmitLabel) ?></button>
    </div>
</div>
