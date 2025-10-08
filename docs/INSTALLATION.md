# Installation Guide - IT Help Desk Ticketing System

This guide will walk you through the complete installation process step-by-step.

## Prerequisites

Before you begin, ensure you have:

1. **XAMPP** (recommended) or any other local server package installed
   - Download from: https://www.apachefriends.org/
   - Includes Apache, MySQL, and PHP

2. **Composer** - PHP dependency manager
   - Download from: https://getcomposer.org/
   - Required for installing PHPMailer and PhpSpreadsheet

3. **Web Browser** - Chrome, Firefox, Edge, or Safari

## Step-by-Step Installation

### 1. Start XAMPP Services

1. Open XAMPP Control Panel
2. Start **Apache** service
3. Start **MySQL** service
4. Verify both services are running (green indicators)

### 2. Extract Project Files

1. Navigate to `C:\xampp\htdocs\`
2. Ensure the project folder is named `IThelp`
3. Your path should be: `C:\xampp\htdocs\IThelp\`

### 3. Install PHP Dependencies

1. Open Command Prompt or PowerShell
2. Navigate to project directory:
   ```bash
   cd C:\xampp\htdocs\IThelp
   ```
3. Install dependencies:
   ```bash
   composer install
   ```
4. Wait for installation to complete

### 4. Create Database

**Option A: Using phpMyAdmin (Recommended)**

1. Open browser and go to: `http://localhost/phpmyadmin`
2. Click "New" in the left sidebar
3. Enter database name: `ithelp`
4. Select Collation: `utf8mb4_unicode_ci`
5. Click "Create"

**Option B: Using SQL Command**

1. In phpMyAdmin, click "SQL" tab
2. Run:
   ```sql
   CREATE DATABASE ithelp_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   ```

### 5. Import Database Schema

1. Select `ithelp_db` database from left sidebar
2. Click "Import" tab
3. Click "Choose File"
4. Navigate to: `C:\xampp\htdocs\IThelp\database\schema.sql`
5. Click "Go" at the bottom
6. Wait for "Import has been successfully finished" message

### 6. Verify Database Import

Check that the following tables were created:
- `users` (3 sample users)
- `categories` (6 categories)
- `tickets` (4 sample tickets)
- `ticket_activity` (7 activity logs)

### 7. Configure Database Connection

1. Open `config/database.php` in a text editor
2. Verify/Update settings:
   ```php
   define('DB_HOST', 'localhost');
   define('DB_USER', 'root');
   define('DB_PASS', '');          // Usually empty for XAMPP
   define('DB_NAME', 'ithelp_db');
   ```

### 8. Configure Application Settings

1. Open `config/config.php`
2. Update `BASE_URL`:
   ```php
   define('BASE_URL', 'http://localhost/IThelp/');
   ```
3. Save the file

### 9. Create Upload Directory

The `uploads/` folder should already exist. If not:

**Windows:**
1. Create folder: `C:\xampp\htdocs\IThelp\uploads`
2. Right-click folder > Properties > Security
3. Ensure "Users" group has write permissions

**Linux/Mac:**
```bash
mkdir uploads
chmod 777 uploads
```

### 10. Configure Email (Optional)

For email notifications to work:

1. Open `config/config.php`
2. Update email settings:
   ```php
   define('MAIL_HOST', 'smtp.gmail.com');
   define('MAIL_PORT', 587);
   define('MAIL_USERNAME', 'your-email@gmail.com');
   define('MAIL_PASSWORD', 'your-app-password');
   ```

**For Gmail:**
- Go to: https://myaccount.google.com/security
- Enable 2-Step Verification
- Generate App Password
- Use that password in config

**Note:** Email functionality is optional. The system works without it, but users won't receive notifications.

### 11. Test the Installation

1. Open browser
2. Go to: `http://localhost/IThelp/`
3. You should see the login page

### 12. Login with Test Account

Use any of these credentials:

**Admin Account:**
- Username: `admin`
- Password: `admin123`

