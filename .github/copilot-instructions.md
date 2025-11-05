# IT Help Desk - AI Coding Agent Instructions# IT Help Desk - AI Coding Agent Instructions# IT Help Desk - AI Coding Agent Instructions



## Project Overview

**IT Help Desk Ticketing System** — PHP 7.4+ ticket management system with MySQL backend and TailwindCSS frontend. Custom MVC architecture (no frameworks), designed for IT support teams to manage hardware/software/network tickets from both employees and external users.

## Project Overview## Project Overview

**Tech Stack:** PHP 7.4+, MySQL 5.7+, TailwindCSS (CDN), Chart.js, PHPMailer, Font Awesome 6.4.0

**IT Help Desk Ticketing System** — PHP 7.4+ ticket management system with MySQL backend and TailwindCSS frontend. Custom MVC architecture (no frameworks), designed for IT support teams to manage hardware/software/network tickets from both employees and external users.IT Help Desk Ticketing System built with **PHP 7.4+, MySQL, TailwindCSS**. Uses MVC-inspired architecture with Controllers handling business logic and Models for data access. Recently transformed from glass morphism to **minimalist design** (gray-scale palette, no gradients/shadows).

---



## Critical Architecture Patterns

**Tech Stack:** PHP 7.4+, MySQL 5.7+, TailwindCSS (CDN), Chart.js, PHPMailer, Font Awesome 6.4.0## Architecture & Code Organization

### 1. Dual User System (Most Important!)

The system supports TWO separate user types submitting tickets:

- **`users` table**: IT staff (role: `it_staff`/`admin`) — manage system, assign tickets

- **`employees` table**: Company employees (role: `employee`) — submit tickets only---### MVC Pattern (Custom Implementation)



**All ticket queries MUST use CASE statements** to join both tables:- **Controllers**: `controllers/admin/` - Handle business logic, prepare data for views

```php

SELECT t.*,## Critical Architecture Patterns  - Example: `DashboardController.php` initializes models, fetches data, passes to view

    CASE 

        WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)  - Controllers check authentication/authorization via `Auth` class

        ELSE u1.full_name

    END as submitter_name### 1. Dual User System (Most Important!)- **Models**: `models/` - Active Record pattern, one class per table

FROM tickets t

LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'The system supports TWO separate user types submitting tickets:  - Direct database access via PDO (no ORM)

LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'

```- **`users` table**: IT staff (role: `it_staff`/`admin`) — manage system, assign tickets  - Example: `Ticket.php`, `User.php`, `Category.php`

**Reference:** `models/Ticket.php:41-62` (findById method)

- **`employees` table**: Company employees (role: `employee`) — submit tickets only- **Views**: `views/admin/` - PHP templates with embedded logic

**Common mistake:** Forgetting to check `submitter_type` causes tickets to show wrong submitter names or fail entirely.

  - Structure: `views/layouts/header.php` + `views/admin/{page}.view.php` + `views/layouts/footer.php`

### 2. MVC Architecture

- **Controllers** (`controllers/admin/`, `controllers/customer/`): Business logic, authorization checks, data preparation**All ticket queries MUST use CASE statements** to join both tables:  - Views receive data as variables from controllers (e.g., `$currentUser`, `$stats`)

- **Models** (`models/`): Active Record pattern with PDO prepared statements—one model per table

- **Views** (`views/admin/`, `views/customer/`): PHP templates with layout inheritance```php

- **Layouts**: `header.php` (auth check + navigation) → view → `footer.php`

SELECT t.*,### Database Architecture

**Pattern: Controller → Model → View**

```php    CASE **Dual User System** - Critical pattern to understand:

// DashboardController.php (controller)

public function index() {        WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)```php

    $this->auth->requireLogin();

    $stats = $this->ticketModel->getStats($userId, $role); // Model call        ELSE u1.full_name// Tickets can be submitted by TWO user types:

    $this->loadView('admin/dashboard', ['stats' => $stats]); // View render

}    END as submitter_nametickets.submitter_type = 'employee' → links to employees table



// loadView helper: extract($data); include "$viewName.view.php"FROM tickets ttickets.submitter_type = 'user' → links to users table

```

LEFT JOIN employees e ON t.submitter_id = e.id AND t.submitter_type = 'employee'

### 3. Authentication & Authorization Flow

