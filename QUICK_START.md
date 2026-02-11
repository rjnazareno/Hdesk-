# QUICK START: Reset Your Ticket System

## 🌐 PRODUCTION SERVER (Hostinger)
**URL:** https://hdesk.resourcestaffonline.com/

### 🚀 Fast Track (3 Steps)

### 1️⃣ Clean Database
**Hostinger phpMyAdmin:**
1. Login → https://hpanel.hostinger.com/
2. Databases → `u816220874_ticketing` → Enter phpMyAdmin
3. SQL tab → Paste contents of `database/CLEAN_RESET_TICKETS.sql`
4. Click **Go**

### 2️⃣ Clean Files
**Visit in browser:**
```
https://hdesk.resourcestaffonline.com/cleanup_uploads.php
```
- Copy the confirmation key
- Paste and submit
- Wait for completion

### 3️⃣ Verify
**Visit:**
```
https://hdesk.resourcestaffonline.com/verify_reset.php
```
- Should show "Reset Successful! ✅"
- Clear browser cache
- Login and test creating a ticket

---

## 📁 Files Created

| File | Purpose |
|------|---------|
| `database/CLEAN_RESET_TICKETS.sql` | Deletes all tickets & related data (run in phpMyAdmin) |
| `cleanup_uploads.php` | **Web-based** file cleanup for production |
| `verify_reset.php` | **Web-based** verification tool |
| `PRODUCTION_RESET_GUIDE.md` | **Complete production guide** |
| `database/OPTIONAL_SIMPLIFY_SCHEMA.sql` | Remove dual-user complexity |
| `QUICK_START.md` | This file |
| `clean-uploads.ps1` | PowerShell script (local dev only) |

---

## ✅ What Gets Deleted

- ❌ All tickets
- ❌ All ticket activity/logs
- ❌ All notifications
- ❌ All SLA tracking
- ❌ All attachment files

## ✅ What Gets Preserved

- ✅ User accounts (admins, IT staff)
- ✅ Employee accounts
- ✅ Categories
- ✅ SLA policies
- ✅ System settings

---

## 🔧 Optional: Simplify System (Recommended)

After cleanup, if you want to prevent future tracking issues:

**Run:** `database/OPTIONAL_SIMPLIFY_SCHEMA.sql`

This removes the complex dual-user system (submitter_type, assignee_type) that was causing confusion.

---

## 🐛 Troubleshooting

**Problem:** SQL script errors in phpMyAdmin
- **Fix:** Some tables may not exist - this is OK, script continues
- **Fix:** Check database selected: `u816220874_ticketing`

**Problem:** Can't access cleanup_uploads.php
- **Fix:** Verify file uploaded to server
- **Fix:** Check URL: https://hdesk.resourcestaffonline.com/cleanup_uploads.php

**Problem:** Still see old tickets after cleanup
- **Fix:** Clear browser cache (Ctrl + Shift + Delete)
- **Fix:** Use incognito/private browsing mode
- **Fix:** Logout and login again

---

- **Production (Hostinger):** See `PRODUCTION_RESET_GUIDE.md` ← **Use this!**
- **Local Development:** See `RESET_GUIDE.md`

See: `RESET_GUIDE.md` for complete details and recommendations

---

## ⚡ After Reset Checklist

- [ ] Run database cleanup SQL ✓
- [ ] Run file cleanup PowerShell ✓
- [ ] Verify 0 tickets in dashboard ✓
- [ ] Create test ticket
- [ ] Assign test ticket to IT staff
- [ ] Verify assignment persists
- [ ] Check no tracking errors
- [ ] (Optional) Run schema simplification

---

**Ready to start fresh! 🎉**
