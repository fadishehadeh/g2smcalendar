# G2 Social Media Calendar

## 1. Purpose

G2 Social Media Calendar is a PHP + MySQL internal/client collaboration platform for planning social media content, managing approvals, sharing artwork, tracking notifications, and producing reports.

Core goals:

- Plan content by client and month.
- Create single posts or bulk-generate many posts.
- Upload artwork and maintain version history.
- Send posts to client review.
- Let clients approve or request changes.
- Track comments, status changes, edit history, downloads, notifications, and activity logs.
- Generate reports and review analytics.

## 2. User Roles

### Master Admin

Can access:

- Dashboard
- Calendar
- Clients
- Employees
- Assignments
- All Posts
- Analytics
- Approvals
- Artwork Library
- Notifications
- Activity Log
- Reports
- Integrations
- Settings
- Profile
- All wizards

Additional powers:

- Create/delete employees
- Create/delete clients
- Manage assignments
- Configure integrations
- Configure report automation
- Send test email
- Approve/review content

### Employee

Can access:

- Dashboard
- Calendar
- Clients
- All Posts
- Analytics
- Approvals
- Artwork Library
- Notifications
- Activity Log
- Profile
- Wizards
- Client detail pages

Restrictions:

- Cannot create/delete employees
- Cannot access integrations/settings/reports automation admin controls
- Cannot directly set post status to `Approved` or `Rejected`

### Client

Can access:

- Dashboard
- Calendar
- All Posts
- Analytics
- Approvals
- Artwork Library
- Notifications
- Activity Log
- Profile

Client actions:

- Review pending approvals
- Approve content
- Request changes
- Add client comments
- Upload reference images to a post

Restrictions:

- Cannot edit internal post fields
- Cannot create calendars/clients/employees
- Cannot access admin setup screens

## 3. Main Navigation

The shared shell includes:

- Dashboard
- Calendar
- Clients
- Employees
- All Posts
- Analytics
- Approvals
- Artwork Library
- Notifications
- Activity Log
- Settings
- Profile

Visibility depends on role.

## 4. Route Map

Main routes:

- `login`
- `logout`
- `profile`
- `profile.update`
- `profile.password`
- `dashboard`
- `clients`
- `clients.show`
- `clients.store`
- `clients.resend-welcome`
- `clients.send-reset-email`
- `clients.delete`
- `employees`
- `employees.store`
- `employees.delete`
- `assignments`
- `assignments.store`
- `assignments.remove`
- `calendar`
- `calendar.create`
- `calendar.item`
- `calendar.save`
- `calendar.artwork`
- `calendar.status`
- `calendar.comment`
- `calendar.ai-suggest`
- `calendar.ai-apply`
- `wizard`
- `wizard.generate`
- `wizard.calendar`
- `wizard.calendar.store`
- `wizard.client`
- `wizard.client.store`
- `wizard.approval`
- `wizard.approval.submit`
- `posts`
- `posts.bulk`
- `analytics`
- `reports`
- `reports.generate`
- `reports.download`
- `reports.dispatch`
- `reports.settings`
- `integrations`
- `integrations.save`
- `integrations.test`
- `integrations.sync`
- `approvals`
- `artwork`
- `notifications`
- `activity`
- `settings`
- `settings.test-email`
- `approval.review`
- `approval.review.submit`
- `download.file`
- `preview.file`

## 5. Core Data Entities

### Roles

Fields:

- `id`
- `name`
- `created_at`

Values:

- `master_admin`
- `employee`
- `client`

### Users

Fields:

- `role_id`
- `name`
- `email`
- `password`
- `status`
- `phone`
- `last_login_at`
- `created_at`
- `updated_at`

### Clients

Fields:

- `company_name`
- `contact_name`
- `contact_email`
- `contact_phone`
- `logo_path`
- `client_user_id`
- `account_owner_employee_id`
- `status`
- `workflow_preferences`
- `approval_turnaround`
- `brand_notes`
- `naming_conventions`
- `created_at`
- `updated_at`

