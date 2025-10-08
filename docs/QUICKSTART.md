# Quick Start Guide

Get up and running with the IT Help Desk Ticketing System in under 5 minutes!

## Prerequisites Checklist

- [ ] XAMPP installed and running
- [ ] Composer installed
- [ ] Project files in `C:\xampp\htdocs\IThelp`

## Installation in 3 Steps

### Step 1: Install Dependencies (1 minute)

Open PowerShell and run:

```powershell
cd C:\xampp\htdocs\IThelp
composer install
```

### Step 2: Setup Database (2 minutes)

1. Open: http://localhost/phpmyadmin
2. Click "New" → Create database: `ithelp_db`
3. Click "Import" → Select `database/schema.sql` → Click "Go"

### Step 3: Access the System (1 minute)

1. Open: http://localhost/IThelp/
2. Login with: `admin` / `admin123`

**That's it! You're ready to go!** 🎉

## Quick Test

After logging in:

1. ✅ View Dashboard (should show charts)
2. ✅ Click "Tickets" in sidebar
3. ✅ Click "New Ticket" button
4. ✅ Fill form and submit

If all above works, installation is successful!

## Default Login Credentials

| Username | Password | Role |
|----------|----------|------|
| admin | admin123 | Admin |
| mahfuzul | admin123 | IT Staff |
| john.doe | admin123 | Employee |

## Common Issues & Fixes

### ❌ "Cannot connect to database"

**Fix:**
1. Start MySQL in XAMPP Control Panel
2. Check database name is `ithelp_db`

### ❌ "Composer not found"

**Fix:**
1. Download from: https://getcomposer.org/
2. Install and restart PowerShell

### ❌ "Page not found"

**Fix:**
Check `BASE_URL` in `config/config.php`:
```php
define('BASE_URL', 'http://localhost/IThelp/');
```

## What's Next?

### For Employees:
1. Click "Create Ticket" in sidebar
2. Fill in ticket details
3. Submit and track status

### For IT Staff:
1. View all tickets in "Tickets" page
2. Click ticket to view details
3. Update status and add resolution
4. Export reports via "Export" button

### For Admins:
1. Access all features
2. Manage customers
3. View analytics
4. Generate reports

## Features Overview

### Employee Features
- ✅ Submit support tickets
- ✅ Track ticket status
- ✅ Upload attachments
- ✅ Add comments
- ✅ View ticket history

### IT Staff Features
- ✅ View all tickets
- ✅ Assign tickets
- ✅ Update status
- ✅ Add resolutions
- ✅ Export reports
- ✅ View analytics

### Admin Features
- ✅ All IT Staff features
- ✅ User management
- ✅ System configuration
- ✅ Full access

## Email Notifications (Optional)

To enable email alerts:

1. Open `config/config.php`
2. Update SMTP settings:
   ```php
   define('MAIL_USERNAME', 'your-email@gmail.com');
   define('MAIL_PASSWORD', 'your-app-password');
   ```
3. For Gmail: Generate App Password at https://myaccount.google.com/security

## Customization Tips

### Change Application Name
Edit `config/config.php`:
```php
define('APP_NAME', 'Your Company IT Help Desk');
```

### Add New Category
1. Go to phpMyAdmin
2. Open `ithelp_db` → `categories` table
3. Insert new row with name, description, icon, color

### Modify Ticket Priorities
Edit available priorities in ticket forms or database

## File Structure (Simplified)

```
IThelp/
├── config/          ← Configuration files
├── models/          ← Database logic
├── includes/        ← Helper classes
├── database/        ← SQL schema
├── uploads/         ← User files
├── dashboard.php    ← Main dashboard
├── tickets.php      ← Ticket list
├── create_ticket.php ← New ticket form
├── view_ticket.php  ← Ticket details
└── README.md        ← Full documentation
```

## Getting Help

### Documentation
- 📖 **README.md** - Complete documentation
- 📖 **INSTALLATION.md** - Detailed installation guide
- 📖 **FOLDER_STRUCTURE.md** - Architecture explanation

### Troubleshooting
1. Check error logs: `C:\xampp\php\logs\php_error_log`
2. Verify database connection
3. Clear browser cache
4. Check file permissions

### Support
- GitHub Issues: [Report bugs or ask questions]
- Email: support@company.com

## Security Reminders

🔒 **Important Security Steps:**

1. **Change Default Passwords**
   - Login with each account
   - Update passwords immediately

2. **Production Deployment**
   - Use HTTPS/SSL
   - Update database credentials
   - Set strong passwords
   - Disable error display

3. **Regular Maintenance**
   - Backup database weekly
   - Update dependencies monthly
   - Review user access regularly

## Performance Tips

- Keep uploads under 5MB
- Archive old tickets (closed > 6 months)
- Regular database optimization
- Enable browser caching

## Next Steps

### Day 1
- [x] Install system
- [ ] Test all features
- [ ] Change default passwords
- [ ] Add real users

### Week 1
- [ ] Configure email notifications
- [ ] Customize categories
- [ ] Train staff
- [ ] Create first real tickets

### Month 1
- [ ] Review ticket statistics
- [ ] Export first report
- [ ] Gather user feedback
- [ ] Optimize workflow

## Success Checklist

You've successfully set up the system when:

- [x] Dashboard loads with charts
- [x] Can create tickets
- [x] Can view ticket details
- [x] IT staff can update tickets
- [x] Export works (IT staff)
- [x] All 3 roles tested

## Congratulations! 🎉

Your IT Help Desk Ticketing System is now ready for production use!

For detailed information, see **README.md**

---

**Need help?** Check the full documentation or create a GitHub issue.