**Session Management** (`includes/Auth.php`):LEFT JOIN users u1 ON t.submitter_id = u1.id AND t.submitter_type = 'user'// Always use CASE statements in queries:

1. User logs in via `login_process.php` → `Auth::login()` sets `$_SESSION['user_type']` (`'employee'` or `'user'`)

2. Sessions checked in controllers: `$auth->requireLogin()` → timeout check (30 min inactivity)```CASE 

3. Role-based access: `$auth->requireITStaff()` or `$auth->requireRole('admin')`

4. Redirects: Employees → `customer/dashboard.php`, IT staff/admin → `admin/` routes**Reference:** `models/Ticket.php:41-62` (findById method)    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)



**Three roles:**    ELSE u1.full_name

- `employee`: Submit tickets only (no admin access)

- `it_staff`: Manage tickets, view dashboards**Common mistake:** Forgetting to check `submitter_type` causes tickets to show wrong submitter names or fail entirely.END as submitter_name

- `admin`: Full system access, manage users/categories/SLA

```

### 4. Database Constraints & Indexing

All models use PDO with prepared statements (no raw SQL concatenation). Indexes on:### 2. MVC ArchitectureSee `models/Ticket.php:41-62` for reference implementation.

- `tickets.status`, `priority`, `submitter_id`, `created_at` (query performance)

- `users.role`, `email` (login lookups)- **Controllers** (`controllers/admin/`, `controllers/customer/`): Business logic, authorization checks, data preparation

- `employees.username` (employee login)

- Foreign keys enforce referential integrity- **Models** (`models/`): Active Record pattern with PDO prepared statements—one model per table### Authentication Flow



---- **Views** (`views/admin/`, `views/customer/`): PHP templates with layout inheritance1. Login via `includes/Auth.php` → Sets `$_SESSION['user_type']` ('employee' or 'user')



## Development Patterns- **Layouts**: `header.php` (auth check + navigation) → view → `footer.php`2. Authorization checked in controllers: `$this->auth->requireITStaff()` or `$this->auth->requireRole('admin')`



### Adding a New Page3. Three roles: `employee` (submit only), `it_staff` (manage), `admin` (full access)

1. **Create Model** (if needed): `models/NewEntity.php` extending database operations with `create()`, `findById()`, `getAll()` methods

2. **Create Controller**: `controllers/admin/NewEntityController.php` with authorization checks and data prep**Pattern: Controller → Model → View**4. Role-based redirects: IT staff → `it_dashboard.php`, Admin → `dashboard.php`

3. **Create View**: `views/admin/new_entity.view.php` with minimalist HTML

4. **Register Route**: Add link to `includes/admin_nav.php` navigation menu```php

5. **Test Auth**: Ensure controller calls `$this->auth->requireITStaff()` or `requireRole('admin')`

// DashboardController.php (controller)## Design System (Recently Updated - October 2025)

### Controller Pattern (Template)

```phppublic function index() {

class FeatureController {

    private $auth;    $this->auth->requireLogin();### Minimalist UI Guidelines

    private $model;

        $stats = $this->ticketModel->getStats($userId, $role); // Model call**DO:**

    public function __construct() {

        $this->auth = new Auth();    $this->loadView('admin/dashboard', ['stats' => $stats]); // View render- Page backgrounds: `bg-gray-50`

        $this->auth->requireITStaff(); // Check permission first!

        $this->model = new FeatureModel();}- Cards: `bg-white border border-gray-200`

    }

    - Icons: `bg-gray-100` with `text-gray-700` OR `bg-gray-900` with `text-white`

    public function index() {

        $data = $this->model->getAll(); // Call model// loadView helper: extract($data); include "$viewName.view.php"- Buttons: `bg-gray-900 text-white hover:bg-gray-800`

        $this->loadView('admin/feature', ['data' => $data]);

    }```- Hover: `hover:bg-gray-50` or `hover:border-gray-300`

    

    private function loadView($view, $data = []) {- Typography: `text-xl lg:text-2xl font-semibold text-gray-900` (headings), `text-sm text-gray-500` (body)

        extract($data); // Variables become accessible in view

        include __DIR__ . '/../../views/' . $view . '.view.php';### 3. Authentication & Authorization Flow

    }

}**Session Management** (`includes/Auth.php`):**DON'T:**

```

1. User logs in via `login_process.php` → `Auth::login()` sets `$_SESSION['user_type']` (`'employee'` or `'user'`)- ❌ Gradients: `bg-gradient-to-r`

### Model Pattern (Template)

```php2. Sessions checked in controllers: `$auth->requireLogin()` → timeout check (30 min inactivity)- ❌ Glass effects: `backdrop-blur-sm`, `bg-white/80`

class Feature {

    private $db;3. Role-based access: `$auth->requireITStaff()` or `$auth->requireRole('admin')`- ❌ Heavy shadows: `shadow-xl`, `shadow-lg`

    

    public function __construct() {4. Redirects: Employees → `customer/dashboard.php`, IT staff/admin → `admin/` routes- ❌ Rounded corners: `rounded-xl` (use square edges)

        $this->db = Database::getInstance()->getConnection(); // Singleton DB

    }- ❌ Scale animations: `group-hover:scale-110`

    

    public function getAll($filters = []) {**Three roles:**- ❌ Colorful icon backgrounds (use gray-100 or gray-900)

        $sql = "SELECT * FROM features WHERE 1=1";

        if (isset($filters['status'])) {- `employee`: Submit tickets only (no admin access)

            $sql .= " AND status = :status";

        }- `it_staff`: Manage tickets, view dashboards**Status/Priority Colors** - Keep semantic colors for badges only:

        $stmt = $this->db->prepare($sql);

        $stmt->execute($filters); // Safe prepared execution- `admin`: Full system access, manage users/categories/SLA```php

        return $stmt->fetchAll(PDO::FETCH_ASSOC);

    }// Status badges retain colored backgrounds for clarity

}