### Employee Client Assignments

Fields:

- `employee_user_id`
- `client_id`
- `created_at`

### Calendars

Fields:

- `title`
- `campaign_name`
- `client_id`
- `assigned_employee_id`
- `month`
- `year`
- `status`
- `notes`
- `creation_mode`
- `posting_frequency`
- `primary_platforms`
- `approval_timeline`
- `created_by`
- `created_at`
- `updated_at`

Calendar statuses:

- `draft`
- `active`
- `completed`
- `archived`

### Calendar Items / Posts

Fields:

- `calendar_id`
- `client_id`
- `created_by`
- `assigned_employee_id`
- `title`
- `platform`
- `scheduled_date`
- `scheduled_time`
- `post_type`
- `format`
- `size`
- `caption_en`
- `caption_ar`
- `hashtags`
- `campaign`
- `content_pillar`
- `cta`
- `priority`
- `approval_route`
- `artwork_path`
- `artwork_thumbnail_path`
- `version_number`
- `status`
- `internal_notes`
- `client_notes`
- `deleted_at`
- `deleted_by`
- `created_at`
- `updated_at`

Post statuses:

- `Draft`
- `In Progress`
- `Pending Approval`
- `Approved`
- `Rejected`
- `Revision Requested`
- `Ready for Download`
- `Downloaded`
- `Published`
- `Cancelled`

Platforms:

- `Instagram`
- `Facebook`
- `TikTok`
- `YouTube`
- `X`

### Item Files

Fields:

- `calendar_item_id`
- `version_number`
- `original_name`
- `stored_name`
- `file_path`
- `mime_type`
- `file_size`
- `uploaded_by`
- `created_at`

### Item Comments

Fields:

- `calendar_item_id`
- `user_id`
- `visibility`
- `comment`
- `created_at`

Visibility values:

- `shared`
- `internal`

### Item Status History

Fields:

- `calendar_item_id`
- `changed_by`
- `previous_status`
- `new_status`
- `comment`
- `created_at`

### Item Edit History

Fields:

- `calendar_item_id`
- `changed_by`
- `field_name`
- `old_value`
- `new_value`
- `created_at`

### Post Metrics

Fields:

- `calendar_item_id`
- `metric_date`
- `reach`
- `engagement`
- `clicks`
- `impressions`
- `saves`
- `shares`
- `created_at`
- `updated_at`

### Report Runs

Fields:

- `report_type`
- `report_month`
- `report_year`
- `period_start`
- `period_end`
- `generated_by`
- `recipient_email`
- `status`
- `report_subject`
- `report_body`
- `created_at`

### Integration Sync Logs

Fields:

- `provider`
- `action`
- `status`
- `message`
- `triggered_by`
- `created_at`

### Wizard Drafts

Fields:

- `wizard_key`
- `user_id`
- `draft_title`
- `draft_payload`
- `status`
- `created_at`
- `updated_at`

### Notifications

Fields:

- `user_id`
- `calendar_item_id`
- `type`
- `subject`
- `body`
- `is_read`
- `sent_at`
- `provider`
- `provider_message_id`
- `provider_message_uuid`
- `provider_status`
- `provider_response`
- `created_at`

### Download Logs

Fields:

- `calendar_item_id`
- `item_file_id`
- `downloaded_by`
- `created_at`

### Activity Logs

Fields:

- `user_id`
- `action`
- `entity_type`
- `entity_id`
- `details`
- `ip_address`
- `created_at`

## 6. Authentication

### Login Screen

Purpose:

- Authenticate users into the workspace.

Fields:

- `email`
- `password`
- `remember`

Other UI elements:

- Forgot password link placeholder
- Quick Demo Access buttons

Demo credentials shown in repo:

- `admin@g2.local`
- `fadi@g2.local` in README
- `fadi.chehade@greydoha.com` in login screen shortcut
- `client@g2.local`

