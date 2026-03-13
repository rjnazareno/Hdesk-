# HDesk Ticketing System — Full System Documentation

**Version:** 2.0.0  
**Application Name:** HDesk — Multi-Department Service Portal  
**URL:** https://hdesk.resourcestaffonline.com  
**Timezone:** Asia/Manila (UTC+8)  
**Last Updated:** June 2025

---

## Table of Contents

1. [System Overview](#1-system-overview)
2. [Technology Stack](#2-technology-stack)
3. [System Architecture](#3-system-architecture)
4. [Database Schema](#4-database-schema)
5. [Authentication & Authorization](#5-authentication--authorization)
6. [Role-Based Access Control (RBAC)](#6-role-based-access-control-rbac)
7. [Models (Data Layer)](#7-models-data-layer)
8. [Controllers (Business Logic)](#8-controllers-business-logic)
9. [Views (Presentation Layer)](#9-views-presentation-layer)
10. [API Endpoints](#10-api-endpoints)
11. [Ticket Lifecycle & Workflow](#11-ticket-lifecycle--workflow)
12. [SLA (Service Level Agreement) System](#12-sla-service-level-agreement-system)
13. [Category Hierarchy & Priority Mapping](#13-category-hierarchy--priority-mapping)
14. [Notification System](#14-notification-system)
15. [Email System](#15-email-system)
16. [Push Notifications (Firebase)](#16-push-notifications-firebase)
17. [External Integrations](#17-external-integrations)
18. [Reports & Exports](#18-reports--exports)
19. [Configuration Reference](#19-configuration-reference)
20. [File Structure Reference](#20-file-structure-reference)
21. [User Flows](#21-user-flows)
22. [Screenshots Guide](#22-screenshots-guide)

---

## 1. System Overview

HDesk is a custom-built IT Help Desk Ticketing System designed for Resource Staff Online. It manages hardware, software, network, and HR-related support tickets from both company employees and internal IT/HR staff.

### Key Features

| Feature | Description |
|---------|-------------|
| **Multi-Department Support** | Routes tickets to IT and HR departments |
| **Dual User System** | Separate tables for employees and IT/admin staff |
| **Ticket Queue (Bucket Model)** | Staff grab/release tickets from a shared pool |
| **SLA Tracking** | Business-hours-aware deadline monitoring |
| **RBAC** | 4-level role hierarchy (Super Admin → Dept Admin → IT Staff → Employee) |
| **Real-time Notifications** | In-app, email (PHPMailer), and push (Firebase FCM) |
| **Auto-Priority** | Category-to-priority mapping sets ticket priority automatically |
| **3-Level Category Hierarchy** | Parent → Subcategory → Specific Concern |
| **Excel Export** | PhpSpreadsheet-powered ticket and summary reports |
| **Password Reset** | Secure token-based password recovery |
| **Harley HRIS Sync** | Webhook + cron integration with external HR system |
| **Profile Management** | Profile photo upload, password change, personal info edit |

### System Users

| User Type | Table | Roles | Dashboard |
|-----------|-------|-------|-----------|
| **IT Staff / Admin** | `users` | `it_staff`, `admin` | `admin/dashboard.php` |
| **Employee** | `employees` | `employee`, `internal` | `customer/dashboard.php` |
| **Employee w/ Admin Rights** | `employees` | `internal` + `admin_rights_hdesk` | `admin/dashboard.php` |

---

## 2. Technology Stack

| Component | Technology | Version |
|-----------|-----------|---------|
| **Backend** | PHP | 7.4+ |
| **Database** | MySQL | 5.7+ (utf8mb4) |
| **Frontend CSS** | TailwindCSS | CDN or local minified |
| **Icons** | Font Awesome | 6.4.0 |
| **Charts** | Chart.js | 4.4.0 |
| **Email** | PHPMailer | via Composer |
| **Excel Export** | PhpSpreadsheet | via Composer |
| **Push Notifications** | Firebase Admin SDK | via Composer |
| **Server** | Apache (XAMPP local / Hostinger prod) | — |
| **Package Manager** | Composer | — |

### External Services

| Service | Purpose | Configuration |
|---------|---------|---------------|
| **Gmail SMTP** | Email notifications | smtp.gmail.com:587 (TLS) |
| **Firebase Cloud Messaging** | Push notifications | Service account JSON |
| **Harley HRIS** | Employee data sync | MySQL direct connection |
| **UI Avatars API** | Default profile avatars | ui-avatars.com |

---

## 3. System Architecture

### MVC Pattern (Custom Implementation)

```
┌─────────────────────────────────────────────────────┐
│                   Entry Points                       │
│  login.php → login_process.php → LoginController     │
│  index.php → redirects based on session              │
│  admin/dashboard.php → DashboardController           │
│  customer/dashboard.php → CustomerDashboardController│
│  webhook_employee_sync.php → API key auth            │
└──────────────────────┬──────────────────────────────┘
                       │
              ┌────────▼────────┐
              │   Controllers   │
              │  (Business Logic)│
              └────────┬────────┘
                       │
          ┌────────────┼────────────┐
          │            │            │
   ┌──────▼──────┐ ┌──▼──┐ ┌──────▼──────┐
   │   Models    │ │Auth │ │    Views    │
   │ (Data Layer)│ │RBAC │ │(Templates) │
   └──────┬──────┘ └─────┘ └─────────────┘
          │
   ┌──────▼──────┐
   │   MySQL     │
   │ (PDO/utf8mb4)│
   └─────────────┘
```

### Request Flow

1. Browser hits entry point (e.g., `admin/tickets.php`)
2. Entry point requires `config/config.php` (autoloader, constants, helpers)
3. Entry point instantiates corresponding Controller
4. Controller constructor calls `Auth::requireITStaff()` or similar
5. Controller method calls Model methods for data
6. Controller calls `loadView()` with data array
7. View file renders HTML with extracted data variables
8. Layout wraps view: `header.php` → `{page}.view.php` → `footer.php`

### Autoloader

Defined in `config/config.php`:
```php
spl_autoload_register(function ($class) {
    $directories = [
        __DIR__ . '/../models/',
        __DIR__ . '/../controllers/',
        __DIR__ . '/../controllers/admin/',
        __DIR__ . '/../controllers/customer/',
        __DIR__ . '/../includes/',
    ];
    foreach ($directories as $dir) {
        $file = $dir . $class . '.php';
        if (file_exists($file)) { require_once $file; return; }
    }
});
```

### Layout System

```
views/layouts/header.php     ← HTML head, CSS, conditional nav include
├── includes/admin_nav.php   ← Admin sidebar (if IT staff/admin)
├── includes/customer_nav.php ← Employee sidebar (if employee)
views/admin/{page}.view.php  ← Page-specific content
views/layouts/footer.php     ← Common JS, helpers.js, notifications.js
```

---

## 4. Database Schema

### Entity-Relationship Overview

```
┌─────────┐       ┌───────────┐       ┌──────────┐
│  users  │◄──────│  tickets  │──────►│categories│
│(IT/Admin)│       │           │       │(hierarchy)│
└─────┬───┘       └─────┬─────┘       └──────┬───┘
      │                 │                     │
      │           ┌─────┼──────┐              │
      │           │     │      │          ┌───▼────┐
      │     ┌─────▼───┐ │ ┌───▼────┐     │ depts  │
      │     │activity │ │ │replies │     └────────┘
      │     └─────────┘ │ └────────┘
      │                 │
      │           ┌─────▼──────┐
      │           │sla_tracking│
      │           └────────────┘
      │
┌─────▼──────┐     ┌──────────────┐
│ employees  │     │notifications │
│(Company EE)│     │(dual user_id/│
└────────────┘     │ employee_id) │
                   └──────────────┘
```

### Table Definitions

#### `users` — IT Staff & Admin Accounts

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | User ID |
| `username` | VARCHAR(50) UNIQUE | Login username |
| `email` | VARCHAR(100) UNIQUE | Email address |
| `password` | VARCHAR(255) | bcrypt hash |
| `full_name` | VARCHAR(100) | Display name |
| `role` | ENUM('employee','it_staff','admin') | System role |
| `department` | VARCHAR(50) | Department assignment |
| `phone` | VARCHAR(20) | Phone number |
| `is_active` | TINYINT(1) DEFAULT 1 | Active flag |
| `role_id` | INT | FK → roles.id (RBAC) |
| `last_login_at` | TIMESTAMP | Last login timestamp |
| `fcm_token` | VARCHAR(255) | Firebase push token |
| `created_at` | TIMESTAMP | Account creation |
| `updated_at` | TIMESTAMP | Last modification |

**Indexes:** `idx_role(role)`, `idx_email(email)`

---

#### `employees` — Company Employee Accounts

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Employee ID |
| `employee_id` | VARCHAR(50) | External ID (Harley: `HRLY-{id}`) |
| `username` | VARCHAR(50) UNIQUE | Login username |
| `email` | VARCHAR(100) | Company email |
| `personal_email` | VARCHAR(100) | Personal email |
| `password` | VARCHAR(255) | bcrypt hash |
| `fname` | VARCHAR(100) | First name |
| `lname` | VARCHAR(100) | Last name |
| `company` | VARCHAR(100) | Company name |
| `position` | VARCHAR(100) | Job position |
| `contact` | VARCHAR(20) | Phone number |
| `official_sched` | VARCHAR(100) | Work schedule |
| `role` | VARCHAR(20) | `'employee'` or `'internal'` |
| `admin_rights_hdesk` | VARCHAR(20) | `'superadmin'`, `'it'`, `'hr'`, or NULL |
| `status` | VARCHAR(20) DEFAULT 'active' | `active`, `inactive`, `terminated` |
| `profile_picture` | VARCHAR(255) | Photo filename |
| `fcm_token` | VARCHAR(255) | Firebase push token |
| `created_at` | TIMESTAMP | Account creation |
| `updated_at` | TIMESTAMP | Last modification |

---

#### `tickets` — Main Ticket Data

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Ticket ID |
| `ticket_number` | VARCHAR(20) UNIQUE | Format: `TKT-YYYY-XXXX` |
| `title` | VARCHAR(200) | Ticket subject |
| `description` | TEXT | Full description |
| `category_id` | INT FK → categories.id | Issue category |
| `department_id` | INT FK → departments.id | Assigned department |
| `priority` | ENUM('low','medium','high','urgent') | Priority level |
| `admin_priority` | ENUM('low','medium','high','urgent') | Admin-overridden priority |
| `status` | ENUM('pending','open','in_progress','resolved','closed') | Current status |
| `submitter_id` | INT | ID of person who submitted |
| `submitter_type` | ENUM('employee','user') | Which table submitter is in |
| `assigned_to` | INT | ID of assigned staff |
| `assignee_type` | ENUM('user','employee') | Which table assignee is in |
| `grabbed_by` | INT | ID of staff who grabbed from queue |
| `grabbed_at` | TIMESTAMP | When ticket was grabbed |
| `resolution` | TEXT | Resolution notes |
| `attachments` | TEXT | JSON array of file paths |
| `resolved_at` | TIMESTAMP | When resolved |
| `closed_at` | TIMESTAMP | When closed |
| `created_at` | TIMESTAMP | Creation time |
| `updated_at` | TIMESTAMP | Last update |

**Indexes:** `idx_status`, `idx_priority`, `idx_submitter`, `idx_assigned`, `idx_category`, `idx_created`

**Critical Pattern — Dual User System:**
```sql
SELECT t.*,
    CASE 
        WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
        ELSE u1.full_name
    END as submitter_name
FROM tickets t
LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'
LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'
```

---

#### `categories` — Issue Categories (Hierarchical)

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Category ID |
| `department_id` | INT FK → departments.id | Department ownership |
| `parent_id` | INT FK → categories.id | Parent (NULL = top-level) |
| `name` | VARCHAR(100) | Category name |
| `description` | TEXT | Description |
| `icon` | VARCHAR(50) | Font Awesome icon |
| `color` | VARCHAR(20) | Hex color code |
| `sort_order` | INT DEFAULT 0 | Display order |
| `is_active` | TINYINT(1) DEFAULT 1 | Soft delete flag |
| `requires_fields` | TEXT | JSON custom field requirements |
| `created_at` | TIMESTAMP | Creation time |

---

#### `departments` — Department Configuration

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Department ID |
| `name` | VARCHAR(100) | Department name (e.g., "Information Technology") |
| `code` | VARCHAR(10) UNIQUE | Short code (e.g., "IT", "HR") |
| `description` | TEXT | Description |
| `icon` | VARCHAR(50) | Font Awesome icon |
| `color` | VARCHAR(20) | Hex color |
| `is_active` | TINYINT(1) DEFAULT 1 | Active flag |
| `created_at` | TIMESTAMP | Creation time |

---

#### `notifications` — In-App Notifications

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Notification ID |
| `user_id` | INT | FK → users.id (for IT staff/admin) |
| `employee_id` | INT | FK → employees.id (for employees) |
| `type` | VARCHAR(50) | Notification type |
| `title` | VARCHAR(200) | Notification title |
| `message` | TEXT | Notification body |
| `ticket_id` | INT | FK → tickets.id |
| `related_user_id` | INT | Who triggered the notification |
| `is_read` | TINYINT(1) DEFAULT 0 | Read status |
| `created_at` | TIMESTAMP | Creation time |

**Note:** Each notification targets EITHER `user_id` OR `employee_id`, never both.

---

#### `ticket_activity` — Audit Trail

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Activity ID |
| `ticket_id` | INT FK → tickets.id | Related ticket |
| `user_id` | INT | Who performed the action |
| `user_type` | VARCHAR(20) | `'user'` or `'employee'` |
| `action_type` | VARCHAR(50) | Action type (see below) |
| `old_value` | TEXT | Previous value |
| `new_value` | TEXT | New value |
| `comment` | TEXT | Optional comment |
| `created_at` | TIMESTAMP | Action time |

**Action Types:** `created`, `status_change`, `priority_change`, `assigned`, `grabbed`, `released`, `comment`, `resolved`, `closed`, `reopened`

---

#### `ticket_replies` — Conversation Thread

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Reply ID |
| `ticket_id` | INT FK → tickets.id | Related ticket |
| `user_id` | INT | Author ID |
| `user_type` | VARCHAR(20) | `'user'` or `'employee'` |
| `message` | TEXT | Reply content |
| `created_at` | TIMESTAMP | Reply time |

---

#### `sla_tracking` — SLA Deadline Tracking

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Tracking ID |
| `ticket_id` | INT FK → tickets.id UNIQUE | One SLA record per ticket |
| `response_due_at` | DATETIME | First response deadline |
| `resolution_due_at` | DATETIME | Resolution deadline |
| `first_response_at` | DATETIME | Actual first response time |
| `response_time_minutes` | INT | Actual response time |
| `response_sla_status` | VARCHAR(20) | `'met'` or `'breached'` |
| `resolution_sla_status` | VARCHAR(20) | `'met'` or `'breached'` |
| `is_business_hours` | TINYINT(1) DEFAULT 1 | Business hours SLA flag |
| `created_at` | TIMESTAMP | Create time |

---

#### `sla_policies` — SLA Policy Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Policy ID |
| `priority` | VARCHAR(20) | `low`, `medium`, `high` |
| `response_time` | INT | Response time in minutes |
| `resolution_time` | INT | Resolution time in minutes |
| `is_business_hours` | TINYINT(1) DEFAULT 1 | Business hours flag |
| `is_active` | TINYINT(1) DEFAULT 1 | Active flag |

---

#### `sla_department_policies` — Department-Specific SLA Overrides

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Override ID |
| `department_id` | INT FK → departments.id | Department |
| `priority` | VARCHAR(20) | Priority level |
| `response_time` | INT | Response time in minutes |
| `resolution_time` | INT | Resolution time in minutes |
| `is_business_hours` | TINYINT(1) DEFAULT 1 | Business hours flag |

---

#### `roles` — RBAC Role Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Role ID |
| `name` | VARCHAR(50) | Display name |
| `slug` | VARCHAR(50) UNIQUE | Identifier: `super_admin`, `dept_admin`, `it_staff`, `employee` |
| `description` | TEXT | Role description |
| `hierarchy_level` | INT | 100 (Super Admin), 50 (Dept Admin), 30 (IT Staff), 10 (Employee) |
| `is_active` | TINYINT(1) DEFAULT 1 | Active flag |
| `is_system` | TINYINT(1) DEFAULT 0 | System role (non-deletable) |

---

#### `permissions` — RBAC Permission Definitions

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Permission ID |
| `name` | VARCHAR(100) | Display name |
| `slug` | VARCHAR(100) UNIQUE | e.g., `tickets.view`, `users.create` |
| `description` | TEXT | Permission description |
| `category` | VARCHAR(50) | Group: `TICKETS`, `USERS`, `DEPARTMENTS`, `REPORTS`, `SETTINGS` |

---

#### `role_permissions` — Role ↔ Permission Mapping

| Column | Type | Description |
|--------|------|-------------|
| `role_id` | INT FK → roles.id | Role |
| `permission_id` | INT FK → permissions.id | Permission |
| **PK** | (role_id, permission_id) | Composite primary key |

---

#### `user_departments` — User ↔ Department Assignment

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Assignment ID |
| `user_id` | INT FK → users.id | User |
| `department_id` | INT FK → departments.id | Department |
| `is_primary` | TINYINT(1) DEFAULT 1 | Primary department flag |
| `assigned_by` | INT | Who assigned |
| `assigned_at` | TIMESTAMP | Assignment date |

---

#### `category_priority_map` — Auto-Priority Assignment

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Mapping ID |
| `category_id` | INT FK → categories.id UNIQUE | Category |
| `default_priority` | ENUM('low','medium','high') | Auto-assigned priority |

---

#### `password_reset_tokens` — Password Recovery

| Column | Type | Description |
|--------|------|-------------|
| `id` | INT AUTO_INCREMENT PK | Token ID |
| `email` | VARCHAR(100) | User email |
| `user_type` | VARCHAR(20) | `'user'` or `'employee'` |
| `token` | VARCHAR(64) | Secure random token |
| `expires_at` | DATETIME | Token expiration (24h) |
| `is_used` | TINYINT(1) DEFAULT 0 | Used flag |
| `created_at` | TIMESTAMP | Created time |

---

## 5. Authentication & Authorization

### Authentication Flow

```
┌──────────┐     ┌──────────────┐     ┌──────────────┐     ┌────────────┐
│login.php │────►│login_process │────►│LoginController│────►│  Auth.php  │
│  (form)  │POST │   .php       │     │  .login()    │     │  .login()  │
└──────────┘     └──────────────┘     └──────────────┘     └─────┬──────┘
                                                                  │
                                               ┌──────────────────┤
                                               │                  │
                                        ┌──────▼──────┐   ┌──────▼──────┐
                                        │ users table │   │employees tbl│
                                        │ (IT/Admin)  │   │ (Employees) │
                                        └──────┬──────┘   └──────┬──────┘
                                               │                  │
                                               └────────┬─────────┘
                                                        │
                                               ┌────────▼────────┐
                                               │ $_SESSION vars  │
                                               │ user_id, role,  │
                                               │ user_type, etc. │
                                               └─────────────────┘
```

### Login Process

1. User submits username/password on `login.php`
2. `login_process.php` delegates to `LoginController::login()`
3. `Auth::login()` checks **both** `users` and `employees` tables:
   - First tries `users` table (`User::verifyLogin()`)
   - Then tries `employees` table (`Employee::verifyLogin()`)
4. On success, sets session variables:

| Session Variable | Description |
|-----------------|-------------|
| `$_SESSION['logged_in']` | `true` |
| `$_SESSION['user_id']` | Numeric ID from respective table |
| `$_SESSION['user_type']` | `'user'` or `'employee'` |
| `$_SESSION['username']` | Login username |
| `$_SESSION['full_name']` | Display name |
| `$_SESSION['email']` | Email address |
| `$_SESSION['role']` | Role string |
| `$_SESSION['department']` | Department string |
| `$_SESSION['admin_rights']` | For employees: `'superadmin'`, `'it'`, `'hr'`, or null |

5. Redirects based on role:
   - Employees (no admin rights) → `customer/dashboard.php`
   - IT Staff → `admin/it_dashboard.php`
   - Admin / Employee with admin_rights → `admin/dashboard.php`

### Session Configuration

| Setting | Value |
|---------|-------|
| Session Lifetime | 28,800 seconds (8 hours) |
| GC Max Lifetime | 28,800 seconds |
| Cookie Secure | Auto (HTTPS detection) |
| SameSite | Lax |

### Authorization Methods (Auth.php)

| Method | Description |
|--------|-------------|
| `requireLogin()` | Redirect to login if not authenticated |
| `requireITStaff()` | Allow: `it_staff`, `admin`, employees with `admin_rights_hdesk` |
| `requireRole($role)` | Allow specific role only |
| `requireAdmin()` | Allow `admin` from users table only |
| `requireAdminOrInternal()` | Allow admin or employees with admin rights |
| `isAdmin()` | Check if current user is admin |
| `isSuperAdmin()` | Check if super admin (users admin OR employee superadmin) |
| `isEmployeeAdmin()` | Check if employee has admin rights |
| `hasAdminRights($type)` | Check specific admin rights type (`'it'`, `'hr'`, `'superadmin'`) |
| `getCurrentUser()` | Return user data array from session |

### Password Security

- Passwords hashed with `password_hash()` using `PASSWORD_DEFAULT` (bcrypt)
- Verified with `password_verify()`
- Reset tokens: 32-byte `random_bytes()` hex, 24-hour expiry
- No email enumeration on forgot password (always shows "success")

---

## 6. Role-Based Access Control (RBAC)

### Role Hierarchy

| Level | Role | Slug | Access |
|-------|------|------|--------|
| 100 | **Super Admin** | `super_admin` | Full system access, all departments |
| 50 | **Department Admin** | `dept_admin` | Manage assigned department(s) |
| 30 | **IT Staff** | `it_staff` | Handle tickets, view dashboards |
| 10 | **Employee** | `employee` | Submit and view own tickets only |

### Permission Categories

| Category | Permissions |
|----------|------------|
| **TICKETS** | `tickets.view`, `tickets.view_own`, `tickets.view_department`, `tickets.create`, `tickets.update`, `tickets.delete`, `tickets.assign`, `tickets.comment`, `tickets.close`, `tickets.reopen`, `tickets.export` |
| **USERS** | `users.view`, `users.create`, `users.update`, `users.delete`, `users.assign_role` |
| **DEPARTMENTS** | `departments.view`, `departments.create`, `departments.update`, `departments.delete`, `departments.manage_staff` |
| **REPORTS** | `reports.view`, `reports.export`, `reports.sla` |
| **SETTINGS** | `settings.view`, `settings.update`, `settings.manage_roles` |

### RBAC Check Methods (RBAC.php)

| Method | Usage |
|--------|-------|
| `RBAC::getInstance()->can('tickets.assign')` | Single permission check |
| `RBAC::getInstance()->canAny(['tickets.view', 'tickets.view_department'])` | At least one |
| `RBAC::getInstance()->canAll(['tickets.view', 'tickets.update'])` | All required |
| `RBAC::getInstance()->canAccessDepartment($deptId)` | Department access check |
| `RBAC::getInstance()->requirePermission('settings.update')` | Redirect if denied |
| `RBAC::getInstance()->isSuperAdmin()` | Super admin check |

### Employee Admin Rights (Legacy System)

Employees from the `employees` table can have admin rights via `admin_rights_hdesk`:

| Value | Access Level |
|-------|-------------|
| `'superadmin'` | Equivalent to Super Admin — full system access |
| `'it'` | IT Department Admin — manage IT tickets |
| `'hr'` | HR Department Admin — manage HR tickets |
| `NULL` | Regular employee — submit tickets only |

---

## 7. Models (Data Layer)

All models use PDO prepared statements via `Database::getInstance()->getConnection()`.

### Ticket.php — Core Ticket Operations

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `create($data)` | array | int\|false | Create ticket, returns ID |
| `grabTicket($ticketId, $userId, $assigneeType)` | int, int, string | bool | Grab ticket from queue |
| `releaseTicket($ticketId, $userId)` | int, int | bool | Release ticket back to queue |
| `getQueue($departmentId, $filters)` | int, array | array | Get department ticket queue |
| `getMyTickets($userId, $filters)` | int, array | array | Get tickets grabbed by user |
| `findById($id)` | int | array\|false | Get ticket with all related data |
| `findByTicketNumber($num)` | string | array\|false | Find by TKT number |
| `getAll($filters, $sortBy, $sortDir, $limit, $offset)` | mixed | array | Filtered, sorted, paginated listing |
| `getTotalCount($filters)` | array | int | Count for pagination |
| `update($id, $data)` | int, array | bool | Update ticket fields |
| `delete($id)` | int | bool | Delete ticket |
| `getStats($userId, $role)` | int, string | array | Dashboard statistics |
| `getDailyStats($days)` | int | array | Daily counts for charts |
| `getDailyNewVsClosedStats($days)` | int | array | New vs closed trend |
| `getStatusBreakdown()` | — | array | Status distribution |

### User.php — IT Staff/Admin Management

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `create($data)` | array | int\|false | Create user |
| `findById($id, $activeOnly)` | int, bool | array\|false | Find user |
| `findByUsername($username)` | string | array\|false | Find by username |
| `verifyLogin($username, $password)` | string, string | array\|false | Authenticate |
| `getAll($role)` | string\|null | array | All users |
| `getITStaff()` | — | array | Active IT staff + admins |
| `update($id, $data)` | int, array | bool | Update user |
| `delete($id)` | int | bool | Soft delete |
| `getStats()` | — | array | User statistics |
| `assignRole($userId, $roleId)` | int, int | bool | RBAC role assignment |
| `assignDepartment($userId, $deptId, $primary, $by)` | int, int, bool, int | bool | Department assignment |
| `syncDepartments($userId, $deptIds, $by)` | int, array, int | bool | Replace dept assignments |
| `getDepartmentStaff($deptId, $includeAdmins)` | int, bool | array | Staff in department |

### Employee.php — Company Employee Management

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `create($data)` | array | int\|false | Create employee |
| `findById($id)` | int | array\|false | Find by ID |
| `findByUsername($username)` | string | array\|false | Find by username |
| `findByEmployeeId($empId)` | string | array\|false | Find by external ID (Harley sync) |
| `getAdminEmployees()` | — | array | Employees with admin rights |
| `verifyLogin($username, $password)` | string, string | array\|false | Authenticate |
| `getAll($status)` | string\|null | array | All employees |
| `update($id, $data)` | int, array | bool | Update employee |
| `delete($id)` | int | bool | Soft delete (status=terminated) |
| `updateByEmployeeId($empId, $data)` | string, array | bool | Update by external ID |
| `getStats()` | — | array | Employee statistics |

### Category.php — Category Hierarchy

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getAll($departmentId)` | int\|null | array | All active categories |
| `getParentCategories($deptId)` | int | array | Top-level only |
| `getSubCategories($parentId)` | int | array | Children of parent |
| `getByDepartmentHierarchy($deptId)` | int | array | Full hierarchy tree |
| `findById($id)` | int | array\|false | Single category |
| `create($data)` | array | int\|false | Create category |
| `update($id, $data)` | int, array | bool | Update category |
| `delete($id)` | int | bool | Soft delete |
| `getStats($deptId)` | int\|null | array | Category ticket counts |

### Department.php — Department Management

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getAll()` | — | array | Active departments |
| `findById($id)` | int | array\|false | Find department |
| `findByCode($code)` | string | array\|false | Find by code (IT/HR) |
| `create($data)` | array | int\|false | Create department |
| `getStats()` | — | array | Department ticket stats |
| `getTicketQueue($deptId, $filters)` | int, array | array | Department queue |
| `getStaff($deptId)` | int | array | Department staff |
| `getCategoriesHierarchy($deptId)` | int | array | Department categories tree |

### SLA.php — Service Level Agreements

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `isWeekend($date)` | DateTime | bool | Weekend check |
| `isWithinBusinessHours($date)` | DateTime | bool | Business hours check (Mon-Fri 8-16) |
| `getBusinessMinutesBetween($start, $end, $bizOnly)` | DateTime, DateTime, bool | int | Calculate business minutes |
| `getNextBusinessTime($date)` | DateTime\|null | DateTime | Next business day/time |
| `isSLABreached($dueDate, $bizOnly, $checkTime)` | DateTime, bool, DateTime | bool | Breach check |
| `getBusinessMinutesRemaining($dueDate, $bizOnly)` | DateTime, bool | int | Minutes remaining |
| `getPolicyByPriority($priority, $deptId)` | string, int\|null | array\|false | Policy lookup |
| `getAllPolicies()` | — | array | All active policies |
| `createTracking($ticketId, $priority)` | int, string | bool | Create SLA record for new ticket |
| `getAtRiskTickets($threshold)` | int | array | Tickets near breach |
| `getBreachedTickets()` | — | array | Breached tickets |
| `getMonthlySummary()` | — | array | Current month stats |
| `recordFirstResponse($ticketId)` | int | bool | Record first response time |

### Notification.php — Dual-User Notifications

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `create($data)` | array | bool | Create (user_id OR employee_id) |
| `getRecentByUser($id, $limit, $userType)` | int, int, string | array | Recent notifications |
| `getUnreadCount($id, $userType)` | int, string | int | Unread count |
| `markAsRead($notifId, $id, $userType)` | int, int, string | bool | Mark single read |
| `markAllAsRead($id, $userType)` | int, string | bool | Mark all read |
| `delete($notifId, $id, $userType)` | int, int, string | bool | Delete notification |

**Static helpers:** `notifyTicketAssigned()`, `notifyTicketUpdated()`, `notifyCommentAdded()`, `notifyStatusChanged()`

### TicketActivity.php — Audit Log

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `log($data)` | array | bool | Record activity |
| `getByTicketId($ticketId)` | int | array | Ticket activities |
| `getRecent($limit, $userId, $role)` | int, int, string | array | Recent activities |
| `getStats($days)` | int | array | Activity trends |
| `deleteByTicketId($ticketId)` | int | bool | Clear ticket activities |

### TicketReply.php — Conversation Thread

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `create($data)` | array | bool | Add reply |
| `getByTicketId($ticketId)` | int | array | Get replies with user names |
| `getReplyCount($ticketId)` | int | int | Count replies |

### Role.php — RBAC Role Management

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getAll($activeOnly)` | bool | array | All roles |
| `findById($id)` | int | array\|false | Role by ID |
| `findBySlug($slug)` | string | array\|false | Role by slug |
| `getAssignableRoles($level)` | int | array | Roles assignable by hierarchy |
| `getPermissions($roleId)` | int | array | Role permissions |
| `hasPermission($roleId, $slug)` | int, string | bool | Permission check |
| `create($data)` | array | int\|false | Create role |
| `update($id, $data)` | int, array | bool | Update role |

### Permission.php — RBAC Permission Management

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getAll($category)` | string\|null | array | All permissions |
| `getAllGrouped()` | — | array | Grouped by category |
| `userHasPermission($userId, $slug)` | int, string | bool | Check user permission |
| `getUserPermissions($userId)` | int | array | User's permission slugs |

### CategoryPriorityMap.php — Auto-Priority

| Method | Parameters | Returns | Description |
|--------|-----------|---------|-------------|
| `getDefaultPriority($categoryId)` | int | string\|null | Priority for category |
| `getAllAsLookup()` | — | array | `[catId => priority]` for JS |
| `updatePriority($catId, $priority)` | int, string | bool | Set/update mapping |
| `getSLATargets($priority, $dept)` | string, string\|null | array | SLA targets reference |

---

## 8. Controllers (Business Logic)

### Admin Controllers (`controllers/admin/`)

| Controller | File | Auth Requirement | Purpose |
|-----------|------|-----------------|---------|
| **DashboardController** | `DashboardController.php` | Admin/Internal | System-wide stats, charts, SLA report |
| **ITStaffController** | `ITStaffController.php` | IT Staff | Personal dashboard, assigned tickets, SLA compliance |
| **TicketsController** | `TicketsController.php` | IT Staff | Ticket listing, filtering, sorting, pagination, delete |
| **CreateTicketController** | `CreateTicketController.php` | IT Staff | Create ticket on behalf of employees |
| **EmployeesController** | `EmployeesController.php` | IT Staff | Employee CRUD, search, sort |
| **CategoriesController** | `CategoriesController.php` | IT Staff | Category management, hierarchy display |
| **SLAManagementController** | `SLAManagementController.php` | Admin/Internal | SLA policies, breach monitoring |
| **SLAPerformanceController** | `SLAPerformanceController.php` | IT Staff | SLA compliance reports |
| **NotificationsController** | `NotificationsController.php` | Login | Notification listing, mark read/delete |
| **TicketWorkflowController** | `TicketWorkflowController.php` | Login + RBAC | Assignment, escalation, routing |
| **ProfileController** | (admin) | IT Staff | Admin profile management |
| **SettingsController** | (admin) | Admin | System settings |

### Customer Controllers (`controllers/customer/`)

| Controller | File | Auth Requirement | Purpose |
|-----------|------|-----------------|---------|
| **CustomerDashboardController** | `CustomerDashboardController.php` | Employee | Personal dashboard, own ticket stats |
| **CustomerTicketsController** | `CustomerTicketsController.php` | Employee | Own ticket listing, filtering, delete |
| **CustomerCreateTicketController** | `CustomerCreateTicketController.php` | Employee | Submit new ticket with dept/category selection |
| **CustomerViewTicketController** | `CustomerViewTicketController.php` | Employee | View own ticket details, conversation |
| **ProfileController** | `ProfileController.php` | Employee | Profile updates, password, avatar |

### Auth Controller

| Controller | File | Purpose |
|-----------|------|---------|
| **LoginController** | `LoginController.php` | Login processing, logout, redirect routing |

### Controller Pattern

Every controller follows this structure:

```php
class FeatureController {
    private $auth;
    private $model;

    public function __construct() {
        $this->auth = new Auth();
        $this->auth->requireITStaff();  // Authorization check
        $this->model = new FeatureModel();
    }

    public function index() {
        $data = $this->model->getAll();
        $this->loadView('admin/feature', ['data' => $data]);
    }

    private function loadView($view, $data = []) {
        extract($data);  // Variables accessible in view
        include __DIR__ . '/../../views/' . $view . '.view.php';
    }
}
```

---

## 9. Views (Presentation Layer)

### Admin Views (`views/admin/`)

| View File | Purpose |
|-----------|---------|
| `dashboard.view.php` | Admin dashboard with stats, charts, recent tickets |
| `it_dashboard.view.php` | IT staff personal dashboard, workload, SLA compliance |
| `tickets.view.php` | Ticket listing with filters, sorting, pagination |
| `create_ticket.view.php` | 4-step ticket wizard (Employee → Department → Details → Review) |
| `employees.view.php` | Employee listing with search, sort, pagination |
| `add_employee.view.php` | Add new employee form |
| `edit_employee.view.php` | Edit employee form |
| `add_user.view.php` | Add new IT staff/admin user form |
| `categories.view.php` | Category management with hierarchical display |
| `add_category.view.php` | Add new category form |
| `manage_categories.view.php` | Advanced category management |
| `sla_management.view.php` | SLA policy configuration, breach monitoring |
| `sla_performance.view.php` | SLA performance reports, staff comparison |
| `notifications.view.php` | Notification listing, mark read, delete |
| `admin_settings.view.php` | System settings page |

### Customer Views (`views/customer/`)

| View File | Purpose |
|-----------|---------|
| `dashboard.view.php` | Employee dashboard, recent tickets, quick actions |
| `tickets.view.php` | Employee's ticket listing |
| `create_ticket_v2.view.php` | 3-step ticket wizard (Department → Details → Review) |
| `view_ticket.view.php` | Ticket detail, activity timeline, conversation |
| `profile.view.php` | Profile management, photo upload, password change |
| `notifications.view.php` | Employee notifications |

### Layout Files

| File | Purpose |
|------|---------|
| `views/layouts/header.php` | HTML head, CSS/JS includes, conditional nav sidebar |
| `views/layouts/footer.php` | Common JS (helpers.js, notifications.js), greeting, dark mode |
| `includes/admin_nav.php` | Admin sidebar: Dashboard, Pool, My Tickets, Create, Management |
| `includes/customer_nav.php` | Employee sidebar: Home, My Requests, Create Ticket |
| `includes/customer_header.php` | Employee top header bar |
| `includes/top_header.php` | Admin top header bar |

### View Data Flow

```php
// Controller
$this->loadView('admin/dashboard', [
    'stats'         => $stats,
    'recentTickets' => $tickets,
    'chartData'     => $chartData,
    'currentUser'   => $this->auth->getCurrentUser()
]);

// In view, variables are extracted:
// $stats, $recentTickets, $chartData, $currentUser are directly available
```

---

## 10. API Endpoints

### `api/ticket_actions.php` — Bucket Model Operations

| Action | Method | Parameters | Response |
|--------|--------|-----------|----------|
| `grab` | POST | `ticket_id` | `{success, message, ticket_number}` |
| `release` | POST | `ticket_id` | `{success, message, ticket_number}` |
| `get_queue` | GET | — | `{success, tickets[], count}` |
| `get_my_tickets` | GET | — | `{success, tickets[], count}` |

### `api/ticket_workflow.php` — RBAC-Protected Assignment

| Action | Method | Parameters | Response |
|--------|--------|-----------|----------|
| `assign_department` | POST | `ticket_id`, `department_id`, `notes` | `{success, message}` |
| `assign_staff` | POST | `ticket_id`, `staff_id`, `notes` | `{success, message}` |
| `reassign` | POST | `ticket_id`, `department_id?`, `staff_id?`, `notes` | `{success, message}` |
| `override` | POST | `ticket_id`, `reason` | `{success, message}` |
| `update_status` | POST | `ticket_id`, `status`, `resolution?` | `{success, message}` |
| `update_priority` | POST | `ticket_id`, `priority` | `{success, message}` |
| `pending_routing` | GET | — | `{success, tickets[], count}` |
| `get_department_staff` | GET | `department_id` | `{success, staff[]}` |

### `api/notifications.php` — Notification Management

| Action | Method | Parameters | Response |
|--------|--------|-----------|----------|
| `get_recent` | GET | — | `{success, notifications[], unread_count}` |
| `get_count` | GET | — | `{success, unread_count}` |
| `mark_read` | POST | `notification_id` | `{success, message}` |
| `mark_all_read` | POST | — | `{success, message}` |
| `delete` | POST | `notification_id` | `{success, message}` |

### All API responses return JSON with `Content-Type: application/json`.

---

## 11. Ticket Lifecycle & Workflow

### Status Flow

```
                          ┌──────────┐
                          │ PENDING  │ ← New ticket created
                          └────┬─────┘
                               │
                    ┌──────────┼──────────┐
                    │          │          │
              ┌─────▼───┐ ┌───▼────┐    │
              │  OPEN   │ │IN_PROG │    │
              │(viewed) │ │(grabbed)│    │
              └────┬────┘ └───┬────┘    │
                   │          │          │
                   └────┬─────┘          │
                        │                │
                  ┌─────▼─────┐          │
                  │ RESOLVED  │◄─────────┘
                  │(solution  │
                  │ provided) │
                  └─────┬─────┘
                        │
                  ┌─────▼─────┐
                  │  CLOSED   │
                  └───────────┘
```

### Bucket Model (Grab/Release)

The system uses a **Bucket Model** for ticket distribution:

1. **Queue**: New tickets land in department queue (`grabbed_by IS NULL`)
2. **Grab**: Staff member clicks "Grab Ticket" → ticket moves to their personal list
3. **Work**: Staff processes the ticket (updates, replies, resolves)
4. **Release**: Staff can release ticket back to queue if needed

```
┌─────────────────┐    Grab     ┌──────────────────┐
│  Ticket Queue   │ ─────────► │  My Tickets      │
│  (Department)   │            │  (Personal)      │
│  grabbed_by=NULL│ ◄───────── │  grabbed_by=user │
└─────────────────┘   Release  └──────────────────┘
```

### Ticket Fields on Grab

| Field | Before Grab | After Grab |
|-------|-------------|------------|
| `status` | pending | in_progress |
| `assigned_to` | NULL | user ID |
| `assignee_type` | NULL | 'user' or 'employee' |
| `grabbed_by` | NULL | user ID |
| `grabbed_at` | NULL | NOW() |

### Ticket Creation Flow

1. Employee or admin fills ticket form (3-step or 4-step wizard)
2. **Nonce token** validated (prevent duplicate submission)
3. Ticket number generated: `TKT-YYYY-XXXX`
4. Department determined from submission
5. Priority auto-set from `category_priority_map` (or manual override)
6. Ticket record created in `tickets` table
7. SLA tracking record created in `sla_tracking`
8. Activity logged: "Ticket created"
9. Notifications created (employee + admins)
10. Email sent via PHPMailer
11. Push notification sent via Firebase FCM

### Ticket Assignment Methods

| Method | Who Can | How |
|--------|---------|-----|
| **Grab from Queue** | Any dept staff | Click "Grab" button on ticket pool |
| **Assign to Staff** | Dept Admin+ | Select staff from dropdown |
| **Assign to Department** | Super Admin | Route ticket to different dept |
| **Override** | Super Admin | Take control of any ticket |
| **Reassign** | Dept Admin+ | Move ticket to different staff |

---

## 12. SLA (Service Level Agreement) System

### Business Hours

| Day | Hours | Notes |
|-----|-------|-------|
| Monday - Thursday | 8:00 AM - 4:00 PM | 8 hours per day |
| Friday | 8:00 AM - 4:00 PM | Special rule: after 4 PM → Monday 8 AM |
| Saturday | CLOSED | SLA clock paused |
| Sunday | CLOSED | SLA clock paused |

### SLA Targets by Priority and Department

#### HR Department

| Priority | First Response | Resolution |
|----------|---------------|------------|
| **HIGH** | 24 business hours | 24 business hours |
| **MEDIUM** | 24 business hours | 48-72 business hours |
| **LOW** | 24 business hours | 56-120 business hours |

#### IT Department

| Priority | First Response | Resolution |
|----------|---------------|------------|
| **HIGH** | 24 business hours | 48 business hours |
| **MEDIUM** | 24 business hours | 72-96 business hours |
| **LOW** | 24 business hours | 72-120 business hours |

### SLA Status Indicators

| Status | Condition | Description |
|--------|-----------|-------------|
| **Safe** | > 60 minutes remaining | On track |
| **At Risk** | ≤ 60 minutes remaining | Near deadline |
| **Breached** | Past due | SLA violated |
| **Met** | Resolved before deadline | SLA satisfied |
| **Paused** | Weekend / after hours | Clock stopped |

### SLA Tracking Flow

```
Ticket Created
    │
    ├── Response SLA: 24h from creation (always)
    │   ├── First staff action → recordFirstResponse()
    │   └── response_sla_status: 'met' or 'breached'
    │
    └── Resolution SLA: Based on priority policy
        ├── Ticket resolved → compare against deadline
        └── resolution_sla_status: 'met' or 'breached'
```

### Friday Rule Example

- Ticket created Friday 3:30 PM → 30 min business time remains Friday
- SLA clock pauses Friday 4:00 PM
- SLA clock resumes Monday 8:00 AM
- Remaining SLA time continues from Monday

---

## 13. Category Hierarchy & Priority Mapping

### Structure: 3-Level Hierarchy

```
Department (IT/HR)
  └── Level 1: Parent Category
       └── Level 2: Subcategory
            └── Level 3: Specific Concern
```

### HR Department Categories

```
HR Department
├── Certificate of Employment (COE) / Request a Document
│   ├── Single Document (LOW)
│   ├── With other documents (LOW)
│   ├── Certificate of Employment (COE) (LOW)
│   ├── Certification of Leave (LOW)
│   └── Others (LOW)
│
├── Salary Dispute
│   ├── Draft Payslip (HIGH)
│   └── Payslip Dispute (HIGH)
│
├── Payroll
│   ├── Draft Payslip Discrepancy (HIGH)
│   └── Post-Payroll Payslip Concerns (HIGH)
│
├── Timekeeping Concerns
│   ├── Log In Error (MEDIUM)
│   └── Missing Log In/Log Out (MEDIUM)
│
├── Leave Concerns
│   ├── Leave Inquiry (LOW)
│   └── Leave Credit Balance (LOW)
│
├── HR General Inquiry
│   ├── Holiday Inquiry (LOW)
│   └── Non-Harley, Payslip Dispute, Leave-Related (LOW)
│
└── Harley Concern
    └── Leave Inquiry (LOW)
```

### IT Department Categories

```
IT Department
├── Hardware
│   └── (subcategories as configured)
├── Software
│   └── (subcategories as configured)
├── Network
│   └── (subcategories as configured)
├── Email
│   └── (subcategories as configured)
└── Access
    └── (subcategories as configured)
```

### Auto-Priority System

When a user selects a category/subcategory:
1. JavaScript looks up `priorityMapData[categoryId]`
2. If mapping exists → auto-sets priority dropdown
3. Shows SLA target banner: "Expected Resolution: 24 business hours"
4. Admin can override auto-priority via checkbox

---

## 14. Notification System

### Notification Types

| Type | Trigger | Recipients |
|------|---------|------------|
| `ticket_created` | New ticket submitted | Admins/IT staff |
| `ticket_assigned` | Ticket assigned to staff | Assigned staff |
| `status_changed` | Status updated | Submitter + assigned staff |
| `comment_added` | New comment/reply | Ticket participants |
| `ticket_resolved` | Ticket resolved | Submitter |
| `priority_changed` | Priority updated | Assigned staff |

### Dual-User Notification Pattern

```php
// For IT staff notifications:
$notification->create([
    'user_id' => $staffUserId,      // → users table
    'type' => 'ticket_assigned',
    'title' => 'New Ticket Assigned',
    'message' => '...',
    'ticket_id' => $ticketId
]);

// For employee notifications:
$notification->create([
    'employee_id' => $employeeId,   // → employees table
    'type' => 'status_changed',
    'title' => 'Ticket Updated',
    'message' => '...',
    'ticket_id' => $ticketId
]);
```

### Notification Display

- **Unread count** shown in navigation header badge
- **Recent notifications** fetched via AJAX (`api/notifications.php?action=get_recent`)
- **Notification page** for full history with pagination
- **Mark as read** on click or "Mark All Read" button

---

## 15. Email System

### PHPMailer Configuration

| Setting | Value |
|---------|-------|
| Host | smtp.gmail.com |
| Port | 587 |
| Encryption | TLS |
| Username | it.resourcestaff@gmail.com |
| From Name | HDesk |
| Charset | UTF-8 |

### Email Templates

| Template | Trigger | Content |
|----------|---------|---------|
| `sendTicketCreated` | New ticket | Ticket details, number, category, priority |
| `sendTicketAssigned` | Ticket assigned | Assignee info, ticket link |
| `sendTicketStatusUpdate` | Status change | Old/new status, ticket link |
| `sendTicketResolved` | Resolution | Resolution notes, satisfaction link |
| `sendPasswordResetEmail` | Forgot password | Reset link (24h expiry) |
| `sendWelcomeEmail` | New account | Username, temp password |

### Fallback Behavior

If PHPMailer is unavailable (Composer not installed), the system falls back to PHP's native `mail()` function.

---

## 16. Push Notifications (Firebase)

### Setup

- **Firebase Admin SDK** loaded via Composer
- **Service Account JSON** at `config/firebase-service-account.json`
- **Client-side** service worker: `firebase-messaging-sw.js`
- **Token storage**: `fcm_token` column in both `users` and `employees` tables

### Push Notification Methods

| Method | Trigger | Icon |
|--------|---------|------|
| `notifyTicketCreated` | New ticket → all IT staff | 🎫 |
| `notifyTicketAssigned` | Assignment → assigned user | 📌 |
| `notifyTicketStatusChanged` | Status change → submitter | Status-specific emoji |

### Token Registration

1. Browser requests notification permission
2. Firebase generates FCM token
3. Token saved via `api/save_fcm_token.php`
4. Stored in `users.fcm_token` or `employees.fcm_token`

---

## 17. External Integrations

### Harley HRIS Sync

The system synchronizes employee data from an external HR system called "Harley."

#### Webhook Endpoint: `webhook_employee_sync.php`

| Feature | Detail |
|---------|--------|
| **Authentication** | API key via `X-API-Key` header |
| **Method** | POST with JSON body |
| **Sync Modes** | `partial` (add/update) or `full` (+ mark missing) |
| **Employee ID Format** | `HRLY-{harley_id}` |
| **Auto-creates** | Username, password, email from Harley data |
| **Syncs** | `admin_rights_hdesk` from Harley |

#### Cron Job: `cron/sync_harley.php`

| Feature | Detail |
|---------|--------|
| **Trigger** | Scheduled cron (e.g., every 15 minutes) |
| **Guard** | `HARLEY_SYNC_ENABLED` flag in config |
| **Service** | `HarleySyncService` class |
| **Modes** | `fullSync()` or `incrementalSync($since)` |
| **Last Sync** | Stored in `storage/last_harley_sync.txt` |

#### Sync Service: `includes/HarleySyncService.php`

| Method | Description |
|--------|-------------|
| `connectToHarley()` | PDO connection to Harley database |
| `fetchHarleyEmployees()` | Get all active Harley employees |
| `fetchUpdatedEmployees($since)` | Get employees created since timestamp |
| `syncEmployee($emp)` | Create or update single employee |
| `mapHarleyToLocal($emp)` | Field mapping (Harley → HDesk) |
| `fullSync()` | Sync all, return stats |
| `incrementalSync($since)` | Sync recent only |
| `testConnection()` | Verify Harley DB connectivity |

---

## 18. Reports & Exports

### Excel Export (`admin/export_tickets.php`)

Uses PhpSpreadsheet via `includes/ReportGenerator.php`.

#### Report Types

| Type | Content |
|------|---------|
| **Tickets Report** | TKT#, Title, Priority, Status, Submitter, Assignee, Date Created, Date Resolved |
| **Summary Report** | Ticket statistics + Category breakdown + Open ticket counts |

#### Filters

| Filter | Options |
|--------|---------|
| `status` | pending, open, in_progress, resolved, closed |
| `priority` | low, medium, high, urgent |
| `category_id` | Any valid category ID |

### SLA Performance Report (`admin/sla_performance.php`)

| Tab | Content |
|-----|---------|
| **My Performance** | Personal SLA compliance, avg response/resolution times, priority breakdown |
| **Staff Report** | Date-range based, all staff comparison, compliance rates, SLA scores |

---

## 19. Configuration Reference

### `config/config.php` — Constants

| Constant | Value | Description |
|----------|-------|-------------|
| `APP_NAME` | `'HDesk'` | Application name |
| `APP_TAGLINE` | `'Multi-Department Service Portal'` | Subtitle |
| `APP_VERSION` | `'2.0.0'` | Version string |
| `APP_TIMEZONE` | `'Asia/Manila'` | PHP timezone |
| `BASE_URL` | Auto-detected or `.env` | Application root URL |
| `UPLOAD_DIR` | `__DIR__ . '/../uploads/'` | File upload directory |
| `MAX_FILE_SIZE` | `5242880` | 5 MB in bytes |
| `ALLOWED_EXTENSIONS` | `['jpg','jpeg','png','pdf','doc','docx','xlsx','txt']` | Upload whitelist |
| `ITEMS_PER_PAGE` | `10` | Default pagination |
| `TICKET_PREFIX` | `'TKT'` | Ticket number prefix |
| `SUPPORTED_DEPARTMENTS` | `['IT', 'HR']` | Active departments |
| `MAIL_HOST` | `'smtp.gmail.com'` | SMTP server |
| `MAIL_PORT` | `587` | SMTP port |
| `MAIL_ENCRYPTION` | `'tls'` | SMTP encryption |

### `config/database.php` — Database

| Setting | Value |
|---------|-------|
| Host | `localhost` (dev) / Hostinger (prod) |
| Database | `u816220874_ticketing` |
| Charset | `utf8mb4` |
| PDO Error Mode | `ERRMODE_EXCEPTION` |
| Fetch Mode | `FETCH_ASSOC` |
| Emulate Prepares | `false` |
| MySQL Timezone | `+08:00` |

### Helper Functions (`config/config.php`)

| Function | Description |
|----------|-------------|
| `isLoggedIn()` | Check if user is authenticated |
| `hasRole($role)` | Check user role |
| `isITStaff()` | Check IT staff or admin |
| `redirect($url)` | HTTP redirect |
| `sanitize($data)` | XSS prevention (htmlspecialchars + strip_tags) |
| `formatDate($date, $format)` | Date formatting |
| `getStatusColor($status)` | Status → Tailwind color mapping |
| `getPriorityColor($priority)` | Priority → Tailwind color mapping |
| `generateTicketNumber()` | Generate `TKT-YYYY-XXXX` format |
| `getTailwindCSS()` | CSS link tag (local or CDN) |

---

## 20. File Structure Reference

```
Hdesk/
│
├── index.php                       # Entry point — redirect based on session
├── login.php                       # Login form
├── login_process.php               # Login POST handler → LoginController
├── logout.php                      # Logout handler
├── forgot_password.php             # Password reset request
├── reset_password.php              # Password reset with token
├── webhook_employee_sync.php       # Harley HRIS webhook endpoint
├── firebase-messaging-sw.js        # Firebase service worker
├── composer.json                   # PHP dependencies
├── package.json                    # Node dependencies (Tailwind build)
├── tailwind.config.js              # Tailwind CSS configuration
│
├── config/
│   ├── config.php                  # Constants, autoloader, helper functions
│   ├── database.php                # PDO singleton, DB connection
│   └── harley_config.php           # Harley HRIS connection settings
│
├── controllers/
│   ├── LoginController.php         # Login/logout logic
│   ├── admin/
│   │   ├── DashboardController.php       # Admin dashboard
│   │   ├── ITStaffController.php         # IT staff personal dashboard
│   │   ├── TicketsController.php         # Ticket listing/management
│   │   ├── CreateTicketController.php    # Admin ticket creation
│   │   ├── EmployeesController.php       # Employee CRUD
│   │   ├── CategoriesController.php      # Category management
│   │   ├── SLAManagementController.php   # SLA policy config
│   │   ├── SLAPerformanceController.php  # SLA reports
│   │   ├── NotificationsController.php   # Notification management
│   │   ├── TicketWorkflowController.php  # Assignment & routing
│   │   └── ProfileController.php         # Admin profile
│   └── customer/
│       ├── CustomerDashboardController.php      # Employee dashboard
│       ├── CustomerTicketsController.php        # Employee ticket list
│       ├── CustomerCreateTicketController.php   # Employee ticket submission
│       ├── CustomerViewTicketController.php     # Employee ticket detail
│       └── ProfileController.php                # Employee profile
│
├── models/
│   ├── Ticket.php                  # Ticket CRUD, queue, stats
│   ├── User.php                    # IT staff/admin management
│   ├── Employee.php                # Employee management
│   ├── Category.php                # Category hierarchy
│   ├── Department.php              # Department management
│   ├── SLA.php                     # SLA policies & tracking
│   ├── Notification.php            # Dual-user notifications
│   ├── TicketActivity.php          # Audit trail
│   ├── TicketReply.php             # Conversation thread
│   ├── Role.php                    # RBAC roles
│   ├── Permission.php              # RBAC permissions
│   └── CategoryPriorityMap.php     # Auto-priority mapping
│
├── views/
│   ├── layouts/
│   │   ├── header.php              # HTML head, CSS, conditional nav
│   │   └── footer.php              # Common JS, helpers, dark mode
│   ├── admin/
│   │   ├── dashboard.view.php            # Admin dashboard UI
│   │   ├── it_dashboard.view.php         # IT staff dashboard UI
│   │   ├── tickets.view.php              # Ticket listing UI
│   │   ├── create_ticket.view.php        # Admin ticket wizard (4-step)
│   │   ├── employees.view.php            # Employee listing UI
│   │   ├── add_employee.view.php         # Add employee form
│   │   ├── edit_employee.view.php        # Edit employee form
│   │   ├── add_user.view.php             # Add IT staff form
│   │   ├── categories.view.php           # Category management UI
│   │   ├── add_category.view.php         # Add category form
│   │   ├── manage_categories.view.php    # Advanced category UI
│   │   ├── sla_management.view.php       # SLA policy config UI
│   │   ├── sla_performance.view.php      # SLA reports UI
│   │   ├── notifications.view.php        # Notifications UI
│   │   └── admin_settings.view.php       # Settings UI
│   └── customer/
│       ├── dashboard.view.php            # Employee dashboard UI
│       ├── tickets.view.php              # Employee ticket list UI
│       ├── create_ticket_v2.view.php     # Employee ticket wizard (3-step)
│       ├── view_ticket.view.php          # Ticket detail + conversation UI
│       ├── profile.view.php              # Profile management UI
│       └── notifications.view.php        # Employee notifications UI
│
├── includes/
│   ├── Auth.php                    # Authentication & session management
│   ├── RBAC.php                    # Role-Based Access Control
│   ├── Mailer.php                  # PHPMailer email sending
│   ├── FCMNotification.php         # Firebase push notifications
│   ├── HarleySyncService.php       # Harley HRIS sync service
│   ├── ReportGenerator.php         # PhpSpreadsheet Excel exports
│   ├── admin_nav.php               # Admin sidebar navigation
│   ├── customer_nav.php            # Employee sidebar navigation
│   ├── customer_header.php         # Employee top header
│   ├── top_header.php              # Admin top header
│   └── ui_helpers.php              # UI helper functions
│
├── api/
│   ├── ticket_actions.php          # Grab/release/queue JSON API
│   ├── ticket_workflow.php         # Assignment/routing JSON API
│   ├── notifications.php           # Notification JSON API
│   └── save_fcm_token.php          # Firebase token storage
│
├── admin/                          # Admin entry points (route to controllers)
│   ├── dashboard.php
│   ├── it_dashboard.php
│   ├── tickets.php
│   ├── create_ticket.php
│   ├── view_ticket.php             # Direct view (no controller)
│   ├── customers.php
│   ├── add_employee.php
│   ├── edit_employee.php
│   ├── add_user.php
│   ├── categories.php
│   ├── add_category.php
│   ├── manage_categories.php
│   ├── sla_management.php
│   ├── sla_performance.php
│   ├── notifications.php
│   ├── settings.php
│   ├── profile.php
│   ├── export_tickets.php
│   ├── export_sla_report.php
│   ├── manage_employee_rights.php
│   └── harley_sync.php
│
├── customer/                       # Employee entry points
│   ├── dashboard.php
│   ├── tickets.php
│   ├── create_ticket.php
│   ├── view_ticket.php
│   ├── notifications.php
│   └── profile.php
│
├── cron/
│   └── sync_harley.php             # Scheduled Harley sync
│
├── database/
│   └── schema.sql                  # Base database schema
│
├── uploads/
│   ├── profiles/                   # Profile pictures
│   ├── temp/                       # Temporary uploads
│   └── tickets/                    # Ticket attachments
│
├── assets/
│   ├── css/                        # Stylesheets (tailwind.min.css, etc.)
│   ├── js/                         # JavaScript (helpers.js, notifications.js)
│   └── webfonts/                   # Font Awesome webfonts
│
├── vendor/                         # Composer dependencies
│   ├── phpmailer/                  # PHPMailer
│   ├── phpoffice/                  # PhpSpreadsheet
│   ├── kreait/                     # Firebase Admin SDK
│   └── ...
│
├── storage/
│   └── last_harley_sync.txt        # Last sync timestamp
│
└── logs/                           # Application logs
```

---

## 21. User Flows

### Flow 1: Employee Submits a Ticket

1. Employee logs in → redirected to `customer/dashboard.php`
2. Clicks "Create Ticket" in sidebar
3. **Step 1 — Select Department**: Chooses IT or HR
4. **Step 2 — Fill Details**:
   - Selects Category → Subcategory → Specific Concern (3-level)
   - Priority auto-set from category mapping
   - Title auto-generated from selections
   - Enters description, optionally uploads files
5. **Step 3 — Review & Submit**: Reviews all details, clicks Submit
6. System creates ticket, SLA tracking, notifications, sends email + push
7. Employee redirected to ticket list with success message

### Flow 2: IT Staff Grabs and Resolves a Ticket

1. IT Staff logs in → redirected to `admin/it_dashboard.php`
2. Clicks "Ticket Pool" in sidebar → sees department queue
3. Clicks "Grab" on a ticket → ticket moves to "My Tickets"
4. Opens ticket → views details, activity timeline
5. Adds comments, updates status to "in_progress"
6. Provides resolution → changes status to "resolved"
7. Employee receives notification and email about resolution
8. SLA compliance recorded (met or breached)

### Flow 3: Admin Creates Ticket on Behalf of Employee

1. Admin logs in → `admin/dashboard.php`
2. Clicks "Create Ticket" in sidebar
3. **Step 1 — Select Employee**: Searches and selects employee
4. **Step 2 — Select Department**: IT or HR
5. **Step 3 — Fill Details**: Category, priority (can override auto), description
6. **Step 4 — Review & Submit**: Confirms all details
7. Ticket created with `submitter_type='employee'` and selected employee as submitter

### Flow 4: Admin Routes Ticket Between Departments

1. Super Admin views unrouted tickets in dashboard
2. Clicks on ticket → views details
3. Uses "Assign to Department" to route to IT or HR
4. Department admins see ticket in their queue
5. Department staff grab and work on ticket

### Flow 5: Password Reset

1. User clicks "Forgot Password?" on login page
2. Enters email address → system generates secure token
3. Email with reset link sent (always shows success)
4. User clicks link → enters new password (8+ chars)
5. Password updated, token marked as used
6. Redirected to login page

### Flow 6: Harley Employee Sync

1. Harley HRIS sends POST to `webhook_employee_sync.php`
2. API key validated from `X-API-Key` header
3. Employee array processed:
   - New employees: created with `HRLY-{id}` format, welcome email sent
   - Existing employees: fields updated (except password)
4. Stats returned: `{success, failed, updated}`
5. Alternatively: `cron/sync_harley.php` runs on schedule for pull-based sync

---

## 22. Screenshots Guide

Below is a comprehensive list of pages/features you should screenshot for system documentation. Organized by user role for clarity.

### Login & Authentication

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 1 | **Login Page** | `login.php` | Full login form with username/password fields and "Forgot Password?" link |
| 2 | **Forgot Password Page** | `forgot_password.php` | Email input form |
| 3 | **Reset Password Page** | `reset_password.php?token=...` | New password form (if you have a valid token) |

### Employee (Customer) Portal

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 4 | **Employee Dashboard** | `customer/dashboard.php` | Stats cards, recent tickets, quick actions, sidebar navigation |
| 5 | **My Requests (Ticket List)** | `customer/tickets.php` | Ticket listing with status badges, priority badges, filters |
| 6 | **Create Ticket — Step 1** | `customer/create_ticket.php` | Department selection (IT / HR cards) |
| 7 | **Create Ticket — Step 2** | (same page, step 2) | Category dropdown, subcategory dropdown, auto-priority banner, title, description, file upload |
| 8 | **Create Ticket — Step 3** | (same page, step 3) | Review panel showing all selections before submit |
| 9 | **Create Ticket — Success** | After submission | Success message/modal confirming ticket number |
| 10 | **View Ticket Detail** | `customer/view_ticket.php?id=X` | Ticket info, status, SLA indicator, activity timeline, conversation thread |
| 11 | **Employee Profile** | `customer/profile.php` | Profile photo, personal info, contact, password change |
| 12 | **Employee Notifications** | `customer/notifications.php` | Notification list with unread indicators |

### Admin / IT Staff Portal

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 13 | **Admin Dashboard** | `admin/dashboard.php` | Stats overview, charts (new vs closed trend, status breakdown), recent tickets, SLA summary |
| 14 | **IT Staff Dashboard** | `admin/it_dashboard.php` | Personal stats, my tickets, SLA compliance score, at-risk tickets, breached tickets |
| 15 | **Ticket Pool (Queue)** | `admin/tickets.php?view=pool` | Department ticket queue with "Grab" buttons, priority sorting |
| 16 | **My Tickets** | `admin/tickets.php?view=my_tickets` | Grabbed tickets list with SLA indicators |
| 17 | **All Tickets** | `admin/tickets.php` | Full ticket listing with all filters (status, priority, department, search, date range) |
| 18 | **Create Ticket (Admin)** | `admin/create_ticket.php` | 4-step wizard: Employee selection, Department, Details, Review |
| 19 | **View Ticket (Admin)** | `admin/view_ticket.php?id=X` | Full ticket detail, assignment controls, status change, reply, activity log, SLA tracking |
| 20 | **Ticket Actions — Grab** | (from pool view) | Show the grab button and confirmation |
| 21 | **Ticket Actions — Resolve** | (from view ticket) | Resolution form with notes |

### Employee Management

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 22 | **Employee List** | `admin/customers.php` | Employee table with search, sort, pagination |
| 23 | **Add Employee** | `admin/add_employee.php` | New employee form |
| 24 | **Edit Employee** | `admin/edit_employee.php?id=X` | Employee edit form with all fields |

### Category Management

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 25 | **Categories List** | `admin/categories.php` | Hierarchical category display by department |
| 26 | **Add Category** | `admin/add_category.php` | Category form with parent/department selection |
| 27 | **Manage Categories** | `admin/manage_categories.php` | Advanced category management view |

### SLA Management

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 28 | **SLA Policies** | `admin/sla_management.php` | SLA policy configuration table, at-risk tickets, breached list |
| 29 | **SLA Performance — My** | `admin/sla_performance.php` | Personal SLA metrics, compliance percentage, response/resolution times |
| 30 | **SLA Performance — Staff** | `admin/sla_performance.php` (Tab 2) | Staff comparison table with date range filter |

### Administration

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 31 | **Admin Rights Management** | `admin/manage_employee_rights.php` | Employee admin rights assignment |
| 32 | **System Settings** | `admin/settings.php` | System configuration page |
| 33 | **Notifications (Admin)** | `admin/notifications.php` | Admin notification list |
| 34 | **Profile (Admin)** | `admin/profile.php` | Admin profile page |

### Reports & Exports

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 35 | **Export Tickets** | Click export button on tickets page | Show the export action/button |
| 36 | **Exported Excel File** | Open the downloaded .xlsx | Show spreadsheet with ticket data |
| 37 | **SLA Export Report** | `admin/export_sla_report.php` | SLA report export |

### Mobile Responsive

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 38 | **Mobile — Login** | `login.php` (mobile view) | Responsive login |
| 39 | **Mobile — Dashboard** | `customer/dashboard.php` (mobile) | Mobile sidebar toggle, responsive stats |
| 40 | **Mobile — Ticket List** | `customer/tickets.php` (mobile) | Responsive table/card layout |

### Notifications (All Types)

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 41 | **In-App Notification Badge** | Any page header | Unread count badge in navigation |
| 42 | **Notification Dropdown** | Click bell icon | Recent notifications popup |
| 43 | **Email Notification** | Gmail inbox | Screenshot of an actual email notification from HDesk |
| 44 | **Push Notification** | Browser/OS notification | Firebase push notification popup |

### Additional Documentation Screenshots

| # | Screenshot | URL/Page | What to Capture |
|---|-----------|----------|----------------|
| 45 | **Database Tables** | phpMyAdmin | Show list of all tables in the database |
| 46 | **Tickets Table Structure** | phpMyAdmin → tickets | Column definitions |
| 47 | **Categories Hierarchy** | phpMyAdmin → categories | Rows showing parent-child relationships |
| 48 | **Harley Sync Endpoint** | Postman (optional) | Show webhook request/response |
| 49 | **Chart.js Dashboard Charts** | `admin/dashboard.php` | Close-up of the trend charts |
| 50 | **SLA Breach Warning** | `admin/it_dashboard.php` | At-risk or breached ticket indicators |

### Screenshot Tips

- **Browser**: Use Chrome DevTools (F12) for mobile screenshots (Toggle Device Toolbar: Ctrl+Shift+M)
- **Resolution**: Full HD (1920×1080) for desktop, 375×812 for mobile
- **Dark Mode**: If available, capture both light and dark themes
- **Data**: Ensure there are sample tickets with different statuses/priorities visible
- **Filters**: Show at least one filtered view (e.g., status=pending, priority=high)
- **SLA**: Ideally have one ticket near breach to show the warning indicators

---

## Appendix A: Default Login Credentials

| Role | Username | Password |
|------|----------|----------|
| Admin | admin | admin123 |
| IT Staff | mahfuzul | admin123 |
| Employee | john.doe | admin123 |

> **Important:** Change these passwords in production.

## Appendix B: Status Badge Colors

| Status | Tailwind Classes |
|--------|-----------------|
| Pending | `bg-yellow-500 text-white` |
| Open | `bg-blue-600 text-white` |
| In Progress | `bg-purple-600 text-white` |
| Resolved | `bg-green-600 text-white` |
| Closed | `bg-gray-600 text-white` |

## Appendix C: Priority Badge Colors

| Priority | Tailwind Classes |
|----------|-----------------|
| Low | `bg-gray-500 text-white` |
| Medium | `bg-blue-500 text-white` |
| High | `bg-yellow-500 text-white` |
| Urgent | `bg-red-500 text-white` |

## Appendix D: Composer Dependencies

| Package | Purpose |
|---------|---------|
| `phpmailer/phpmailer` | SMTP email sending |
| `phpoffice/phpspreadsheet` | Excel file generation |
| `kreait/firebase-php` | Firebase Admin SDK for FCM |
| `guzzlehttp/guzzle` | HTTP client (Firebase dependency) |

---

*End of HDesk System Documentation*