```### 4. Database Constraints & Indexing'pending' => 'bg-yellow-600 text-white'



---All models use PDO with prepared statements (no raw SQL concatenation). Indexes on:'open' => 'bg-blue-600 text-white'



## Design System (Minimalist - November 2025)- `tickets.status`, `priority`, `submitter_id`, `created_at` (query performance)'in_progress' => 'bg-purple-600 text-white'



### CSS Principles- `users.role`, `email` (login lookups)'closed' => 'bg-green-600 text-white'

**Backgrounds & Cards:**

- Page: `bg-gray-50` (light gray backdrop)- `employees.username` (employee login)```

- Cards: `bg-white border border-gray-200` (white with subtle border)

- Sections: `bg-gray-50` (visual separation)- Foreign keys enforce referential integrity



**Typography:**### Page Structure Pattern

- Headings: `text-xl lg:text-2xl font-semibold text-gray-900`

- Body: `text-sm text-gray-600`---All admin pages follow this structure:

- Labels: `text-xs text-gray-500 uppercase`

```php

**Interactions:**

- Buttons: `bg-gray-900 text-white hover:bg-gray-800`## Development Patterns<?php 

- Links: `text-blue-600 hover:underline`

- Icons: `bg-gray-100 text-gray-700` (or `bg-gray-900 text-white` for contrast)$pageTitle = 'Page Name - IT Help Desk';



### What NOT To Use ❌### Adding a New Page$baseUrl = '../';

- Gradients (`bg-gradient-to-r`, `from-slate-900`)

- Glass morphism (`backdrop-blur-md`, `bg-white/80`)1. **Create Model** (if needed): `models/NewEntity.php` extending database operations with `create()`, `findById()`, `getAll()` methodsinclude __DIR__ . '/../layouts/header.php'; 

- Heavy shadows (`shadow-xl`, `shadow-2xl`)

- Rounded corners (`rounded-xl`, `rounded-2xl` — use square `rounded-none`)2. **Create Controller**: `controllers/admin/NewEntityController.php` with authorization checks and data prep?>

- Scale animations (`group-hover:scale-110`)

- Bright/colorful backgrounds (use gray palette)3. **Create View**: `views/admin/new_entity.view.php` with minimalist HTML



### Status & Priority Badges (KEEP COLORS)4. **Register Route**: Add link to `includes/admin_nav.php` navigation menu<div class="lg:ml-64 min-h-screen bg-gray-50">

```php

'pending' => 'bg-yellow-500 text-white',5. **Test Auth**: Ensure controller calls `$this->auth->requireITStaff()` or `requireRole('admin')`    <!-- Top Bar -->

'open' => 'bg-blue-600 text-white',