### Profile Screen

#### Account Details Form

Fields:

- `name`
- `email`

Read-only summary:

- role
- status
- last login
- user ID

#### Change Password Form

Fields:

- `current_password`
- `new_password`
- `confirm_password`

## 7. Dashboard

Purpose:

- Workspace overview.

KPI cards can include:

- Total Clients
- Total Employees
- Total Calendars
- Pending Approvals
- Approved
- Rejected
- Downloads
- Total Posts

Quick action cards for non-client users:

- Add Client
- Create Calendar
- Launch Bulk Wizard
- Review Approvals
- Generate Report

Dashboard content areas:

- Pending Actions
- Recent Activity
- Notifications

Client dashboard hides admin setup actions.

## 8. Clients Module

### Clients List

Purpose:

- Search clients and open client records.

Search field:

- `search`

Card data shown:

- company name
- contact name
- status
- contact email
- calendars count
- employees count

Actions:

- View
- Add Another
- Delete Client
- Open Client Wizard

### Quick Add Client Form

Fields:

- `company_name`
- `contact_name`
- `contact_email`
- `contact_phone`
- `client_user_id`
- `create_portal_access`
- `password_mode`
- `client_password`
- `account_owner_employee_id`
- `employee_ids[]`
- `status`

Behavior:

- Can create a linked client login
- Can generate or manually set password
- Can assign account owner
- Can assign multiple employees

### Client Detail Page

Purpose:

- View one client in detail.

Main information shown:

- company name
- contact name
- contact email
- contact phone
- client login email
- account owner
- calendars count
- employees count
- status

Actions:

- Back to Clients
- Open Calendar
- Send Welcome Email Again
- Send Password Reset Email

Additional sections:

- assigned employees list
- recent calendars list

### Client Wizard

Route:

- `wizard.client`

Steps:

1. Contact Details
2. Team Assignment
3. Workflow Preferences
4. Review

#### Step 1. Add the client contact

Fields:

- `company_name`
- `contact_name`
- `contact_email`
- `contact_phone`
- `logo`
- `client_user_id`
- `create_portal_access`
- `send_welcome_email`
- `password_mode`
- `client_password`

#### Step 2. Assign the team

Fields:

- `account_owner_employee_id`
- `status`
- `employee_ids[]`

#### Step 3. Set workflow preferences

Fields:

- `workflow_preferences`
- `approval_turnaround`
- `brand_notes`
- `naming_conventions`

#### Step 4. Final review

Purpose:

- Review summary before create.

Wizard capabilities:

- Save draft
- Create client

## 9. Employees Module

### Employees List

Purpose:

- View/search employees and create new ones.

Search field:

- `search`

Card data shown:

- name
- role
- status
- email
- clients count
- active tasks
- assigned client pills

Actions:

- Assignments
- Quick Add
- Delete Employee

### Add Employee Form

Fields:

- `name`
- `email`
- `password`
- `status`

### Assignments Screen

Purpose:

- Connect employees to clients.

Per employee:

- shows assigned client pills
- allows removal of assignment
- allows adding another client

Assignment fields:

- `employee_user_id`
- `client_id`

Actions:

- `assignments.store`
- `assignments.remove`

## 10. Calendar Module

### Calendar Index

Purpose:

- Main monthly/weekly planning view.

Filters visible in code:

- `client_id`
- `month`
- `year`
- `view`
- `anchor_date`
- `status`
- `platform`
- `search`

Actions:

- New Post
- calendar item detail

### New Post Screen

Purpose:

- Create one standalone post in an existing calendar.

Fields:

- `client_id`
- `calendar_id`
- `title`
- `assigned_employee_id`
- `platform`
- `scheduled_date`
- `scheduled_time`
- `post_type`
- `format`
- `size`
- `campaign`
- `content_pillar`
- `cta`
- `status`
- `caption_en`
- `caption_ar`
- `hashtags`
- `internal_notes`
- `client_notes`
- `artwork`

