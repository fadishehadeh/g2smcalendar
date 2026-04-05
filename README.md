# G2 Social Media Calendar

PHP + MySQL workspace for G2 to manage social media planning, approvals, comments, artwork delivery, notifications, and activity tracking.

## Local Setup

1. Import the schema:
   - [`database/schema.sql`](/c:/xampp/htdocs/G2%20SM%20Calendar/database/schema.sql)
2. Seed realistic demo workspace data:
   - `C:\xampp\php\php.exe database\seed_demo.php`
3. Open:
   - `http://localhost/G2%20SM%20Calendar/index.php?route=login`

## Demo Logins

- Admin: `admin@g2.local` / `password`
- Employee: `fadi@g2.local` / `password`
- Client: `client@g2.local` / `password`

## Included Modules

- Role-based auth for admin, employee, and client
- Fixed enterprise app shell with reusable sidebar, topbar, page header, and status system
- Dashboard with KPI cards, recent activity, and notifications
- Monthly content calendar with scoped post loading
- Bulk creation wizard
- Client management
- Employee management
- Employee-client assignments
- All posts table
- Approval cards
- Artwork library
- Notifications page
- Activity log
- Settings overview

## Demo Data

The demo seeder adds:
- Users: `admin`, `fadi`, `client`
- Client: `Dukhan Bank`
- One employee-client assignment
- April 2026 calendar and fresh test posts
- Unapproved approval queue items
- Comments, status history, notifications, and activity logs
- Inline-viewable dummy artwork images in `storage/uploads/private`

## Notes

- Uploads remain protected through the download controller.
- Notifications are still stored in the database for in-app alerts.
- Outbound email supports Mailjet via `.env` or server environment variables:
  - `MAIL_DRIVER=mailjet`
  - `MAIL_LOG_ONLY=false`
  - `MAIL_FROM_EMAIL=notifications@yourdomain.com`
  - `MAIL_FROM_NAME=G2 Social Calendar`
  - `MAIL_REPLY_TO_EMAIL=team@yourdomain.com`
  - `MAIL_REPLY_TO_NAME=G2 Team`
  - `MAILJET_API_KEY=your_mailjet_api_key`
  - `MAILJET_API_SECRET=your_mailjet_api_secret`
  - `APP_URL=https://your-domain.com/G2%20SM%20Calendar`
- A local `.env` loader is included, and a `.env.example` template is provided.
- Admin can trigger a live delivery check from the Settings page using `Send Test Email`.
- If Mailjet is not configured, email activity falls back to `storage/logs/mail.log`.
- Re-running `database/seed_demo.php` is safe and refreshes the seeded workspace without rebuilding the schema.