'in_progress' => 'bg-purple-600 text-white',    <div class="bg-white border-b border-gray-200">

'resolved' => 'bg-green-600 text-white',

'closed' => 'bg-gray-600 text-white',### Controller Pattern (Template)        <!-- Icon: bg-gray-900 text-white, 10×10 size -->

```

```php        <!-- Title: text-xl lg:text-2xl font-semibold -->

### Sidebar Navigation

- Located in `includes/admin_nav.php` (loaded in header)class FeatureController {    </div>

- Hidden on mobile, `lg:w-64` on desktop

- Dark gray background with hover states on links    private $auth;    



---    private $model;    <!-- Content -->



## Key Integrations & Features        <div class="p-8">



### SLA (Service Level Agreement) System    public function __construct() {        <div class="bg-white border border-gray-200 p-6">

**Model**: `models/SLA.php` — Tracks response/resolution deadlines per ticket priority

- Policies stored in `sla_policies` table (priority → response_time/resolution_time)        $this->auth = new Auth();            <!-- Card content -->

- Each ticket gets `sla_tracking` record on creation

- Dashboard shows breach warnings        $this->auth->requireITStaff(); // Check permission first!        </div>

- **Pattern**: SLA times calculated from `tickets.created_at`, accounting for business hours

        $this->model = new FeatureModel();    </div>

### Notification System

**Model**: `models/Notification.php` — Supports both user_id (IT staff) and employee_id (employees)    }</div>

- Created via `Mailer.php` when tickets updated

- Dual-table query pattern similar to tickets    

- Types: ticket_created, status_changed, assigned, mentioned

- Unread count displayed in header    public function index() {<?php include __DIR__ . '/../layouts/footer.php'; ?>



### Email Notifications        $data = $this->model->getAll(); // Call model```

**Class**: `includes/Mailer.php` — Falls back to PHP `mail()` if PHPMailer unavailable

- Configured via `config/config.php` (MAIL_* constants)        $this->loadView('admin/feature', ['data' => $data]);

- Templates for: ticket_created, status_updated, assigned_to

- Sends to both submitter and assignee    }## Development Workflows



### Reports & Exports    

**File**: `export_tickets.php` — Uses PhpSpreadsheet for Excel generation

- Depends on `composer install`    private function loadView($view, $data = []) {### Adding New Features

- Filters by date range, status, priority

- Includes submitter names (uses CASE statement pattern!)        extract($data); // Variables become accessible in view1. **Create Model** (if new table): `models/FeatureName.php` extending database operations



### Charts & Analytics        include __DIR__ . '/../../views/' . $view . '.view.php';2. **Create Controller**: `controllers/admin/FeatureNameController.php` with business logic

**Setup**: `$includeChartJs = true` in controller sets Chart.js CDN in header

- Used in admin dashboard (`views/admin/dashboard.view.php`)    }3. **Create View**: `views/admin/feature_name.view.php` following minimalist pattern

- Daily ticket counts, status breakdown, priority distribution

- Data prepared in controller (`prepareChartData()` methods)}4. **Route Access**: Add to `includes/admin_nav.php` navigation



---```



## Configuration & Setup### Common Patterns



### Essential Configuration (`config/config.php`)### Model Pattern (Template)**Fetching Data in Controllers:**

```php

define('BASE_URL', 'http://localhost/IThelp/');```php```php

define('UPLOAD_DIR', __DIR__ . '/../uploads/');

define('MAX_FILE_SIZE', 5242880); // 5MBclass Feature {// Pattern used in DashboardController, ITStaffController

define('ITEMS_PER_PAGE', 10);

define('TICKET_PREFIX', 'TKT');    private $db;$stats = $this->ticketModel->getStats($userId, $role);

```

    $recentTickets = $this->ticketModel->getAll(['limit' => 5]);

### Database Connection (`config/database.php`)

- Singleton pattern: `Database::getInstance()->getConnection()`    public function __construct() {$this->loadView('admin/dashboard', compact('stats', 'recentTickets'));

- PDO with error mode exceptions enabled

- Charset: `utf8mb4` for emoji/international support        $this->db = Database::getInstance()->getConnection(); // Singleton DB```



### Autoloading (`config/config.php`)    }

SPL autoloader searches: `models/`, `controllers/`, `controllers/admin/`, `controllers/customer/`, `includes/`