Submit action:

- Create Post

### Post Detail Screen

Purpose:

- Full post management screen.

Main areas:

- Artwork Preview
- Post Details
- Caption display
- Edit Post
- AI Assistant
- Approval Action / Workflow Action
- Add Comment
- Client Feedback
- Artwork Versions
- timeline/activity/history sections

#### Artwork actions for admin/employee

- Upload Image or Video
- Use Dummy Image
- Use Dummy Video
- Open Submit for Approval Wizard
- Download Latest

#### Artwork action for client

- Upload Reference Image

#### Edit Post Form

Fields:

- `item_id`
- `calendar_id`
- `client_id`
- `assigned_employee_id`
- `title`
- `platform`
- `scheduled_date`
- `scheduled_time`
- `post_type`
- `format`
- `size`
- `campaign`
- `content_pillar`
- `cta`
- `status`
- `caption_en`
- `caption_ar`
- `hashtags`
- `internal_notes`
- `client_notes`

Action:

- Save Post Changes

What it does:

- Updates the existing post record
- Records edit history for changed fields
- Can change the post status
- Can trigger client approval notification if status changes into `Pending Approval`

#### AI Assistant

Actions:

- Generate Caption + Best Time
- Apply Suggestion

Output shown:

- suggested caption
- source
- suggested publish time
- rationale

#### Status Update Form

Fields:

- `item_id`
- `status`
- `comment`

Action:

- Update Status

Role behavior:

- Employee cannot set `Approved` or `Rejected`
- Client can only approve/reject through allowed path
- Client rejection requires a comment in direct status logic

#### Comment Form

Employee/admin fields:

- `item_id`
- `visibility`
- `comment`

Client behavior:

- visibility is forced to `shared`

### Artwork/Download Behavior

- Files are stored privately
- Preview goes through `preview.file`
- Download goes through `download.file`
- Downloading an approved item can move it to `Downloaded`

## 11. Approval Flows

### Employee Approval Submission Wizard

Route:

- `wizard.approval`

Steps:

1. Checklist
2. Confirm
3. Preview
4. Submit

#### Step 1. Check what is missing

Checklist items:

- Title is filled in
- Caption is added
- Client note is added
- Artwork is uploaded
- Version number is available
- Platform and date are set

#### Step 2. Confirm the handoff

Fields / confirmations:

- checkbox: latest artwork version is correct
- checkbox: title and caption text checked
- checkbox: client-facing note added if needed
- `submission_note`

#### Step 3. Preview what the client will see

Displays:

- latest artwork/video preview
- title
- platform
- date
- version
- caption
- client note

#### Step 4. Submit to client review

Result:

- sets status to `Pending Approval`
- writes status history
- notifies client

### Client Guided Review

Route:

- `approval.review`

Steps:

1. Review
2. Decision

#### Step 1. Review the content

Displays:

- artwork/video preview
- title
- platform
- date
- status
- caption
- client note

#### Step 2. Choose decision

Fields:

- `review_action` with values:
  - `approve`
  - `request_changes`
- `change_reason`
- `comment`

Quick reason options:

- Change caption
- Update artwork
- Change size
- Wrong date
- Other

Behavior:

- Approve sets post to `Approved`
- Request Changes sets post to `Revision Requested`
- Feedback is stored in status history
- Feedback can also be stored as a shared comment
- Assigned employee is notified

### Approvals Queue Screen

Purpose:

- See all items waiting in the approval flow.

Screen behavior by role:

- Admin/client: `Guided Review`
- Employee: `Open Item`

Card data:

- preview
- client
- title
- caption excerpt
- pending approval badge

## 12. Wizards

### Bulk Post Generation Wizard

Route:

- `wizard`

Steps:

1. Setup
2. Dates
3. Channels
4. Defaults
5. Preview

#### Step 1. Choose the calendar

Fields:

- `client_id`
- `calendar_id`
- `month`
- `year`
- `calendar_title`
- `assigned_employee_id`

#### Step 2. Pick dates

Fields:

- `selected_dates`
- `repeat_weekdays[]`

Behavior:

- select dates manually on calendar grid
- optionally repeat by weekday across month

#### Step 3. Choose channels and quantities

Dynamic per selected date:

- platform selection(s)
- post type per platform/date
- quantity per platform/date

The generated field names are produced dynamically from JS and submitted as:

- `platforms[date][]`
- `post_types[date][]`
- `quantities[date][]`

#### Step 4. Apply shared defaults

Fields:

- `format`
- `size`
- `campaign`
- `content_pillar`
- `cta`
- `priority`
- `approval_route`
- `posting_frequency`
- `approval_timeline`
- `caption_placeholder`
- `notes`
- `auto_attach_demo`
- `submit_after_create`

Behavior:

- auto size recommendations by selected channels
- can attach demo artwork automatically
- can auto-submit to pending approval if demo artwork is attached

#### Step 5. Preview everything

Purpose:

- show generated post count
- review generated structure before insert

Wizard capabilities:

- Save draft
- Create Posts

### Calendar Creation Wizard

Route:

- `wizard.calendar`

Steps:

1. Calendar
2. Creation Mode
3. Plan Assumptions
4. Review

#### Step 1. Calendar basics

Fields:

- `client_id`
- `month`
- `year`
- `title`
- `campaign_name`
- `notes`

#### Step 2. Choose how to start

Fields:

- `creation_mode`
- `template_key`

Creation modes:

- `blank`
- `template`
- `duplicate_previous`
- `bulk`

Template options in code:

- `monthly_standard`
- `campaign_launch`
- `always_on`

#### Step 3. Set planning assumptions

Fields:

- `posting_frequency`
- `approval_timeline`
- `primary_platforms[]`

#### Step 4. Review before create

Purpose:

- show summary before creating calendar

Wizard capabilities:

- Save draft
- Create Calendar

### Client Onboarding Wizard

Already described in Clients Module.

### Approval Submission Wizard

Already described in Approval Flows.

## 13. All Posts Screen

Purpose:

- Table view across all accessible posts.

Views:

- active
- trash

Search field:

- `search`

Bulk actions for admin/employee:

- Edit Selected
- Move to Trash
- Restore from Trash

Bulk form fields:

- `selected_ids[]`
- `bulk_action`
- `view`

Columns:

- post
- client
- platform
- date
- status

## 14. Analytics

Purpose:

- View performance metrics by month, client, and platform.

Filters:

- `month`
- `year`
- `client_id`
- `platform`

KPI outputs:

- Total Reach
- Total Engagement
- Total Clicks
- Tracked Posts

Other sections:

- Month Comparison
- Platform Performance
- Post-level analytics table

Per-post analytics columns:

- Post
- Client
- Platform
- Reach
- Engagement
- Clicks
- Status

## 15. Reports

Purpose:

- Generate downloadable weekly or monthly reports.

### Generate Report Form

Fields:

- `report_type`
- `month`
- `year`
- `client_id`
- `recipient_email`
- `send_email`

Actions:

- Generate & Download

### Automation Form

Fields:

- `weekly_enabled`
- `weekly_recipient`
- `monthly_enabled`
- `monthly_recipient`

Actions:

- Save Automation
- Dispatch Due Reports

History table columns:

- Type
- Period
- Recipient
- Status
- Generated By
- Created
- Download

## 16. Integrations

Purpose:

- Store credentials and run tests/syncs.

Per provider fields:

- `provider`
- `enabled`
- `api_key`

Actions:

- Save
- Test
- Sync Metrics

Sync log columns:

- Provider
- Action
- Status
- Message
- User
- Created

## 17. Artwork Library

Purpose:

- Search all uploaded artwork and versions.

Search field:

- `search`

Columns:

- Artwork
- Client
- File Type
- Version
- Status
- Actions

Actions:

- View
- Download
- Open related post

## 18. Notifications

Purpose:

- Show recent notification history.

Each notification shows:

- subject
- body
- unread marker
- created time
- detail link

Notification types used in code include:

- `item_submitted`
- `client_review`
- `client_comment`
- `client_upload`

## 19. Activity Log

Purpose:

- Search system/user events.

Search field:

- `search`

Columns:

- User
- Action
- Item
- Status
- Date/Time

Examples of logged actions from code:

- item_saved
- artwork_uploaded
- client_reference_uploaded
- status_changed
- comment_added
- ai_suggestion_generated
- ai_suggestion_applied
- approval_submitted_via_wizard
- client_approved_guided
- client_requested_changes_guided
- client_created
- client_created_via_wizard
- employee_created
- employee_deleted
- assignment_created
- assignment_removed
- wizard_generated_items

## 20. Settings

Purpose:

- Display workspace/system settings overview.

Sections:

- Branding
- Security
- Email Delivery
- Automation
- Provider Setup

Email Delivery details shown:

- driver
- log-only mode
- Mailjet configured/missing
- app URL

Action:

- Send Test Email

## 21. Email Features

Email-related features present:

- Client welcome email
- Client password reset email
- Client approval notification email
- Employee review outcome notifications
- Test email from settings
- Report email dispatch

Email transport:

- Mailjet when configured
- log fallback to `storage/logs/mail.log`

Important configuration values:

- `MAIL_DRIVER`
- `MAIL_LOG_ONLY`
- `MAIL_FROM_EMAIL`
- `MAIL_FROM_NAME`
- `MAIL_REPLY_TO_EMAIL`
- `MAIL_REPLY_TO_NAME`
- `MAILJET_API_KEY`
- `MAILJET_API_SECRET`
- `MAILJET_ENDPOINT`
- `APP_URL`

## 22. File Handling

Supported upload usage in code:

- Post artwork upload by employee/admin
- Client reference image upload
- Client logo upload during onboarding

Allowed examples:

- images
- videos
- PDF
- SVG

Files are protected and served through controller routes, not directly exposed.

## 23. Draft-Saving Support

Wizards can save drafts to `wizard_drafts`.

Wizard keys used:

- `bulk_posts`
- `calendar_create`
- `client_onboarding`
- `approval_submit_{item_id}`

## 24. Important Business Rules

- Employees only see clients assigned to them.
- Clients only see content for their own client record.
- Client comments are shared only.
- Employee internal comments can be internal or shared.
- Employee cannot directly approve or reject.
- Client guided review sets final client decision.
- Submitting for approval sets `Pending Approval`.
- Artwork version history is preserved.
- Duplicate recent identical notifications are suppressed.
- Downloading approved artwork can change status to `Downloaded`.
- Soft delete is used in All Posts trash flow.

## 25. Summary of All User-Facing Forms

Authentication:

- login
- profile update
- password change

Clients:

- quick create client
- client onboarding wizard
- resend welcome email
- password reset email
- delete client

Employees:

- create employee
- delete employee
- create assignment
- remove assignment

Calendars/Posts:

- create single post
- save post changes
- upload artwork
- upload client reference image
- update status
- add comment
- generate AI suggestion
- apply AI suggestion

Wizards:

- bulk post generation
- calendar creation
- client onboarding
- approval submission
- client guided review

Workspace:

- post bulk actions
- analytics filters
- report generation
- report automation settings
- dispatch due reports
- integration save/test/sync
- notifications open
- activity search
- send test email

## 26. Suggested Reading Order for Stakeholders

If the document is being used for handoff or review, read in this order:

1. Purpose
2. User Roles
3. Main Navigation
4. Clients Module
5. Calendar Module
6. Approval Flows
7. Wizards
8. Reports / Analytics
9. Integrations / Settings
10. Data Entities

