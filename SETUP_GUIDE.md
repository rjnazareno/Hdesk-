# Admin Dashboard - Quick Setup Guide

## 🚀 Installation & Setup

### Prerequisites
- ✅ PHP 8+ installed
- ✅ MySQL/MariaDB running
- ✅ XAMPP/WAMP/MAMP
- ✅ Modern web browser
- ✅ Internet connection (for CDN assets)

### 1. Verify File Structure

Check that these directories and files exist:
```
c:\xampp\htdocs\IThelp\
├── admin/
│   ├── index.php
│   ├── controllers/
│   │   └── DashboardController.php
│   └── assets/
│       ├── css/
│       │   └── admin.css
│       └── js/
│           └── admin.js
├── config/
│   ├── config.php
│   └── database.php
└── includes/
    └── security.php
```

### 2. Database Setup

Your existing database should already have:
- `tickets` table
- `ticket_responses` table
- `users` table

No additional database changes needed! ✅

### 3. Access the Dashboard

#### Step 1: Start XAMPP
```
Start Apache
Start MySQL
```

#### Step 2: Login as IT Staff
```
Navigate to: http://localhost/IThelp/login.php
Login with IT staff credentials
```

#### Step 3: Access Admin Dashboard
```
Navigate to: http://localhost/IThelp/admin/
or
http://localhost/IThelp/admin/index.php
```

### 4. Verify Everything Works

Check these features:

#### ✅ Visual Elements
- [ ] Dark theme loads correctly
- [ ] Sidebar navigation visible
- [ ] Cards display properly
- [ ] No broken images/icons

#### ✅ Data Display
- [ ] Statistics show numbers
- [ ] Chart displays
- [ ] Table shows recent tickets
- [ ] Activity feed populated

#### ✅ Interactions
- [ ] Sidebar links work
- [ ] Search box functional
- [ ] Hover effects work
- [ ] Buttons clickable

#### ✅ Real-time Features
- [ ] Charts animate
- [ ] Progress bars fill
- [ ] Stats count up
- [ ] Notifications dropdown works

## 🔧 Troubleshooting

### Issue: Dashboard shows blank/white page

**Solution:**
```php
// 1. Check PHP errors
// In admin/index.php, add at top:
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 2. Check database connection
// In config/database.php, verify credentials
```

### Issue: Charts not displaying

**Solution:**
```
1. Check browser console (F12)
2. Verify internet connection (Chart.js uses CDN)
3. Check if chartData is being passed from PHP
4. Clear browser cache
```

### Issue: "Access Denied" or redirect

**Solution:**
```php
// Verify user is IT staff in database
// Check includes/security.php - isITStaff() function
// Verify session is active
```

### Issue: Stats showing 0 or wrong data

**Solution:**
```sql
-- Check if tables have data
SELECT COUNT(*) FROM tickets;
SELECT COUNT(*) FROM ticket_responses;

-- Verify DashboardController.php can connect
```

### Issue: Styles not applying

**Solution:**
```
1. Check admin/assets/css/admin.css exists
2. Verify Tailwind CDN link in index.php
3. Clear browser cache (Ctrl+F5)
4. Check browser console for 404 errors
```

## 🎨 Customization

### Change Company Name

Edit `admin/index.php` line ~46:
```php
<span class="text-xl font-bold text-white">Simply Web</span>
<!-- Change to: -->
<span class="text-xl font-bold text-white">Your Company</span>
```

### Change Theme Colors

Edit `admin/assets/css/admin.css`:
```css
:root {
    --accent-blue: #3B82F6;    /* Change to your primary color */
    --accent-purple: #A855F7;  /* Change to your secondary color */
}
```

### Modify Chart Colors

Edit `admin/assets/js/admin.js` line ~11:
```javascript
gradient.addColorStop(0, 'rgba(59, 130, 246, 0.8)');
gradient.addColorStop(1, 'rgba(168, 85, 247, 0.8)');
// Change to your preferred colors
```

### Add More Sidebar Items

Edit `admin/index.php` navigation section:
```php
<a href="your-page.php" class="nav-item">
    <i class="fas fa-your-icon"></i>
    <span>Your Page</span>
</a>
```

## 📊 Understanding the Data Flow

### 1. Page Load
```
admin/index.php
  ↓
Requires DashboardController.php
  ↓
Controller fetches data from database
  ↓
Data passed to view
  ↓
HTML rendered with PHP
```

### 2. Real-time Updates
```
JavaScript timer (30s)
  ↓
AJAX request to controller
  ↓
Controller returns JSON
  ↓
JavaScript updates DOM
  ↓
Animations triggered
```