- No need for manual `require` statements (classes auto-loaded by filename)    **Database Queries (Models):**



---    public function getAll($filters = []) {```php



## Common Developer Workflows        $sql = "SELECT * FROM features WHERE 1=1";// Use prepared statements ALWAYS



### Debugging a Ticket Query        if (isset($filters['status'])) {$stmt = $this->db->prepare("SELECT * FROM tickets WHERE id = :id");

1. Check if using CASE statement (dual user system)

2. Verify both `employees` and `users` tables are LEFT JOINed            $sql .= " AND status = :status";$stmt->execute([':id' => $id]);

3. Test with `$_SESSION['submitter_type']` value

4. Use prepared statements with named placeholders (`:id`, `:status`)        }return $stmt->fetch(PDO::FETCH_ASSOC);



### Adding a New Permission        $stmt = $this->db->prepare($sql);```

1. Add logic to `includes/Auth.php` (new method like `requireCustomRole()`)

2. Call in controller constructor: `$this->auth->requireCustomRole()`        $stmt->execute($filters); // Safe prepared execution

3. Test with all three roles (employee, it_staff, admin)

        return $stmt->fetchAll(PDO::FETCH_ASSOC);**Helper Functions** (defined in `config/config.php`):

### Fixing a Multi-User Bug

1. Likely a notification or email issue (forgot to send to both user types)    }- `isLoggedIn()` - Check authentication

2. Check `Notification.php` and `Mailer.php` for user_id vs employee_id handling

3. Review `includes/admin_nav.php` for duplicate display elements}- `isITStaff()` - Check IT staff or admin role



### Testing Locally```- `formatDate($date, $format)` - Consistent date formatting

```

XAMPP server: http://localhost/IThelp/- `sanitize($data)` - XSS prevention

Login credentials (see database/schema.sql):

  Admin: admin / admin123---

  IT Staff: mahfuzul / admin123

  Employee: john.doe / admin123## File Naming Conventions

  

Session timeout: 30 minutes inactivity## Design System (Minimalist - November 2025)- **Views**: `{page_name}.view.php` (e.g., `dashboard.view.php`, `tickets.view.php`)

Error display: Enabled in config/config.php (disable in production)

```- **Controllers**: `{Feature}Controller.php` (PascalCase, singular)



---### CSS Principles- **Models**: `{Entity}.php` (PascalCase, singular, matches table name)



## File Structure Reference**Backgrounds & Cards:**- **CSS/JS**: Kebab-case (e.g., `dark-mode.css`, `diagnostic-dropdown.js`)

```

controllers/admin/          # Controllers for admin routes- Page: `bg-gray-50` (light gray backdrop)

  DashboardController.php   # Main dashboard (reference for patterns)

  TicketsController.php     # Ticket CRUD operations- Cards: `bg-white border border-gray-200` (white with subtle border)## Configuration

  

models/- Sections: `bg-gray-50` (visual separation)- **Base URL**: Set in `config/config.php` → `define('BASE_URL', 'http://localhost/IThelp/')`

  Ticket.php               # CRITICAL: Check for dual user CASE statement

  User.php, Employee.php   # Both user types- **Database**: `config/database.php` using Singleton pattern

  Notification.php         # Supports user_id AND employee_id

  SLA.php                  # SLA policy tracking**Typography:**- **Autoloading**: SPL autoloader checks `models/`, `controllers/`, `includes/` directories

  

views/admin/- Headings: `text-xl lg:text-2xl font-semibold text-gray-900`- **Dependencies**: Composer for PHPMailer, PhpSpreadsheet (run `composer install`)

  dashboard.view.php       # Reference minimalist template

  tickets.view.php- Body: `text-sm text-gray-600`

  

includes/- Labels: `text-xs text-gray-500 uppercase`## Testing & Debugging

  Auth.php                 # Session & authorization logic

  admin_nav.php           # Sidebar navigation**Local Development:**

  Mailer.php              # Email sending

  **Interactions:**- XAMPP stack (Apache + MySQL)

config/

  config.php              # Global config & helper functions- Buttons: `bg-gray-900 text-white hover:bg-gray-800`- Access: `http://localhost/IThelp/`

  database.php            # PDO singleton

