# Debug Files Archive

This folder contains diagnostic and testing files created during the notification system implementation (October 14-16, 2025).

## ⚠️ Important Note
These files are **NOT needed for production** but are kept for reference in case future debugging is needed.

## Files Archived

### Diagnostic Pages
- **notifications_diagnostic.php** (admin) - Comprehensive diagnostic tool for admin notifications
- **notifications_diagnostic.php** (customer) - Diagnostic tool for employee notifications
- **check_notifications_table.php** - Verifies notification table schema
- **check_recent_tickets.php** - Shows recent tickets and notifications for debugging

### Test/Auto-add Tools
- **auto_add_notifications.php** (admin) - Manually adds test notifications for admin users
- **auto_add_notifications.php** (customer) - Manually adds test notifications for employees

### Old/Backup Files
- **admin_old.php** - Old admin dashboard (backup)
- **categories_old.php** - Old categories page (backup)
- **dashboard_old.php** - Old dashboard (backup)
- **api_test.html** - API testing page

## Purpose of Each File

### `notifications_diagnostic.php` (admin & customer)
**Used for:** Identifying why notifications weren't appearing
- Shows session information
- Displays table structure
- Counts notifications
- Provides SQL fix recommendations

### `check_notifications_table.php`
**Used for:** Finding the "user_id cannot be NULL" issue
- Displays table schema
- Identifies NOT NULL constraints
- Provides SQL fix code

### `check_recent_tickets.php`
**Used for:** Verifying automatic notification creation
- Lists recent tickets
- Shows recent notifications
- Lists all admin users
- Helps track which tickets generated notifications

### `auto_add_notifications.php`
**Used for:** Testing notification display during development
- Manually creates test notifications
- Useful for UI testing before automatic system was working

### Old Files (admin_old, etc.)
**Purpose:** Backups from previous iterations of pages

## Can These Be Deleted?

**Yes, but we recommend keeping them archived** in case:
- Future debugging is needed
- You want to reference how the system was fixed
- Similar issues occur with other features

## If You Need to Use These Again

1. Copy the file from `debug_archive/` back to its original location
2. Visit the page in your browser (e.g., `/admin/notifications_diagnostic.php`)
3. Follow the diagnostic steps shown
4. Move it back to archive when done

## Related Documentation

See the main documentation for the production system:
- `/docs/NOTIFICATION_SYSTEM_COMPLETE.md` - Full system documentation
- `/docs/NOTIFICATION_IMPLEMENTATION_SUMMARY.md` - Quick summary
- `/docs/FIX_NOTIFICATIONS_DROPDOWN.md` - Original troubleshooting guide

## Cleaning Up

If you want to **permanently delete** these debug files:

```bash
# PowerShell command to remove the entire debug_archive folder
Remove-Item -Path "C:\xampp\htdocs\IThelp\debug_archive" -Recurse -Force
```

---

**Archived Date:** October 16, 2025  
**Status:** No longer needed for production  
**Recommendation:** Keep archived for 30 days, then delete
