# Quick Testing Guide - MVC Refactoring

## 🎯 All 5 Pages Are Ready to Test!

### Quick Test URLs
```
Dashboard:    http://localhost/IThelp/admin/dashboard.php
Tickets:      http://localhost/IThelp/admin/tickets.php
Employees:    http://localhost/IThelp/admin/customers.php
Categories:   http://localhost/IThelp/admin/categories.php
Admin:        http://localhost/IThelp/admin/admin.php
```

---

## ⚡ Quick Smoke Test (5 minutes)

Test each page loads without errors:

### 1. Dashboard
- [ ] Open `http://localhost/IThelp/admin/dashboard.php`
- [ ] Page loads without errors
- [ ] Charts display (Chart.js works)
- [ ] Search works

### 2. Tickets
- [ ] Open `http://localhost/IThelp/admin/tickets.php`
- [ ] Page loads without errors
- [ ] Tickets table displays
- [ ] Filters work

### 3. Employees
- [ ] Open `http://localhost/IThelp/admin/customers.php`
- [ ] Page loads without errors
- [ ] Employee table displays
- [ ] Search works

### 4. Categories
- [ ] Open `http://localhost/IThelp/admin/categories.php`
- [ ] Page loads without errors
- [ ] Category cards display
- [ ] Search works

### 5. Admin Settings
- [ ] Open `http://localhost/IThelp/admin/admin.php`
- [ ] Page loads without errors (or redirects if not admin)
- [ ] User table displays
- [ ] Edit modal opens

---

## 🔍 Common Issues to Check

### Issue: Blank Page
**Possible Causes:**
1. PHP error - check error logs
2. View file not found
3. Controller class not loaded

**Quick Fix:**
```powershell
# Check PHP error log
Get-Content c:\xampp\php\logs\php_error_log -Tail 20
```

### Issue: Headers Already Sent
**Possible Cause:** BOM or whitespace in PHP files

**Quick Fix:**
Ensure all PHP files start with `<?php` on line 1 with no spaces before it.

### Issue: 404 Not Found
**Possible Cause:** Route file doesn't exist

**Quick Fix:**
```powershell
# Verify route files exist
cd c:\xampp\htdocs\IThelp\admin
dir *.php
```

### Issue: Modal Doesn't Open (Admin Page)
**Possible Cause:** JavaScript not loaded

**Quick Fix:**
- Check browser console for errors (F12)
- Verify helpers.js and footer.php are loaded

### Issue: Dark Mode Doesn't Work
**Possible Cause:** Footer not included properly

**Quick Fix:**
- Check footer.php is included in view
- Verify initDarkMode() is called

---

## 🧪 Detailed Testing (30 minutes)

### Dashboard Page Testing

**Load Test:**
```
URL: http://localhost/IThelp/admin/dashboard.php
Expected: Page loads with stats, charts, and tables
```

**Feature Tests:**
1. **Stats Boxes:** Click each stat box (Total, Pending, Open, etc.) - should filter tickets
2. **Search:** Type in search box - should filter table rows
3. **Chart:** Verify Chart.js displays daily statistics graph
4. **Recent Tickets:** Table should display with data
5. **Dark Mode:** Click moon icon - should toggle dark mode
6. **Dropdowns:** Click Quick Actions and User Menu - should open/close

**Console Check:**
- Press F12
- Look for errors in Console tab
- Should see minimal or no errors

---

### Tickets Page Testing

**Load Test:**
```
URL: http://localhost/IThelp/admin/tickets.php
Expected: Page loads with tickets table and filters
```

**Feature Tests:**
1. **Filter Form:** Select status/priority/category - click Apply Filters
2. **Quick Search:** Type in search box - should filter table
3. **Clear Filters:** Click Clear Filters - should reset
4. **Quick Actions:** Test Create, Export, Print options
5. **Mobile Search:** Resize window - mobile search should sync with desktop

**Role-Based Testing:**
- **IT Staff:** Should see Submitter and Assigned To columns
- **Employee:** Should only see own tickets, no Submitter/Assigned columns

---

### Employees Page Testing

**Load Test:**
```
URL: http://localhost/IThelp/admin/customers.php
Expected: Page loads with employee table
```

**Feature Tests:**
1. **Table Display:** Verify all employees show
2. **Avatars:** Check avatar images generate (ui-avatars.com)
3. **Search:** Type in search - count badge should update (X Total → X Found)
4. **Status Badges:** Verify Active/Inactive badges display correctly
5. **Quick Actions:** Test dropdowns

---

### Categories Page Testing

**Load Test:**
```
URL: http://localhost/IThelp/admin/categories.php
Expected: Page loads with category cards in grid
```

**Feature Tests:**
1. **Card Grid:** Verify categories display as cards (not table)
2. **Color Icons:** Check colored category icons display
3. **Search:** Type in search - should filter cards
4. **View Statistics:** Click button - should show alert with totals
5. **Responsive:** Resize window - grid should adjust (3 cols → 2 → 1)

---

### Admin Settings Page Testing

**Load Test:**
```
URL: http://localhost/IThelp/admin/admin.php
Expected: Page loads with user management table (or redirects if not admin)
```

**Feature Tests:**
1. **User Table:** Verify all users display
2. **Edit Modal:**
   - Click edit icon (blue pencil)
   - Modal should open with user data
   - Edit a field and submit
   - Should redirect with success message
3. **Password Modal:**
   - Click key icon (green key)
   - Modal should open
   - Try mismatched passwords - should show error
   - Try short password (<6 chars) - should show error
   - Enter valid password - should submit successfully
