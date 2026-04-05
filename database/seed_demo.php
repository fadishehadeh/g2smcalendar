<?php

declare(strict_types=1);

$db = require __DIR__ . '/../config/database.php';

$dsn = sprintf(
    'mysql:host=%s;port=%d;dbname=%s;charset=%s',
    $db['host'],
    $db['port'],
    $db['database'],
    $db['charset']
);

$pdo = new PDO($dsn, $db['username'], $db['password'], [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);

$root = dirname(__DIR__);
$uploadDir = $root . '/storage/uploads/private';

function q(PDO $pdo, string $sql, array $params = []): void
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
}

function v(PDO $pdo, string $sql, array $params = []): mixed
{
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetchColumn();
    return $result === false ? null : $result;
}

function createSvg(string $path, string $title, string $subtitle, string $primary, string $secondary): void
{
    $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" width="1200" height="1200" viewBox="0 0 1200 1200">
  <defs>
    <linearGradient id="g" x1="0" y1="0" x2="1" y2="1">
      <stop offset="0%" stop-color="{$primary}" />
      <stop offset="100%" stop-color="{$secondary}" />
    </linearGradient>
  </defs>
  <rect width="1200" height="1200" fill="url(#g)" />
  <circle cx="980" cy="220" r="180" fill="rgba(255,255,255,0.15)" />
  <circle cx="240" cy="980" r="220" fill="rgba(255,255,255,0.10)" />
  <rect x="90" y="120" width="1020" height="960" rx="44" fill="rgba(255,255,255,0.12)" />
  <text x="120" y="280" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="64" font-weight="700">Dukhan Bank</text>
  <text x="120" y="360" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="84" font-weight="800">{$title}</text>
  <text x="120" y="440" fill="#ffe7e7" font-family="Segoe UI, Arial, sans-serif" font-size="34">{$subtitle}</text>
  <rect x="120" y="860" width="290" height="110" rx="28" fill="rgba(255,255,255,0.18)" />
  <text x="165" y="930" fill="#ffffff" font-family="Segoe UI, Arial, sans-serif" font-size="34" font-weight="700">G2 Draft Artwork</text>
</svg>
SVG;

    file_put_contents($path, $svg);
}

