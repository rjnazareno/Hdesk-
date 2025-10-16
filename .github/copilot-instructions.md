# IT Help Desk - AI Coding Agent Instructions

## Project Overview
IT Help Desk Ticketing System built with **PHP 7.4+, MySQL, TailwindCSS**. Uses MVC-inspired architecture with Controllers handling business logic and Models for data access. Recently transformed from glass morphism to **minimalist design** (gray-scale palette, no gradients/shadows).

## Architecture & Code Organization

### MVC Pattern (Custom Implementation)
- **Controllers**: `controllers/admin/` - Handle business logic, prepare data for views
  - Example: `DashboardController.php` initializes models, fetches data, passes to view
  - Controllers check authentication/authorization via `Auth` class
- **Models**: `models/` - Active Record pattern, one class per table
  - Direct database access via PDO (no ORM)
  - Example: `Ticket.php`, `User.php`, `Category.php`
- **Views**: `views/admin/` - PHP templates with embedded logic
  - Structure: `views/layouts/header.php` + `views/admin/{page}.view.php` + `views/layouts/footer.php`
  - Views receive data as variables from controllers (e.g., `$currentUser`, `$stats`)

### Database Architecture
**Dual User System** - Critical pattern to understand:
```php
// Tickets can be submitted by TWO user types:
tickets.submitter_type = 'employee' → links to employees table
tickets.submitter_type = 'user' → links to users table

// Always use CASE statements in queries:
CASE 
    WHEN t.submitter_type = 'employee' THEN CONCAT(e.fname, ' ', e.lname)
    ELSE u1.full_name
END as submitter_name
```
See `models/Ticket.php:41-62` for reference implementation.

### Authentication Flow
1. Login via `includes/Auth.php` → Sets `$_SESSION['user_type']` ('employee' or 'user')
2. Authorization checked in controllers: `$this->auth->requireITStaff()` or `$this->auth->requireRole('admin')`
3. Three roles: `employee` (submit only), `it_staff` (manage), `admin` (full access)
4. Role-based redirects: IT staff → `it_dashboard.php`, Admin → `dashboard.php`

## Design System (Recently Updated - October 2025)

### Minimalist UI Guidelines
**DO:**
- Page backgrounds: `bg-gray-50`
- Cards: `bg-white border border-gray-200`
- Icons: `bg-gray-100` with `text-gray-700` OR `bg-gray-900` with `text-white`
- Buttons: `bg-gray-900 text-white hover:bg-gray-800`
- Hover: `hover:bg-gray-50` or `hover:border-gray-300`
- Typography: `text-xl lg:text-2xl font-semibold text-gray-900` (headings), `text-sm text-gray-500` (body)

**DON'T:**
- ❌ Gradients: `bg-gradient-to-r`
- ❌ Glass effects: `backdrop-blur-sm`, `bg-white/80`
- ❌ Heavy shadows: `shadow-xl`, `shadow-lg`
- ❌ Rounded corners: `rounded-xl` (use square edges)
- ❌ Scale animations: `group-hover:scale-110`
- ❌ Colorful icon backgrounds (use gray-100 or gray-900)

**Status/Priority Colors** - Keep semantic colors for badges only:
```php
// Status badges retain colored backgrounds for clarity
'pending' => 'bg-yellow-600 text-white'
'open' => 'bg-blue-600 text-white'
'in_progress' => 'bg-purple-600 text-white'
'closed' => 'bg-green-600 text-white'
```

### Page Structure Pattern
All admin pages follow this structure:
```php
<?php 
$pageTitle = 'Page Name - IT Help Desk';
$baseUrl = '../';
include __DIR__ . '/../layouts/header.php'; 
?>

<div class="lg:ml-64 min-h-screen bg-gray-50">
    <!-- Top Bar -->
    <div class="bg-white border-b border-gray-200">
        <!-- Icon: bg-gray-900 text-white, 10×10 size -->
        <!-- Title: text-xl lg:text-2xl font-semibold -->
    </div>
    
    <!-- Content -->
    <div class="p-8">
        <div class="bg-white border border-gray-200 p-6">
            <!-- Card content -->
        </div>
    </div>
</div>

<?php include __DIR__ . '/../layouts/footer.php'; ?>
```