4. **Toggle Status:**
   - Click toggle icon (orange)
   - Should confirm before changing
   - Status should update
5. **Export Users:**
   - Click Quick Actions → Export Users
   - Should download CSV file
6. **Search:**
   - Type in search box
   - Should filter user table rows

---

## 🚨 Critical Test Cases

### Authentication Test
```
1. Logout from application
2. Try accessing: http://localhost/IThelp/admin/dashboard.php
3. Should redirect to login page
4. Login and verify redirect back works
```

### Admin Role Test
```
1. Login as regular employee (not IT staff)
2. Try accessing: http://localhost/IThelp/admin/admin.php
3. Should redirect (not authorized)
4. Login as admin - should allow access
```

### Form Submission Test (Admin Page)
```
1. Open admin.php
2. Edit a user's information
3. Submit form
4. Should redirect to: admin.php?success=user_updated
5. Success message should display
```

### Search Synchronization Test (Tickets/Employees)
```
1. Open page on desktop view
2. Type in desktop search box
3. Resize to mobile view
4. Mobile search should have same value
5. Type in mobile search
6. Resize to desktop
7. Desktop search should update
```

---

## 📊 Browser Console Checks

Open each page and check browser console (F12):

### Common Expected Messages (OK)
- jQuery loaded (if used)
- Chart.js loaded (dashboard only)
- No 404 errors for CSS/JS files

### Red Flags (NOT OK)
- ❌ `Uncaught ReferenceError: [function] is not defined`
- ❌ `404 Not Found: helpers.js`
- ❌ `404 Not Found: notifications.js`
- ❌ `Failed to load resource`
- ❌ `Uncaught TypeError`

### How to Check:
```
1. Press F12 to open Developer Tools
2. Click "Console" tab
3. Reload page (Ctrl+F5)
4. Look for red error messages
5. If errors found, note the file/line number
```

---

## 📱 Mobile Responsive Test

### Test on Each Page:
1. **Desktop View** (>1024px)
   - Full sidebar visible
   - All columns show
   - Desktop search visible

2. **Tablet View** (768px - 1024px)
   - Sidebar collapses
   - Some columns hide
   - Layout adjusts

3. **Mobile View** (<768px)
   - Hamburger menu
   - Single column layout
   - Mobile search shows
   - Cards stack vertically

### How to Test:
```
1. Press F12
2. Click device toolbar icon (phone/tablet icon)
3. Select device: iPhone 12, iPad, etc.
4. Test all features on mobile view
```

---

## ✅ Success Criteria

All tests PASS if:
- ✅ All 5 pages load without blank screens
- ✅ No PHP errors in logs
- ✅ No red errors in browser console
- ✅ Search works on all pages
- ✅ Dropdowns open/close properly
- ✅ Modals open/close (admin page)
- ✅ Form submissions work (admin page)
- ✅ Authentication redirects work
- ✅ Mobile responsive design works
- ✅ Dark mode toggles properly

---

## 🔧 Troubleshooting Commands

### Check PHP Errors
```powershell
# View last 20 lines of PHP error log
Get-Content c:\xampp\php\logs\php_error_log -Tail 20

# Or Apache error log
Get-Content c:\xampp\apache\logs\error.log -Tail 20
```

### Verify File Structure
```powershell
cd c:\xampp\htdocs\IThelp

# Check controllers exist
dir controllers\admin\*.php

# Check views exist
dir views\admin\*.php

# Check layouts exist
dir views\layouts\*.php

# Check routes exist
dir admin\dashboard.php,admin\tickets.php,admin\customers.php,admin\categories.php,admin\admin.php
```

### Test Specific Page
```powershell
# Start server (if not running)
cd c:\xampp
.\apache_start.bat
.\mysql_start.bat

# Open specific page in browser
start http://localhost/IThelp/admin/dashboard.php
```

---

## 📝 Report Template

Use this template to report test results:

```markdown
# MVC Testing Results

**Date:** [Date]
**Tester:** [Name]
**Browser:** [Chrome/Firefox/Edge]

## Page Tests

### Dashboard
- [ ] Loads successfully
- [ ] Stats display correctly
- [ ] Chart renders
- [ ] Search works
- [ ] Issues: [None / List issues]

### Tickets
- [ ] Loads successfully
- [ ] Filters work
- [ ] Search works
- [ ] Issues: [None / List issues]

### Employees
- [ ] Loads successfully
- [ ] Table displays
- [ ] Search works
- [ ] Issues: [None / List issues]

### Categories
- [ ] Loads successfully
- [ ] Cards display
- [ ] Search works
- [ ] Issues: [None / List issues]

### Admin Settings
- [ ] Loads successfully
- [ ] Edit modal works
- [ ] Password modal works
- [ ] Toggle status works
- [ ] Issues: [None / List issues]

## Overall Result
- [ ] All tests PASSED
- [ ] Some issues found (see details above)
- [ ] Ready for production

## Notes
[Any additional observations]
```

---

## 🎉 When All Tests Pass

1. **Celebrate!** 🎊 The MVC refactoring is complete and working!

2. **Clean Up Backups** (optional):
   ```powershell
   cd c:\xampp\htdocs\IThelp\admin
   Remove-Item *_old.php
   ```

3. **Update Documentation** (mark as tested):
   - Add "✅ Tested" to MVC_COMPLETION_SUMMARY.md

4. **Deploy to Production** (if applicable):
   - Test in staging first
   - Monitor error logs
   - Collect user feedback

---

*Quick Testing Guide - MVC Refactoring Complete*