function downloadDemoVideo(string $path): void
{
    $sources = [
        'https://samplelib.com/lib/preview/mp4/sample-5s.mp4',
        'https://filesamples.com/samples/video/mp4/sample_640x360.mp4',
    ];

    foreach ($sources as $source) {
        $context = stream_context_create([
            'http' => ['timeout' => 12],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);
        $binary = @file_get_contents($source, false, $context);

        if ($binary !== false && strlen($binary) > 1024) {
            file_put_contents($path, $binary);
            return;
        }
    }
}

q(
    $pdo,
    "CREATE TABLE IF NOT EXISTS item_edit_history (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        calendar_item_id INT UNSIGNED NOT NULL,
        changed_by INT UNSIGNED NOT NULL,
        field_name VARCHAR(80) NOT NULL,
        old_value TEXT NULL,
        new_value TEXT NULL,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        INDEX idx_item_edit_history_item (calendar_item_id),
        INDEX idx_item_edit_history_created (created_at),
        CONSTRAINT fk_item_edit_history_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
        CONSTRAINT fk_item_edit_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

q(
    $pdo,
    "CREATE TABLE IF NOT EXISTS post_metrics (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        calendar_item_id INT UNSIGNED NOT NULL,
        metric_date DATE NOT NULL,
        reach INT UNSIGNED NOT NULL DEFAULT 0,
        engagement INT UNSIGNED NOT NULL DEFAULT 0,
        clicks INT UNSIGNED NOT NULL DEFAULT 0,
        impressions INT UNSIGNED NOT NULL DEFAULT 0,
        saves INT UNSIGNED NOT NULL DEFAULT 0,
        shares INT UNSIGNED NOT NULL DEFAULT 0,
        created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        UNIQUE KEY uniq_post_metrics_item_date (calendar_item_id, metric_date),
        INDEX idx_post_metrics_date (metric_date),
        CONSTRAINT fk_post_metrics_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
);

$artworks = [
    ['filename' => 'dukhan-campaign-1.svg', 'title' => 'Digital Banking Week', 'subtitle' => 'Campaign launch creative', 'primary' => '#8b1e1e', 'secondary' => '#d92d2a'],
    ['filename' => 'dukhan-campaign-2.svg', 'title' => 'Ramadan Savings Story', 'subtitle' => 'Story format visual', 'primary' => '#7c2d12', 'secondary' => '#ea580c'],
    ['filename' => 'dukhan-campaign-3.svg', 'title' => 'Corporate Banking Reel', 'subtitle' => 'High-end reel cover', 'primary' => '#1d4ed8', 'secondary' => '#2563eb'],
    ['filename' => 'dukhan-campaign-4.svg', 'title' => 'Mobile App Feature', 'subtitle' => 'Feature highlight carousel', 'primary' => '#0f766e', 'secondary' => '#14b8a6'],
];

foreach ($artworks as $artwork) {
    createSvg(
        $uploadDir . DIRECTORY_SEPARATOR . $artwork['filename'],
        $artwork['title'],
        $artwork['subtitle'],
        $artwork['primary'],
        $artwork['secondary']
    );
}

$videoAsset = $uploadDir . DIRECTORY_SEPARATOR . 'dukhan-demo-video.mp4';
if (!is_file($videoAsset) || filesize($videoAsset) < 1024) {
    downloadDemoVideo($videoAsset);
}

$roleIds = [
    'master_admin' => (int) v($pdo, "SELECT id FROM roles WHERE name = 'master_admin'"),
    'employee' => (int) v($pdo, "SELECT id FROM roles WHERE name = 'employee'"),
    'client' => (int) v($pdo, "SELECT id FROM roles WHERE name = 'client'"),
];

q($pdo, 'SET FOREIGN_KEY_CHECKS=0');
foreach ([
    'download_logs',
    'notifications',
    'post_metrics',
    'item_status_history',
    'item_edit_history',
    'item_comments',
    'item_files',
    'activity_logs',
    'calendar_items',
    'calendars',
    'employee_client_assignments',
    'clients',
] as $table) {
    q($pdo, 'DELETE FROM ' . $table);
}
q($pdo, "DELETE FROM users WHERE email <> 'admin@g2.local'");
q($pdo, 'SET FOREIGN_KEY_CHECKS=1');

$password = password_hash('password', PASSWORD_DEFAULT);

q(
    $pdo,
    "UPDATE users SET name = 'admin', password = :password, status = 'active' WHERE email = 'admin@g2.local'",
    ['password' => $password]
);

$adminId = (int) v($pdo, "SELECT id FROM users WHERE email = 'admin@g2.local'");

q(
    $pdo,
    'INSERT INTO users (role_id, name, email, password, status) VALUES (:role_id, :name, :email, :password, :status)',
    ['role_id' => $roleIds['employee'], 'name' => 'fadi', 'email' => 'fadi@g2.local', 'password' => $password, 'status' => 'active']
);
$employeeId = (int) v($pdo, "SELECT id FROM users WHERE email = 'fadi@g2.local'");

q(
    $pdo,
    'INSERT INTO users (role_id, name, email, password, status) VALUES (:role_id, :name, :email, :password, :status)',
    ['role_id' => $roleIds['client'], 'name' => 'client', 'email' => 'client@g2.local', 'password' => $password, 'status' => 'active']
);
$clientUserId = (int) v($pdo, "SELECT id FROM users WHERE email = 'client@g2.local'");

q(
    $pdo,
    'INSERT INTO clients (company_name, contact_name, contact_email, client_user_id, status) VALUES (:company_name, :contact_name, :contact_email, :client_user_id, :status)',
    [
        'company_name' => 'Dukhan Bank',
        'contact_name' => 'client',
        'contact_email' => 'client@g2.local',
        'client_user_id' => $clientUserId,
        'status' => 'active',
    ]
);
$clientId = (int) v($pdo, "SELECT id FROM clients WHERE company_name = 'Dukhan Bank'");

q(
    $pdo,
    'INSERT INTO employee_client_assignments (employee_user_id, client_id) VALUES (:employee_user_id, :client_id)',
    ['employee_user_id' => $employeeId, 'client_id' => $clientId]
);

q(
    $pdo,
    'INSERT INTO calendars (title, client_id, assigned_employee_id, month, year, status, created_by)
     VALUES (:title, :client_id, :assigned_employee_id, :month, :year, :status, :created_by)',
    [
        'title' => 'April 2026 Content Plan',
        'client_id' => $clientId,
        'assigned_employee_id' => $employeeId,
        'month' => 4,
        'year' => 2026,
        'status' => 'active',
        'created_by' => $adminId,
    ]
);
$calendarId = (int) v($pdo, 'SELECT id FROM calendars WHERE client_id = :client_id LIMIT 1', ['client_id' => $clientId]);

$items = [
    ['date' => '2026-04-03', 'platform' => 'Instagram', 'title' => 'Dukhan App Launch Reel', 'post_type' => 'Reel', 'format' => 'Video', 'size' => '9:16', 'campaign' => 'App Launch', 'status' => 'Pending Approval', 'artwork' => is_file($videoAsset) ? 'dukhan-demo-video.mp4' : 'dukhan-campaign-1.svg'],
    ['date' => '2026-04-06', 'platform' => 'Instagram', 'title' => 'Ramadan Savings Story Set', 'post_type' => 'Story', 'format' => 'Image', 'size' => '9:16', 'campaign' => 'Ramadan Savings', 'status' => 'Draft', 'artwork' => 'dukhan-campaign-2.svg'],
    ['date' => '2026-04-09', 'platform' => 'Facebook', 'title' => 'Corporate Services Highlight', 'post_type' => 'Post', 'format' => 'Image', 'size' => '1080x1080', 'campaign' => 'Corporate Banking', 'status' => 'In Progress', 'artwork' => 'dukhan-campaign-3.svg'],
    ['date' => '2026-04-12', 'platform' => 'YouTube', 'title' => 'Merchant Banking Explainer', 'post_type' => 'Video', 'format' => 'Video', 'size' => '16:9', 'campaign' => 'Merchant Banking', 'status' => 'Pending Approval', 'artwork' => is_file($videoAsset) ? 'dukhan-demo-video.mp4' : 'dukhan-campaign-4.svg'],
    ['date' => '2026-04-15', 'platform' => 'TikTok', 'title' => 'Mobile Transfer Quick Tips', 'post_type' => 'Video', 'format' => 'Video', 'size' => '9:16', 'campaign' => 'Mobile Banking', 'status' => 'Revision Requested', 'artwork' => is_file($videoAsset) ? 'dukhan-demo-video.mp4' : 'dukhan-campaign-1.svg'],
    ['date' => '2026-04-18', 'platform' => 'Instagram', 'title' => 'Payroll Solution Carousel', 'post_type' => 'Carousel', 'format' => 'Carousel', 'size' => '1080x1080', 'campaign' => 'Payroll Solutions', 'status' => 'Pending Approval', 'artwork' => 'dukhan-campaign-2.svg'],
    ['date' => '2026-04-21', 'platform' => 'Facebook', 'title' => 'Business Account Feature Post', 'post_type' => 'Post', 'format' => 'Image', 'size' => '1080x1080', 'campaign' => 'Business Accounts', 'status' => 'Draft', 'artwork' => 'dukhan-campaign-3.svg'],
    ['date' => '2026-04-24', 'platform' => 'Instagram', 'title' => 'Branch Experience Story', 'post_type' => 'Story', 'format' => 'Image', 'size' => '9:16', 'campaign' => 'Branch Experience', 'status' => 'Pending Approval', 'artwork' => 'dukhan-campaign-4.svg'],
];

$itemIds = [];
$fileIds = [];
foreach ($items as $index => $item) {
    q(
        $pdo,
        'INSERT INTO calendar_items (
            calendar_id, client_id, created_by, assigned_employee_id, title, platform, scheduled_date, post_type,
            format, size, caption_en, campaign, content_pillar, cta, artwork_path, version_number, status
         ) VALUES (
            :calendar_id, :client_id, :created_by, :assigned_employee_id, :title, :platform, :scheduled_date, :post_type,
            :format, :size, :caption_en, :campaign, :content_pillar, :cta, :artwork_path, :version_number, :status
         )',
        [
            'calendar_id' => $calendarId,
            'client_id' => $clientId,
            'created_by' => $employeeId,
            'assigned_employee_id' => $employeeId,
            'title' => $item['title'],
            'platform' => $item['platform'],
            'scheduled_date' => $item['date'],
            'post_type' => $item['post_type'],
            'format' => $item['format'],
            'size' => $item['size'],
            'caption_en' => 'Draft copy for ' . $item['title'],
            'campaign' => $item['campaign'],
            'content_pillar' => 'Product Awareness',
            'cta' => 'Learn More',
            'artwork_path' => str_replace('\\', '/', $uploadDir . DIRECTORY_SEPARATOR . $item['artwork']),
            'version_number' => 1,
            'status' => $item['status'],
        ]
    );

    $itemId = (int) v($pdo, 'SELECT LAST_INSERT_ID()');
    $itemIds[$item['title']] = $itemId;

    q(
        $pdo,
        'INSERT INTO item_files (calendar_item_id, version_number, original_name, stored_name, file_path, mime_type, file_size, uploaded_by)
         VALUES (:calendar_item_id, 1, :original_name, :stored_name, :file_path, :mime_type, :file_size, :uploaded_by)',
        [
            'calendar_item_id' => $itemId,
            'original_name' => $item['artwork'],
            'stored_name' => $item['artwork'],
            'file_path' => str_replace('\\', '/', $uploadDir . DIRECTORY_SEPARATOR . $item['artwork']),
            'mime_type' => str_ends_with($item['artwork'], '.mp4') ? 'video/mp4' : 'image/svg+xml',
            'file_size' => max(1, filesize($uploadDir . DIRECTORY_SEPARATOR . $item['artwork'])),
            'uploaded_by' => $employeeId,
        ]
    );

    $fileIds[$item['title']] = (int) v($pdo, 'SELECT LAST_INSERT_ID()');
}

$commentRows = [
    [$itemIds['Dukhan App Launch Reel'], $clientUserId, 'shared', 'Please keep the headline more formal and reduce the final CTA.'],
    [$itemIds['Mobile Transfer Quick Tips'], $clientUserId, 'shared', 'Revise the second frame and update the icon set.'],
    [$itemIds['Merchant Banking Explainer'], $employeeId, 'internal', 'Awaiting final compliance line before resubmission.'],
];

foreach ($commentRows as [$itemId, $userId, $visibility, $comment]) {
    q(
        $pdo,
        'INSERT INTO item_comments (calendar_item_id, user_id, visibility, comment) VALUES (:item_id, :user_id, :visibility, :comment)',
        ['item_id' => $itemId, 'user_id' => $userId, 'visibility' => $visibility, 'comment' => $comment]
    );
}

$historyRows = [
    [$itemIds['Dukhan App Launch Reel'], $employeeId, 'Draft', 'Pending Approval', 'Submitted first draft for review'],
    [$itemIds['Mobile Transfer Quick Tips'], $clientUserId, 'Pending Approval', 'Revision Requested', 'Requested visual changes'],
    [$itemIds['Merchant Banking Explainer'], $employeeId, 'Draft', 'Pending Approval', 'Awaiting client confirmation'],
];

foreach ($historyRows as [$itemId, $changedBy, $previousStatus, $newStatus, $comment]) {
    q(
        $pdo,
        'INSERT INTO item_status_history (calendar_item_id, changed_by, previous_status, new_status, comment)
         VALUES (:item_id, :changed_by, :previous_status, :new_status, :comment)',
        [
            'item_id' => $itemId,
            'changed_by' => $changedBy,
            'previous_status' => $previousStatus,
            'new_status' => $newStatus,
            'comment' => $comment,
        ]
    );
}

$notificationRows = [
    [$clientUserId, $itemIds['Dukhan App Launch Reel'], 'item_submitted', 'New post submitted for approval', 'Dukhan App Launch Reel is waiting for review.'],
    [$employeeId, $itemIds['Mobile Transfer Quick Tips'], 'client_comment', 'Client added revision feedback', 'Client requested updates on Mobile Transfer Quick Tips.'],
    [$employeeId, $itemIds['Merchant Banking Explainer'], 'item_reminder', 'Approval still pending', 'Merchant Banking Explainer is still waiting for client action.'],
];

foreach ($notificationRows as [$userId, $itemId, $type, $subject, $body]) {
    q(
        $pdo,
        'INSERT INTO notifications (user_id, calendar_item_id, type, subject, body, is_read, sent_at)
         VALUES (:user_id, :item_id, :type, :subject, :body, 0, NOW())',
        [
            'user_id' => $userId,
            'item_id' => $itemId,
            'type' => $type,
            'subject' => $subject,
            'body' => $body,
        ]
    );
}

$activityRows = [
    [$employeeId, 'item_created', 'calendar_item', $itemIds['Dukhan App Launch Reel'], 'Created Dukhan App Launch Reel'],
    [$employeeId, 'item_submitted', 'calendar_item', $itemIds['Merchant Banking Explainer'], 'Submitted Merchant Banking Explainer for approval'],
    [$clientUserId, 'comment_added', 'calendar_item', $itemIds['Mobile Transfer Quick Tips'], 'Client requested changes'],
    [$adminId, 'calendar_reviewed', 'calendar', $calendarId, 'Reviewed Dukhan Bank planning board'],
];

foreach ($activityRows as [$userId, $action, $entityType, $entityId, $details]) {
    q(
        $pdo,
        'INSERT INTO activity_logs (user_id, action, entity_type, entity_id, details, ip_address)
         VALUES (:user_id, :action, :entity_type, :entity_id, :details, :ip_address)',
        [
            'user_id' => $userId,
            'action' => $action,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'details' => $details,
            'ip_address' => '127.0.0.1',
        ]
    );
}

$editRows = [
    [$itemIds['Dukhan App Launch Reel'], $employeeId, 'Caption (EN)', 'Draft copy for Dukhan App Launch Reel', 'Refined launch copy with stronger hook'],
    [$itemIds['Merchant Banking Explainer'], $employeeId, 'Scheduled Date', '2026-04-11', '2026-04-12'],
    [$itemIds['Mobile Transfer Quick Tips'], $employeeId, 'Platform', 'Instagram', 'TikTok'],
];

foreach ($editRows as [$itemId, $changedBy, $fieldName, $oldValue, $newValue]) {
    q(
        $pdo,
        'INSERT INTO item_edit_history (calendar_item_id, changed_by, field_name, old_value, new_value)
         VALUES (:item_id, :changed_by, :field_name, :old_value, :new_value)',
        [
            'item_id' => $itemId,
            'changed_by' => $changedBy,
            'field_name' => $fieldName,
            'old_value' => $oldValue,
            'new_value' => $newValue,
        ]
    );
}

foreach ($items as $index => $item) {
    $itemId = $itemIds[$item['title']];
    $seed = ($index + 1) * 41;
    $reach = 1400 + $seed * 9;
    $engagement = 120 + $seed;
    $clicks = 32 + ($seed % 37);
    $impressions = (int) round($reach * 1.6);
    $saves = (int) round($engagement * 0.2);
    $shares = (int) round($engagement * 0.12);

    q(
        $pdo,
        'INSERT INTO post_metrics (calendar_item_id, metric_date, reach, engagement, clicks, impressions, saves, shares)
         VALUES (:item_id, :metric_date, :reach, :engagement, :clicks, :impressions, :saves, :shares)',
        [
            'item_id' => $itemId,
            'metric_date' => $item['date'],
            'reach' => $reach,
            'engagement' => $engagement,
            'clicks' => $clicks,
            'impressions' => $impressions,
            'saves' => $saves,
            'shares' => $shares,
        ]
    );
}

echo "Single-client Dukhan Bank demo workspace seeded.\n";
