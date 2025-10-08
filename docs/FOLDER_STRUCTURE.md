# Project Structure Documentation

This document explains the organization and purpose of each directory and file in the IT Help Desk Ticketing System.

## Architecture Overview

The project follows a **Model-View pattern** with separated concerns for maintainability and scalability. It's organized to support:

- Clean separation of business logic and presentation
- Easy maintenance and updates
- Scalability for future features
- Security through proper file organization

## Directory Structure

### `/config` - Configuration Files

**Purpose**: Centralized configuration management

**Files**:
- `config.php` - Main application configuration (URLs, settings, helper functions)
- `database.php` - Database connection using Singleton pattern

**Why separate?**
- Easy environment switching (dev/staging/production)
- Single point for configuration changes
- Prevents hardcoding values throughout the application

---

### `/models` - Data Models

**Purpose**: Database interaction and business logic

**Files**:
- `User.php` - User management (CRUD, authentication)
- `Ticket.php` - Ticket operations (create, read, update, statistics)
- `Category.php` - Category management
- `TicketActivity.php` - Activity logging

**Pattern**: Active Record - Each class represents a database table

**Why this structure?**
- Encapsulates database logic
- Reusable across different views
- Easier to test and maintain
- Follows DRY principle (Don't Repeat Yourself)

---

### `/includes` - Helper Classes

**Purpose**: Reusable utilities and services

**Files**:
- `Auth.php` - Authentication and session management
- `Mailer.php` - Email notifications (PHPMailer wrapper)
- `ReportGenerator.php` - Excel report generation (PhpSpreadsheet wrapper)

**Why separate from models?**
- These are services, not data models
- Can be used across multiple models
- Easier to swap implementations (e.g., different email service)

---

### `/database` - Database Scripts

**Purpose**: Version-controlled database structure

**Files**:
- `schema.sql` - Complete database structure with seed data

**Benefits**:
- Easy database recreation
- Version control for schema changes
- Documentation of database structure
- Simplified deployment to new environments

---

### `/uploads` - User Uploads

**Purpose**: Storage for user-uploaded files

**Security Considerations**:
- Should have write permissions
- Listed in .gitignore (don't commit user files)
- Consider moving outside web root in production
- Implement file type validation

---

### Root Directory - View Files

**Purpose**: User-facing pages

**Main Files**:
- `index.php` - Entry point (redirects to login/dashboard)
- `login.php` - Login page
- `login_process.php` - Login form handler
- `logout.php` - Logout handler
- `dashboard.php` - Main dashboard with analytics
- `tickets.php` - Ticket list with filters
- `create_ticket.php` - Ticket creation form
- `view_ticket.php` - Ticket details and updates
- `customers.php` - Customer management (IT staff only)
- `categories.php` - Category overview
- `article.php` - Knowledge base placeholder
- `export_tickets.php` - Excel export handler

**Why in root?**
- Easy URL structure (e.g., /tickets.php instead of /views/tickets.php)
- Common PHP convention
- Each file is a complete page (view + logic)

---

## File Organization Principles

### 1. Separation of Concerns

```
config/     → Application settings
models/     → Data and business logic
includes/   → Services and utilities
root/       → User interface
```

### 2. Single Responsibility

Each file has one clear purpose:
- `User.php` → Only user-related operations
- `Auth.php` → Only authentication logic
- `Mailer.php` → Only email functionality

### 3. DRY (Don't Repeat Yourself)

Common functions are centralized:
- Database connection → `database.php`
- Helper functions → `config.php`
- Email templates → `Mailer.php`

### 4. Security by Design

```
config/         → Protected by .htaccess
uploads/        → Direct listing disabled
models/         → Not directly accessible
includes/       → Not directly accessible
```

## Comparison with MVC Frameworks

### Traditional MVC (e.g., Laravel, CodeIgniter)

```
/app
  /Controllers     → Handle requests, call models
  /Models          → Database interaction
  /Views           → Display templates
/public            → Publicly accessible files
/routes            → URL routing
```

### This Project (Simplified MVC)

```
/config            → Configuration
/models            → Models (same as MVC)
/includes          → Services (like Controllers but specialized)
/                  → Views + Controllers combined
```

**Why combined View + Controller?**
- Simpler for smaller projects
- Less boilerplate code
- Easier to understand for beginners
- Sufficient for this scale of application

## Adding New Features

### To add a new page:

1. Create file in root (e.g., `reports.php`)
2. Include config: `require_once 'config/config.php'`
3. Add authentication check
4. Build UI with TailwindCSS
5. Add sidebar link in navigation

### To add new data model:

1. Create table in database
2. Add model class in `/models` (e.g., `Report.php`)
3. Implement CRUD methods
4. Use in view files

### To add new service:

1. Create class in `/includes` (e.g., `SMS.php`)
2. Implement required methods
3. Use across application

## Best Practices Used

### 1. Security
- Prepared statements (PDO) prevent SQL injection
- `htmlspecialchars()` prevents XSS
- Password hashing with bcrypt
- Session management with timeouts

### 2. Code Organization
- One class per file
- Descriptive file and function names
- Comments for complex logic
- Consistent naming conventions

### 3. Database
- Foreign keys for referential integrity
- Indexes on frequently queried columns
- Soft deletes where appropriate
- Audit trail (ticket_activity)

### 4. Frontend
- TailwindCSS for consistent styling
- Font Awesome for icons
- Chart.js for visualizations
- Responsive design

## Scalability Considerations

### Current Scale: Small to Medium (1-500 users)

**Can scale by:**
1. Adding caching (Redis/Memcached)
2. Implementing queue system for emails
3. Moving uploads to cloud storage (S3, etc.)
4. Adding API endpoints for mobile app
5. Implementing full MVC framework for larger scale

### When to Refactor:

**Consider full MVC framework when:**
- More than 10 different page types
- Team of 3+ developers
- Need for complex routing
- API requirements
- Mobile app integration

**For now:**
- Current structure is optimal
- Easy to understand and maintain
- Minimal learning curve
- Good performance

## Maintenance Guide

### Daily Tasks
- Monitor error logs
- Check upload folder size

### Weekly Tasks
- Database backup
- Review open tickets
- Check email delivery

### Monthly Tasks
- Update dependencies (composer update)
- Review user accounts
- Analyze statistics

### Quarterly Tasks
- Security audit
- Performance optimization
- User feedback review

## Conclusion

This folder structure balances:
- **Simplicity**: Easy to understand and navigate
- **Maintainability**: Organized and documented
- **Security**: Proper file organization
- **Scalability**: Can grow with your needs

The structure is production-ready for small to medium organizations and can be enhanced as requirements grow.
