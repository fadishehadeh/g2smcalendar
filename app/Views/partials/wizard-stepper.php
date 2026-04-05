<?php

$wizardSteps = $wizardSteps ?? [];
$wizardCurrentStep = (int) ($wizardCurrentStep ?? 1);
?>
<div class="wizard-stepper" data-wizard-stepper>
    <?php foreach ($wizardSteps as $index => $step): ?>
        <?php $stepNumber = $index + 1; ?>
        <button class="wizard-step <?= $stepNumber === $wizardCurrentStep ? 'is-active' : '' ?>" type="button" data-step-nav="<?= $stepNumber ?>">
            <span><?= $stepNumber ?></span>
            <strong><?= htmlspecialchars((string) $step) ?></strong>
        </button>
    <?php endforeach; ?>
</div>