```- Links: `text-blue-600 hover:underline`- Error display enabled in `config/config.php` (disable in production)



---- Icons: `bg-gray-100 text-gray-700` (or `bg-gray-900 text-white` for contrast)



## Critical Gotchas**Default Credentials:**

1. **Forgetting CASE statements** when querying tickets → wrong submitter names

2. **Not checking `submitter_type`** in notifications → emails go to wrong user table### What NOT To Use ❌```

3. **Using `include` instead of `include_once`** → duplicate nav loaded

4. **Creating new tables without indexes** → dashboard queries timeout- Gradients (`bg-gradient-to-r`, `from-slate-900`)Admin: admin / admin123

5. **Hardcoding BASE_URL** → breaks on different environments

6. **Session timeout not considered** → dashboard shows stale data- Glass morphism (`backdrop-blur-md`, `bg-white/80`)IT Staff: mahfuzul / admin123  

7. **Using colorful backgrounds** on new pages → violates minimalist design

- Heavy shadows (`shadow-xl`, `shadow-2xl`)Employee: john.doe / admin123

---

- Rounded corners (`rounded-xl`, `rounded-2xl` — use square `rounded-none`)```

## Questions? Check These References

- **Route not loading?** → `includes/admin_nav.php` (add link there)- Scale animations (`group-hover:scale-110`)

- **Query returning NULL?** → `models/Ticket.php:41-62` (check CASE statement)

- **Email not sending?** → `config/config.php` (verify MAIL_* constants)- Bright/colorful backgrounds (use gray palette)**Common Issues:**

- **Auth failing?** → `includes/Auth.php:checkSession()` (session timeout?)

- **UI looking wrong?** → Search for old gradients/shadows, use minimalist classes- **Session timeouts**: Check `includes/Auth.php` timeout setting (default 30 min)


### Status & Priority Badges (KEEP COLORS)- **Dual submitter bugs**: Always join BOTH employees and users tables with CASE statements

```php- **Avatar colors**: Use `background=000000` (black) not `2563eb` (blue) for minimalist design

'pending' => 'bg-yellow-500 text-white',

'open' => 'bg-blue-600 text-white',## Key Integrations

'in_progress' => 'bg-purple-600 text-white',- **Email**: PHPMailer via `includes/Mailer.php` - configure SMTP in `config/config.php`

'resolved' => 'bg-green-600 text-white',- **Reports**: PhpSpreadsheet for Excel export - see `export_tickets.php`

'closed' => 'bg-gray-600 text-white',- **Charts**: Chart.js loaded conditionally via `$includeChartJs = true` in page variables

