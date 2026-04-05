<?php

use App\Core\Ui;

require dirname(__DIR__) . '/partials/header.php';

$roleName = $authUser['role_name'] ?? '';
$canApprove = in_array($roleName, ['master_admin', 'client'], true);
$subtitle = $item['company_name'] . ' - ' . $item['scheduled_date'];
$pageActions = [
    ['label' => 'Back to Calendar', 'href' => $config['app']['base_url'] . '/index.php?route=calendar&month=' . date('n', strtotime($item['scheduled_date'])) . '&year=' . date('Y', strtotime($item['scheduled_date'])), 'class' => 'btn-secondary', 'icon' => 'calendar'],
];
require dirname(__DIR__) . '/partials/page-header.php';

$latestPreviewFileId = $files[0]['id'] ?? null;
$latestPreviewMime = $files[0]['mime_type'] ?? '';
$latestPreviewName = $files[0]['original_name'] ?? '';
$latestPreviewVersion = (int) ($files[0]['version_number'] ?? 0);
$latestPreviewCreatedAt = $files[0]['created_at'] ?? '';
$latestPreviewUploadedBy = $files[0]['uploaded_by_name'] ?? 'System';
$latestPreviewFileSize = (int) ($files[0]['file_size'] ?? 0);
$latestPreviewPath = $files[0]['file_path'] ?? '';

$formatBytes = static function (int $bytes): string {
    if ($bytes <= 0) {
        return '-';
    }

    $units = ['B', 'KB', 'MB', 'GB'];
    $power = (int) floor(log($bytes, 1024));
    $power = max(0, min($power, count($units) - 1));
    $value = $bytes / (1024 ** $power);

    return number_format($value, $power === 0 ? 0 : 2) . ' ' . $units[$power];
};

$latestPreviewDimensions = $item['size'] ?: '-';
if ($latestPreviewPath && is_file($latestPreviewPath) && str_starts_with((string) $latestPreviewMime, 'image/')) {
    $imageInfo = @getimagesize($latestPreviewPath);
    if (is_array($imageInfo) && !empty($imageInfo[0]) && !empty($imageInfo[1])) {
        $latestPreviewDimensions = $imageInfo[0] . 'x' . $imageInfo[1];
    }
}

$timeline = [];
foreach ($files as $file) {
    $timeline[] = [
        'type' => 'version',
        'timestamp' => $file['created_at'] ?? '',
        'title' => 'Artwork v' . (int) $file['version_number'] . ' uploaded',
        'meta' => trim(($file['uploaded_by_name'] ?? 'System') . (!empty($file['uploaded_by_role']) ? ' · ' . \App\Core\Ui::roleLabel($file['uploaded_by_role']) : '')),
        'body' => ($file['original_name'] ?? 'Artwork file') . ' · ' . strtoupper(\App\Core\Ui::mediaKind($file['mime_type'] ?? 'file')),
        'href' => $config['app']['base_url'] . '/index.php?route=download.file&file_id=' . (int) $file['id'],
        'action' => 'Download version',
    ];
}

foreach ($history as $entry) {
    $timeline[] = [
        'type' => 'status',
        'timestamp' => $entry['created_at'] ?? '',
        'title' => ($entry['previous_status'] ?: 'None') . ' -> ' . $entry['new_status'],
        'meta' => ($entry['name'] ?? 'System') . ' · Status change',
        'body' => $entry['comment'] ?? '',
        'badge' => $entry['new_status'] ?? '',
    ];
}

foreach ($comments as $comment) {
    $timeline[] = [
        'type' => 'comment',
        'timestamp' => $comment['created_at'] ?? '',
        'title' => 'Comment added',
        'meta' => ($comment['name'] ?? 'System') . ' · ' . \App\Core\Ui::roleLabel($comment['role_name'] ?? ''),
        'body' => $comment['comment'] ?? '',
        'badge' => ucfirst((string) ($comment['visibility'] ?? 'shared')),
    ];
}

foreach ($activity as $entry) {
    $timeline[] = [
        'type' => 'activity',
        'timestamp' => $entry['created_at'] ?? '',
        'title' => ucwords(str_replace('_', ' ', (string) ($entry['action'] ?? 'activity'))),
        'meta' => ($entry['name'] ?? 'System') . (!empty($entry['role_name']) ? ' · ' . \App\Core\Ui::roleLabel($entry['role_name']) : ''),
        'body' => $entry['details'] ?? '',
    ];
}

