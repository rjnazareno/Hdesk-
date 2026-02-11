# PRODUCTION DEPLOYMENT GUIDE

## Database Configuration Fix

The error "Access denied for user 'root'@'localhost' (using password: NO)" occurs because the production server needs proper database credentials configured.

### Step 1: Get Your Database Credentials

**From your hosting provider control panel, get:**
- Database Host (usually `localhost`)  
- Database Username (e.g., `u123456_youruser`)
- Database Password 
- Database Name (e.g., `u123456_yourdb`)

### Step 2: Create .env File on Production Server

**In your website's root directory, create a file named `.env` (not .env.example) with:**

```bash
# Production Environment Configuration
APP_ENV=production

# Database Configuration - Replace with YOUR actual credentials
DB_HOST=localhost
DB_USER=your_actual_database_username
DB_PASS=your_actual_database_password  
DB_NAME=your_actual_database_name

# Email Configuration (optional)
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
MAIL_FROM_EMAIL=noreply@yourdomain.com
MAIL_FROM_NAME=HDesk
MAIL_ENCRYPTION=tls

# Base URL - Your domain (leave empty to auto-detect)
BASE_URL=

# Error Reporting - Keep off for production
ERROR_REPORTING=0
DISPLAY_ERRORS=0
```

### Step 3: Upload Database

**Import the database schema:**
1. Find `database/schema.sql` in your project
2. Import it to your production database via your hosting control panel
3. Or upload `database/Main DB/u816220874_resolveIT.sql` if it exists

### Step 4: Set File Permissions

**If using Linux hosting:**
```bash
chmod 755 directories
chmod 644 files  
chmod 777 uploads/
```

### Step 5: Test Database Connection

Visit your website. The database error should be resolved.

### Common Hosting Providers Database Settings:

**Hostinger:**
- Host: `localhost`
- Username: `u123456_username` 
- Name: `u123456_databasename`

**cPanel Hosting:**
- Host: `localhost`
- Username: `cpanel_username_dbuser`
- Name: `cpanel_username_dbname`

**Note:** Never commit the actual `.env` file with real credentials to your repository. Only commit `.env.example` as a template.