```- **Icons**: Font Awesome 6.4.0 (CDN)

- **Styling**: TailwindCSS via CDN (no build process)

### Sidebar Navigation

- Located in `includes/admin_nav.php` (loaded in header)## Recent Changes Log

- Hidden on mobile, `lg:w-64` on desktop**October 2025 - Minimalist Design Transformation:**

- Dark gray background with hover states on links- Converted all admin pages from glass morphism → minimalist

- Removed gradients, shadows, rounded corners, scale animations

---- Updated: IT Dashboard, Admin Dashboard, Tickets, Employees, Categories, Admin Settings

- Icon backgrounds changed from colorful gradients → gray-100 or gray-900

## Key Integrations & Features- Button styling unified to gray-900 with simple hover states



### SLA (Service Level Agreement) System## Critical Files Reference

**Model**: `models/SLA.php` — Tracks response/resolution deadlines per ticket priority- **Authentication**: `includes/Auth.php` (login, role checks, session management)

- Policies stored in `sla_policies` table (priority → response_time/resolution_time)- **Dual submitter handling**: `models/Ticket.php:41-62` (CASE statement pattern)

- Each ticket gets `sla_tracking` record on creation- **Dashboard controller pattern**: `controllers/admin/DashboardController.php`

- Dashboard shows breach warnings- **Minimalist page template**: `views/admin/dashboard.view.php` (lines 1-70, 505-623)

- **Pattern**: SLA times calculated from `tickets.created_at`, accounting for business hours- **Global config & helpers**: `config/config.php`

- **Database singleton**: `config/database.php`

### Notification System
**Model**: `models/Notification.php` — Supports both user_id (IT staff) and employee_id (employees)
- Created via `Mailer.php` when tickets updated
- Dual-table query pattern similar to tickets
- Types: ticket_created, status_changed, assigned, mentioned
- Unread count displayed in header

### Email Notifications
**Class**: `includes/Mailer.php` — Falls back to PHP `mail()` if PHPMailer unavailable
- Configured via `config/config.php` (MAIL_* constants)
- Templates for: ticket_created, status_updated, assigned_to
- Sends to both submitter and assignee

### Reports & Exports
**File**: `export_tickets.php` — Uses PhpSpreadsheet for Excel generation
- Depends on `composer install`
- Filters by date range, status, priority
- Includes submitter names (uses CASE statement pattern!)

### Charts & Analytics
**Setup**: `$includeChartJs = true` in controller sets Chart.js CDN in header
- Used in admin dashboard (`views/admin/dashboard.view.php`)
- Daily ticket counts, status breakdown, priority distribution
- Data prepared in controller (`prepareChartData()` methods)

---

## Configuration & Setup

### Essential Configuration (`config/config.php`)
```php
define('BASE_URL', 'http://localhost/IThelp/');
define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ITEMS_PER_PAGE', 10);
define('TICKET_PREFIX', 'TKT');
```

### Database Connection (`config/database.php`)
- Singleton pattern: `Database::getInstance()->getConnection()`
- PDO with error mode exceptions enabled
- Charset: `utf8mb4` for emoji/international support

### Autoloading (`config/config.php`)
SPL autoloader searches: `models/`, `controllers/`, `controllers/admin/`, `controllers/customer/`, `includes/`
- No need for manual `require` statements (classes auto-loaded by filename)

---

## Common Developer Workflows

### Debugging a Ticket Query
1. Check if using CASE statement (dual user system)
2. Verify both `employees` and `users` tables are LEFT JOINed
3. Test with `$_SESSION['submitter_type']` value
4. Use prepared statements with named placeholders (`:id`, `:status`)

### Adding a New Permission
1. Add logic to `includes/Auth.php` (new method like `requireCustomRole()`)
2. Call in controller constructor: `$this->auth->requireCustomRole()`
3. Test with all three roles (employee, it_staff, admin)

### Fixing a Multi-User Bug
1. Likely a notification or email issue (forgot to send to both user types)
2. Check `Notification.php` and `Mailer.php` for user_id vs employee_id handling
3. Review `includes/admin_nav.php` for duplicate display elements

### Testing Locally
```
XAMPP server: http://localhost/IThelp/
Login credentials (see database/schema.sql):
  Admin: admin / admin123
  IT Staff: mahfuzul / admin123
  Employee: john.doe / admin123
  
Session timeout: 30 minutes inactivity
Error display: Enabled in config/config.php (disable in production)
```

---

## File Structure Reference
```
controllers/admin/          # Controllers for admin routes
  DashboardController.php   # Main dashboard (reference for patterns)
  TicketsController.php     # Ticket CRUD operations
  
models/
  Ticket.php               # CRITICAL: Check for dual user CASE statement
  User.php, Employee.php   # Both user types
  Notification.php         # Supports user_id AND employee_id
  SLA.php                  # SLA policy tracking
  
views/admin/
  dashboard.view.php       # Reference minimalist template
  tickets.view.php
  
includes/
  Auth.php                 # Session & authorization logic
  admin_nav.php           # Sidebar navigation
  Mailer.php              # Email sending
  
config/
  config.php              # Global config & helper functions
  database.php            # PDO singleton
```

---

## Critical Gotchas
1. **Forgetting CASE statements** when querying tickets → wrong submitter names
2. **Not checking `submitter_type`** in notifications → emails go to wrong user table
3. **Using `include` instead of `include_once`** → duplicate nav loaded
4. **Creating new tables without indexes** → dashboard queries timeout
5. **Hardcoding BASE_URL** → breaks on different environments
6. **Session timeout not considered** → dashboard shows stale data
7. **Using colorful backgrounds** on new pages → violates minimalist design

---

## Questions? Check These References
- **Route not loading?** → `includes/admin_nav.php` (add link there)
- **Query returning NULL?** → `models/Ticket.php:41-62` (check CASE statement)
- **Email not sending?** → `config/config.php` (verify MAIL_* constants)
- **Auth failing?** → `includes/Auth.php:checkSession()` (session timeout?)
- **UI looking wrong?** → Search for old gradients/shadows, use minimalist classes
