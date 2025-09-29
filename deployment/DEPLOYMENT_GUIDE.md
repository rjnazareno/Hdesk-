# IT Ticketing System - Live Deployment Guide

## 📋 Pre-Deployment Requirements

### Server Requirements
- **Web Server**: Apache 2.4+ or Nginx 1.18+
- **PHP**: 8.0 or higher
- **Database**: MySQL 8.0+ or MariaDB 10.4+
- **SSL Certificate**: Required for production
- **Storage**: Minimum 1GB free space
- **Memory**: Minimum 512MB RAM

### PHP Extensions Required
```bash
php-mysql
php-pdo
php-mbstring
php-openssl
php-json
php-curl
php-gd
php-zip
php-xml
```

## 🚀 Deployment Steps

### Step 1: File Upload
1. **Upload System Files**
   ```bash
   # Upload all files except:
   # - /deployment/ folder (contains sensitive templates)
   # - /logs/ folder (will be created automatically)
   # - /uploads/ folder (will be created automatically)
   # - config/config.php (create from template)
   ```

2. **Set File Permissions**
   ```bash
   # Files: 644
   find /path/to/ticketing/system -type f -exec chmod 644 {} \;
   
   # Directories: 755
   find /path/to/ticketing/system -type d -exec chmod 755 {} \;
   
   # Writable directories: 777
   chmod 777 uploads/
   chmod 777 logs/
   ```

### Step 2: Database Setup
1. **Create Database**
   ```sql
   CREATE DATABASE it_ticketing_live CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
   CREATE USER 'ticketing_user'@'localhost' IDENTIFIED BY 'your_secure_password';
   GRANT ALL PRIVILEGES ON it_ticketing_live.* TO 'ticketing_user'@'localhost';
   FLUSH PRIVILEGES;
   ```

2. **Import Schema**
   ```bash
   mysql -u ticketing_user -p it_ticketing_live < deployment/live_database_schema.sql
   ```

### Step 3: Configuration
1. **Create Production Config**
   ```bash
   cp deployment/live_config_template.php config/config.php
   ```

2. **Update Configuration Values**
   ```php
   // Edit config/config.php
   define('DB_HOST', 'your_db_host');
   define('DB_NAME', 'it_ticketing_live');
   define('DB_USER', 'ticketing_user');
   define('DB_PASS', 'your_secure_password');
   define('APP_URL', 'https://yourdomain.com/ticketing/');
   // ... update all other settings
   ```

### Step 4: Web Server Configuration

#### Apache (.htaccess)
```apache
# Main .htaccess (already included in system)
RewriteEngine On
RewriteCond %{HTTPS} off
RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Security headers
Header always set X-Content-Type-Options nosniff
Header always set X-Frame-Options DENY
Header always set X-XSS-Protection "1; mode=block"
Header always set Strict-Transport-Security "max-age=31536000; includeSubDomains"
```

#### Nginx Configuration
```nginx
server {
    listen 80;
    server_name yourdomain.com;
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    root /path/to/ticketing/system;
    index index.php simple_login.php;

    ssl_certificate /path/to/your/certificate.crt;
    ssl_certificate_key /path/to/your/private.key;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.0-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ^~ /uploads/ {
        internal;
    }

    location ^~ /logs/ {
        deny all;
    }
}
```

### Step 5: Initial Setup
1. **Create Admin Account**
   ```bash
   # The database schema includes a default admin account:
   # Username: admin
   # Password: password (default hash)
   # IMPORTANT: Change this immediately after first login
   ```

2. **Test Employee Account**
   ```bash
   # Use existing employee accounts from your employees table
   # Or create test employees with hashed passwords
   ```

### Step 6: Security Hardening

#### File Security
```bash
# Remove sensitive files
rm -rf deployment/
rm -f README.md
rm -f *.md

# Secure sensitive directories
echo "deny from all" > logs/.htaccess
echo "deny from all" > uploads/.htaccess
echo "deny from all" > config/.htaccess
```

#### Database Security
```sql
-- Remove default admin account after creating secure one
-- Change all default passwords
-- Create database user with minimal privileges
```

## 🧪 Testing Checklist

### Functional Testing
- [ ] Login with employee account
- [ ] Login with IT staff account
- [ ] Create new ticket (employee)
- [ ] View ticket details
- [ ] Add response to ticket
- [ ] Update ticket status (IT staff only)
- [ ] Assign ticket (IT staff only)
- [ ] File upload functionality
- [ ] Dashboard statistics
- [ ] Search and filtering
- [ ] Logout functionality

### Security Testing
- [ ] HTTPS redirection working
- [ ] File upload restrictions working
- [ ] Permission checks (employee vs IT staff)
- [ ] SQL injection prevention
- [ ] XSS protection
- [ ] CSRF token validation
- [ ] Session security
- [ ] Direct file access denied

### Performance Testing
- [ ] Page load times acceptable
- [ ] Database queries optimized
- [ ] File uploads working
- [ ] Email notifications working (if enabled)

## 🔧 Post-Deployment Configuration

### 1. Email Setup
```php
// Test email configuration
// Update SMTP settings in config/config.php
// Test with actual ticket creation
```

### 2. Regular Maintenance
```bash
# Set up cron jobs for:
# - Database backups (daily)
# - Log rotation (weekly)
# - Cleanup old files (monthly)

# Example crontab:
# 0 2 * * * /path/to/backup_script.sh
# 0 3 * * 0 /usr/sbin/logrotate /path/to/logrotate.conf
# 0 4 1 * * /path/to/cleanup_script.sh
```

### 3. Monitoring Setup
- Set up uptime monitoring
- Configure error alerting
- Monitor disk space usage
- Track database performance

## 🚨 Emergency Procedures

### System Recovery
```bash
# Restore from backup
mysql -u username -p database_name < backup_file.sql

# Reset admin password
# Use password reset script or database update
```

### Troubleshooting
```bash
# Check PHP error logs
tail -f logs/php_errors.log

# Check web server logs
tail -f /var/log/apache2/error.log  # Apache
tail -f /var/log/nginx/error.log    # Nginx

# Check MySQL logs
tail -f /var/log/mysql/error.log
```

## 📞 Support Information

### System Information
- **Version**: 1.0.0
- **PHP Version Required**: 8.0+
- **Database**: MySQL 8.0+ / MariaDB 10.4+
- **Framework**: Pure PHP (No framework dependencies)

### Default Accounts
```
IT Staff:
Username: admin
Password: admin123 (CHANGE IMMEDIATELY)

Employee:
Use existing employee accounts from your database
Default password format: password123
```

## ✅ Go-Live Checklist

- [ ] All files uploaded and permissions set
- [ ] Database created and schema imported
- [ ] Configuration file updated with production settings
- [ ] SSL certificate installed and HTTPS working
- [ ] Web server configuration applied
- [ ] Default passwords changed
- [ ] Security hardening completed
- [ ] All functionality tested
- [ ] Email notifications tested (if enabled)
- [ ] Backup procedures in place
- [ ] Monitoring configured
- [ ] Documentation provided to users
- [ ] Support procedures established

---

**🎉 Your IT Ticketing System is ready for production!**

*Remember to keep your system updated and regularly backup your data.*