## Development Workflows

### Adding New Features
1. **Create Model** (if new table): `models/FeatureName.php` extending database operations
2. **Create Controller**: `controllers/admin/FeatureNameController.php` with business logic
3. **Create View**: `views/admin/feature_name.view.php` following minimalist pattern
4. **Route Access**: Add to `includes/admin_nav.php` navigation

### Common Patterns
**Fetching Data in Controllers:**
```php
// Pattern used in DashboardController, ITStaffController
$stats = $this->ticketModel->getStats($userId, $role);
$recentTickets = $this->ticketModel->getAll(['limit' => 5]);
$this->loadView('admin/dashboard', compact('stats', 'recentTickets'));
```

**Database Queries (Models):**
```php
// Use prepared statements ALWAYS
$stmt = $this->db->prepare("SELECT * FROM tickets WHERE id = :id");
$stmt->execute([':id' => $id]);
return $stmt->fetch(PDO::FETCH_ASSOC);
```

**Helper Functions** (defined in `config/config.php`):
- `isLoggedIn()` - Check authentication
- `isITStaff()` - Check IT staff or admin role
- `formatDate($date, $format)` - Consistent date formatting
- `sanitize($data)` - XSS prevention

## File Naming Conventions
- **Views**: `{page_name}.view.php` (e.g., `dashboard.view.php`, `tickets.view.php`)
- **Controllers**: `{Feature}Controller.php` (PascalCase, singular)
- **Models**: `{Entity}.php` (PascalCase, singular, matches table name)
- **CSS/JS**: Kebab-case (e.g., `dark-mode.css`, `diagnostic-dropdown.js`)

## Configuration
- **Base URL**: Set in `config/config.php` → `define('BASE_URL', 'http://localhost/IThelp/')`
- **Database**: `config/database.php` using Singleton pattern
- **Autoloading**: SPL autoloader checks `models/`, `controllers/`, `includes/` directories
- **Dependencies**: Composer for PHPMailer, PhpSpreadsheet (run `composer install`)

## Testing & Debugging
**Local Development:**
- XAMPP stack (Apache + MySQL)
- Access: `http://localhost/IThelp/`
- Error display enabled in `config/config.php` (disable in production)

**Default Credentials:**
```
Admin: admin / admin123
IT Staff: mahfuzul / admin123  
Employee: john.doe / admin123
```

**Common Issues:**
- **Session timeouts**: Check `includes/Auth.php` timeout setting (default 30 min)
- **Dual submitter bugs**: Always join BOTH employees and users tables with CASE statements
- **Avatar colors**: Use `background=000000` (black) not `2563eb` (blue) for minimalist design

## Key Integrations
- **Email**: PHPMailer via `includes/Mailer.php` - configure SMTP in `config/config.php`
- **Reports**: PhpSpreadsheet for Excel export - see `export_tickets.php`
- **Charts**: Chart.js loaded conditionally via `$includeChartJs = true` in page variables
- **Icons**: Font Awesome 6.4.0 (CDN)
- **Styling**: TailwindCSS via CDN (no build process)

## Recent Changes Log
**October 2025 - Minimalist Design Transformation:**
- Converted all admin pages from glass morphism → minimalist
- Removed gradients, shadows, rounded corners, scale animations
- Updated: IT Dashboard, Admin Dashboard, Tickets, Employees, Categories, Admin Settings
- Icon backgrounds changed from colorful gradients → gray-100 or gray-900
- Button styling unified to gray-900 with simple hover states

## Critical Files Reference
- **Authentication**: `includes/Auth.php` (login, role checks, session management)
- **Dual submitter handling**: `models/Ticket.php:41-62` (CASE statement pattern)
- **Dashboard controller pattern**: `controllers/admin/DashboardController.php`
- **Minimalist page template**: `views/admin/dashboard.view.php` (lines 1-70, 505-623)
- **Global config & helpers**: `config/config.php`
- **Database singleton**: `config/database.php`
