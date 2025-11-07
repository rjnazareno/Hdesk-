# 🧪 Add Employee Form - Testing Guide

**Date Created:** November 7, 2025  
**Production URL:** https://resolveit.resourcestaffonline.com/admin/add_employee.php  
**Test Script:** https://resolveit.resourcestaffonline.com/admin/test_add_employee.php

---

## ✅ Pre-Testing Checklist

- [ ] Logged in as admin or IT staff
- [ ] Browser DevTools open (F12) - Console tab visible
- [ ] Network tab recording enabled
- [ ] Screenshots ready to capture

---

## 🔬 Test Cases

### Test 1: Form Validation (Empty Submission)

**Steps:**
1. Navigate to Add Employee page
2. Leave ALL fields empty
3. Click "Add Employee" button

**Expected Result:**
- ❌ Form should NOT submit
- Browser should show "Please fill out this field" on first required field
- Red error message: "Please fill in all required fields"

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 2: Username Already Exists

**Steps:**
1. Fill form with existing username (e.g., "john.doe")
2. Fill other required fields with valid data
3. Submit form

**Expected Result:**
- ❌ Submission rejected
- Red error banner: "Username already exists."
- Form data preserved (not cleared)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 3: Email Already Exists

**Steps:**
1. Fill form with NEW username
2. Use existing email (e.g., "john.doe@resolveit.com")
3. Fill other required fields
4. Submit form

**Expected Result:**
- ❌ Submission rejected
- Red error banner: "Email already exists."
- Form stays on page

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 4: Successful Employee Creation

**Test Data:**
- **Username:** test.employee
- **Email:** test.employee@resolveit.com
- **Password:** TestPass123!
- **First Name:** Test
- **Last Name:** Employee
- **Position:** QA Tester
- **Company:** ResourceStaff Online
- **Contact:** 09123456789
- **Official Schedule:** Mon-Fri 9AM-6PM
- **Role:** employee

**Steps:**
1. Fill ALL fields with test data above
2. Click "Add Employee"

**Expected Result:**
- ✅ Redirect to `/admin/customers.php`
- Green success banner: "Employee added successfully!"
- New employee appears in employee list
- Employee has status "active"

**Verification:**
```sql
SELECT * FROM employees WHERE username = 'test.employee';
```

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 5: Auto-Generated Username

**Steps:**
1. Enter First Name: "Sarah"
2. Enter Last Name: "Connor"
3. Click into another field (blur username input)
4. Observe username field

**Expected Result:**
- ✅ Username auto-fills with: "sarah.connor"
- Username field becomes editable (can still be changed manually)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 6: Profile Picture Upload (Valid Image)

**Test File:** 
- Use a JPG/PNG image < 2MB
- Example: User avatar, logo, test image

**Steps:**
1. Fill required fields
2. Click "Choose File" for Profile Picture
3. Select valid image
4. Submit form

**Expected Result:**
- ✅ Form submits successfully
- Image uploaded to `/uploads/profiles/`
- Filename format: `profile_XXXXX.jpg` (unique ID)
- File size < 2MB verified
- Employee record has `profile_picture` field populated

**Verification:**
- Check `/uploads/profiles/` directory
- Run test script: `test_add_employee.php`

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 7: Profile Picture Upload (Invalid File)

**Test Files:**
- PDF document
- Text file (.txt)
- Executable (.exe)

**Steps:**
1. Fill required fields
2. Try uploading invalid file type
3. Submit form

**Expected Result:**
- ✅ Form submits (but file ignored)
- No profile picture saved
- Employee created without profile_picture
- No error shown (graceful degradation)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 8: Profile Picture Upload (File Too Large)

**Test File:**
- Image > 5MB (check `MAX_FILE_SIZE` in config)

**Steps:**
1. Fill required fields
2. Upload oversized image
3. Submit form

**Expected Result:**
- Browser may show size error OR
- PHP rejects upload (check logs)
- Employee created without profile_picture

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 9: Password Strength (Weak Password)

**Steps:**
1. Fill form with password: "123"
2. Submit form

**Expected Result:**
- ⚠️ Currently: Form may accept (no client-side validation)
- Password hashed and stored
- Recommendation: Add password strength requirements

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 10: Email Notification