usort($timeline, static function (array $a, array $b): int {
    return strcmp((string) ($b['timestamp'] ?? ''), (string) ($a['timestamp'] ?? ''));
});
?>

<section class="item-detail-page">
    <article class="card item-preview-card">
        <div class="card-head">
            <div>
                <h3>Artwork Preview</h3>
                <p>Latest uploaded artwork version for this content item.</p>
            </div>
            <span class="status-badge <?= Ui::statusClass($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span>
        </div>
        <div class="artwork-frame">
            <?php if ($latestPreviewFileId): ?>
                <button
                    class="artwork-frame-trigger"
                    type="button"
                    data-artwork-modal-open
                    aria-label="Open artwork preview details"
                >
                    <?php if (Ui::mediaKind($latestPreviewMime) === 'video'): ?>
                        <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestPreviewFileId ?>" controls playsinline preload="metadata"></video>
                    <?php else: ?>
                        <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestPreviewFileId ?>" alt="<?= htmlspecialchars($item['title']) ?>">
                    <?php endif; ?>
                </button>
            <?php else: ?>
                <div class="empty-state"><p>No artwork uploaded yet.</p></div>
            <?php endif; ?>
        </div>
        <div class="page-actions">
            <?php if ($latestPreviewFileId): ?>
                <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=download.file&file_id=<?= (int) $latestPreviewFileId ?>">Download Latest</a>
            <?php endif; ?>
        </div>
        <?php if ($latestPreviewFileId): ?>
            <div class="detail-stats compact-stats">
                <div><span>Dimensions</span><strong><?= htmlspecialchars($latestPreviewDimensions) ?></strong></div>
                <div><span>File Size</span><strong><?= htmlspecialchars($formatBytes($latestPreviewFileSize)) ?></strong></div>
                <div><span>Filename</span><strong><?= htmlspecialchars($latestPreviewName ?: 'Artwork file') ?></strong></div>
            </div>
        <?php endif; ?>

        <?php if (($authUser['role_name'] ?? '') !== 'client'): ?>
            <div class="detail-section">
                <h4>Artwork Upload</h4>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.artwork" enctype="multipart/form-data" class="stack compact">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <input type="file" name="artwork" accept="image/*,video/*,.pdf,.svg">
                    <button class="btn btn-secondary" type="submit">Upload Image or Video</button>
                </form>
                <div class="page-actions">
                    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.artwork">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="demo_kind" value="image">
                        <button class="btn btn-secondary" type="submit">Use Dummy Image</button>
                    </form>
                    <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.artwork">
                        <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                        <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                        <input type="hidden" name="demo_kind" value="video">
                        <button class="btn btn-secondary" type="submit">Use Dummy Video</button>
                    </form>
                </div>
            </div>
            <?php if ($roleName === 'employee'): ?>
                <div class="detail-section">
                    <h4>Send To Client</h4>
                    <?php if ($latestPreviewFileId && in_array($item['status'], ['Draft', 'In Progress', 'Rejected', 'Revision Requested'], true)): ?>
                        <p>Latest artwork is uploaded. Send it to the client review queue in one click.</p>
                        <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.status" class="stack compact">
                            <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                            <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                            <input type="hidden" name="status" value="For Client Approval">
                            <textarea name="comment" placeholder="Optional note for the client review email or internal handoff"></textarea>
                            <button class="btn btn-primary" type="submit">Send Artwork For Client Approval</button>
                        </form>
                    <?php elseif (!$latestPreviewFileId): ?>
                        <p class="muted">Upload artwork first, then this one-click send action will be available.</p>
                    <?php else: ?>
                        <p class="muted">This item is already in the client review flow or beyond it.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="detail-section">
                <h4>Share Reference Image</h4>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.artwork" enctype="multipart/form-data" class="stack compact">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <input type="file" name="artwork" accept="image/*" required>
                    <small class="muted">Upload an image reference or feedback visual. The employee will be able to see it on this post.</small>
                    <button class="btn btn-secondary" type="submit">Upload Reference Image</button>
                </form>
            </div>
        <?php endif; ?>
    </article>

    <article class="card item-meta-card">
        <div class="card-head">
            <div>
                <h3>Post Details</h3>
                <p>Content, approval, and delivery metadata.</p>
            </div>
        </div>
        <div class="detail-stats">
            <div><span>Client</span><strong><?= htmlspecialchars($item['company_name']) ?></strong></div>
            <div><span>Platform</span><strong><?= htmlspecialchars($item['platform']) ?></strong></div>
            <div><span>Status</span><strong><span class="status-badge <?= Ui::statusClass($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span></strong></div>
            <div><span>Post Type</span><strong><?= htmlspecialchars($item['post_type']) ?></strong></div>
            <div><span>Format</span><strong><?= htmlspecialchars($item['format'] ?: '-') ?></strong></div>
            <div><span>Size</span><strong><?= htmlspecialchars($item['size'] ?: '-') ?></strong></div>
            <div><span>Version</span><strong>v<?= (int) $item['version_number'] ?></strong></div>
        </div>

        <div class="detail-section">
            <h4>Caption</h4>
            <p><?= nl2br(htmlspecialchars((string) ($item['caption_en'] ?: 'No caption added yet.'))) ?></p>
        </div>

        <div class="detail-section">
            <h4><?= $canApprove ? 'Approval Action' : 'Workflow Action' ?></h4>
            <?php if ($roleName === 'client'): ?>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.status" class="stack compact" data-client-review-form>
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <textarea
                        name="comment"
                        data-client-review-comment
                        placeholder="Add a note for approval or rejection. Required if you reject."
                    ></textarea>
                    <small class="muted">Use this one note field for approval feedback or rejection reason. A rejection note is required.</small>
                    <div class="page-actions">
                        <button class="btn btn-primary" type="submit" name="status" value="Approved">Approve</button>
                        <button class="btn btn-danger-soft" type="submit" name="status" value="Rejected" data-client-reject>Reject</button>
                    </div>
                </form>
            <?php else: ?>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.status" class="stack compact">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <select name="status">
                        <?php if ($roleName === 'employee'): ?>
                        <?php foreach (array_filter($statuses, static fn (string $status): bool => !in_array($status, ['Approved', 'Rejected'], true)) as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <?php foreach ($statuses as $status): ?>
                            <option value="<?= htmlspecialchars($status) ?>" <?= $item['status'] === $status ? 'selected' : '' ?>><?= htmlspecialchars($status) ?></option>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </select>
                    <textarea
                        name="comment"
                        placeholder="Add approval notes, revision feedback, or internal follow-up"
                    ></textarea>
                    <button class="btn btn-primary" type="submit">Update Status</button>
                </form>
            <?php endif; ?>
        </div>

        <?php if ($roleName !== 'client'): ?>
            <div class="detail-section">
                <h4>Add Comment</h4>
                <form method="post" action="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=calendar.comment" class="stack compact">
                    <input type="hidden" name="_csrf" value="<?= htmlspecialchars(\App\Core\Csrf::token()) ?>">
                    <input type="hidden" name="item_id" value="<?= (int) $item['id'] ?>">
                    <select name="visibility">
                        <option value="shared">Shared</option>
                        <option value="internal">Internal</option>
                    </select>
                    <textarea name="comment" required placeholder="Write a comment"></textarea>
                    <button class="btn btn-secondary" type="submit">Add Comment</button>
                </form>
            </div>
        <?php endif; ?>
    </article>
</section>

<section class="item-detail-grid">
    <article class="card">
        <div class="card-head">
            <div>
                <h3>Artwork Versions</h3>
                <p>Uploaded files, uploader details, and downloadable versions.</p>
            </div>
        </div>
        <div class="file-mini-list">
            <?php foreach ($files as $file): ?>
                <a class="file-mini" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=download.file&file_id=<?= (int) $file['id'] ?>">
                    <strong>v<?= (int) $file['version_number'] ?></strong>
                    <small><?= htmlspecialchars($file['original_name']) ?></small>
                    <small><?= htmlspecialchars(($file['uploaded_by_name'] ?? 'System') . ' · ' . ($file['created_at'] ?? '')) ?></small>
                </a>
            <?php endforeach; ?>
        </div>
    </article>
    <article class="card">
        <div class="card-head">
            <div>
                <h3>Comments</h3>
                <p>Shared and internal conversation history.</p>
            </div>
        </div>
        <div class="mini-list">
            <?php foreach ($comments as $comment): ?>
                <div class="mini-comment">
                    <strong><?= htmlspecialchars($comment['name']) ?> <small><?= htmlspecialchars($comment['role_name']) ?></small></strong>
                    <p><?= nl2br(htmlspecialchars($comment['comment'])) ?></p>
                </div>
            <?php endforeach; ?>
        </div>
    </article>
    <?php if ($roleName === 'master_admin'): ?>
        <article class="card">
            <div class="card-head">
                <div>
                    <h3>Version History</h3>
                    <p>Combined audit trail for uploads, comments, approvals, and activity logs.</p>
                </div>
            </div>
            <div class="timeline-list">
                <?php foreach ($timeline as $entry): ?>
                    <div class="timeline-item timeline-<?= htmlspecialchars((string) ($entry['type'] ?? 'activity')) ?>">
                        <div class="timeline-dot"></div>
                        <div class="timeline-content">
                            <div class="timeline-head">
                                <strong><?= htmlspecialchars((string) ($entry['title'] ?? 'Update')) ?></strong>
                                <?php if (!empty($entry['badge'])): ?>
                                    <span class="status-badge <?= \App\Core\Ui::statusClass((string) $entry['badge']) ?>"><?= htmlspecialchars((string) $entry['badge']) ?></span>
                                <?php endif; ?>
                            </div>
                            <?php if (!empty($entry['meta'])): ?>
                                <small><?= htmlspecialchars((string) ($entry['meta'])) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($entry['body'])): ?>
                                <p><?= nl2br(htmlspecialchars((string) $entry['body'])) ?></p>
                            <?php endif; ?>
                            <?php if (!empty($entry['timestamp'])): ?>
                                <small><?= htmlspecialchars((string) $entry['timestamp']) ?></small>
                            <?php endif; ?>
                            <?php if (!empty($entry['href'])): ?>
                                <div class="page-actions">
                                    <a class="btn btn-secondary" href="<?= htmlspecialchars((string) $entry['href']) ?>"><?= htmlspecialchars((string) ($entry['action'] ?? 'Open')) ?></a>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </article>
    <?php endif; ?>
</section>

<?php if ($latestPreviewFileId): ?>
    <div class="modal-backdrop" data-artwork-modal hidden>
        <div class="item-modal artwork-modal">
            <button class="icon-btn modal-close" type="button" data-close-artwork-modal aria-label="Close">
                <?= \App\Core\Ui::icon('close') ?>
            </button>
            <div class="item-modal-preview">
                <?php if (Ui::mediaKind($latestPreviewMime) === 'video'): ?>
                    <video src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestPreviewFileId ?>" controls playsinline preload="metadata"></video>
                <?php else: ?>
                    <img src="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestPreviewFileId ?>" alt="<?= htmlspecialchars($latestPreviewName ?: $item['title']) ?>">
                <?php endif; ?>
            </div>
            <div class="item-modal-body">
                <div class="item-modal-top">
                    <div>
                        <h3>Artwork Details</h3>
                        <p><?= htmlspecialchars($item['title']) ?></p>
                    </div>
                    <span class="status-badge <?= Ui::statusClass($item['status']) ?>"><?= htmlspecialchars($item['status']) ?></span>
                </div>
                <div class="detail-stats">
                    <div><span>Filename</span><strong><?= htmlspecialchars($latestPreviewName ?: 'Artwork file') ?></strong></div>
                    <div><span>Type</span><strong><?= htmlspecialchars(strtoupper(Ui::mediaKind($latestPreviewMime))) ?></strong></div>
                    <div><span>Dimensions</span><strong><?= htmlspecialchars($latestPreviewDimensions) ?></strong></div>
                    <div><span>File Size</span><strong><?= htmlspecialchars($formatBytes($latestPreviewFileSize)) ?></strong></div>
                    <div><span>Version</span><strong>v<?= $latestPreviewVersion ?></strong></div>
                    <div><span>Uploaded By</span><strong><?= htmlspecialchars($latestPreviewUploadedBy) ?></strong></div>
                    <div><span>Uploaded At</span><strong><?= htmlspecialchars($latestPreviewCreatedAt ?: '-') ?></strong></div>
                    <div><span>Client</span><strong><?= htmlspecialchars($item['company_name']) ?></strong></div>
                </div>
                <div class="page-actions">
                    <a class="btn btn-secondary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=preview.file&file_id=<?= (int) $latestPreviewFileId ?>" target="_blank">Open in New Tab</a>
                    <a class="btn btn-primary" href="<?= htmlspecialchars($config['app']['base_url']) ?>/index.php?route=download.file&file_id=<?= (int) $latestPreviewFileId ?>">Download</a>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php require dirname(__DIR__) . '/partials/footer.php'; ?>