### 3. Chart Rendering
```
PHP prepares chartData array
  ↓
JSON encoded to JavaScript
  ↓
Chart.js processes data
  ↓
Canvas element rendered
  ↓
Animations applied
```

## 🎯 Testing Checklist

### Before Going Live

- [ ] Test with real data
- [ ] Verify all statistics are accurate
- [ ] Test on different screen sizes
- [ ] Test on different browsers (Chrome, Firefox, Edge)
- [ ] Check mobile responsiveness
- [ ] Verify security (IT staff only access)
- [ ] Test search functionality
- [ ] Test notification dropdown
- [ ] Check console for errors
- [ ] Verify all links work
- [ ] Test logout functionality

### Browser Compatibility

Tested and working on:
- ✅ Chrome 90+
- ✅ Firefox 88+
- ✅ Edge 90+
- ✅ Safari 14+

## 📱 Mobile Testing

### Test on Mobile Devices
1. Open on phone browser
2. Verify hamburger menu appears
3. Test sidebar toggle
4. Check card stacking
5. Verify touch interactions
6. Test chart responsiveness

### Responsive Test URLs
```
Desktop: http://localhost/IThelp/admin/
Mobile: http://your-local-ip/IThelp/admin/
```

To find your local IP:
```bash
# Windows
ipconfig

# Look for IPv4 Address (e.g., 192.168.1.100)
```

## 🔐 Security Checklist

- [ ] Only IT staff can access `/admin/`
- [ ] Session timeout works
- [ ] SQL injection protected (PDO)
- [ ] XSS protected (htmlspecialchars)
- [ ] Error messages don't expose sensitive info
- [ ] File permissions correct (644 for files, 755 for directories)

## 🎉 Success Indicators

You know it's working when:
1. ✅ Dark theme loads beautifully
2. ✅ Charts animate smoothly
3. ✅ Numbers display correctly
4. ✅ Stats update in real-time
5. ✅ Navigation is smooth
6. ✅ No console errors
7. ✅ Responsive on all devices
8. ✅ Fast page load

## 📞 Support

### Check Documentation
- `admin/README.md` - Detailed admin docs
- `PROJECT_STRUCTURE.md` - File organization
- `DASHBOARD_TRANSFORMATION.md` - Before/after comparison

### Debug Mode
Enable detailed errors:
```php
// Add to admin/index.php top
error_reporting(E_ALL);
ini_set('display_errors', 1);
```

### Check Logs
```
XAMPP: C:\xampp\apache\logs\error.log
PHP: Check php.ini for error_log location
```

## 🚀 Next Steps

### Phase 1: Complete Core Pages
1. Create `admin/tickets.php` - Full ticket management
2. Create `admin/customers.php` - Customer CRUD
3. Create `admin/categories.php` - Category management
4. Create `admin/settings.php` - System settings

### Phase 2: Add Features
1. Export to PDF/Excel
2. Advanced filtering
3. Bulk operations
4. Email notifications from admin

### Phase 3: Advanced Features
1. Analytics dashboard
2. Report generation
3. API documentation
4. Mobile app integration

## 📝 Configuration Tips

### Optimize for Production

1. **Disable Debug Mode**
```php
// Remove from admin/index.php
error_reporting(0);
ini_set('display_errors', 0);
```

2. **Enable Caching**
```php
// Add to admin/index.php
header('Cache-Control: public, max-age=3600');
```

3. **Minify Assets**
- Minify admin.css
- Minify admin.js
- Use production CDN links

4. **Database Optimization**
```sql
-- Add indexes
CREATE INDEX idx_status ON tickets(status);
CREATE INDEX idx_created_at ON tickets(created_at);
```

## 🎨 Quick Reference

### File Locations
```
Main Dashboard: admin/index.php
Styles: admin/assets/css/admin.css
Scripts: admin/assets/js/admin.js
Controller: admin/controllers/DashboardController.php
Config: config/database.php
Security: includes/security.php
```

### Important Functions
```php
// Controller
$controller->getDashboardData()  // Get all data
$controller->getStats()           // Get statistics
$controller->getRecentTickets()   // Get recent tickets
$controller->getActivities()      // Get activity feed
$controller->getChartData()       // Get chart data
```

### JavaScript Functions
```javascript
initDailyTicketsChart()    // Initialize chart
updateDashboardStats()     // Refresh stats via AJAX
showToast(message, type)   // Show notification
filterTableRows(query)     // Filter table
```

---

**Setup Complete!** 🎉

You now have a professional, enterprise-grade admin dashboard!

**Access URL**: http://localhost/IThelp/admin/

**Need Help?** Check the documentation files or enable debug mode.

**Last Updated**: October 2025