**IT Staff Account:**
- Username: `mahfuzul`
- Password: `admin123`

**Employee Account:**
- Username: `john.doe`
- Password: `admin123`

### 13. Verify Core Features

After logging in, test:

1. ✅ Dashboard loads with charts
2. ✅ View tickets list
3. ✅ Create a new ticket
4. ✅ View ticket details
5. ✅ Update ticket status (IT staff only)
6. ✅ View customers (IT staff only)
7. ✅ View categories

## Troubleshooting Common Issues

### Issue: "Database connection failed"

**Cause:** Incorrect database credentials or MySQL not running

**Solution:**
1. Check MySQL is running in XAMPP Control Panel
2. Verify database credentials in `config/database.php`
3. Ensure database `ithelp_db` exists in phpMyAdmin

### Issue: "Page not found" or "404 Error"

**Cause:** Incorrect base URL or .htaccess issues

**Solution:**
1. Verify `BASE_URL` in `config/config.php` matches your setup
2. Ensure `.htaccess` file exists in root directory
3. Enable `mod_rewrite` in Apache:
   - Open `C:\xampp\apache\conf\httpd.conf`
   - Find: `#LoadModule rewrite_module modules/mod_rewrite.so`
   - Remove `#` to uncomment
   - Restart Apache

### Issue: "Cannot upload files"

**Cause:** Upload directory permissions

**Solution:**
1. Ensure `uploads/` directory exists
2. Give write permissions to the folder
3. Check `upload_max_filesize` in php.ini (should be at least 5M)

### Issue: "Composer command not found"

**Cause:** Composer not installed or not in PATH

**Solution:**
1. Download Composer from: https://getcomposer.org/download/
2. Run installer
3. Restart Command Prompt/PowerShell
4. Try again: `composer install`

### Issue: "Session timeout too fast"

**Cause:** Default session timeout is 30 minutes

**Solution:**
1. Open `includes/Auth.php`
2. Find: `$timeout = 1800;`
3. Change to desired seconds (e.g., 3600 for 1 hour)

### Issue: Charts not displaying

**Cause:** JavaScript/CDN loading issue

**Solution:**
1. Check internet connection (Chart.js loads from CDN)
2. Clear browser cache
3. Check browser console for errors (F12)

## Post-Installation Steps

### 1. Change Default Passwords

For security, change all default passwords:

1. Login as each user
2. Navigate to profile/settings (when implemented)
3. Update password

Or update directly in database:
```sql
UPDATE users 
SET password = '$2y$10$YOUR_NEW_HASHED_PASSWORD' 
WHERE username = 'admin';
```

Use PHP to generate hash:
```php
echo password_hash('your_new_password', PASSWORD_DEFAULT);
```

### 2. Configure for Production

If deploying to a live server:

1. Update `BASE_URL` to your domain
2. Change database credentials
3. Set up proper SMTP for emails
4. Enable HTTPS/SSL
5. Set `display_errors = 0` in php.ini
6. Set up regular database backups

### 3. Add Real Users

1. Login as admin
2. Create actual user accounts
3. Delete or disable demo accounts

### 4. Customize Categories

1. Navigate to Categories page
2. Add/edit categories relevant to your organization
3. Set appropriate colors and icons

## Getting Help

If you encounter issues:

1. Check this installation guide
2. Review the main README.md
3. Check error logs:
   - PHP errors: `C:\xampp\php\logs\php_error_log`
   - Apache errors: `C:\xampp\apache\logs\error.log`
4. Create an issue on GitHub with:
   - Error message
   - Steps to reproduce
   - Your environment (PHP version, OS, etc.)

## Next Steps

Now that installation is complete:

1. Explore the dashboard
2. Create test tickets
3. Test different user roles
4. Configure email notifications
5. Customize for your organization
6. Add real users and categories
7. Train staff on using the system

Congratulations! Your IT Help Desk Ticketing System is now ready to use! 🎉
