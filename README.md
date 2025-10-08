# IT Help Desk Ticketing System

A comprehensive, production-ready ticketing system built with PHP and TailwindCSS for managing IT support requests efficiently.

![IT Help Desk Dashboard](screenshot.png)

## 📋 Table of Contents

- [Features](#features)
- [System Requirements](#system-requirements)
- [Installation](#installation)
- [Folder Structure](#folder-structure)
- [Configuration](#configuration)
- [Usage](#usage)
- [User Roles](#user-roles)
- [Database Schema](#database-schema)
- [Email Notifications](#email-notifications)
- [Report Generation](#report-generation)
- [Security Features](#security-features)
- [Troubleshooting](#troubleshooting)
- [License](#license)

## ✨ Features

### Core Functionality
- **User Authentication**: Secure login/logout with role-based access control
- **Ticket Management**: Create, view, update, and track support tickets
- **Dashboard Analytics**: Visual charts and statistics for ticket metrics
- **Real-time Status Updates**: Track ticket progress from submission to resolution
- **Activity Logging**: Complete audit trail of all ticket activities
- **File Attachments**: Support for uploading files with tickets
- **Priority Levels**: Four priority levels (Low, Medium, High, Urgent)
- **Category Management**: Organize tickets by predefined categories

### User Features
- **Employee Portal**: Submit tickets and track their status
- **IT Staff Dashboard**: Manage and resolve tickets
- **Admin Panel**: Full system administration
- **Search & Filters**: Advanced filtering by status, priority, category
- **Responsive Design**: Works seamlessly on desktop, tablet, and mobile

### Reporting & Notifications
- **Email Notifications**: Automated emails for ticket events (PHPMailer)
- **Excel Export**: Generate detailed reports in Excel format (PhpSpreadsheet)
- **Activity Timeline**: View complete history of ticket updates
- **Statistics Dashboard**: Visual representation of ticket data

## 🔧 System Requirements

- **PHP**: 7.4 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.2+
- **Web Server**: Apache 2.4+ or Nginx
- **Extensions**: 
  - PDO
  - PDO_MySQL
  - GD
  - Zip
  - XML
  - MBString

## 📦 Installation

### Step 1: Clone/Download the Project

```bash
# If using Git
git clone https://github.com/AYRGO/IThelp.git

# Or download and extract to your web server directory
# For XAMPP: C:\xampp\htdocs\IThelp
# For WAMP: C:\wamp64\www\IThelp
```

### Step 2: Install Dependencies

```bash
cd IThelp
composer install
```

If you don't have Composer installed, download it from [getcomposer.org](https://getcomposer.org/)

### Step 3: Create Database

1. Open phpMyAdmin (http://localhost/phpmyadmin)
2. Create a new database named `ithelp_db`
3. Import the database schema:
   - Navigate to the database
   - Click "Import"
   - Select `database/schema.sql`
   - Click "Go"

### Step 4: Configure Database Connection

Edit `config/database.php` and update the database credentials:

```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'ithelp_db');   // Database name
```

### Step 5: Configure Base URL

Edit `config/config.php` and set the correct base URL:

```php
define('BASE_URL', 'http://localhost/IThelp/');
```

### Step 6: Set Up File Upload Directory

```bash
# Create uploads directory with write permissions
mkdir uploads
chmod 777 uploads  # On Linux/Mac
```

On Windows, ensure the web server has write permissions to the `uploads` folder.

### Step 7: Configure Email (Optional)

Edit `config/config.php` and update email settings:

```php
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'your-email@gmail.com');
define('MAIL_PASSWORD', 'your-app-password');
define('MAIL_FROM_EMAIL', 'noreply@company.com');
define('MAIL_FROM_NAME', 'IT Help Desk');
```

**For Gmail**: Generate an App Password from your Google Account settings.

### Step 8: Access the Application

Open your browser and navigate to:
```
http://localhost/IThelp/
```

## 🔑 Default Login Credentials

| Role | Username | Password | Description |
|------|----------|----------|-------------|
| Admin | admin | admin123 | Full system access |
| IT Staff | mahfuzul | admin123 | Manage and resolve tickets |
| Employee | john.doe | admin123 | Submit and track tickets |

**⚠️ IMPORTANT**: Change these passwords immediately in production!

## 📁 Folder Structure

```
IThelp/
│
├── config/                      # Configuration files
│   ├── config.php              # Main application config
│   └── database.php            # Database connection
│
├── models/                      # Data models (Business Logic)
│   ├── User.php                # User management
│   ├── Ticket.php              # Ticket operations
│   ├── Category.php            # Category management
│   └── TicketActivity.php      # Activity logging
│
├── includes/                    # Helper classes and utilities
│   ├── Auth.php                # Authentication handler
│   ├── Mailer.php              # Email notification system
│   └── ReportGenerator.php     # Excel report generator
│
├── database/                    # Database files
│   └── schema.sql              # Database structure and seed data
│
├── uploads/                     # File upload directory
│   └── (user uploaded files)
│
├── views/                       # Frontend pages
│   ├── dashboard.php           # Main dashboard
│   ├── tickets.php             # Tickets list
│   ├── create_ticket.php       # Ticket creation form
│   ├── view_ticket.php         # Ticket details
│   ├── customers.php           # Customer management
│   ├── categories.php          # Category management
│   └── article.php             # Knowledge base
│
├── login.php                    # Login page
├── login_process.php            # Login handler
├── logout.php                   # Logout handler
├── export_tickets.php           # Excel export
├── index.php                    # Entry point
├── .htaccess                    # Apache configuration
├── composer.json                # PHP dependencies
└── README.md                    # This file
```

### Folder Structure Explanation

#### `/config`
Contains all configuration files. Separates configuration from business logic for easier maintenance and deployment across environments.

#### `/models`
Data models following the Active Record pattern. Each model represents a database table and contains all related business logic and database operations.

#### `/includes`
Helper classes and utilities that support the main application. These are reusable components used across multiple pages.

#### `/database`
SQL scripts for database creation and seeding. Keeps database structure version-controlled.

#### `/uploads`
Storage for user-uploaded files. Should have write permissions and be backed up regularly.

#### Root Directory
Contains view files (pages) that users interact with. Each file handles both display and form processing.

## ⚙️ Configuration

### Application Settings

Edit `config/config.php` to customize:

```php
// Application Name
define('APP_NAME', 'Simply Web');

// Timezone
define('APP_TIMEZONE', 'UTC');

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf', 'doc', 'docx']);

// Pagination
define('ITEMS_PER_PAGE', 10);

// Session Timeout (in seconds)
$timeout = 1800; // 30 minutes
```

### Email Configuration

For production, configure SMTP settings properly:

```php
// Gmail Example
define('MAIL_HOST', 'smtp.gmail.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');

// Office 365 Example
define('MAIL_HOST', 'smtp.office365.com');
define('MAIL_PORT', 587);
define('MAIL_ENCRYPTION', 'tls');

// Custom SMTP Example
define('MAIL_HOST', 'mail.yourdomain.com');
define('MAIL_PORT', 465);
define('MAIL_ENCRYPTION', 'ssl');
```

## 👥 User Roles

### Employee
- Submit new tickets
- View their own tickets
- Track ticket status
- Add comments to tickets
- Upload attachments

### IT Staff
- View all tickets
- Update ticket status
- Assign tickets to team members
- Add resolutions
- Access customer list
- View categories and statistics
- Export reports

### Admin
- All IT Staff permissions
- Manage users
- System configuration
- Full access to all features

## 🗄️ Database Schema

### Users Table
Stores all system users (employees, IT staff, admins)

```sql
- id (Primary Key)
- username (Unique)
- email (Unique)
- password (Hashed)
- full_name
- role (employee, it_staff, admin)
- department
- phone
- is_active
- created_at
- updated_at
```

### Tickets Table
Main ticketing data

```sql
- id (Primary Key)
- ticket_number (Unique)
- title
- description
- category_id (Foreign Key)
- priority (low, medium, high, urgent)
- status (pending, open, in_progress, resolved, closed)
- submitter_id (Foreign Key -> users)
- assigned_to (Foreign Key -> users)
- resolution
- attachments
- created_at
- updated_at
- resolved_at
- closed_at
```

### Categories Table
Ticket categories

```sql
- id (Primary Key)
- name
- description
- icon
- color
- is_active
- created_at
```

### Ticket Activity Table
Activity log for all ticket actions

```sql
- id (Primary Key)
- ticket_id (Foreign Key)
- user_id (Foreign Key)
- action_type
- old_value
- new_value
- comment
- created_at
```

## 📧 Email Notifications

The system sends automatic emails for:

1. **Ticket Created**: Confirmation to submitter
2. **Ticket Assigned**: Notification to assigned IT staff
3. **Status Updated**: Alert to submitter when status changes
4. **Ticket Resolved**: Notification with resolution details

### Customizing Email Templates

Edit `includes/Mailer.php` to modify email templates:

```php
private function getTicketCreatedTemplate($ticket, $submitter) {
    // Customize HTML email template here
}
```

## 📊 Report Generation

### Export Tickets

Navigate to Tickets page and click "Export" button to generate Excel reports.

**Features:**
- All tickets with filters applied
- Summary statistics
- Category breakdown
- Customizable date ranges

### Report Types

1. **Tickets Report**: Detailed list of all tickets
2. **Summary Report**: Overview with statistics

### Custom Reports

Extend `includes/ReportGenerator.php` to create custom reports:

```php
public function generateCustomReport() {
    // Add your custom report logic
}
```

## 🔒 Security Features

- **Password Hashing**: Uses PHP's `password_hash()` with bcrypt
- **SQL Injection Protection**: Prepared statements with PDO
- **XSS Prevention**: All output is escaped with `htmlspecialchars()`
- **CSRF Protection**: Can be implemented using tokens
- **Session Management**: Automatic timeout after inactivity
- **File Upload Validation**: Type and size restrictions
- **Role-Based Access Control**: Granular permissions

### Security Best Practices

1. **Change Default Passwords**: Immediately update all default credentials
2. **Use HTTPS**: Enable SSL certificate in production
3. **Regular Updates**: Keep PHP, MySQL, and dependencies updated
4. **Backup Database**: Schedule regular automated backups
5. **File Permissions**: Restrict write permissions where possible
6. **Error Logging**: Disable display_errors in production

## 🐛 Troubleshooting

### Database Connection Error

**Problem**: "Database connection failed"

**Solution**:
- Verify database credentials in `config/database.php`
- Ensure MySQL service is running
- Check if database exists
- Verify user has proper permissions

### Email Not Sending

**Problem**: Notifications not being received

**Solution**:
- Check SMTP credentials in `config/config.php`
- Enable "Less secure app access" for Gmail
- Use App Password for Gmail
- Check spam folder
- Verify mail server allows connections

### File Upload Failed

**Problem**: Cannot upload attachments

**Solution**:
- Ensure `uploads/` directory exists
- Check directory permissions (777 on Linux/Mac)
- Verify `MAX_FILE_SIZE` setting
- Check `upload_max_filesize` in php.ini

### Session Timeout Issues

**Problem**: Getting logged out too quickly

**Solution**:
- Adjust timeout in `includes/Auth.php`:
  ```php
  $timeout = 3600; // 1 hour
  ```

### Charts Not Displaying

**Problem**: Dashboard charts are blank

**Solution**:
- Ensure Chart.js CDN is accessible
- Check browser console for JavaScript errors
- Verify data is being fetched from database

## 🚀 Deployment to Production

### Pre-Deployment Checklist

- [ ] Change all default passwords
- [ ] Update `BASE_URL` in config
- [ ] Configure proper SMTP settings
- [ ] Set `display_errors = 0` in php.ini
- [ ] Enable error logging
- [ ] Set up database backups
- [ ] Install SSL certificate
- [ ] Review file permissions
- [ ] Test all functionality
- [ ] Set up monitoring

### Recommended Server Configuration

**Apache `.htaccess`**:
```apache
# Already included in the project
RewriteEngine On
Options -Indexes
```

**PHP Settings** (php.ini):
```ini
upload_max_filesize = 10M
post_max_size = 10M
max_execution_time = 300
memory_limit = 256M
display_errors = Off
log_errors = On
error_log = /path/to/error.log
```

## 📞 Support

For issues, questions, or contributions:

- **GitHub**: [https://github.com/AYRGO/IThelp](https://github.com/AYRGO/IThelp)
- **Email**: support@company.com

## 📄 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 👏 Acknowledgments

- **TailwindCSS**: For the beautiful UI framework
- **Font Awesome**: For the icon library
- **Chart.js**: For dashboard visualizations
- **PHPMailer**: For email functionality
- **PhpSpreadsheet**: For Excel export capabilities

---

**Built with ❤️ for efficient IT support management**