**Steps:**
1. Create employee with valid email
2. Check email inbox (if Mailer configured)
3. Look for welcome email

**Expected Result:**
- 📧 Welcome email sent (if Mailer configured)
- Email contains login credentials
- Email has proper formatting

**Note:** May not work if Mailer not configured. Check:
```php
// config/config.php
define('MAIL_FROM_ADDRESS', 'noreply@resolveit.com');
define('MAIL_FROM_NAME', 'ResolveIT Help Desk');
```

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail | ⚠️ Not Configured

---

### Test 11: UI/UX - Quick Tip Banner

**Steps:**
1. Load Add Employee page
2. Observe top banner

**Expected Result:**
- ✅ Cyan/blue gradient banner visible
- Lightbulb icon present
- Text: "Quick Tip: Fields marked with * are required..."
- Responsive on mobile

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 12: UI/UX - Sidebar Instructions

**Steps:**
1. View page on desktop (width > 1024px)
2. Scroll through sidebar

**Expected Result:**
- ✅ Sidebar visible on right (33% width)
- 4 sections present:
  1. Role Guide
  2. Best Practices
  3. Important Notes (amber box, white text)
  4. Quick Tips (gradient box, white text)
- Icons colored properly (amber-400 for Important section)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 13: UI/UX - Input Styling

**Steps:**
1. Click into each input field
2. Hover over inputs
3. Type text

**Expected Result:**
- ✅ Inputs have rounded corners (`rounded-lg`)
- Cyan focus ring visible on focus
- Hover shows lighter border color
- Smooth transitions (no jumps)
- White text on dark background
- Placeholder text visible (slate-500)

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 14: Mobile Responsiveness

**Steps:**
1. Open DevTools (F12)
2. Toggle device toolbar (Ctrl+Shift+M)
3. Test at 375px width (iPhone SE)

**Expected Result:**
- ✅ Sidebar moves below form (stacks vertically)
- Form fields full width
- All text readable
- No horizontal scroll
- Buttons accessible

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

### Test 15: Session Timeout

**Steps:**
1. Load form
2. Wait 31 minutes (session timeout = 30 min)
3. Try to submit form

**Expected Result:**
- ❌ Redirect to login page
- Session expired message
- Form data lost

**Status:** ⬜ Not Tested | ✅ Pass | ❌ Fail

---

## 🔧 Automated Test Script

After manual testing, run the automated script:

**URL:** `/admin/test_add_employee.php`

**What it checks:**
- ✅ Recent employees in database
- ✅ No duplicate usernames
- ✅ No duplicate emails
- ✅ All passwords properly hashed
- ✅ Profile pictures uploaded correctly
- ✅ Employee statistics (by role, status)
- ✅ Username generation pattern verification

---

## 📊 Test Results Summary

**Date Tested:** _____________  
**Tester Name:** _____________

| Test # | Test Name | Status | Notes |
|--------|-----------|--------|-------|
| 1 | Empty Submission | ⬜ | |
| 2 | Username Exists | ⬜ | |
| 3 | Email Exists | ⬜ | |
| 4 | Successful Creation | ⬜ | |
| 5 | Auto Username | ⬜ | |
| 6 | Valid Image Upload | ⬜ | |
| 7 | Invalid File Upload | ⬜ | |
| 8 | Oversized Image | ⬜ | |
| 9 | Weak Password | ⬜ | |
| 10 | Email Notification | ⬜ | |
| 11 | Quick Tip Banner | ⬜ | |
| 12 | Sidebar Instructions | ⬜ | |
| 13 | Input Styling | ⬜ | |
| 14 | Mobile Responsive | ⬜ | |
| 15 | Session Timeout | ⬜ | |

**Overall Status:** ⬜ Not Started | 🟡 In Progress | ✅ Complete

---

## 🐛 Known Issues

_Document any bugs found during testing:_

1. 
2. 
3. 

---

## 📝 Notes

_Additional observations:_

---

## 🔗 Related Files

- Form View: `views/admin/add_employee.view.php`
- Controller: `controllers/admin/AddEmployeeController.php`
- Model: `models/Employee.php`
- Test Script: `admin/test_add_employee.php`
