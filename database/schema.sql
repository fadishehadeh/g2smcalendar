CREATE DATABASE IF NOT EXISTS g2_social_media_calendar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE g2_social_media_calendar;

CREATE TABLE roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) NOT NULL UNIQUE,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    role_id INT UNSIGNED NOT NULL,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    phone VARCHAR(50) NULL,
    last_login_at DATETIME NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_users_role FOREIGN KEY (role_id) REFERENCES roles(id)
);

CREATE TABLE clients (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    company_name VARCHAR(150) NOT NULL,
    contact_name VARCHAR(120) NOT NULL,
    contact_email VARCHAR(190) NOT NULL,
    contact_phone VARCHAR(50) NULL,
    logo_path VARCHAR(255) NULL,
    client_user_id INT UNSIGNED NULL,
    status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_clients_user FOREIGN KEY (client_user_id) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE employee_client_assignments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    employee_user_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_employee_client (employee_user_id, client_id),
    CONSTRAINT fk_assignment_employee FOREIGN KEY (employee_user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_assignment_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE
);

CREATE TABLE calendars (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    assigned_employee_id INT UNSIGNED NOT NULL,
    month TINYINT UNSIGNED NOT NULL,
    year SMALLINT UNSIGNED NOT NULL,
    status ENUM('draft', 'active', 'completed', 'archived') NOT NULL DEFAULT 'draft',
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_calendar_client_month (client_id, month, year),
    CONSTRAINT fk_calendars_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_calendars_employee FOREIGN KEY (assigned_employee_id) REFERENCES users(id),
    CONSTRAINT fk_calendars_creator FOREIGN KEY (created_by) REFERENCES users(id)
);

CREATE TABLE calendar_items (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_id INT UNSIGNED NOT NULL,
    client_id INT UNSIGNED NOT NULL,
    created_by INT UNSIGNED NOT NULL,
    assigned_employee_id INT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    platform VARCHAR(50) NOT NULL,
    scheduled_date DATE NOT NULL,
    scheduled_time TIME NULL,
    post_type VARCHAR(50) NOT NULL,
    format VARCHAR(50) NULL,
    size VARCHAR(50) NULL,
    caption_en TEXT NULL,
    caption_ar TEXT NULL,
    hashtags TEXT NULL,
    campaign VARCHAR(100) NULL,
    content_pillar VARCHAR(100) NULL,
    cta VARCHAR(100) NULL,
    artwork_path VARCHAR(255) NULL,
    artwork_thumbnail_path VARCHAR(255) NULL,
    version_number INT UNSIGNED NOT NULL DEFAULT 1,
    status VARCHAR(50) NOT NULL DEFAULT 'Draft',
    internal_notes TEXT NULL,
    client_notes TEXT NULL,
    deleted_at DATETIME NULL,
    deleted_by INT UNSIGNED NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_items_date (scheduled_date),
    INDEX idx_items_status (status),
    INDEX idx_items_platform (platform),
    CONSTRAINT fk_items_calendar FOREIGN KEY (calendar_id) REFERENCES calendars(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_client FOREIGN KEY (client_id) REFERENCES clients(id) ON DELETE CASCADE,
    CONSTRAINT fk_items_creator FOREIGN KEY (created_by) REFERENCES users(id),
    CONSTRAINT fk_items_employee FOREIGN KEY (assigned_employee_id) REFERENCES users(id),
    CONSTRAINT fk_items_deleted_by FOREIGN KEY (deleted_by) REFERENCES users(id) ON DELETE SET NULL
);

CREATE TABLE item_files (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_item_id INT UNSIGNED NOT NULL,
    version_number INT UNSIGNED NOT NULL,
    original_name VARCHAR(190) NOT NULL,
    stored_name VARCHAR(190) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    mime_type VARCHAR(120) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    uploaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_item_files_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_files_user FOREIGN KEY (uploaded_by) REFERENCES users(id)
);

CREATE TABLE item_comments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_item_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    visibility ENUM('shared', 'internal') NOT NULL DEFAULT 'shared',
    comment TEXT NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_item_comments_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_item_comments_user FOREIGN KEY (user_id) REFERENCES users(id)
);

CREATE TABLE item_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_item_id INT UNSIGNED NOT NULL,
    changed_by INT UNSIGNED NOT NULL,
    previous_status VARCHAR(50) NULL,
    new_status VARCHAR(50) NOT NULL,
    comment TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_status_history_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_status_history_user FOREIGN KEY (changed_by) REFERENCES users(id)
);

CREATE TABLE item_edit_history (
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
);

CREATE TABLE post_metrics (
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
);

CREATE TABLE notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    calendar_item_id INT UNSIGNED NULL,
    type VARCHAR(80) NOT NULL,
    subject VARCHAR(190) NOT NULL,
    body TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    sent_at DATETIME NULL,
    provider VARCHAR(40) NULL,
    provider_message_id VARCHAR(80) NULL,
    provider_message_uuid VARCHAR(80) NULL,
    provider_status VARCHAR(40) NULL,
    provider_response TEXT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notifications_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_notifications_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE SET NULL
);

CREATE TABLE download_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    calendar_item_id INT UNSIGNED NOT NULL,
    item_file_id INT UNSIGNED NOT NULL,
    downloaded_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_downloads_item FOREIGN KEY (calendar_item_id) REFERENCES calendar_items(id) ON DELETE CASCADE,
    CONSTRAINT fk_downloads_file FOREIGN KEY (item_file_id) REFERENCES item_files(id) ON DELETE CASCADE,
    CONSTRAINT fk_downloads_user FOREIGN KEY (downloaded_by) REFERENCES users(id)
);

CREATE TABLE activity_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NULL,
    action VARCHAR(120) NOT NULL,
    entity_type VARCHAR(80) NOT NULL,
    entity_id INT UNSIGNED NULL,
    details TEXT NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_activity_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
);

INSERT INTO roles (name) VALUES ('master_admin'), ('employee'), ('client')
ON DUPLICATE KEY UPDATE name = VALUES(name);

INSERT INTO users (role_id, name, email, password, status)
SELECT r.id, 'G2 Admin', 'admin@g2.local', '$2y$10$VKliivvEGjLq2/NN56TtROvawXx/pAzhg/JybYHjyWkkMUlpX0N.e', 'active'
FROM roles r
WHERE r.name = 'master_admin'
AND NOT EXISTS (
    SELECT 1 FROM users WHERE email = 'admin@g2.local'
);
